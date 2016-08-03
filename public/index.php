<?php
require_once(dirname(__DIR__).'/GiphyGetter.php');

# You must POST to this script
if(empty($_POST)){http_response_code(400); exit(0);}

try{
	$giphyGetter = new GiphyGetter\GiphyGetter(null,'/var/tmp/');
	$giphyGetter->setImageSize('fixed_height');
}catch(Throwable $T){
	error_log($T->getMessage());
	exit(0);
}

# Are we responding to a user pressing a button on a GIF?
if(isset($_POST['payload']) && $payload = json_decode($_POST['payload'],true)){
	# Parse the request
	if(!$request = explode('|',$payload['actions'][0]['value']))
		throw new Exception("Unable to parse payload request from value");

	/*
	 * A note to those curious: The $request variable above is an array populated by a string delimited by pipes.
	 * The string is created manually in the $buttons array below, using 'value'. The contents of the $request array
	 * are set up to always be the following:
	 * [0]: The name of the action being performed
	 * [1]: The relevant gif url, if any
	 * [2]: The relevant search term, if any
	 */

	switch($request[0]){
		case 'ban':
			# User wants to ban a gif from ever showing up again
			$giphyGetter->banGif($request[1]);
			header("Content-Type: application/json");
			print json_encode(["delete_original" => true,]);
			http_response_code(200);
			exit(0);
		case 'delete':
			# User wants to delete the message from Slack
			header("Content-Type: application/json");
			print json_encode(["delete_original" => true,]);
			http_response_code(200);
			exit(0);
		case 'reserve':
			# User wants to lock this gif to this keyword
			$giphyGetter->reserveKeyword($request[1],$request[2]);
			$search = $request[2];
			break;
		case 'retry':
			# User wants to request another gif
			$search = $request[2];
			break;
		default:
			http_response_code(400);
			exit(0);
	}
}

try{
	# Save the search term, which will currently not be set if a button was pressed
	$search = $search ?? $_POST['text'] ?? null;
	
	# If there is no search term, return a 400
	if(!$search){
		http_response_code(400);
		exit(0);
	}

	# Get the GIF info
	$gif = $giphyGetter->requestGifUrl($search);

	# Define the buttons to be presented to the user
	$buttons = [
		[
			"name" => "action",
			"value" => "retry|nothing|$search",
			"type" => "button",
			"text" => "Different GIF",
		],
		[
			"name" => "action",
			"value" => "delete|nothing|nothing",
			"type" => "button",
			"text" => "Delete",
		],
	];

	# If this gif is not reserved, give the option to reserve it
	if(!$gif['reserve'])
		$buttons[] = [
			"name" => "action",
			"value" => "reserve|$gif[url]|$search",
			"type" => "button",
			"text" => "Reserve",
			"confirm" => [
				"title" => "Reserve this search term?",
				"text" => "If you reserve this term ($search), it will always return this gif, and if 
										this term is already reserved, that gif will be replaced with this one.",
				"ok_text" => "Reserve",
				"dismiss_text" => "Cancel",
			]
		];

	# Always put the banish button last
	$buttons[] = [
		"name" => "action",
		"value" => "ban|$gif[url]|nothing",
		"type" => "button",
		"text" => "Banish",
		"style" => "danger",
		"confirm" => [
			"title" => "Banish this gif?",
			"text" => "If you banish this gif, it will never show up in any GiphyGetter search again.",
			"ok_text" => "Banish",
			"dismiss_text" => "Cancel",
		]
	];

	# Respond to Slack
	header('Content-Type: application/json');
	print json_encode([
		"text" => null,
		"response_type" => "in_channel",
		"attachments" => [
			[
				"text" => null,
				"fallback" => "Someone posted a GIF using GiphyGetter!",
				"callback_id" => uniqid(),
				"image_url" => $gif['url'],
				"actions" => $buttons,
			],
		],
	]);
	http_response_code(200);
	exit(0);

}catch(Throwable $T){
	error_log($T->getMessage());
	http_response_code(500);
	exit(0);
}

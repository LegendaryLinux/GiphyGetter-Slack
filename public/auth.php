<?php
try{
	if(!isset($_GET['code'])){
		http_response_code(400);
		exit(0);
	}

	$curl = curl_init();
	curl_setopt_array($curl,[
		CURLOPT_URL => "https://slack.com/api/oauth.access",
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => [
			"client_id" => getenv('GG_CLIENT_ID'),
			"client_secret" => getenv('GG_CLIENT_SECRET'),
			"code" => $_GET["code"],
			"redirect_uri" => getenv('GG_REDIRECT_URI'),
		],
		CURLOPT_RETURNTRANSFER => true,
	]);
	if(!$response = curl_exec($curl)){
		header('Content-Type: text/html');
		print 'An error occurred and your Slack was not integrated with GiphyGetter.';
		http_response_code(500);
		exit(0);
	}

	header('Content-Type: text/html');
	header('Location: https://github.com/LegendaryLinux/GiphyGetter-Slack');
	exit(0);

}catch(Throwable $T){
	$file = basename($T->getFile());
	error_log("[$file:{$T->getLine()}] {$T->getMessage()}");
	http_response_code(500);
	exit(1);
}

<?php
namespace GiphyGetter;
/**
 * Class Database
 * A handler for connecting to the database
 */
class Database{
	/**
	 * Connect to a MySQL database with given parameters. If no parameters are provided, a default connection
	 * is attempted with stored local credentials
	 * @return \PDO | bool
	 */
	public static function Connect(){
		try{
			# We need the database host
			$host = getenv('GG_DB_HOST');

			# Try to connect five times before quitting
			for($wait = 0;$wait<5;$wait++){
				# Wait a slightly longer time between each attempt
				sleep($wait);

				if($db = new \PDO("mysql:host=$host;charset=utf8",getenv('GG_DB_UN'),getenv('GG_DB_PW')))
					return $db;
			}

			throw new \Exception("Unable to connect to MySQL after five attempts");
		}catch(\Throwable $T){
			error_log("[{$T->getFile()}:{$T->getLine()}] {$T->getMessage()}");
			return false;
		}
	}
}
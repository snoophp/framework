<?php

/************************
 * DATABASE CONFIGURATION
 ************************/

/**
 * @var array	$dbConfig	array containing database configuration
 */
$dbConfig = [
	"master"	=> [
		"host"		=> "localhost",
		"schema"	=> "eventmi",
		"username"	=> "eventmi",
		"password"	=> "RaxSox4&"
	]
];

// Create database for each config
foreach ($dbConfig as $dbName => $dbInfo)
{
	$GLOBALS["db".ucwords($dbName)] = new \PDO(
		"mysql:dbname=".$dbInfo["schema"].";dbhost=".$dbInfo["host"],
		$dbInfo["username"],
		$dbInfo["password"]
	);

	$db = $GLOBALS["dbMaster"];
}
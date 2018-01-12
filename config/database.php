<?php

/************************
 * DATABASE CONFIGURATION
 ************************/

/**
 * @var array $dbConfig array containing database configuration
 */
$dbConfig = [
	"master"	=> [
		"host"		=> "localhost",
		"schema"	=> "snoophp",
		"username"	=> "snoophp",
		"password"	=> "Charlie00"
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
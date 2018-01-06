<?php

/**
 * Load given class from base directory
 * 
 * @param string	$className	name of the class to load
 * @param string	$baseDir	directory containing classes and namespaces (relative to root folder)
 */
function autoload($className, $baseDir)
{
	$className = trim($className);
	$filename = "";
	$namespace = "";

	// Build path
	if ($i = strpos($className, "\\"))
	{
		$namespace = substr($className, 0, $i);
		$className = substr($className, $i + 1);
		$filename = str_replace("\\", "/", $namespace) . "/";
	}
	// Append class name
	$filename .= str_replace("\\", "/", $className) . ".php";
	
	include_once __DIR__.$baseDir."/".$filename;
}

/**
 * Autoloader for framework libraries
 * 
 * @param string	$className	name of the class to load
 */
function libAutoloader($className)
{
	autoload($className, "/lib");
}

/**
 * Autoloader for application classes
 * 
 * @param string	$className	name of the class to load
 */
function appAutoloader($className)
{
	autoload($className, "/app");
}


// Register autoloaders
spl_autoload_register("libAutoloader");
spl_autoload_register("appAutoloader");

// Include configs
foreach (glob(__DIR__."/config/*.php") as $configFile) require_once $configFile;
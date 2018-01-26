<?php

if (!function_exists("escape_unicode"))
{
	/**
	 * Escape unicode codes in string
	 * 
	 * @param string	$content	string to escape
	 * 
	 * @return string
	 */
	function escape_unicode($content)
	{
		return preg_replace("/\\\\u/", "\\\\\\\\u", $content);
	}
}

if (!function_exists("from_json"))
{
	/**
	 * Decode from json
	 * 
	 * @param string	$content	json string
	 * @param bool		$assoc		if true return an array rather than an object
	 * 
	 * @return array|object
	 */
	function from_json($content, $assoc = false)
	{
		return json_decode($content, $assoc);
	}
}

if (!function_exists("path"))
{
	/**
	 * Return absolute path for project directory
	 * 
	 * @param string $name directory name
	 * 
	 * @return string
	 */
	function path($name)
	{
		$rootDir = defined("ROOT_DIR") ? ROOT_DIR : $_SERVER["DOCUMENT_ROOT"];
		$path = realpath($rootDir."/".$name);
		return file_exists($path) ?
		$path :
		false;
	}
}

if (!function_exists("to_json"))
{
	/**
	 * Encode content as json string
	 * 
	 * @param string|array|object $content content to encode
	 * 
	 * @return string
	 */
	function to_json($content)
	{
		return is_string($content) ?
		$content :
		json_encode($content);
	}
}

if (!function_exists("to_utf8"))
{
	/**
	 * Encode content as utf8 string
	 * 
	 * @param string $content content to encode
	 * 
	 * @return string
	 */
	function to_utf8($content)
	{
		return mb_convert_encoding($content, "UTF-8");
	}
}

if (!function_exists("view"))
{
	/**
	 * Include view
	 * 
	 * @see SnooPHP\Utils::view()
	 */
	function view($name, array $args = [], SnooPHP\Http\Request $request = null)
	{
		SnooPHP\Utils::view($name, $args, $request);
	}
}

if (!function_exists("unescape_unicode"))
{
	/**
	 * Unescape unicode codes in string
	 * 
	 * @param string $content string to unescape
	 * 
	 * @return string
	 */
	function unescape_unicode($content)
	{
		return preg_replace("/\\\\\\\\u/", "\\\\u", $content);
	}
}
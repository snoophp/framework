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

if (!function_exists("mime_type"))
{
	/**
	 * Alias for deprecated mime_content_type
	 * 
	 * uses Fileinfo
	 * 
	 * @param string $filename file to test
	 * 
	 * @return string mime type
	 */
	function mime_type($filename)
	{
		$fileInfo	= new finfo();
		$type		= $fileInfo ? $fileInfo->file($filename, FILEINFO_MIME_TYPE) : false;
		error_log($type);
		// If plain/text, try to use extension
		if ($type === "text/plain")
		{
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			switch ($ext)
			{
				case "css":
					return "text/css";
				case "js":
					return "text/javascript";	
				default:
					return "text/plain";
			}
		}

		return $type;
	}
}

if (!function_exists("path"))
{
	/**
	 * Return absolute path for project directory
	 * 
	 * @param string	$path	relative path
	 * @param bool		$safe	return false if file/directory doesn't exist
	 * 
	 * @return string
	 */
	function path($path, $safe = false)
	{
		$rootDir = defined("ROOT_DIR") ? ROOT_DIR : $_SERVER["DOCUMENT_ROOT"];
		return $safe ?
		realpath($rootDir."/".$path) :
		$rootDir."/".$path;
	}
}

if (!function_exists("read_file"))
{
	/**
	 * Return content from file
	 * 
	 * It is really just an alias for @see file_get_contents()
	 * 
	 * @param string $path path to file
	 * 
	 * @return string|bool false if fails
	 */
	function read_file($path)
	{
		// Calc real path
		$path = ltrim($path);
		if ($path[0] === '/')	$path = rtrim($path);
		else					$path = path($path);

		return file_get_contents($path);
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

if (!function_exists("vueComponent"))
{
	/**
	 * Include a vue component
	 * 
	 * @see SnooPHP\Utils::vueComponent()
	 */
	function vueComponent($name, array $args = [], SnooPHP\Http\Request $request = null)
	{
		SnooPHP\Utils::vueComponent($name, $args, $request);
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

if (!function_exists("write_file"))
{
	/**
	 * Put content to file using put_content native php function
	 * 
	 * @param string	$path		path to file
	 * @param mixed		$content	string, binary or object/array content (converted to json)
	 * @param bool		$createDir	if directories don't exist create them
	 * @param bool		$serialize	if true, objects and arrays will be serialized rather than converted to json
	 * 
	 * @return bool false if fails
	 */
	function write_file($path, $content, $createDir = true, $serialize = false)
	{
		// Calc real path
		$path = ltrim($path);
		if ($path[0] === '/')	$path = rtrim($path);
		else					$path = path($path);

		// Check if dir exists
		// Create it otherwise
		$dir = dirname($path);
		if (!file_exists($dir) && (!$createDir || !mkdir($dir, 0755, true))) return false;

		// Convert content
		$content = is_string($content) ? $content : (
			$serialize ? serialize($content) : to_json($content)
		);

		// Write file
		return file_put_contents($path, $content) !== false;
	}
}
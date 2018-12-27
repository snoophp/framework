<?php

if (!function_exists("compile_style"))
{
	/**
	 * Compile style content using specified css preprocessor
	 * 
	 * @param string	$content	content to compile
	 * @param string	$lang		css preprocessor (default to vanilla css, no process)
	 * 
	 * @return string compiled content
	 */
	function compile_style($content, $lang)
	{
		$compiled	= $content;
		$content	= preg_replace("/\"/", "\\\"", $content);
		switch ($lang) {
			case "lessc":
			case "less":
				if (empty(`which lessc`))
				{
					error_log("lessc module not found (run `npm i -g lessc` to install)");
					break;
				}

				$compiled = `echo "$content" | lessc - --compress`;
				break;
			
			case "stylus":
				if (empty(`which stylus`))
				{
					error_log("stylus module not found (run `npm i -g stylus` to install)");
					break;
				}

				$compiled = `echo "$content" | stylus --compress`;
				break;
			
			default:
				/* Nothing to compile */
				break;
		}

		// Return result
		return $compiled;
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

if (!function_exists("parse_args"))
{
	/**
	 * Parses the given array of arguments
	 * according to the commands array
	 * 
	 * @param array	$argv	array of argument strings
	 * @param array	$cmds	array of commands to match
	 * 
	 * @return array
	 */
	function parse_args(array $argv, array $cmds = [])
	{
		// Out array
		$out = [
			"arg"		=> null,
			"params"	=> $cmds
		];

		// Current operation
		$op = null;

		for ($i = 1; $i < count($argv); ++$i)
		{
			// Match parameter
			if (preg_match("/^(?<prefix>-{0,2})(?<param>[^-].*)$/", $argv[$i], $matches))
			{
				$prefix = $matches["prefix"] ?: null;
				$param = $matches["param"];

				if ($prefix === null)
				{
					if ($op)
					{
						// Get operand
						$operand = $cmds[$op] ?? null;

						if (is_callable($operand))
							// Call function
							$out["params"][$op] = $operand($param, $op);
						else
							// Set value
							$out["params"][$op] = $param;
						
							// Reset op
						$op = null;
					}
					else
						// Set main parameter
						$out["arg"] = $param;
				}
				else if (count($prefix) == 1)
				{
					// Set current op
					$op = $param;
				}
				else
				{
					// Get operand
					$operand = $cmds[$param];

					if (is_callable($operand))
						// If it's callable, call it
						$out["params"][$param] = $operand($param);
					else
						// Else set operand flag
						$out["params"][$param] = true;
				}
			}
		}

		return $out;
	}
}

if (!function_exists("parse_string"))
{
	/**
	 * Attempt to parse string and cast it
	 * 
	 * @param string $value
	 * 
	 * @return mixed
	 */
	function parse_string($value)
	{
		if (is_string($value))
		{
			$val = trim($value);
			// Return boolean
			if (preg_match("/^(?:TRUE|FALSE|ON|OFF)$/i", $val))
				return !strcasecmp($val, "TRUE") || !strcasecmp($val, "ON") ? true : false;
			// Return integer
			if (preg_match("/^[0-9]+$/", $val) && strlen($val) < 16)
				return (int)$val;
			// Return float
			if (preg_match("/^[0-9]*\.(?:[0-9]+f?|f)+$/", $val))
				return (float)$val;
			
			// Return string
			return $value;
		}
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
	 * @return string|bool
	 */
	function path($path = "", $safe = false)
	{
		$rootDir = defined("ROOT_DIR") ? ROOT_DIR : $_SERVER["DOCUMENT_ROOT"];
		return $safe ?
		realpath($rootDir."/".$path) :
		$rootDir."/".$path;
	}
}

if (!function_exists("path_relative"))
{
	/**
	 * Return relative path (relative to project root folder by default)
	 * 
	 * @param string		$path	absolute path
	 * @param string|null	$root	the root folder by default
	 * 
	 * @return string|bool
	 */
	function path_relative($path, $root = null)
	{
		$root = $root ?: path();
		if (preg_match("@^$root@", $path))
			return substr($path, strlen($root));
		else
			return false;
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
	 * @param string	$name		view name
	 * @param array		$args		arguments to expose
	 * @param Request	$request	request if differs from current request
	 * 
	 * @return bool
	 */
	function view($name, array $args = [], SnooPHP\Http\Request $request = null)
	{
		$request = $request ?: Request::current();
		$fullPath = path("views")."/{$name}.php";
		if (file_exists($fullPath))
		{
			include $fullPath;
			return true;
		}

		return false;
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
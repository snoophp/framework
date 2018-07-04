<?php

namespace SnooPHP\Utils;

use SnooPHP\Http\Request;
use SnooPHP\Vue\Component;

/**
 * Set of utility methods
 * 
 * @author sneppy
 */
class Utils
{
	/**
	 * Include view
	 * 
	 * @deprecated v1.0.0
	 * 
	 * @param string	$name		view name
	 * @param array		$args		arguments to expose
	 * @param Request	$request	request if differs from current request
	 * 
	 * @return bool
	 */
	public static function view($name, array $args, Request $request = null)
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

	/**
	 * Compile style content using specified css preprocessor
	 * 
	 * @deprecated v1.0.0
	 * 
	 * @param string	$content	content to compile
	 * @param string	$lang		css preprocessor (default to vanilla css, no process)
	 * 
	 * @return string compiled content
	 */
	public static function compileStyle($content, $lang = "vanilla")
	{
		return compile_style($content, $lang);
	}

	/**
	 * Minify javascript using uglify-js if available
	 * 
	 * @param string $content content to minify
	 */
	public static function minifyJs($content)
	{
		if (!empty(`which uglifyjs`))
			return `echo "$content" | uglifyjs --compress --mangle --mangle-props`;

		return $content;
	}

	/**
	 * Get or set session errors
	 * 
	 * @param string $err error string
	 */
	public static function errors($err = null)
	{
		// Start session
		if (!isset($_SESSION)) session_start();
		if ($err)$_SESSION["errors"][] = $err;
		return isset($_SESSION["errors"]) ? $_SESSION["errors"] : [];
	}

	/**
	 * Flush all errors
	 */
	public static function flushErrors()
	{
		if (isset($_SESSION)) unset($_SESSION["errors"]);
	}

	/**
	 * Get path relative to public folder
	 * 
	 * @param string $absolutePath absolute path
	 * 
	 * @return string|bool
	 */
	public static function publicPath($absolutePath)
	{
		$publicPath = path("public");
		if (preg_match("@^{$publicPath}(.*)$@", $absolutePath, $matches))
			return $matches[1];

		return false;
	}

	/**
	 * Parse string as value
	 * 
	 * @deprecated v1.0.0
	 * 
	 * @param string $val string to parse
	 * 
	 * @return mixed
	 */
	public static function parseValue($val)
	{
		return parse_string($val);
	}
}
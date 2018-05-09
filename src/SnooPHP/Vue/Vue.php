<?php

namespace SnooPHP\Vue;

use SnooPHP\Http\Request;
use SnooPHP\Utils;

/**
 * Root component
 */
class Vue extends Component
{
	/**
	 * @var Component[] $components list of registered component
	 */
	protected $components = [];

	/**
	 * Register component globally
	 * 
	 * @param string		$file		vue document filename
	 * @param array			$args		list of arguments to pass to the view
	 * @param Request|null	$request	custom request or current if null
	 */
	public function __construct($file, array $args = [], Request $request = null)
	{
		// Register globally
		$GLOBALS["vue"] = $this;

		// Call parent constructor
		parent::__construct($file, $args, $request);
	}

	/**
	 * The root component doesn't have a template block
	 */
	public function parse()
	{
		// Parse style blocks
		$this->parseScript();
		$this->parseStyle();
	}

	/**
	 * Parse script block
	 */
	protected function parseScript()
	{
		if (!empty($this->document) && preg_match("~<script>(.+)</script>~s", $this->document, $matches))
			$this->script = trim($matches[1]);
		else
			$this->valid = false;
	}

	/**
	 * Register a sub-component
	 * 
	 * @param Component $comp component to register
	 */
	public function register(Component $comp)
	{
		$this->components[] = $comp;
	}

	/**
	 * Get full document
	 * 
	 * @param bool $inline if true, style and scripts are appended to the document instead of being saved in a temporary file
	 * 
	 * @return string
	 */
	public function document($inline = false)
	{
		// Remove style and script blocks
		$document	= $this->document;
		$document	= preg_replace("~<style[^>]*>[^<]*</style>~", "", $document);
		$document	= preg_replace("~<script>.+</script>~s", "", $document);

		// Get name and file paths
		$name		= basename($this->file, ".php");
		$scriptFile	= path("resources/tmp/$name.js");
		$styleFile	= path("resources/tmp/$name.css");

		// If files exist, use them (if development force rebuild instead)
		if (file_exists($scriptFile) && file_exists($styleFile) && env("env", "development") === "production")
			// Link externally
			return str_replace('</body>', '<script type="text/javascript" src="/tmp/'.$name.'.js"></script>
			<link rel="stylesheet" type="text/css" href="/tmp/'.$name.'.css"/></body>', $document);

		// Parse vue document
		$this->parse();
		if (!$this->valid())
		{
			error_log("($this->file} is not a valid Vue document");
			return "";
		}

		$script	= $this->script;
		$style	= $this->style;

		// Process components
		foreach ($this->components as $comp)
		{
			$comp->parse();
			if ($comp->valid())
			{
				$script	.= $comp->script();
				$style	.= $comp->style();
			}
		}

		// Minify javascript and css (note that some tools like stylus or scss may have built-in compression)
		$script = Utils::minifyJs($script);
		//$style	= Utils::minifyCss($style);

		// I specified, write style and script inline
		if ($inline)
			return str_replace("</body>", "<script>$script</script><style>$style</style></body>", $document);
		else
		{
			// Write external files
			write_file($scriptFile, $script);
			write_file($styleFile, $style);

			// Link externally
			return str_replace('</body>', '<script type="text/javascript" src="/tmp/'.$name.'.js"></script>
			<link rel="stylesheet" type="text/css" href="/tmp/'.$name.'.css"/></body>', $document);
		}
	}
}
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
	 * @return string
	 */
	public function document()
	{
		// Remove style and script blocks
		$document	= $this->document;
		$document	= preg_replace("~<style[^>]*>[^<]*</style>~", "", $document);
		$document	= preg_replace("~<script>.+</script>~s", "", $document);

		// Get name and file paths
		$name		= str_replace("/", ".", path_relative($this->file, path("views/")));
		$scriptFile	= path("resources/tmp/$name.js");
		$styleFile	= path("resources/tmp/$name.css");

		// We're not caching scripts, otherwise we would lose php dynamic content
		$script = "";
		foreach($this->components as $comp)
		{
			$comp->parseTemplate();
			$comp->parseScript();
			if ($comp->valid()) $script .= $comp->script();
		}
		$this->parseScript();
		$script .= $this->script();

		// Minify javascript in production mode
		if (env("env", "development") === "production") $script = Utils::minifyJs($script);

		// Write script file
		write_file($scriptFile, $script);

		// If style files does not exists or it's development mode, rebuild them
		if (!file_exists($styleFile) || env("env", "development") === "development")
		{	
			// Process components style
			$style = "";
			foreach ($this->components as $comp)
			{
				$comp->parseStyle();
				$style	.= $comp->style();
			}
			$this->parseStyle();
			$style .= $this->style();
	
			// I specified, write style and script inline
			write_file($styleFile, $style);
		}

	
		// Link externally
		return str_replace('</head>',
		'<script type="text/javascript" src="/tmp/'.$name.'.js" defer></script>
		<link rel="stylesheet" type="text/css" href="/tmp/'.$name.'.css" media="none" onload="media = \'all\'"/>', $document);
	}
}
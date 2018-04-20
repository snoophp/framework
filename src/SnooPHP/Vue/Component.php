<?php

namespace SnooPHP\Vue;

use SnooPHP\Http\Request;
use SnooPHP\Utils;

/**
 * Methods to handle vue components
 * 
 * Vue components in SnooPHP works in a very similar way to npm
 * 
 * To use a vue component you must first create a view file (.php) in the `views/components` folder
 * with an html template wrapped inside a <template> block
 * a script used to register a global component or declare a local component - @see https://vuejs.org/v2/guide/components.html#Global-Registration
 * and a style block
 * 
 * SnooPHP parses the template content in the `template` property of the component, script and style tag are included as is in the final view
 * 
 * Use `vueComponent()` global function to include a vue component
 * 
 * @author sneppy
 */
class Component
{
	/**
	 * @var string $file filepath
	 */
	protected $file;
	
	/**
	 * @var string $id component id
	 */
	protected $id;

	/**
	 * @var string $content processed content
	 */
	protected $content = "";
	
	/**
	 * @var int count number of components parsed in current request
	 */
	protected static $count = 0;

	/**
	 * Create a new vue component
	 * 
	 * @param string	$name	component full name
	 * @param array		$args		arguments to expose
	 * @param Request	$request	request if differs from current request
	 */
	public function __construct($name, array $args = [], Request $request = null)
	{
		$request = $request ?: Request::current();

		$this->file = path("views/components")."/$name.php";
		if (file_exists($this->file))
		{
			// Get component content
			ob_start();
			include $this->file;
			$this->content = ob_get_contents();
			ob_end_clean();
		}

		// Increment component count
		static::$count++;
	}

	/**
	 * Return true if component is valid
	 * 
	 * @return bool
	 */
	public function valid()
	{
		return !empty($this->content);
	}

	/**
	 * Parse content as vue component
	 * 
	 * First search for <template> block
	 * If found, minify content and place it in vue component tempalte property
	 * Template property should be prefixed by a colon `:template,` in the vue component
	 * Finally remove the <template> block and return content
	 * 
	 * @return string parsed content
	 */
	public function parse()
	{
		// Parse vue template
		$this->generateTemplate();
		$this->parseStyles();

		return $this->content;
	}

	/**
	 * Parse template code
	 */
	protected function generateTemplate()
	{
		if (preg_match("~<template>(.+)</template>~s", $this->content, $template))
		{
			$template = trim($template[1], "\n");
			
			// Generate id for component
			$this->id	= "snoophp-comp-".static::$count;
			$template	= preg_replace("~(<[A-Za-z][A-Za-z0-9\-]*)([^>]*)(?=/?>)~", "$1 {$this->id}$2", $template);

			// Remove template block and set component template
			$this->content	= preg_replace("~<template>(.+)</template>~s", "", $this->content);
			$this->content	= str_replace(":template", "template: `$template`", $this->content);
		}
	}

	/**
	 * Parse style blocks
	 */
	protected function parseStyles()
	{
		// Find style tags
		$result = "";
		if (preg_match_all("~(<style[^>]*>)([^<]*)</style>~", $this->content, $styles, PREG_SET_ORDER)) foreach ($styles as $style)
		{
			// Get style properties
			$content	= $style[2];
			$tag		= $style[1];
			$lang		= "vanilla";
			$scoped		= false;
			if (preg_match_all("~([a-z][-a-z0-9]*)(?:=([^\s]+))?~", $tag, $attributes, PREG_SET_ORDER)) foreach ($attributes as $att)
			{
				if ($att[1] === "lang")
				{
					if (isset($att[2])) $lang = trim($att[2], '"');
				}
				else if ($att[1] === "scoped")
				{
					$scoped = true;
				}
				else if ($att[1] !== "style")
				{
					error_log("unknown attribute found on style tag: ".$att[0]);
				}
			}

			// Compile style
			$compiled = Utils::processStyle($content, $lang);

			// Apply scope if necessary
			/** @todo this could be very expensive, but I could not come up with something better */
			if ($scoped)
			{
				// Find selectors
				if (preg_match_all("~(?:(@[^;{}]+){((?:[^{}]+{[^}]*}\s*)*)}|([^;{}]+)({[^}]*})|([^;{}]+;))~", $compiled, $matches, PREG_SET_ORDER))
				{
					// Reset output
					$compiled = "";

					foreach ($matches as $match)
					{
						if (!empty($match[1]))
						{
							$rule	= trim($match[1]);
							$ruled	= trim($match[2]);

							$compiled .= $rule.'{';

							// Rerun regex for rule content
							if (preg_match_all("~([^;{}]+)({[^}]*})~", $ruled, $matches_, PREG_SET_ORDER))
								foreach($matches_ as $match_)
									if (!empty($match_[1]))
									{
										// Split selectors
										$selectors	= explode(",", $match_[1]);
										$content	= trim($match_[2]);
										foreach($selectors as $i => $selector)
											$selectors[$i] = preg_replace("~([\.#]?[a-zA-Z][-a-zA-Z0-9]*)((?:\[[^\]]+\]|\:\:?[a-z]+)*)$~", "$1[{$this->id}]$2", trim($selector));
										
										// Add to compiled
										$compiled .= implode(",", $selectors).$content;
									}
							
							$compiled .= '}';
						}
						else if (!empty($match[3]))
						{
							// Split selectors
							$selectors	= explode(",", $match[3]);
							$content	= trim($match[4]);
							foreach($selectors as $i => $selector)
								$selectors[$i] = preg_replace("~([\.#]?[a-zA-Z][-a-zA-Z0-9]*)((?:\[[^\]]+\]|\:\:?[a-z]+)*)$~", "$1[{$this->id}]$2", trim($selector));
							
							// Add to compiled
							$compiled .= implode(",", $selectors).$content;
						}
						else
						{
							// Add to compiled
							$compiled .= $match[5];
						}
					}
				}
			}

			// Merge in single result
			$result .= $compiled;
		}

		// Replace with unique style block
		$this->content = preg_replace("~<style.*</style>~s", "<style>\n$result\n</style>", $this->content);
	}
}
<?php

namespace SnooPHP\Vue;

use SnooPHP\Http\Request;

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
		$this->compileStyle();

		return $this->content;
	}

	/**
	 * Parse template code
	 */
	protected function generateTemplate()
	{
		if (preg_match("~<template>(.+)</template>~s", $this->content, $template))
		{
			$template = $template[1];
			if (preg_match("~^\s*<div(.*)id=.([^\"']+).~", $template, $matches))
			{
				// Extract id
				$this->id = explode(" ", $matches[2])[0];
			}
			else
			{
				// Generate id for component
				$this->id = "comp".static::$count;
				$template = preg_replace("~^\s*<div~", "<div id=\"{$this->id}\"", $template, 1);
			}

			// Remove template block and set component template
			$this->content	= preg_replace("~<template>(.+)</template>~s", "", $this->content);
			$this->content	= str_replace(":template", "template: `$template`", $this->content);
		}
	}

	/**
	 * Compile style blocks
	 */
	protected function compileStyle()
	{
		$result = "";
		if (preg_match_all("~<style\s*(scoped)?>([^<]*)</style>~s",
			$this->content, $styles, PREG_SET_ORDER))
		{
			foreach ($styles as $style)
			{
				$scoped	= strcmp($style[1], "scoped") === 0;
				$rules	= $scoped ? "#{$this->id}{".$style[2]."}" : $style[2];
				$result	.= $rules;
			}

			// Replace with unique style block
			$this->content = preg_replace("~<style.*</style>~s", "<style>\n$result\n</style>", $this->content);
		}
	}
}
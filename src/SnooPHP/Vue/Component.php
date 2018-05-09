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
 * @author Sneppy
 */
class Component
{
	/**
	 * @var string $id component id
	 */
	protected $id;

	/**
	 * @var string $file filepath
	 */
	protected $file;

	/**
	 * @var string $document unprocessed content
	 */
	protected $document;

	/**
	 * @var string $template parsed template
	 */
	protected $template;

	/**
	 * @var string $script parsed script
	 */
	protected $script;

	/**
	 * @var string $style parsed style
	 */
	protected $style;

	/**
	 * @var bool $valid true if component is valid
	 */
	protected $valid = true;
	
	/**
	 * @var int count number of components parsed in current request
	 */
	protected static $count = 0;

	/**
	 * @const SCOPE_ATTRIBUTE attribute name used to define component scope
	 */
	const SCOPE_ATTRIBUTE = "snoophp-comp-";

	/**
	 * Create a new vue component
	 * 
	 * @param string	$file		component file path
	 * @param array		$args		arguments to expose
	 * @param Request	$request	request if differs from current request
	 */
	public function __construct($file, array $args = [], Request $request = null)
	{
		// Retrieve current request if none specified
		$request = $request ?: Request::current();

		$this->id	= static::$count;
		$this->file	= $file;
		if (file_exists($this->file))
		{
			// Get component content
			ob_start();
			include $this->file;
			$this->document = ob_get_contents();
			ob_end_clean();

			// Increment component count
			static::$count++;
		}
		else
			error_log("component {$this->file} does not exists");
	}

	/**
	 * Return true if component is valid
	 * 
	 * @return bool
	 */
	public function valid()
	{
		return $this->valid;
	}

	/**
	 * Return component template
	 * 
	 * @return string
	 */
	public function template()
	{
		return $this->template;
	}

	/**
	 *  Return component script
	 * 
	 * @return string
	 */
	public function script()
	{
		return $this->script;
	}

	/**
	 * Return component style
	 * 
	 * @return string
	 */
	public function style()
	{
		return $this->style;
	}

	/**
	 * Parse template, script and style blocks
	 */
	public function parse()
	{
		// Parse vue template,script and style
		$this->parseTemplate();
		$this->parseScript();
		$this->parseStyle();
	}

	/**
	 * Parse template block
	 */
	protected function parseTemplate()
	{
		if (!empty($this->document) && preg_match("~<template>(.+)</template>~s", $this->document, $matches))
		{
			$content		= trim($matches[1]);

			// Add scope
			$this->template	= preg_replace("~(<[_A-Za-z][_\-\.A-Za-z0-9]*)([^>]*/?>)~", "$1 ".static::SCOPE_ATTRIBUTE.$this->id."$2", $content);
		}
		else
			$this->valid = false;
	}

	/**
	 * Parse script block
	 */
	protected function parseScript()
	{
		if (!empty($this->document) && preg_match("~<script>(.+)</script>~s", $this->document, $matches))
		{
			$content = trim($matches[1]);

			// Add template
			$this->script = str_replace(":template", "template: `{$this->template}`", $content);
		}
		else
			$this->valid = false;
	}

	/**
	 * Parse style blocks
	 */
	protected function parseStyle()
	{
		$this->style = "";

		// Find style tags
		if (!empty($this->document) && preg_match_all("~(<style [^>]*>)(.*)</style>~s", $this->document, $matches, PREG_SET_ORDER))
			// Multiple style blocks are allowed
			foreach ($matches as $styleBlock)
			{
				$styleTag		= $styleBlock[1];
				$styleContent	= $styleBlock[2];
				$props			= ["lang" => "vanilla", "scoped" => false];
				
				// Get style properties
				if (!empty($styleTag) && preg_match_all("~([^\s\"\'\:>/=]+)(?:\s*=\s*(?:\"([^\"]+)\"|\'[^\']+\'|[^\s\"\'\`<>=]+))?~s", $styleTag, $attributes, PREG_SET_ORDER))
					foreach ($attributes as $attr)
						if ($attr[1] === "lang")
							$props["lang"] = $attr[2] ?? $attr[3] ?? $attr[4] ?? "vanilla";
						else if ($attr[1] === "scoped")
							$props["scoped"] = !empty($attr[2]) ? (bool)$attr[2] : true;
				
				// Compile style
				$compiled = Utils::compileStyle($styleContent, $props["lang"]);

				// Scope style
				if ($props["scoped"])
				{
					$scoped = "";

					// Break up content
					if (preg_match_all("~(?:(?<at_statement>@[^;{]+;)|(?<at_rule>@[^;{]+){(?<at_content>(?:[^{]+{[^{}]+}|[^}])+)}|(?<selector>[\.\-\*\[#_a-zA-Z][^{]*){(?<content>[^{}]*)})~S", $compiled, $parts, PREG_SET_ORDER))
						foreach ($parts as $part)
							if (!empty($part["at_statement"]))
								$scoped .= $part["at_statement"];
							else if (!empty($part["selector"]))
								$scoped .= $this->applyScope($part["selector"])."{".(empty($part["content"]) ? "" : $part["content"])."}";
							else if (!empty($part["at_rule"]))
							{
								// process nested blocks
								$nested = "";
								if (preg_match_all("~(?<selector>[\.\-\*\[#_a-zA-Z][\s\.\-\*\[_+>=a-zA-Z0-9]*){(?<content>[^{}]*)}~", $part["at_content"], $nestedRules, PREG_SET_ORDER))
									foreach ($nestedRules as $nestedRule)
										$nested .= $this->applyScope($nestedRule["selector"])."{{$part["content"]}}";
								else
									// For example @keyframe content is not processed
									$nested = $part["at_content"];
								
								$scoped .= "{$part["at_rule"]}{{$nested}}";
							}
					
					$compiled = $scoped;
				}

				$this->style .= trim($compiled);
			}
	}

	/**
	 * Apply scope to selector
	 * 
	 * @param string $selector selector to scope
	 * 
	 * @return string
	 */
	protected function applyScope($selector)
	{
		$scoped	= "";
		$parts	= preg_split("/([, >+~])/", $selector, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		foreach ($parts as $part)
			if (preg_match("/[, >+~]/", $part))
				$scoped .= $part;
			else
				$scoped .= preg_replace("/([^:]+)((?:::?[^:]+)*)/", "$1[".static::SCOPE_ATTRIBUTE.$this->id."]$2", trim($part));
		return $scoped;
	}
}
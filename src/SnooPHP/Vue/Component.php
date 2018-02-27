<?php

namespace SnooPHP\Vue;

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
	 * Parse content as vue component
	 * 
	 * First search for <template> block
	 * If found, minify content and place it in vue component tempalte property
	 * Template property should be prefixed by a colon `:template,` in the vue component
	 * Finally remove the <template> block and return content
	 * 
	 * @param string $content content to parse
	 * 
	 * @return string parsed content
	 */
	public static function parse($content)
	{
		// Parse vue template
		if (preg_match("~<template>(.+)</template>~s", $content, $template))
		{
			// Remove template code
			$content = preg_replace("~<template>(.+)</template>~", "", $content);
			
			// Minify template code
			/* @todo can cause problems with <pre> blocks */
			$template = preg_replace("~(?:\n|\t)~", "", $template[1]);
			
			// Set component template
			$content = preg_replace("~:template~", "template: `$template`", $content);
		}

		return $content;
	}
}
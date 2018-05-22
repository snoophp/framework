<?php

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
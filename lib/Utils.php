<?php

/**
 * Set of utility methods
 * 
 * @author sneppy
 */
class Utils
{
	/**
	 * Convert to json string
	 * 
	 * @param mixed $content content to convert
	 * 
	 * @return string
	 */
	public static function toJson($content)
	{
		return is_string($content) ?
		$content :
		json_encode($content);
	}

	/**
	 * Decode from json string
	 * 
	 * @param string $content content to decode
	 * 
	 * @return object|array
	 */
	public static function fromJson($content, $assoc = false)
	{
		return json_decode($content, $assoc);
	}

	/**
	 * Parse string as value
	 * 
	 * @param string $val string to parse
	 * 
	 * @return mixed
	 */
	public static function parseValue($val)
	{
		$val = trim($val);
		if (preg_match("/^(?:TRUE|FALSE|ON|OFF)$/i", $val))			return strcasecmp($val, "TRUE") === 0 || strcasecmp($val, "ON") === 0;
		else if (preg_match("/^[0-9]+$/", $val)) 					return (int)$val;
		else if (preg_match("/^[0-9]*\.(?:[0-9]+f?|f)$/", $val))	return (float)$val;

		return $val;
	}
}
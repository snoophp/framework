<?php

namespace SnooPHP\Curl;

/**
 * POST request wrapper
 * 
 * @author sneppy
 */
class Post extends Curl
{
	/**
	 * Create a new POST request
	 * 
	 * @param string		$url		requested url
	 * @param string|array	$data		post data as an associative array or url encoded string
	 * @param array			$headers	list of http headers
	 * @param bool			$initOnly	if true the session won't be executed
	 */
	public function __construct($url, $data = "", array $headers = [], $initOnly = false)
	{
		// If string, set x-www-form-urlencoded header
		if (is_string($data)) $headers["Content-Type"] = "application/x-www-form-urlencoded";

		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "POST",
			CURLOPT_POSTFIELDS		=> $data,
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, $initOnly);
	}
}
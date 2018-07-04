<?php

namespace SnooPHP\Curl;

/**
 * PUT request wrapper
 * 
 * @author sneppy
 */
class Put extends Curl
{
	/**
	 * Create a new PUT request
	 * 
	 * @param string		$url		requested url
	 * @param string|array	$data		data as an associative array or urlencoded string
	 * @param array			$headers	list of http headers
	 * @param bool			$initOnly	if true the session won't be executed
	 */
	public function __construct($url, $data = "", array $headers = [], $initOnly = false)
	{
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "PUT",
			CURLOPT_POSTFIELDS		=> is_string($data) ? $data : http_build_query($data),
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, false);
	}
}
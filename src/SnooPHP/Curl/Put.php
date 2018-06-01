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
	 * @param string|array	$put		data as an associative array or urlencoded string
	 * @param array			$headers	list of http headers
	 * @param bool			$initOnly	if true the session won't be executed
	 */
	public function __construct($url, $put = "", array $headers = [], $initOnly = false)
	{
		if (is_string($put)) $headers["Content-Type"] = "application/x-www-form-urlencoded";
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "PUT",
			CURLOPT_POSTFIELDS		=> http_build_query($put),
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, false);
	}

	/**
	 * Create a new session with an authorization header
	 * 
	 * @deprecated v0.2.4
	 * 
	 * @param string		$url		request url
	 * @param string		$authKey	authorization key
	 * @param string|array	$put		data as an associative array or urlencoded string
	 * @param array			$headers	list of additional http headers
	 * @param bool			$initOnly	if true the session won't be executed
	 * 
	 * @return Put
	 */
	public static function withAuth($url, $authKey = "", $put = "", array $headers = [], $initOnly = false)
	{
		return new Put($url, $put, array_merge(["Authorization" => $authKey], $headers), $initOnly);
	}
}
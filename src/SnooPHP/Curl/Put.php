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
	 * Create a new PUT cURL session
	 * 
	 * @param string		$url		session url
	 * @param string|array	$put		data as an associative array or urlencoded string
	 * @param array			$headers	list of http headers
	 */
	public function __construct($url, $put = "", array $headers = [])
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
	 * @param string		$url		request url
	 * @param string		$authKey	authorization key
	 * @param string|array	$put		data as an associative array or urlencoded string
	 * 
	 * @return Put
	 */
	public static function withAuth($url, $authKey = "", $put = "")
	{
		return new Put($url, $put, ["Authorization" => $authKey]);
	}
}
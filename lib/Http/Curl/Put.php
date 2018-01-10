<?php

namespace Http\Curl;

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
	 * @param string	$url		session url
	 * @param array		$put		data as an associative array
	 * @param array		$headers	list of http headers
	 */
	public function __construct($url, array $put = [], array $headers = [])
	{
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "PUT",
			CURLOPT_POSTFIELDS		=> http_build_query($put),
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, false);
	}

	/**
	 * Create a new session with an authorization header
	 * 
	 * @param string	$url		request url
	 * @param string	$authKey	authorization key
	 * @param array		$put		data as an associative array
	 * 
	 * @return Put
	 */
	public static function withAuth($url, $authKey = "", array $put = [])
	{
		return new Put($url, $put, ["Authorization" => $authKey]);
	}
}
<?php

namespace Http\Curl;

/**
 * GET request wrapper
 * 
 * @author sneppy
 */
class Get extends Curl
{
	/**
	 * Create a new GET cURL session
	 * 
	 * @param string	$url		session url
	 * @param array		$headers	list of http headers
	 */
	public function __construct($url, array $headers = [])
	{
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "GET",
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, false);
	}

	/**
	 * Create a new session with an authorization header
	 * 
	 * @param string	$url		request url
	 * @param string	$authKey	authorization key
	 * 
	 * @return Get
	 */
	public static function withAuth($url, $authKey = "")
	{
		return new Get($url, ["Authorization" => $authKey]);
	}
}
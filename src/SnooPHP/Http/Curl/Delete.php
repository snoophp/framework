<?php

namespace SnooPHP\Http\Curl;

/**
 * DELETE request wrapper
 * 
 * @author sneppy
 */
class Delete extends Curl
{
	/**
	 * Create a new DELETE cURL session
	 * 
	 * @param string	$url		session url
	 * @param array		$headers	list of http headers
	 */
	public function __construct($url, array $headers = [])
	{
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "DELETE",
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, false);
	}

	/**
	 * Create a new session with an authorization header
	 * 
	 * @param string	$url		request url
	 * @param string	$authKey	authorization key
	 * 
	 * @return Delete
	 */
	public static function withAuth($url, $authKey = "")
	{
		return new Delete($url, ["Authorization" => $authKey]);
	}
}
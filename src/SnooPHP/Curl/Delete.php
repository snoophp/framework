<?php

namespace SnooPHP\Curl;

/**
 * DELETE request wrapper
 * 
 * @author sneppy
 */
class Delete extends Curl
{
	/**
	 * Create a new DELETE Curl session
	 * 
	 * @param string	$url		session url
	 * @param array		$headers	list of http headers
	 * @param bool		$initOnly	if true the session won't be executed
	 */
	public function __construct($url, array $headers = [], $initOnly = false)
	{
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "DELETE",
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, $initOnly);
	}

	/**
	 * Create a new session with an authorization header
	 * 
	 * @param string	$url		request url
	 * @param string	$authKey	authorization key
	 * @param array		$headers	list of additional http headers
	 * @param bool		$initOnly	if true the session won't be executed
	 * 
	 * @return Delete
	 */
	public static function withAuth($url, $authKey = "", array $headers = [], $initOnly = false)
	{
		return new Delete($url, array_merge(["Authorization" => $authKey], $headers), $initOnly);
	}
}
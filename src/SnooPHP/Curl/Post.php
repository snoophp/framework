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
	 * @param string|array	$post		post data as an associative array or url encoded string
	 * @param array			$headers	list of http headers
	 * @param bool			$initOnly	if true the session won't be executed
	 */
	public function __construct($url, $post = "", array $headers = [], $initOnly = false)
	{
		// If string, set x-www-form-urlencoded header
		if (is_string($post)) $headers["Content-Type"] = "application/x-www-form-urlencoded";

		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "POST",
			CURLOPT_POSTFIELDS		=> $post,
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, $initOnly);
	}

	/**
	 * Create a new session with an authorization header
	 * 
	 * @deprecated v0.2.4
	 * 
	 * @param string		$url		request url
	 * @param string		$authKey	authorization key
	 * @param string|array	$post		post data as an associative array or urlencoded string
	 * @param array			$headers	list of additional http headers
	 * @param bool			$initOnly	if true the session won't be executed
	 * 
	 * @return Post
	 */
	public static function withAuth($url, $authKey = "", $post = "", array $headers = [], $initOnly = false)
	{
		return new Post($url, $post, array_merge(["Authorization" => $authKey], $headers), $initOnly);
	}
}
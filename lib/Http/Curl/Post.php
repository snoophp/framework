<?php

namespace Http\Curl;

/**
 * POST request wrapper
 * 
 * @author sneppy
 */
class Post extends Curl
{
	/**
	 * Create a new POST cURL session
	 * 
	 * @param string	$url		session url
	 * @param array		$post		post data as an associative array
	 * @param array		$headers	list of http headers
	 */
	public function __construct($url, array $post = [], array $headers = [])
	{
		parent::__construct($url, [
			CURLOPT_CUSTOMREQUEST	=> "POST",
			CURLOPT_POSTFIELDS		=> $post,
			CURLOPT_RETURNTRANSFER	=> true
		], $headers, false);
	}

	/**
	 * Create a new session with an authorization header
	 * 
	 * @param string	$url		request url
	 * @param array		$post		post data as an associative array
	 * @param string	$authKey	authorization key
	 * 
	 * @return Post
	 */
	public static function withAuth($url, array $post = [], $authKey = "")
	{
		return new Post($url, $post, ["Authorization" => $authKey]);
	}
}
<?php

namespace SnooPHP\Git;

use SnooPHP\Http\Request;
use SnooPHP\Http\Response;

/**
 * A webhook designed to work with github webhook system
 * 
 * @author sneppy
 */
class GitHubWebhook extends Webhook
{
	/**
	 * Handle a webhook request coming from github
	 * 
	 * @param Request $request webhook request
	 * 
	 * @return Response
	 */
	public static function handle(Request $request)
	{
		global $webhookConfig;

		$payload = \SnooPHP\Utils::fromJson($request->input("payload", "null"));
		if (!$payload) Response::abort(400, [
			"status"		=> "ERROR",
			"description"	=> "payload not found"
		]);

		// Allow only from known addresses
		$allowed = false; $ip = $request->header("Remote Address");
		foreach ($webhookConfig["whitelist"] as $test) $allowed |= static::checkIp($ip, $test);
		if (!$allowed) Response::abort(400, [
			"status"		=> "ERROR",
			"description"	=> "ip not whitelisted"
		]);

		// Check repository
		if ($payload->repository->id !== $webhookConfig["rep_id"]) Response::abort(400, [
			"status"		=> "ERROR",
			"description"	=> "invalid repository id"
		]);

		// Run script
		if (file_exists($webhookConfig["script"]))
		{
			$output = shell_exec($webhookConfig["script"]." ".$request->header("X-GitHub-Delivery")." ".(isset($webhookConfig["branch"]) ? $webhookConfig["branch"] : "master"));
			return Response::json([
				"status"		=> "OK",
				"description"	=> "webhook deployed",
				"output"		=> $output
			]);
		}
		else
		{
			Response::abort(500, [
				"status"		=> "ERROR",
				"description"	=> "webhook script not found"
			]);
		}
	}
}
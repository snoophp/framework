<?php

/***********************
 * WEBHOOK CONFIGURATION
 ***********************/
use Http\Router;

/**
 * @var array $webhookConfig webhook configuration array
 */
$webhookConfig = [
	"webhook"	=> "Git\GitHubWebhook",
	"whitelist"	=> [
		"192.30.252.0/22",
		"185.199.108.0/22"
	],
	"rep_id"	=> 102726072,
	"script"	=> __DIR__."/../webhook.sh",
	"branch"	=> "master",
	"url"		=> "/webhook",
	"enabled"	=> true
];

/***************
 * DO NOT MODIFY
 ***************/
if ($webhookConfig["enabled"]):
	$router = register_router(new Router($webhookConfig["url"]));
	$router->post("/", function($request) {
		
		global $webhookConfig;
		$webhookName = $webhookConfig["webhook"];
		return ($webhookConfig["webhook"]::handle($request));
	});
endif;
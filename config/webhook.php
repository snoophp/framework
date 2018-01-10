<?php

/***********************
 * WEBHOOK CONFIGURATION
 ***********************/
use Http\Router;

/**
 * @var array $webhookConfig webhook configuration array
 */
$webhookConfig = [
	"url"		=> "/webhook",
	"class"		=> "Git\GitHubWebhook",
	"whitelist"	=> [
		"192.30.252.0/22",
		"185.199.108.0/22"
	],
	"rep_id"	=> 102726072,
	"branch"	=> "master",
	"script"	=> __DIR__."/../webhook.sh",
	"enabled"	=> false
];

/***************
 * DO NOT MODIFY
 ***************/
if ($webhookConfig["enabled"]):
	$router = register_router(new Router($webhookConfig["url"]));
	$router->post("/", function($request) {
		
		global $webhookConfig;
		$webhookName = $webhookConfig["webhook"];
		return ($webhookConfig["class"]::handle($request));
	});
endif;
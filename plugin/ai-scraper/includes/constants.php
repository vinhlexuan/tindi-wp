<?php
return [
	'plugin_name' => 'AI Scraper',
	'plugin_prefix_name'  => 'wp_scraper_gpt',
	'api_url_extractor' => 'https://wp.socibi.com/webhook/wp-rewrite-content-multi',
	'api_rewriter' => 'https://workflow.tindimedia.vn:8443/webhook/3a8d602e-431d-4b5f-84a0-889a5216fa58',
	'api_remain_credits' => 'https://wp.socibi.com/webhook/get-balance',
	'api_topup' => 'https://wp.socibi.com/webhook/topup',
	'schedule_interval' => [
		'manual' => 'Manual',
		'every_30_minutes' => 'Every 30 minutes',
		'hourly' => 'Hourly',
		'twice_a_day' => 'Twice a day',
		'daily' => 'Daily',
		'weekly' => 'Weekly',
	],
];
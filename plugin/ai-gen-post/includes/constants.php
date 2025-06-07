<?php
return [
    'plugin_name' => 'AI Gen post',
    'plugin_prefix_name' => 'wp_post-generator_gpt',
    'api_remain_credits' => 'https://wp.socibi.com/webhook/get-balance',
    'api_topup' => 'https://wp.socibi.com/webhook/topup',
    'n8n_callback_url' => function() {
        return rest_url('n8n-webhook/v1/callback');
    },
];
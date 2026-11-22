<?php

return [
    'allow_demo_seeders' => env('SCOM_ALLOW_DEMO_SEEDERS', false),
    'demo_password' => env('SCOM_DEMO_PASSWORD', ''),

    'ai' => [
        'enabled' => filter_var(env('SCOM_AI_ENABLED', true), FILTER_VALIDATE_BOOL),
        'url' => rtrim((string) env('SCOM_AI_URL', 'http://127.0.0.1:11434'), '/'),
        'model' => (string) env('SCOM_AI_MODEL', 'qwen2.5:3b'),
        'timeout' => (int) env('SCOM_AI_TIMEOUT', 60),
    ],
];
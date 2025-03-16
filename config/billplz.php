<?php

return [
    'api_key' => env('BILLPLZ_API_KEY', ''),
    'api_x_signature' => env('BILLPLZ_XSIGNATURE', ''),
    'api_version' => env('BILLPLZ_API_VERSION', 'v5'),
    'api_url' => env('BILLPLZ_API_URL', 'https://www.billplz.com/api/v3'),
];

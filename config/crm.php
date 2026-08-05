<?php

return [
    'admin_email' => env('CRM_ADMIN_EMAIL'),
    'admin_password' => env('CRM_ADMIN_PASSWORD'),
    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', false),
];

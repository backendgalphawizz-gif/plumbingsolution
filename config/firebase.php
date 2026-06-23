<?php

return [

    'project_id' => env('FIREBASE_PROJECT_ID', 'plumbingsolution'),

    'service_account' => env(
        'FIREBASE_SERVICE_ACCOUNT',
        config_path('firebase-service-account.json')
    ),

];

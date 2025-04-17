<?php

return [
    "paths" => ['notify/api/*'],
    "allowed_methods" => ['GET, POST, PUT, DELETE, OPTION'],
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Origin, Content-Type, X-Auth-Token , Cookie'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true
];
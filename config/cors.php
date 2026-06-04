<?php

return [

    /*
    | Konfigurasi CORS — izinkan mobile app (Flutter) & klien lain
    | mengakses endpoint /api dari origin manapun.
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Token-based (Bearer) tidak butuh cookie, jadi false.
    'supports_credentials' => false,

];

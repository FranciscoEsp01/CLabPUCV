<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | Secret key used to sign and verify JSON Web Tokens (HMAC-SHA256).
    | Defaults to the application key if JWT_SECRET is not explicitly set.
    |
    */
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Access Token TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Expiration time for access tokens in minutes (default 60 minutes).
    |
    */
    'ttl' => env('JWT_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Refresh Token TTL
    |--------------------------------------------------------------------------
    |
    | Expiration time for refresh tokens in minutes (default 7 days = 10080 min).
    |
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 10080),

    /*
    |--------------------------------------------------------------------------
    | Algorithm
    |--------------------------------------------------------------------------
    |
    | Supported signature algorithm (HS256).
    |
    */
    'algo' => 'HS256',

    /*
    |--------------------------------------------------------------------------
    | Token Issuer & Audience
    |--------------------------------------------------------------------------
    |
    | Expected issuer (iss) and audience (aud) claims for validation.
    |
    */
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'https://clab.pucv.cl')),
    'audience' => env('JWT_AUDIENCE', 'clabpucv-web'),
];

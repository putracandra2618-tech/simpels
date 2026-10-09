<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of proxy IPs/CIDRs whose X-Forwarded-* headers
    | should be trusted. Use "*" for local tunneling (e.g. ngrok) so that
    | HTTPS asset URLs are generated behind the proxy. Leave empty when
    | the application is accessed directly without a proxy.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];

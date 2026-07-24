<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MCP Web Transport Token
    |--------------------------------------------------------------------------
    |
    | Static bearer token required to reach the MCP web transport
    | (POST /mcp/reservations). Clients send it as an
    | "Authorization: Bearer <token>" header. Keep it secret; when it is empty
    | the web endpoint refuses all requests.
    |
    */

    'token' => env('MCP_TOKEN'),

];

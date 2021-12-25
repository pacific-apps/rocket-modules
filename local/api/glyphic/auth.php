<?php

declare(strict_types=1);

/**
 * Endpoint: /api/glyphic/auth
 * Responsible for all glyphic transactions
 */

$root = $_SERVER['DOCUMENT_ROOT'];
require $root.'/imports.php';

use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;

$request  = new Request;
$response = new Response;

ACCEPT::method('POST');
ACCEPT::payload(['key','secret','grant_type=client_credentials']);

$key    = getenv('GLYPHIC_KEY');
$secret = getenv('GLYPHIC_SECRET');

if ($key!==$request->payload()->key||$secret!==$request->payload()->secret) {
    Response::unauthorized();
}

Response::transmit([
    'payload' => [
        'status'=>'200 OK',
        'message' => 'authenticated',
        'token' => Token::create([
            'requester'=>'root'
        ])
    ]
]);

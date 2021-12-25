<?php

declare(strict_types=1);

/**
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
ACCEPT::payload(['public','secret','grant_type=client_credentials']);

$key    = getenv('GLYPHIC_PUBLIC');
$secret = getenv('GLYPHIC_SECRET');

if ($key!==$request->payload()->public||$secret!==$request->payload()->secret) {
    Response::unauthorized();
}

$token = new Token();
$token->payload([
    'requester'=>'root'
]);
$token->create();

Response::transmit([
    'payload' => [
        'status'=>'200 OK',
        'message' => 'authenticated',
        'token' => $token->get()
    ]
]);

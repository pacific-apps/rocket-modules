<?php

$root = $_SERVER['DOCUMENT_ROOT'];
require $root.'/imports.php';

use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \glyphic\core\Installer;

$request = new Request;
$response = new Response;

ACCEPT::method('POST');
ACCEPT::payload(['token']);

if (!Token::verify($request->payload()->token)) {
    Response::unauthorized();
}

$requester = Token::getPayload($request->payload()->token);

if ($requester['requester']!=='root') {
    Response::unauthorized();
}

if (!Installer::hasInstance()) {
    Response::abort();
}

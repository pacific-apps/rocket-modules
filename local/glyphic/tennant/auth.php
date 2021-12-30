<?php

declare(strict_types=1);

/**
 * Responsible for all glyphic tennant transactions
 */

$root = $_SERVER['DOCUMENT_ROOT'];
require $root.'/imports.php';

use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \glyphic\tools\MySQLQueryBuilder;
use \glyphic\tools\MySQLDatabase;

$request  = new Request;
$response = new Response;

ACCEPT::method('POST');
ACCEPT::payload(['id','public','private','grant_type=client_credentials']);

# Prepare Glhyphic config
require ROOT.'/data/glyphic/config.php';
$config = get_glyphic_config();

$query = [
    'tableName' => $config['tables_aka']['main_tennants'],
    'tennantId' => $request->payload()->id
];

$query = new MySQLQueryBuilder(
    'tennants/get/tennant',
    $query
);

$result = MySQLDatabase::get($query->build());
if (!$result['hasRecord']) {
    Response::unknown('Tennant not found');
}

if ($result['status']!=='ACTIVE') {
    Response::unauthorized('Account has been disabled');
}

if ($result['publicKey']!==$request->payload()->public) {
    Response::unauthorized('Invalid public or private key');
}

if ($result['privateKey']!==$request->payload()->private) {
    Response::unauthorized('Invalid public or private key');
}

$token = new Token();
$token->payload([
    'tid' => $request->payload()->id
]);
$token->create();

Response::transmit([
    'payload' => [
        'status'=>'200 OK',
        'message' => 'authenticated',
        'token' => $token->get()
    ]
]);

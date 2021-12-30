<?php

declare(strict_types=1);
# Creating a new Glyphic tennant

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \glyphic\tools\MySQLQueryBuilder;
use \glyphic\tools\MySQLDatabase;
use \glyphic\tools\IdGenerator;
use \glyphic\tools\DateManager;
use \glyphic\tools\TypeOf;

Accept::method('POST');
Accept::payload([
    'token'
]);

$request  = new Request;
$response = new Response;

$token = $request->payload()->token ?? null;
if (null==$token) {
    Response::abort(
        'Requires Token Payload'
    );
}

$jwt = new Token($token);

if (!$jwt->isValid()) {
    Response::unauthorized(
        'Token is invalid'
    );
}

$payload = $jwt->payload();
if (!isset($payload['requester'])&&$payload['requester']!=='root') {
    Response::unauthorized(
        'Token is invalid'
    );
}

# Prepare Glhyphic config
require ROOT.'/data/glyphic/config.php';
$config = get_glyphic_config();

# Primary Data Fields
$date                  = DateManager::now('Y-m-d H:i:s');
$tennant['tableName']  = $config['tables_aka']['main_tennants'];
$tennant['id']         = IdGenerator::create32bitKey(IdGenerator::BETANUMERIC);
$tennant['publicKey']  = IdGenerator::create32bitKey(IdGenerator::ALPHANUMERIC);
$tennant['privateKey'] = IdGenerator::create32bitKey(IdGenerator::ALPHANUMERIC);
$tennant['createdAt']  = $date;
$tennant['status']     = $config['default_status']['tennant'];
$tennant['podId']      = $config['current_pod_id'];
$tennant['domain']     = $config['instance_domain'];

$query = new MySQLQueryBuilder(
    'tennants/create/new_tennant',
    $tennant
);

$result = MySQLDatabase::save(
    query: $query->build()
);

Response::transmit([
    'code' => 200,
    'payload' => [
        'message' => 'Tennant has been created',
        'tennantId' => $tennant['id'],
        'publicKey' => $tennant['publicKey'],
        'privateKey' => $tennant['privateKey']
    ]
]);

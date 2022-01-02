<?php

declare(strict_types=1);
# Creating a new Glyphic tennant

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \core\exceptions\UnauthorizedAccessException;
use \core\exceptions\BadRequestException;
use \core\exceptions\AlreadyExistsException;
use \glyphic\RequireApiEndpoint;
use \glyphic\models\Tenant;

$request  = new Request;
$response = new Response;

try {

    RequireApiEndpoint::method('POST');
    RequireApiEndpoint::payload([
        'token'
    ]);

    $jwt = new Token($request->payload()->token);

    if (!$jwt->isValid()) {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    $payload = $jwt->payload();
    if (!isset($payload['requester'])&&$payload['requester']!=='root') {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    $tenant = new Tenant();
    $tenant->create();

    Response::transmit([
        'code' => 200,
        'payload' => [
            'message' => 'Tennant has been created',
            'tennantId' => $tenant->getTenantId(),
            'publicKey' => $tenant->getPublicKey(),
            'privateKey' => $tenant->getPrivateKey()
        ]
    ]);

} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),
            'exception' => 'RocketExceptionsInterface::'.$e->exception()
        ]);
        exit();
    }
    Response::transmit([
        'code' => 400,
        'exception' => $e->getMessage()
    ]);
}

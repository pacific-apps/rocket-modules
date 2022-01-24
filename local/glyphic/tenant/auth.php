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
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;
use \glyphic\TypeOf;
use \core\exceptions\RecordNotFoundException;
use \core\exceptions\UnauthorizedAccessException;
use \glyphic\RequireApiEndpoint;

$request  = new Request;
$response = new Response;

# Prepare Glhyphic config
require ROOT.'/data/glyphic/config.php';
$config = get_glyphic_config();

try {

    RequireApiEndpoint::method('POST');
    RequireApiEndpoint::payload([
        'id',
        'public',
        'private',
        'grant_type=client_credentials'
    ]);

    $query = new PDOQueryController(
        (new QueryBuilder('/tenants/get/tenant'))->build()
    );
    $query->param(
        ':publicKey',
        TypeOf::alphanum(
            'Public Key',
            $request->payload()->public
        )
    );
    $result = $query->get();

    if (!$result['hasRecord']) {
        throw new RecordNotFoundException('Tennant not found');
    }

    if ($result['status']!=='ACTIVE') {
        throw new UnauthorizedAccessException('Account has been disabled');
    }

    if ($result['publicKey']!==$request->payload()->public) {
        throw new UnauthorizedAccessException('Invalid public or private key');
    }

    if ($result['privateKey']!==$request->payload()->private) {
        throw new UnauthorizedAccessException('Invalid public or private key');
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

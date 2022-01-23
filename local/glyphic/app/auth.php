<?php

declare(strict_types=1);

/**
 * Responsible for all glyphic transactions
 * @method POST
 * @param public (string)
 * Glyphic's Public key - stored on an ENV
 * @param secret (string)
 * Glyphic's Secret key - stored on an ENV
 * @param grant_type (string)
 * Must be client_credentials
 */

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';

use \core\http\Request;
use \core\http\Response;
use \core\exceptions\UnauthorizedAccessException;
use \glyphic\RequireApiEndpoint;
use \glyphic\TypeOf;
use \glyphic\models\Glyphic;
use \jwt\Token;

$request  = new Request;
$response = new Response;

try {

    RequireApiEndpoint::header();
    RequireApiEndpoint::method('POST');
    RequireApiEndpoint::payload([
        'public',
        'secret',
        'grant_type=client_credentials'
    ]);

    $glyphic = new Glyphic;

    if(!$glyphic->verifyPublicKey(
        TypeOf::alphanum(
            'Public Key',
            $request->payload()->public
        )
    )) {
        throw new UnauthorizedAccessException(
            'Invalid Public Key provided'
        );
    }

    if (!$glyphic->verifyPrivateKey(
        TypeOf::alphanum(
            'Private Key',
            $request->payload()->secret
        )
    )) {
        throw new UnauthorizedAccessException(
            'Invalid Secret Key provided'
        );
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
            'token' => $token->get(),
            'exp' => '7min'
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
        'exception' => 'Unhandled Exception'
    ]);
}

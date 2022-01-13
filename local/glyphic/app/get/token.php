<?php

declare(strict_types=1);

/**
 * Refreshes token
 * @method GET
 * @param string token
 *
 * @return JSON with JWT Token
 *
 * @since v1.0
 */

 require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
 use \core\http\Request;
 use \core\http\Response;
 use \core\exceptions\UnauthorizedAccessException;
 use \core\exceptions\BadRequestException;
 use \core\exceptions\AlreadyExistsException;
 use \core\exceptions\RecordNotFoundException;
 use \core\exceptions\ResourceAccessForbiddenException;
 use \glyphic\RequireApiEndpoint;
 use \glyphic\models\Tenant;
 use \glyphic\TypeOf;
 use \glyphic\PDOQueryController;
 use \glyphic\QueryBuilder;
 use \glyphic\TimeStamp;
 use \jwt\Token;

try {

    $request = new Request;
    RequireApiEndpoint::header();
    RequireApiEndpoint::method('GET');
    RequireApiEndpoint::query([
        'token'
    ]);

    $jwt = new Token($request->query()->token);

    if (!$jwt->isValid()) {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    $payload = $jwt->payload();

    if (!isset($payload['requester'])||$payload['requester']!=='root') {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    $token = new Token();
    $token->payload([
        'requester'=>'root'
    ]);
    $token->create();

    Response::transmit([
        'code' => 200,
        'payload' => [
            'token' => $token->create()
        ]
    ]);


} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),
            'exception' => 'RocketExceptionsInterface::'.$e->exception(),
            //'exception'=>$e->getMessage()
        ]);
        exit();
    }
    Response::transmit([
        'code' => 400,
        'exception' => $e->getMessage()
    ]);
}

<?php

declare(strict_types=1);
# Creating a new Glyphic instance

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \core\exceptions\UnauthorizedAccessException;
use \core\exceptions\BadRequestException;
use \core\exceptions\AlreadyExistsException;
use \jwt\Token;
use \glyphic\RequireApiEndpoint;
use \glyphic\TypeOf;
use \glyphic\models\Table;
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;

$request = new Request;
$response = new Response;

try {

    RequireApiEndpoint::header();
    RequireApiEndpoint::method('GET');
    RequireApiEndpoint::query([
        'type',
        'token',
        'page'
    ]);

    $type = TypeOf::alpha(
        'Search type',
        $request->query()->type
    );
    $jwt = new Token($request->query()->token);

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

    if ($type==='all') {

        $query = new PDOQueryController(
            (new QueryBuilder('/users/get/all.app.users'))->build()
        );

        $users = $query->getAll();
        $result['users'] = $users;

        Response::transmit([
            'payload' => [
                'status'=>'200 OK',
                'users' => $users
            ]
        ]);


    }

    # Finally, responding 200 OK
    Response::transmit([200]);


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

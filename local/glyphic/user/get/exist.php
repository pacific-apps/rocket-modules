<?php

declare(strict_types=1);

/**
 * Checks if the user exists
 * @method GET
 * NOTE: Accepts both username or email
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

    RequireApiEndpoint::method('GET');
    RequireApiEndpoint::query([
        'uemail',
        'idtype',
        'publickey'
    ]);

    $tenant = new Tenant(
        TypeOf::alphanum(
            'Public Key',
            $request->query()->publickey ?? null
        )
    );

    if ($tenant->getStatus()!=='ACTIVE') {
        throw new ResourceAccessForbiddenException(
            'Tenant is not active'
        );
    }

    # Checking if the given identifications is an email or a username
    $idType = TypeOf::alpha(
        'Id Type',
        $request->query()->idtype ?? null
    );

    if ($idType!=='email'&&$idType!=='username') {
        throw new BadRequestException(
            'Id type must be either email or username'
        );
    }

    if ($idType==='email') {
        $uemail = TypeOf::email(
            'User email',
            $request->query()->uemail ?? null
        );
    }

    if ($idType==='username') {
        $uemail = TypeOf::alphanum(
            'Username',
            $request->query()->uemail ?? null
        );
    }

    $query = new PDOQueryController(
        (new QueryBuilder('users/get/user'))->build()
    );
    $query->prepare([
        ':email' => $uemail,
        ':username' => $uemail,
        ':publicKey' => $request->query()->publickey
    ]);

    $user = $query->get();

    if (!$user['hasRecord']) {
        throw new RecordNotFoundException(
            'User not found'
        );
    }

    if ($user['status']!=='ACTIVE') {
        throw new ResourceAccessForbiddenException(
            'User is not active'
        );
    }

    Response::transmit([
        'code' => 200,
        'payload' => [
            'message' => 'User found'
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

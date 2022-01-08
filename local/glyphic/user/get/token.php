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

    RequireApiEndpoint::method('GET');
    RequireApiEndpoint::query([
        'token'
    ]);

    $jwt = new Token($request->query()->token);

    if (!$jwt->isValid()) {
        throw new UnauthorizedAccessException (
            'Token is either invalid or expired'
        );
    }

    $requester = $jwt->payload();


    $tenant = new Tenant(
        TypeOf::alphanum(
            'Public Key',
            $requester['publicKey'] ?? null
        )
    );

    if ($tenant->getStatus()!=='ACTIVE') {
        throw new ResourceAccessForbiddenException(
            'Tenant is not active'
        );
    }

    $query = new PDOQueryController(
        (new QueryBuilder('users/get/user.by.id'))->build()
    );
    $query->prepare([
        ':userId' => TypeOf::alphanum(
            'User Id',
            $requester['userId'] ?? null
        ),
        ':publicKey' => $requester['publicKey']
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

    $token = new Token();
    $token->payload([
        'userId' => $user['userId'],
        'permissions' => $user['permissions'],
        'publicKey' => $requester['publicKey']
    ]);

    Response::transmit([
        'code' => 200,
        'payload' => [
            'message' => 'User authenticated',
            'token' => $token->create(),
            'exp' => '7min',
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'profilePhoto' => $user['profilePhoto']
        ]
    ]);

    /*

    # Checking if the given identifications is an email or a username
    $idType = TypeOf::alpha(
        'Id Type',
        $request->payload()->idtype ?? null
    );

    if ($idType!=='email'&&$idType!=='username') {
        throw new BadRequestException(
            'Id type must be either email or username'
        );
    }

    if ($idType==='email') {
        $uemail = TypeOf::email(
            'User email',
            $request->payload()->uemail ?? null
        );
    }

    if ($idType==='username') {
        $uemail = TypeOf::alphanum(
            'Username',
            $request->payload()->uemail ?? null
        );
    }

    $query = new PDOQueryController(
        (new QueryBuilder('users/get/user'))->build()
    );
    $query->prepare([
        ':email' => $uemail,
        ':username' => $uemail,
        ':publicKey' => $request->payload()->publickey
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

    if (!password_verify(
        $request->payload()->password,
        $user['password']
        )
    ) {
        throw new UnauthorizedAccessException (
            'Password is incorrect'
        );
    }

    $token = new Token();
    $token->payload([
        'userId' => $user['userId'],
        'permissions' => $user['permissions'],
        'publicKey' => $request->payload()->publickey
    ]);

    Response::transmit([
        'code' => 200,
        'payload' => [
            'message' => 'User authenticated',
            'token' => $token->create(),
            'exp' => '7min',
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'profilePhoto' => $user['profilePhoto']
        ]
    ]);

    */


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

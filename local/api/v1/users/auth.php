<?php

/**
 * API Description Here
 *
 */

declare(strict_types=1);

# Error displaying, has to be removed on production
ini_set('error_reporting','E_ALL');
ini_set( 'display_errors','1');
error_reporting(E_ALL ^ E_STRICT);

# Common libraries
use \core\http\Request;
use \core\http\Response;
use \core\exceptions\UnauthorizedAccessException;
use \core\exceptions\BadRequestException;
use \core\exceptions\AlreadyExistsException;
use \core\exceptions\ConfigurationErrorException;
use \core\exceptions\RecordNotFoundException;
use \core\exceptions\ResourceAccessForbiddenException;
use \jwt\Token;
use \glyphic\RequireApiEndpoint;
use \glyphic\PDOQueryController;
use \glyphic\PDOTransaction;
use \glyphic\QueryBuilder;
use \glyphic\TimeStamp;
use \glyphic\TypeOf;
use \glyphic\UniqueId;

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
$request = new Request;
$response = new Response;

try {

    # Declare all your database queries here
    $queries = [
        "get user details" => "
            SELECT u.userId, u.password, u.status, u.role, u.permissions, p.firstName, p.lastName, p.profilePhoto
            FROM m_glyf_user u
            LEFT JOIN s_glyf_profile p ON u.userId = p.userId
            WHERE u.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND u.email = :email
            OR u.username = :username
            AND u.recordType = 'tenant.user'
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('POST');

    # Require API payload
    RequireApiEndpoint::payload([
        'publicKey',
        'password'
    ]);


    $query = new PDOQueryController(
        $queries['get user details']
    );
    $query->prepare([
        ':publicKey' => TypeOf::alphanum(
            'Public Key',
            $request->payload()->publicKey
        ),
        ':email' => TypeOf::email(
            'User email address',
            $request->payload()->email ?? 'invalid@invalid.com'
        ),
        ':username' => TypeOf::alphanum(
            "Username",
            $request->payload()->username ?? 'invalid'
        )
    ]);

    $user = $query->get();

    if (!$user['doExist']) {
        throw new UnauthorizedAccessException(
            'Invalid Credentials'
        );
    }

    if ($user['status']==='ACTIVE') {

        # Verify password
        if (!password_verify(
            $request->payload()->password,
            $user['password']
        )) {
            throw new UnauthorizedAccessException(
                'Invalid Credentials'
            );
        }

        $token = new Token();
        $token->payload([
            'userId' => $user['userId'],
            'publicKey' => $request->payload()->publicKey,
            'for' => 'general',
            'role' => $user['role'],
            'permissions' => $user['permissions'],
            'status' => $user['status']
        ]);
        $token->create();

        Response::transmit([
            'payload' => [
                'status'=>'200 OK',
                'message' => 'Authenticated',
                'token' => $token->get(),
                'exp' => '7min'
            ]
        ]);

        exit();

    }

    throw new UnauthorizedAccessException(
        'Unknown user status'
    );


} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),

            # Provides only generic error message
            'exception' => 'RocketExceptionsInterface::'.$e->exception(),

            # Allows you to see the exact error message passed on the throw statement
            //'exception'=>$e->getMessage()
        ]);
        exit();
    }
    Response::transmit([
        'code' => 400,
        'exception' => 'Unhandled Exception'

        # Allows you to see the exact error message passed on the throw statement
        //'exception'=>$e->getMessage()
    ]);
}

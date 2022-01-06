<?php

declare(strict_types=1);

/**
 * Handles user creation
 * @param string publickey - The tenant's public key
 * @param array userde - new user profile details
 */


require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \core\exceptions\UnauthorizedAccessException;
use \core\exceptions\BadRequestException;
use \core\exceptions\AlreadyExistsException;
use \core\exceptions\ResourceAccessForbiddenException;
use \glyphic\RequireApiEndpoint;
use \glyphic\models\User;
use \glyphic\models\Tenant;
use \glyphic\TypeOf;
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;


$request = new Request;
$response = new Response;

try {

    RequireApiEndpoint::method('POST');
    RequireApiEndpoint::payload([
        'publickey',
        'userdetails'
    ]);

    $requester = [
        'permissions' => 'WEB'
    ];

    $tenant = new Tenant(
        TypeOf::alphanum(
            'Public Key',
            $request->payload()->publickey ?? null
        )
    );


    if ($requester['permissions']==='WEB'&&$tenant->getStatus()!=='ACTIVE') {
        throw new ResourceAccessForbiddenException(
            'Tenant is not active'
        );
    }

    if ($tenant->getStatus()!=='ACTIVE') {

        /**
         * If the tenant is not active, the flow would then
         * require a token, and requester who can allow the creation
         * would be the following:
         * SUPERUSER, ADMIN, TENANTADMIN
         * and/or special tenant staff permission
         */

         

    }

    $query = new PDOQueryController(
        (new QueryBuilder('/users/get/user'))->build()
    );
    $query->prepare([
        ':publicKey' => $request->payload()->publickey,
        ':email' => TypeOf::email(
            'User Email',
            $request->payload()->userdetails->email ?? 'unknown'
        ),
        ':username' => TypeOf::alphanum(
            'Username',
            $request->payload()->userdetails->username ?? 'unknown'
        )
    ]);
    $user = $query->get();

    if ($user['doExist']) {
        throw new AlreadyExistsException(
            'User already exists'
        );
    }





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


/**
 * 1. Defining Pre-requisites for this endpoint
 * This endpoint only accepts POST request
 * and requires token and profile payload
 */

/*
Accept::method('POST');
Accept::payload(['publicKey','user']);

$password = $request->payload()->profile->password ?? null;
if (null===$password) {
    Response::abort('Requires password');
}

# Get Tennant Id
$tennant = new PDOQuery('tennats/get/tennant.id');
#$tennant->prepare();
$tennantId = $jwt->payload()['tid'];

try {

    $newUserId       = IDGenerator::create32BitKey(IDGenerator::BETANUMERIC);
    $dateNow         = DateManager::now('Y-m-d H:i:s');
    $verificationKey = IDGenerator::create64BitKey(IDGenerator::ALPHANUMERIC);

    $main = new PDOQuery('users/create/user');
    $main->prepare([
        ':userId' => $newUserId,
        ':username' => TypeOf::alphanum(
            'username',
            $request->payload()->profile->username ?? null
        ),
        ':email' => TypeOf::email(
            'email',
            $request->payload()->profile->email ?? null
        ),
        ':password' => password_hash($password,PASSWORD_DEFAULT),
        ':createdAt' => $dateNow,
        ':activatedAt' => null,
        ':role' => 'user',
        ':permissions' => 'WEB',
        ':tennantId' => $tennantId,
        ':status' => 'NEW'
    ]);

    $profile = new PDOQuery('users/create/profile');
    $profile->prepare([
        ':userId' => $newUserId,
        ':firstName' => TypeOf::alpha(
            'First name',
            $request->payload()->profile->firstname ?? null
        ),
        ':lastName' => TypeOf::alpha(
            'Last name',
            $request->payload()->profile->lastname ?? null
        ),
        ':tennantId' => $tennantId,
        ':recordType' => 'user'
    ]);

    $activation = new PDOQuery('users/create/activation');
    $activation->prepare([
        ':userId' => $newUserId,
        ':createdAt' => $dateNow,
        ':verfKey' => $verificationKey,
        ':createdBy' => 'users/create',
        ':createdFor' => 'user_activation',
        ':toExpireAt' => DateManager::add($dateNow, "2 days"),
        ':tennantId' => $tennantId,
        ':recordType' => 'user'
    ]);

    $main->post();
    $profile->post();
    $activation->post();

} catch (\PDOException $e) {
    Response::error();
    exit();
}

Response::transmit([
    'code' => 200,
    'payload' => [
        'message' => 'User has been created',
        'userId' => $newUserId,
        'verificationKey' => $verificationKey
    ]
]);

*/

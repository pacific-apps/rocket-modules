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
use \glyphic\UniqueId;
use \glyphic\TimeStamp;


$request = new Request;
$response = new Response;

try {

    RequireApiEndpoint::method('POST');
    RequireApiEndpoint::payload([
        'publickey',
        'userdetails'
    ]);

    if (!isset($request->payload()->userdetails->password)) {
        throw new BadRequestException (
            'Requires password payload'
        );
    }

    $requester = [
        'permissions' => 'WEB'
    ];

    $tenant = new Tenant(
        TypeOf::alphanum(
            'Public Key',
            $request->payload()->publickey ?? null
        )
    );

    if ($tenant->getStatus()!=='ACTIVE') {

        /**
         * If the tenant is not active, the flow would then
         * require a token, and requester who can allow the creation
         * would be the following:
         * SUPERUSER, ADMIN, TENANTADMIN
         * and/or special tenant staff permission
         */

         if ($requester['permissions']==='WEB') {
             throw new ResourceAccessForbiddenException(
                 'Tenant is not active'
             );
         }

         if (!isset($request->payload()->token)) {
             throw new ResourceAccessForbiddenException(
                 'Requires token'
             );
         }

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

    $newUserId       = UniqueId::create32BitKey(UniqueId::BETANUMERIC);
    $dateNow         = TimeStamp::now();
    $verificationKey = UniqueId::create64BitKey(UniqueId::ALPHANUMERIC);

    $main = new PDOQueryController(
        (new QueryBuilder('users/create/user'))->build()
    );

    $main->prepare([
        ':userId' => $newUserId,
        ':username' => $request->payload()->userdetails->username,
        ':email' => TypeOf::email(
            'email',
            $request->payload()->userdetails->email ?? null
        ),
        ':password' => password_hash(
            $request->payload()->userdetails->password,
            PASSWORD_DEFAULT
        ),
        ':createdAt' => $dateNow,
        ':activatedAt' => null,
        ':role' => 'user',
        ':permissions' => 'WEB',
        ':tenantId' => $tenant->getTenantId(),
        ':status' => 'NEW'
    ]);

    $profile = new PDOQueryController(
        (new QueryBuilder('users/create/profile'))->build()
    );
    $profile->prepare([
        ':userId' => $newUserId,
        ':firstName' => TypeOf::alpha(
            'First name',
            $request->payload()->userdetails->firstname ?? null
        ),
        ':lastName' => TypeOf::alpha(
            'Last name',
            $request->payload()->userdetails->lastname ?? null
        ),
        ':tenantId' => $tenant->getTenantId(),
        ':recordType' => 'user'
    ]);

    $activation = new PDOQueryController(
        (new QueryBuilder('users/create/activation'))->build()
    );
    $activation->prepare([
        ':userId' => $newUserId,
        ':createdAt' => $dateNow,
        ':verfKey' => $verificationKey,
        ':createdBy' => 'users/create',
        ':createdFor' => 'user_activation',
        ':toExpireAt' => TimeStamp::add($dateNow, "2 days"),
        ':tenantId' => $tenant->getTenantId(),
        ':recordType' => 'user'
    ]);

    $contacts = new PDOQueryController(
        (new QueryBuilder('users/create/contact'))->build()
    );
    $contacts->prepare([
        ':userId' => $newUserId,
        ':tenantId' => $tenant->getTenantId(),
        ':recordType' => 'user'
    ]);

    $address = new PDOQueryController(
        (new QueryBuilder('users/create/address'))->build()
    );
    $address->prepare([
        ':userId' => $newUserId,
        ':tenantId' => $tenant->getTenantId(),
        ':recordType' => 'user'
    ]);

    $main->post();
    $profile->post();
    $activation->post();
    $contacts->post();
    $address->post();

    Response::transmit([
        'code' => 200,
        'payload' => [
            'message' => 'User has been created',
            'userId' => $newUserId,
            'verificationKey' => $verificationKey
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

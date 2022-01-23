<?php

declare(strict_types=1);
# Creating a new Glyphic tenant

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;
use \glyphic\UniqueId;
use \glyphic\TimeStamp;
use \core\exceptions\UnauthorizedAccessException;
use \core\exceptions\BadRequestException;
use \core\exceptions\AlreadyExistsException;
use \glyphic\RequireApiEndpoint;
use \glyphic\models\Tenant;

$request  = new Request;
$response = new Response;

try {

    RequireApiEndpoint::header();
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

    $tenant = [
        'tenantId' => UniqueId::create32bitKey(UniqueId::BETANUMERIC),
        'publicKey' => UniqueId::create32bitKey(UniqueId::ALPHANUMERIC),
        'privateKey' => UniqueId::create32bitKey(UniqueId::ALPHANUMERIC)
    ];

    $createTenantQuery = new PDOQueryController(
        (new QueryBuilder('/tenants/create/tenant'))->build()
    );
    $createTenantQuery->prepare([
        ':tenantId' => $tenant['tenantId'],
        ':publicKey' => $tenant['publicKey'],
        ':privateKey' => $tenant['privateKey'],
        ':createdAt' => TimeStamp::now(),
        ':podId' => 1,
        ':domain' => 'localhost',
        ':status' => 'ACTIVE'
    ]);


    $newUserId = UniqueId::create32BitKey(UniqueId::BETANUMERIC);
    $dateNow   = TimeStamp::now();
    $main = new PDOQueryController(
        (new QueryBuilder('users/create/user'))->build()
    );

    $main->prepare([
        ':userId' => $newUserId,
        ':username' => $tenant['publicKey'],
        ':email' => $tenant['publicKey'].'@glyphic.com',
        ':password' => password_hash(
            'admin@glyphic',
            PASSWORD_DEFAULT
        ),
        ':createdAt' => $dateNow,
        ':activatedAt' => $dateNow,
        ':role' => 'tenantadmin',
        ':permissions' => 'WEB,TENANTADMIN',
        ':tenantId' => $tenant['tenantId'],
        ':status' => 'ACTIVE'
    ]);

    $profile = new PDOQueryController(
        (new QueryBuilder('users/create/profile'))->build()
    );
    $profile->prepare([
        ':userId' => $newUserId,
        ':firstName' => 'New',
        ':lastName' => 'Tenant',
        ':tenantId' => $tenant['tenantId'],
        ':recordType' => 'tenant.admin'
    ]);

    $contacts = new PDOQueryController(
        (new QueryBuilder('users/create/contact'))->build()
    );
    $contacts->prepare([
        ':userId' => $newUserId,
        ':tenantId' => $tenant['tenantId'],
        ':recordType' => 'tenant.admin'
    ]);

    $address = new PDOQueryController(
        (new QueryBuilder('users/create/address'))->build()
    );
    $address->prepare([
        ':userId' => $newUserId,
        ':tenantId' => $tenant['tenantId'],
        ':recordType' => 'tenant.admin'
    ]);


    $main->post();
    $profile->post();
    $contacts->post();
    $address->post();
    $createTenantQuery->post();

    Response::transmit([
        'code' => 200,
        'payload' => [
            'message' => 'Tennant has been created',
            'tenantId' => $tenant['tenantId'],
            'publicKey' => $tenant['publicKey'],
            'privateKey' => $tenant['privateKey']
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

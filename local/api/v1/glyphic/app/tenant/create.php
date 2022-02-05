<?php

/**
 * API Description Here
 *
 */

declare(strict_types=1);

# Common libraries
use \core\http\Request;
use \core\http\Response;
use \core\exceptions\UnauthorizedAccessException;
use \core\exceptions\BadRequestException;
use \core\exceptions\AlreadyExistsException;
use \core\exceptions\ConfigurationErrorException;
use \core\exceptions\RecordNotFoundException;
use \core\exceptions\ResourceAccessForbidden;
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
        "create tenant in the main tenant table" => "
            INSERT INTO m_glyf_tnt (tenantId, publicKey, privateKey, createdAt, podId, domain, status)
            VALUES (:tenantId, :publicKey, :privateKey, :createdAt, :podId, :domain, :status)
        ",
        "create tenant admin user" => "
            INSERT INTO m_glyf_user (userId, username, email, password, createdAt, activatedAt, role, permissions, tenantId, recordType, status)
            VALUES (:userId, :username, :email, :password, :createdAt, :activatedAt, :role, :permissions, :tenantId, :recordType, :status)
        ",
        "create tenant admin profile" => "
            INSERT INTO s_glyf_profile (userId, firstName, lastName, tenantId, recordType)
            VALUES (:userId, :firstName, :lastName, :tenantId, :recordType)
        ",
        "create tenant admin address" => "
            INSERT INTO s_glyf_user_adrs (userId, addressType, tenantId, recordType)
            VALUES (:userId, :addressType, :tenantId, :recordType)
        ",
        "create tenant admin contacts" => "
            INSERT INTO s_glyf_user_cnt (userId, contactType, tenantId, recordType)
            VALUES (:userId, :contactType, :tenantId, :recordType)
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('POST');

    # Require API payload
    RequireApiEndpoint::payload([
        'token'
    ]);

    # Verifying JWT
    $jwt = new Token($request->payload()->token);

    if (!$jwt->isValid()) {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    $payload = $jwt->payload();

    if (!isset($payload['requester'])) {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    if ($payload['requester']!=='root') {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    $tenant = [
        'tenantId' => UniqueId::create32bitKey(UniqueId::BETANUMERIC),
        'publicKey' => UniqueId::create32bitKey(UniqueId::ALPHANUMERIC),
        'privateKey' => UniqueId::create32bitKey(UniqueId::ALPHANUMERIC)
    ];

    try {

        $transactions = new PDOTransaction();
        $transactions->query($queries['create tenant in the main tenant table']);
        $transactions->prepare([
            ':tenantId' => $tenant['tenantId'],
            ':publicKey' => $tenant['publicKey'],
            ':privateKey' => $tenant['privateKey'],
            ':createdAt' => TimeStamp::now(),
            ':podId' => 1,
            ':domain' => 'localhost',
            ':status' => 'ACTIVE'
        ]);
        $transactions->post();

        /**
         * Creating a new tenant admin user
         * Every new tenant is created, it automatically creates
         * an admin user for the tenant as well
         * NOTE: It gives default values for username, email, and other
         * tenant profile data
         */
        $newUserId = UniqueId::create32BitKey(UniqueId::BETANUMERIC);
        $dateNow   = TimeStamp::now();

        $transactions->query($queries['create tenant admin user']);
        $transactions->prepare([
            ':userId' => $newUserId,
            ':username' => $tenant['publicKey'],
            ':email' => $tenant['publicKey'].'@glyphic.com',
            ':password' => password_hash(
                'admin@glyphic',
                PASSWORD_DEFAULT
            ),
            ':createdAt' => $dateNow,
            ':activatedAt' => $dateNow,
            ':role' => 'tenant admin',
            ':permissions' => 'WEB,TENANTADMIN',
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.admin',
            ':status' => 'ACTIVE'
        ]);
        $transactions->post();

        /**
         * Creating tenant admin's profile
         * Default full name when created is "New Tenant"
         */
        $transactions->query($queries['create tenant admin profile']);
        $transactions->prepare([
            ':userId' => $newUserId,
            ':firstName' => 'New',
            ':lastName' => 'Tenant',
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.admin'
        ]);
        $transactions->post();

        /**
         * Creating tenant admin's primary address entry
         */
        $transactions->query($queries['create tenant admin address']);
        $transactions->prepare([
            ':userId' => $newUserId,
            ':addressType' => 'primary',
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.admin'
        ]);
        $transactions->post();

        /**
         * Creating tenant admin's contacts entry
         */
        $transactions->query($queries['create tenant admin contacts']);
        $transactions->prepare([
            ':userId' => $newUserId,
            ':contactType' => 'primary',
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.admin'
        ]);
        $transactions->post();

        /**
         * Committing transaction
         */
        $transactions->commit();

    } catch (\PDOException $e) {

        $transactions->rollBack();

        Response::transmit([
            'code' => 500,
            'exception' => 'Exception::'.$e->getMessage()
        ]);

    }

    Response::transmit([
        'payload' => [
            'status'=>'201',
            'message' => 'Tenant has been created',
            'tenantId' => $tenant['tenantId'],
            'publicKey' => $tenant['publicKey'],
            'privateKey' => $tenant['privateKey']
        ]
    ]);

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
    ]);
}

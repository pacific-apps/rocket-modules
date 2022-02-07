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
        "do user exists" => "
            SELECT u.userId
            FROM m_glyf_user u
            WHERE u.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND u.email = :email
            OR u.username = :username
        ",
        "get tenant status" => "
            SELECT tenantId, status
            FROM m_glyf_tnt
            WHERE publicKey = :publicKey
        ",
        "create user in the main user table" => "
            INSERT INTO m_glyf_user (userId, username, email, password, createdAt, activatedAt, role, permissions, tenantId, recordType, status)
            VALUES (:userId, :username, :email, :password, :createdAt, :activatedAt, :role, :permissions, :tenantId, :recordType, :status)
        ",
        "create user record in the profile table" => "
            INSERT INTO s_glyf_profile (userId, firstName, lastName, tenantId, recordType)
            VALUES (:userId, :firstName, :lastName, :tenantId, :recordType)
        ",
        "create user records in the address table" => "
            INSERT INTO s_glyf_user_adrs (userId, addressType, tenantId, recordType)
            VALUES (:userId, :addressType, :tenantId, :recordType)
        ",
        "create user records in the contacts table" => "
            INSERT INTO s_glyf_user_cnt (userId, contactType, tenantId, recordType)
            VALUES (:userId, :contactType, :tenantId, :recordType)
        ",
        "create user activation key" => "
            INSERT INTO m_glyf_verf_keys (userId, verfKey, createdAt, createdBy, createdFor, toExpireAt, tenantId, recordType)
            VALUES (:userId, :verfKey, :createdAt, :createdBy, :createdFor, :toExpireAt, :tenantId, :recordType)
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('POST');

    # Require API payload
    RequireApiEndpoint::payload([
        'publicKey',
        'firstName',
        'lastName',
        'email',
        'username',
        'password'
    ]);

    $query = new PDOQueryController(
        $queries['do user exists']
    );
    $query->prepare([
        ':publicKey' => TypeOf::alphanum(
            'Public Key',
            $request->payload()->publicKey
        ),
        ':email' => trim(TypeOf::email(
            'User Email',
            $request->payload()->email ?? 'unknown')
        ),
        ':username' => trim(TypeOf::alphanum(
            'Username',
            $request->payload()->username ?? 'unknown')
        )
    ]);
    $user = $query->get();

    if ($user['doExist']) {
        throw new AlreadyExistsException(
            'User already exists with the given username and/or email'
        );
    }

    /**
     * Checking if tenant exists under the public key
     * or is tenant is active
     */
    $query = new PDOQueryController(
        $queries['get tenant status']
    );
    $query->prepare([
        ':publicKey' => $request->payload()->publicKey
    ]);
    $tenant = $query->get();

    if (!$tenant['doExist']) {
        throw new RecordNotFoundException(
            'Tenant not found'
        );
    }

    if ($tenant['status']!=='ACTIVE') {
        throw new ResourceAccessForbiddenException(
            'tenant is not active'
        );
    }

    $user = [
        'userId' => UniqueId::create32bitKey(UniqueId::BETANUMERIC),
        'dateNow'=> TimeStamp::now(),
        'activationKey' => UniqueId::create64bitKey(UniqueId::BETANUMERIC)
    ];


    try {

        $transactions = new PDOTransaction();

        /**
         * Creating user record in the main users table
         * The main users table contains the lists of all the users
         * of the application, regardless of which
         * tenant it belongs to
         */
        $transactions->query($queries['create user in the main user table']);
        $transactions->prepare([
            ':userId' => $user['userId'],
            ':username' => trim(TypeOf::alphanum(
                'Username',
                $request->payload()->username)
            ),
            ':email' => trim(TypeOf::email(
                'User email address',
                $request->payload()->email)
            ),
            ':password' => password_hash(
                trim($request->payload()->password),
                PASSWORD_DEFAULT
            ),
            ':createdAt' => $user['dateNow'],
            ':activatedAt' => '',
            ':role' => 'tenant user',
            ':permissions' => 'WEB',
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.user',
            ':status' => 'NEW'
        ]);
        $transactions->post();


        /**
         * Creating user's profile
         */
        $transactions->query($queries['create user record in the profile table']);
        $transactions->prepare([
            ':userId' => $user['userId'],
            ':firstName' => trim(TypeOf::fullname(
                'First Name',
                $request->payload()->firstName)
            ),
            ':lastName' => trim(TypeOf::fullname(
                'Last Name',
                $request->payload()->lastName)
            ),
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.user'
        ]);
        $transactions->post();


        /**
         * Creating user's primary address entry
         */
        $transactions->query($queries['create user records in the address table']);
        $transactions->prepare([
            ':userId' => $user['userId'],
            ':addressType' => 'primary',
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.user'
        ]);
        $transactions->post();


        /**
         * Creating user contacts entry
         */
        $transactions->query($queries['create user records in the contacts table']);
        $transactions->prepare([
            ':userId' => $user['userId'],
            ':contactType' => 'primary',
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.user'
        ]);
        $transactions->post();

        /**
         * Creating user activation key
         */
        $transactions->query($queries['create user activation key']);
        $transactions->prepare([
            ':userId' => $user['userId'],
            ':createdAt' => $user['dateNow'],
            ':verfKey' => $user['activationKey'],
            ':createdBy' => 'users/register',
            ':createdFor' => 'user_activation',
            ':toExpireAt' => TimeStamp::add($user['dateNow'], "2 days"),
            ':tenantId' => $tenant['tenantId'],
            ':recordType' => 'tenant.user'
        ]);
        $transactions->post();

        $transactions->commit();

    } catch (\PDOException $e) {

        $transactions->rollBack();

        Response::transmit([
            'code' => 500,
            'exception' => 'Exception::'.$e->getMessage()
        ]);

    }



    Response::transmit([
        'code' => 201,
        'payload' => [
            'status'=>'201',
            'message' => 'New user created',
            'userId' => $user['userId'],
            'activationKey' => $user['activationKey']
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
        'exception' => $e->getMessage()
    ]);
}

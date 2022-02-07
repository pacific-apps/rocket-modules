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
        "get all verification details" => "
            SELECT v.toExpireAt as toExpireAt, a.toExpireAt as holdUntil, u.email as userEmail, u.status as userStatus
            FROM m_glyf_verf_keys v
            LEFT OUTER JOIN s_glyf_user_actrk a ON v.userId = a.userId
                AND a.actionType = 'user_email_update'
                AND a.recordType = 'tenant.user'
            LEFT OUTER JOIN m_glyf_user u ON v.userId = u.userId
                AND u.recordType = 'tenant.user'
            WHERE v.userId = :userId
            AND v.verfKey = :verfKey
            AND v.createdBy = 'users/email/request'
            AND v.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            ORDER BY a.createdAt DESC
        ",
        "do user exists" => "
            SELECT u.userId
            FROM m_glyf_user u
            WHERE u.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND u.email = :email
        ",
        "delete verification key" => "
            DELETE FROM m_glyf_verf_keys
            WHERE verfKey = :verfKey
            AND tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND userId = :userId
            AND createdFor = 'user_email_update '
            AND createdBy = 'users/email/request'
            AND recordType = 'tenant.user'
        ",
        "register user action" => "
            INSERT INTO s_glyf_user_actrk (userId, actionType, createdAt, toExpireAt, tenantId, recordType)
            VALUES (:userId, :actionType, :createdAt, :toExpireAt, (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey), :recordType)
        ",
        "update user email" => "
            UPDATE m_glyf_user
            SET email = :email
            WHERE userId = :userId
            AND tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND recordType = 'tenant.user'
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('POST');

    # Require API payload
    RequireApiEndpoint::payload([
        'token',
        'verfKey',
        'newEmailAddress'
    ]);


    $jwt = new Token($request->payload()->token);

    if (!$jwt->isValid()) {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    $payload = $jwt->payload();
    $requester = [];

    # Getting the requester User Id
    $requester['userId'] = TypeOf::alphanum(
        'User Id',
        $payload['userId'] ?? null
    );

    # Getting the requester Public Key
    $requester['publicKey'] = TypeOf::alphanum(
        'Public key',
        $payload['publicKey'] ?? null
    );

    # Making sure that the user is on active status
    if ('ACTIVE'!==TypeOf::alpha(
        'User status',
        $payload['status'] ?? null
    )) {
        throw new UnauthorizedAccessException(
            'Requester status is not active'
        );
    }

    $query = new PDOQueryController(
        $queries['get all verification details']
    );

    $query->prepare([
        ':publicKey' => TypeOf::alphanum(
            'Public Key',
            $requester['publicKey']
        ),
        ':userId' => TypeOf::alphanum(
            'User Id',
            $requester['userId']
        ),
        ':verfKey' => TypeOf::alphanum(
            'Verification Key',
            $request->payload()->verfKey
        )
    ]);

    $verification = $query->getAll();

    if (empty($verification)) {
        throw new RecordNotFoundException(
            'Verification Key not found or invalid'
        );
    }

    if ($verification[0]['toExpireAt'] < TimeStamp::now()) {
        throw new ResourceAccessForbiddenException(
            'Verification Key has expired'
        );
    }

    if (null!==$verification[0]['holdUntil']) {
        if ($verification[0]['holdUntil']>TimeStamp::now()) {
            throw new ResourceAccessForbiddenException(
                'Unable to update email at the moment, email has been updated recently'
            );
        }
    }

    if ($verification[0]['userStatus']!=='ACTIVE') {
        throw new UnauthorizedAccessException(
            'User is currently not active'
        );
    }

    if (trim($request->payload()->newEmailAddress)=='') {
        throw new BadRequestException(
            'New email address is invalid'
        );
    }

    if ($verification[0]['userEmail']===TypeOf::email(
        'New email address',
        $request->payload()->newEmailAddress
    )) {
        throw new BadRequestException(
            'New email address cannot be the same with the current one'
        );
    }


    /**
     * Checking if a user already exists with the new email
     */
    $query = new PDOQueryController(
        $queries['do user exists']
    );
    $query->prepare([
        ':publicKey' => $requester['publicKey'],
        ':email' => trim($request->payload()->newEmailAddress)
    ]);

    if ($query->get()['hasRecord']) {
        throw new AlreadyExistsException(
            'New user email already exists'
        );
    }

    /**
     * All validation has passed as this point
     */
    try {

        $transactions = new PDOTransaction();

        # Delete verification key
        $transactions->query($queries['delete verification key']);
        $transactions->prepare([
            ':publicKey' => $requester['publicKey'],
            ':userId' => $requester['userId'],
            ':verfKey' => $request->payload()->verfKey,
        ]);

        $transactions->post();

        # Add action to user action tracker to temporarily
        # disable the action
        $transactions->query($queries['register user action']);
        $transactions->prepare([
            ':userId' => $payload['userId'],
            ':actionType' => 'user_email_update',
            ':createdAt' => TimeStamp::now(),
            ':toExpireAt' => TimeStamp::add(TimeStamp::now(),'2 days'),
            ':publicKey' => $payload['publicKey'],
            ':recordType' => 'tenant.user'
        ]);
        $transactions->post();

        # Actual updating of user password
        $transactions->query($queries['update user email']);
        $transactions->prepare([
            ':userId' => $payload['userId'],
            ':publicKey' => $payload['publicKey'],
            ':email' => TypeOf::email(
                'New email address',
                $request->payload()->newEmailAddress
            ),
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
        'payload' => [
            'status'=>'200 OK',
            'message' => 'User email updated'
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

        # Allows you to see the exact error message passed on the throw statement
        //'exception'=>$e->getMessage()
    ]);
}

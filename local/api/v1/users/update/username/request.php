<?php

/**
 * API Description Here
 *
 */

declare(strict_types=1);

# Error displaying, has to be removed on production

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
        "create verification key" => "
            INSERT INTO m_glyf_verf_keys (userId, verfKey, createdAt, createdBy, createdFor, toExpireAt, tenantId, recordType)
            VALUES (:userId, :verfKey, :createdAt, :createdBy, :createdFor, :toExpireAt, (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey), :recordType)
        ",
        "check user restriction" => "
            SELECT a.toExpireAt
            FROM s_glyf_user_actrk a
            LEFT JOIN m_glyf_user u ON u.userId = a.userId
            WHERE a.userId = :userId
            AND a.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND a.actionType = 'user_username_update'
            AND a.recordType = 'tenant.user'
            ORDER BY a.createdAt DESC
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
        $queries['check user restriction']
    );
    $query->prepare([
        ':userId' => $payload['userId'],
        ':publicKey' => $payload['publicKey']
    ]);

    $restriction = $query->getAll();

    if (!empty($restriction)) {

        if ($restriction[0]['toExpireAt'] > TimeStamp::now()) {
            throw new ResourceAccessForbiddenException(
                'Unable to request to update username at the moment, username has been updated recently'
            );
        }

    }

    $verificationKey = UniqueId::create64bitKey(UniqueId::BETANUMERIC);

    # Saving verification key on the database
    $query = new PDOQueryController(
        $queries['create verification key']
    );
    $query->prepare([
        ':userId' => $requester['userId'],
        ':createdAt' => TimeStamp::now(),
        ':verfKey' => $verificationKey,
        ':createdBy' => 'users/username/request',
        ':createdFor' => 'user_username_update',
        ':toExpireAt' => TimeStamp::add(TimeStamp::now(), "1 day"),
        ':publicKey' => $requester['publicKey'],
        ':recordType' => 'tenant.user'
    ]);
    $query->post();

    Response::transmit([
        'code' => 201,
        'payload' => [
            'status'=>'201',
            'message' => 'Username update requested',
            'verificationKey' => $verificationKey
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

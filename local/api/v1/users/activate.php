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
        "get active activation key" => "
            SELECT v.verfKey, v.toExpireAt, u.status
            FROM m_glyf_verf_keys v
            LEFT JOIN m_glyf_user u ON v.userId = u.userId
            WHERE v.userId = :userId
            AND v.verfKey = :verfKey
            AND u.status = 'NEW'
            AND v.recordType = 'tenant.user'
            AND v.createdFor = 'user_activation'
            AND v.createdBy = 'users/register'
        ",
        "activate user" => "
            UPDATE m_glyf_user
            SET status = 'ACTIVE',
            activatedAt = :activatedAt
            WHERE userId = :userId
            AND status = 'NEW'
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('PUT');

    # Require API payload
    RequireApiEndpoint::payload([
        'activationKey',
        'userId'
    ]);

    $query = new PDOQueryController(
        $queries['get active activation key']
    );
    $query->prepare([
        ':userId' => TypeOf::alphanum(
            'User Id',
            $request->payload()->userId
        ),
        ':verfKey' => TypeOf::alphanum(
            'Activation Key',
            $request->payload()->activationKey
        )
    ]);

    $activation = $query->get();

    if (!$activation['hasRecord']) {
        throw new AlreadyExistsException(
            'User has already been activated'
        );
    }

    $query = new PDOQueryController(
        $queries['activate user']
    );
    $query->prepare([
        ':userId' => $request->payload()->userId,
        ':activatedAt' => TimeStamp::now()
    ]);

    $query->post();




    Response::transmit([
        'payload' => [
            'status'=>'200',
            'message' => 'User activated successfully'
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

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
        "get address by type" => "
            SELECT id
            FROM s_glyf_user_adrs
            WHERE addressType = :addressType
            AND userId = :userId
            AND tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND recordType = 'tenant.user'
        ",
        "get address type by id" => "
            SELECT addressType
            FROM s_glyf_user_adrs
            WHERE id = :rowId
            AND userId = :userId
            AND tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND recordType = 'tenant.user'
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('PUT');

    # Require API payload
    RequireApiEndpoint::payload([
        'token',
        'switchTo',
        'id'
    ]);


    # Requester validation
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


    /**
     * Get the row Id of the address of the type
     * that needs to be switched into
     */
    $query = new PDOQueryController(
        $queries['get address by type']
    );
    $query->prepare([
        ':userId' => $requester['userId'],
        ':publicKey' => $requester['publicKey'],
        ':addressType' => TypeOf::alpha(
            'Switch To',
            $request->payload()->switchTo
        )
    ]);

    $addressToSwitchWith = $query->get();

    if (!$addressToSwitchWith['hasRecord']) {
        throw new RecordNotFoundException(
            'Address type of '.$request->payload()->switchTo.' not found for this user'
        );
    }


    /**
     * Get the address type of the address that requests
     * the switch
     */
    $query = new PDOQueryController(
        $queries['get address type by id']
    );
    $query->prepare([
        ':userId' => $requester['userId'],
        ':publicKey' => $requester['publicKey'],
        ':rowId' => TypeOf::integer(
            'Id',
            $request->payload()->id
        )
    ]);

    $addressToSwitchFrom = $query->get();

    if (!$addressToSwitchFrom['hasRecord']) {
        throw new RecordNotFoundException(
            'Address with id '.$request->payload()->id.' not found for this user'
        );
    }

    /**
     * Switch operation
     */

    try {

        $transactions = new PDOTransaction();



    } catch (\PDOException $e) {

        $transactions->rollBack();

        Response::transmit([
            'code' => 500,
            'exception' => 'Exception::'.$e->getMessage()
        ]);

    }



    // echo json_encode($query->get());
    // exit();


    Response::transmit([
        'payload' => [
            'status'=>'200 OK',
            'message' => 'your_message_here'
        ]
    ]);

} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),

            # Provides only generic error message
            //'exception' => 'RocketExceptionsInterface::'.$e->exception(),

            # Allows you to see the exact error message passed on the throw statement
            'exception'=>$e->getMessage()
        ]);
        exit();
    }
    Response::transmit([
        'code' => 400,
        // 'exception' => 'Unhandled Exception'

        # Allows you to see the exact error message passed on the throw statement
        'exception'=>$e->getMessage()
    ]);
}

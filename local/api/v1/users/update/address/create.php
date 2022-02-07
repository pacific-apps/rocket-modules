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
        "does address type exist" => "
            SELECT addressType
            FROM s_glyf_user_adrs
            WHERE addressType = :addressType
            AND userId = :userId
            AND tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
        ",
        "add new address" => "
            INSERT INTO s_glyf_user_adrs (userId, addressType, addressLine1, city, province, region, country, zipcode, tenantId, recordType)
            VALUES (:userId, :addressType, :addressLine1, :city, :province, :region, :country, :zipcode, (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey ), 'tenant.user')
        ",
        "get all registered addresses" => "
            SELECT id, addressType, addressLine1, addressLine2, apartment, building, street, zone, barangay, town, city, province, region, island, country, zipcode
            FROM s_glyf_user_adrs
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
        'addressType',
        'addressLine1',
        'city',
        'province',
        'region',
        'country',
        'zipcode'
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
     * Checking if the address type under this user already exists
     * If so, deny the request as it has to go through the update endpoint
     */

    $query = new PDOQueryController(
        $queries["does address type exist"]
    );
    $query->prepare([
        ':userId' => $requester['userId'],
        ':publicKey' => $requester['publicKey'],
        ':addressType' => TypeOf::alpha(
            'Address type',
            $request->payload()->addressType
        )
    ]);

    if ($query->get()['hasRecord']) {
        throw new AlreadyExistsException(
            'Address type of '.$request->payload()->addressType.' already exist for this user'
        );
    }


    $address = [
        ':userId' => $requester['userId'],
        ':addressType' => $request->payload()->addressType,
        ':addressLine1' => TypeOf::all(
            'Address Line 1',
            $request->payload()->addressLine1,
            'NOT EMPTY'
        ),
        ':city' => TypeOf::all(
            'City',
            $request->payload()->city,
            'NOT EMPTY'
        ),
        ':province' => TypeOf::all(
            'Province',
            $request->payload()->province,
            'NOT EMPTY'
        ),
        ':region' => TypeOf::all(
            'Region',
            $request->payload()->region,
            'NOT EMPTY'
        ),
        ':country' => TypeOf::all(
            'Country',
            $request->payload()->country,
            'NOT EMPTY'
        ),
        ':zipcode' => TypeOf::all(
            'Zipcode',
            $request->payload()->zipcode,
            'NOT EMPTY'
        ),
        ':publicKey' => $requester['publicKey']
    ];

    $query = new PDOQueryController(
        $queries['add new address']
    );
    $query->prepare($address);
    $query->post();


    /**
     * Returning all the address registered under this user
     */
    $query = new PDOQueryController(
     $queries['get all registered addresses']
    );
    $query->prepare([
     ':userId' => $requester['userId'],
     ':publicKey' =>$requester['publicKey']
    ]);
    $addresses = $query->getAll();



    Response::transmit([
        'code' => 201,
        'payload' => [
            'status'=>'201',
            'message' => 'User address created',
            'addresses' => $addresses
        ]
    ]);



} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),

            # Provides only generic error message
            'exception' => 'RocketExceptionsInterface::'.$e->exception(),

            # Allows you to see the exact error message passed on the throw statement
            // 'exception'=>$e->getMessage()
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

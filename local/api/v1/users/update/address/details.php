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
        "does address type exist" => "
            SELECT addressType
            FROM s_glyf_user_adrs
            WHERE addressType = :addressType
            AND id = :rowId
            AND userId = :userId
            AND tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
        ",
        "update address" => "
            UPDATE s_glyf_user_adrs
            SET {{setArguments}}
            WHERE tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey )
            AND userId = :userId
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('PUT');

    # Require API payload
    RequireApiEndpoint::payload([
        'token',
        'addressType',
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
     * Checks if the address type and corresponding row Id is correct
     */

    $query = new PDOQueryController(
        $queries['does address type exist']
    );
    $query->prepare([
        ':addressType' => TypeOf::alpha(
            'Address Type',
            $request->payload()->addressType
        ),
        ':rowId' => TypeOf::integer(
            'Address Id',
            $request->payload()->id
        ),
        ':userId' => TypeOf::alphanum(
            'User Id',
            $requester['userId']
        ),
        ':publicKey' => TypeOf::alphanum(
            'Public Key',
            $requester['publicKey']
        )
    ]);
    $address = $query->get();

    if (!$address['hasRecord']) {
        throw new RecordNotFoundException(
            'Address type of '.$request->payload()->addressType.' not found for this user'
        );
    }


    /**
     * $sets will be used to fill in data for the SET
     * statement in the update address query above
     */
    $sets = [];


    $prepareArguments = [
        ':publicKey' => $requester['publicKey'],
        ':userId' => $requester['userId']
    ];

    if (isset($request->payload()->addressLine1)) {
        $sets['addressLine1'] = ':addressLine1';
        $prepareArguments[':addressLine1'] = TypeOf::all(
            'Address Line 1',
            $request->payload()->addressLine1 ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->addressLine2)) {
        $sets['addressLine2'] = ':addressLine2';
        $prepareArguments[':addressLine2'] = TypeOf::all(
            'Address Line 2',
            $request->payload()->addressLine2 ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->apartment)) {
        $sets['apartment'] = ':apartment';
        $prepareArguments[':apartment'] = TypeOf::all(
            'Apartment',
            $request->payload()->apartment ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->building)) {
        $sets['building'] = ':building';
        $prepareArguments[':building'] = TypeOf::all(
            'Building',
            $request->payload()->building ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->street)) {
        $sets['street'] = ':street';
        $prepareArguments[':street'] = TypeOf::all(
            'Street',
            $request->payload()->street ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->zone)) {
        $sets['zone'] = ':zone';
        $prepareArguments[':zone'] = TypeOf::all(
            'Zone',
            $request->payload()->street ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->barangay)) {
        $sets['barangay'] = ':barangay';
        $prepareArguments[':barangay'] = TypeOf::all(
            'Barangay',
            $request->payload()->barangay ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->town)) {
        $sets['town'] = ':town';
        $prepareArguments[':town'] = TypeOf::all(
            'Town',
            $request->payload()->town ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->city)) {
        $sets['city'] = ':city';
        $prepareArguments[':city'] = TypeOf::all(
            'City',
            $request->payload()->city ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->province)) {
        $sets['province'] = ':province';
        $prepareArguments[':province'] = TypeOf::all(
            'Province',
            $request->payload()->province ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->region)) {
        $sets['region'] = ':region';
        $prepareArguments[':region'] = TypeOf::all(
            'Region',
            $request->payload()->region ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->island)) {
        $sets['island'] = ':island';
        $prepareArguments[':island'] = TypeOf::all(
            'Island',
            $request->payload()->island ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->country)) {
        $sets['country'] = ':country';
        $prepareArguments[':country'] = TypeOf::all(
            'Country',
            $request->payload()->country ?? null,
            'NULLABLE'
        );
    }

    if (isset($request->payload()->zipcode)) {
        $sets['zipcode'] = ':zipcode';
        $prepareArguments[':zipcode'] = TypeOf::all(
            'Zipcode',
            $request->payload()->zipcode ?? null,
            'NULLABLE'
        );
    }


    /**
     * Builds the SET statement of the query
     */
    $queryBuilder = new QueryBuilder(
        $queries["update address"]
    );
    $queryBuilder->set($sets);

    $query = new PDOQueryController(
        $queryBuilder->build()
    );
    $query->prepare($prepareArguments);
    $query->post();

     Response::transmit([
         'payload' => [
             'status'=>'200 OK',
             'message' => 'User '.$request->payload()->addressType.' address updated'
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
        //'exception' => 'Unhandled Exception'

        # Allows you to see the exact error message passed on the throw statement
        'exception'=>$e->getMessage()
    ]);
}

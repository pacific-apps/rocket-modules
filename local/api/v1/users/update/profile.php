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
        "update profile" => "
            UPDATE s_glyf_profile
            SET {{setArguments}}
            WHERE tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey )
            AND userId = :userId
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('PATCH');

    # Require API payload
    RequireApiEndpoint::payload([
        'token',
        'profile'
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
     * Checking to see if the profile payload
     * is a type of an Object, and contains
     * at least one property
     */

    if (!is_object($request->payload()->profile)) {
        throw new BadRequestException(
            'Profile payload must be type of an object'
        );
    }

    if (count(get_object_vars($request->payload()->profile)) === 0) {
        throw new BadRequestException(
            'Profile payload must contain at least one profile property'
        );
    }

    /**
     * $sets will be used to fill in data for the SET
     * statement in the update profile query above
     */
    $sets = [];


    $prepareArguments = [
        ':publicKey' => $requester['publicKey'],
        ':userId' => $requester['userId']
    ];

    if (isset($request->payload()->profile->firstName)) {
        $sets['firstName'] = ':firstName';
        $prepareArguments[':firstName'] = TypeOf::alpha(
            'First name',
            $request->payload()->profile->firstName ?? null
        );
    }

    if (isset($request->payload()->profile->lastName)) {
        $sets['lastName'] = ':lastName';
        $prepareArguments[':lastName'] = TypeOf::alpha(
            'Last name',
            $request->payload()->profile->lastName ?? null
        );
    }

    if (property_exists($request->payload()->profile,'middleName')) {
        $sets['middleName'] = ':middleName';
        $prepareArguments[':middleName'] = TypeOf::alpha(
            'Middle name',
            $request->payload()->profile->middleName ?? null,
            'NULLABLE'
        );
    }

    if (property_exists($request->payload()->profile,'nameTitle')) {
        $sets['nameTitle'] = ':nameTitle';
        $prepareArguments[':nameTitle'] = TypeOf::alpha(
            'Name title',
            $request->payload()->profile->nameTitle ?? null,
            'NULLABLE'
        );
    }

    if (property_exists($request->payload()->profile,'suffix')) {
        $sets['suffix'] = ':suffix';
        $prepareArguments[':suffix'] = TypeOf::alpha(
            'Name suffix',
            $request->payload()->profile->suffix ?? null,
            'NULLABLE'
        );
    }

    if (property_exists($request->payload()->profile,'gender')) {
        $sets['gender'] = ':gender';
        $prepareArguments[':gender'] = TypeOf::alpha(
            'Gender',
            $request->payload()->profile->gender ?? null,
            'NULLABLE'
        );
    }

    if (property_exists($request->payload()->profile,'profilePhoto')) {
        $sets['profilePhoto'] = ':profilePhoto';
        $prepareArguments[':profilePhoto'] = TypeOf::url(
            'Profile Photo',
            $request->payload()->profile->profilePhoto ?? null,
            'NULLABLE'
        );
    }

    /**
     * Builds the SET statement of the query
     */
    $queryBuilder = new QueryBuilder(
        $queries["update profile"]
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
            'message' => 'User profile updated'
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

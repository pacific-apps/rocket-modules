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
        "glyf_init" => "CREATE TABLE glyf_init (created_at VARCHAR(32) NOT NULL);",
        "m_glyf_tnt" => "
            CREATE TABLE m_glyf_tnt (
                id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenantId VARCHAR(32) NOT NULL,
                publicKey VARCHAR(32) NOT NULL,
                privateKey VARCHAR(32) NOT NULL,
                createdAt VARCHAR(32) NOT NULL,
                podId INT(12) NOT NULL,
                domain VARCHAR(255) NOT NULL,
                status VARCHAR(32) NOT NULL
            );
        ",
        "main_users" => "
            CREATE TABLE m_glyf_user (
                id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userId VARCHAR(32) NOT NULL,
                username VARCHAR(32) NOT NULL,
                email VARCHAR(64) NOT NULL,
                password VARCHAR(255) NOT NULL,
                createdAt VARCHAR(32) NOT NULL,
                activatedAt VARCHAR(32),
                role VARCHAR(32) NOT NULL,
                permissions VARCHAR(64) NOT NULL,
                tenantId VARCHAR(32) NOT NULL,
                recordType VARCHAR(32) NOT NULL,
                status VARCHAR(32) NOT NULL
            );
        ",
        "main_verf_keys" => "
            CREATE TABLE m_glyf_verf_keys (
                id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userId VARCHAR(32) NOT NULL,
                verfKey VARCHAR(64) NOT NULL,
                createdAt VARCHAR(32) NOT NULL,
                createdBy VARCHAR(32) NOT NULL,
                createdFor VARCHAR(32) NOT NULL,
                toExpireAt VARCHAR(32) NOT NULL,
                tenantId VARCHAR(32) NOT NULL,
                recordType VARCHAR(32) NOT NULL
            );
        ",
        "sub_profile" => "
            CREATE TABLE s_glyf_profile (
                id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userId VARCHAR(32) NOT NULL,
                firstName VARCHAR(64) NOT NULL,
                middleName VARCHAR(64),
                lastName VARCHAR(32) NOT NULL,
                nameTitle VARCHAR(32),
                suffix VARCHAR(32),
                gender VARCHAR(32),
                profilePhoto TEXT,
                tenantId VARCHAR(32) NOT NULL,
                recordType VARCHAR(32) NOT NULL
            );
        ",
        "sub_user_actions_tracker"=>"
            CREATE TABLE s_glyf_user_actrk (
                id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userId VARCHAR(32) NOT NULL,
                actionType VARCHAR(32) NOT NULL,
                createdAt VARCHAR(32) NOT NULL,
                toExpireAt VARCHAR(32) NOT NULL,
                tenantId VARCHAR(32) NOT NULL,
                recordType VARCHAR(32) NOT NULL
            );
        ",
        "sub_user_address" => "
            CREATE TABLE s_glyf_user_adrs (
                id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userId VARCHAR(32) NOT NULL,
                addressType VARCHAR(32),
                addressLine1 VARCHAR(32),
                addressLine2 VARCHAR(32),
                apartment VARCHAR(32),
                building VARCHAR(32),
                street VARCHAR(32),
                zone VARCHAR(32),
                barangay VARCHAR(32),
                town VARCHAR(32),
                city VARCHAR(32),
                province VARCHAR(32),
                region VARCHAR(32),
                island VARCHAR(32),
                country VARCHAR(32),
                zipcode VARCHAR(32),
                tenantId VARCHAR(32) NOT NULL,
                recordType VARCHAR(32) NOT NULL
            );
        ",
        "sub_user_contact" => "
            CREATE TABLE s_glyf_user_cnt (
                id INT(12) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userId VARCHAR(32) NOT NULL,
                contactType VARCHAR(32),
                secondaryEmail VARCHAR(64),
                businessEmail VARCHAR(64),
                primaryPhone VARCHAR(32),
                secondaryPhone VARCHAR(32),
                businessPhone VARCHAR(32),
                faxNumber VARCHAR(32),
                emerContPerson VARCHAR(64),
                emerContNumber VARCHAR(32),
                tenantId VARCHAR(32) NOT NULL,
                recordType VARCHAR(32) NOT NULL
            );
        "
    ];

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


    $init = ROOT.'/data/glyphic/init.txt';
    if (file_exists($init)) {
        throw new AlreadyExistsException(
            'Glyphic instance exists'
        );
    }

    $tableExistsQuery = 'SELECT *
        FROM information_schema.tables
        WHERE table_schema = :dbName
            AND table_name = :tableName
        LIMIT 1;
    ';

    $query = new PDOQueryController($tableExistsQuery);
    $query->prepare([
        ':dbName' => getenv('GLYPHIC_DATABASE'),
        ':tableName' => 'glyf_init'
    ]);

    $table = $query->get();

    if ($table['hasRecord']) {
        throw new AlreadyExistsException(
            'Glyphic instance exists'
        );
    }

    foreach ($queries as $tableName => $tableQuery) {
        $tableCreate = new PDOQueryController($tableQuery);
        $tableCreate->post();
    }

    file_put_contents($init,TimeStamp::now());

    Response::transmit([
        'code' => 201,
        'payload' => [
            'status'=>'201',
            'message' => 'App initialized'
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

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
    $queries = [];

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

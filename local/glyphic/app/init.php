<?php

declare(strict_types=1);
# Creating a new Glyphic instance

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \core\exceptions\UnauthorizedAccessException;
use \core\exceptions\BadRequestException;
use \core\exceptions\AlreadyExistsException;
use \jwt\Token;
use \glyphic\RequireApiEndpoint;
use \glyphic\models\Table;

$request = new Request;
$response = new Response;

try {

    RequireApiEndpoint::method('POST');
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
    if (!isset($payload['requester'])&&$payload['requester']!=='root') {
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

    try {
        $table = new Table('glyf_init','GET');
        if ($table->doExist()) {
            throw new AlreadyExistsException(
                'Glyphic instance exists'
            );
        }
    } catch (\PDOException $e) {
        Response::error();
        exit();
    }

    # Initializing the tables
    $initTables = scandir(ROOT.'/data/glyphic/queries/init');

    try {
        foreach ($initTables as $tableNames) {
            if ($tableNames!=='.'&&$tableNames!=='..') {
                list($tableName,$value) = explode('.',$tableNames,2);
                # Executing the query for each init SQL files
                $table = new Table($tableName);
                $table->create("init/{$tableName}");
            }
        }
    } catch (\PDOException $e) {
        Response::error();
        exit();
    }

    # Instantiates the application
    file_put_contents(ROOT.'/data/glyphic/init.txt','');

    # Finally, responding 200 OK
    Response::transmit([200]);


} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),
            'exception' => 'RocketExceptionsInterface::'.$e->exception()
        ]);
        exit();
    }
    Response::transmit([
        'code' => 400,
        'exception' => 'Unhandled Exception'
    ]);
}

<?php

declare(strict_types=1);
# Creating a new Glyphic instance

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \glyphic\tools\MySQLQueryBuilder;
use \glyphic\tools\MySQLDatabase;

$request = new Request;
$response = new Response;

# Specifying this api call's request method, payload, and query params
ACCEPT::method('POST');
ACCEPT::payload(['token']);

$token = $request->payload()->token ?? null;
if (null==$token) {
    Response::abort(
        'Requires Token Payload'
    );
}

$jwt = new Token($token);

if (!$jwt->isValid()) {
    Response::unauthorized(
        'Token is invalid'
    );
}

$payload = $jwt->payload();
if (!isset($payload['requester'])&&$payload['requester']!=='root') {
    Response::unauthorized(
        'Token is invalid'
    );
}

$init = ROOT.'/data/glyphic/init.txt';
if (file_exists($init)) {
    Response::abort(
        'Glyphic instance exists'
    );
}

$query = new MySQLQueryBuilder('tables/do.exist',([
    'dbName' => getenv('GLYPHIC_DATABASE'),
    'tableName' => 'glyf_init'
]));

$result = MySQLDatabase::get(
    query: $query->build(),
    flag: '--OBJECT'
);

if ($result->hasRecord) {
    Response::abort(
        'Glyphic instance exists'
    );
}

# Instantiates the application
file_put_contents(ROOT.'/data/glyphic/init.txt','');

# Creating the init table
$query = new MySQLQueryBuilder('tables/init.glyf',[]);
MySQLDatabase::save(
    $query->build()
);

# Prepare table aliases
require ROOT.'/data/glyphic/config.php';
$tableAlias = get_glyphic_config()['tables_aka'];

$initTables = scandir(ROOT.'/data/glyphic/queries/init');
foreach ($initTables as $tableNames) {
    if ($tableNames!=='.'&&$tableNames!=='..') {
        list($tableName,$value) = explode('.',$tableNames,2);
        # Executing the query for each init SQL files
        $query = new MySQLQueryBuilder('init/main_tennants',[
            'tableName' => $tableAlias[$tableName]
        ]);
        MySQLDatabase::save(
            $query->build()
        );
    }
}

# Creating the init table
Response::transmit([200]);

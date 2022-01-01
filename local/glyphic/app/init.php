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
use \glyphic\tools\PDOQuery;

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

try {

    $query = new PDOQuery('tables/do.exist');
    $query->param(':dbName',getenv('GLYPHIC_DATABASE'));
    $query->param(':tableName','glyf_init');
    $result = $query->get();

    if ($result['hasRecord']) {
        Response::abort('Glyphic instance exists');
    }

} catch (\PDOException $e) {
    Response::error();
    exit();
}

# Instantiates the application
file_put_contents(ROOT.'/data/glyphic/init.txt','');

# Creating the init table
try {
    $query = new PDOQuery('tables/init.glyf');
    $query->post();

} catch (\PDOException $e) {
    Response::error();
    exit();
}


# Prepare table aliases
require ROOT.'/data/glyphic/config.php';
$tableAlias = get_glyphic_config()['tables_aka'];

$initTables = scandir(ROOT.'/data/glyphic/queries/init');
foreach ($initTables as $tableNames) {
    if ($tableNames!=='.'&&$tableNames!=='..') {
        list($tableName,$value) = explode('.',$tableNames,2);
        # Executing the query for each init SQL files
        $query = new PDOQuery("init/{$tableName}");
        $query->post();
    }
}

# Creating the init table
Response::transmit([200]);

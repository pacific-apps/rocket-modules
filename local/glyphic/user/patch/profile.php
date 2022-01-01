<?php

/**
 * Modifies the user profile table
 * Has to include profile information
 * @param string token - User authentication token
 */

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \glyphic\tools\TypeOf;
use \glyphic\tools\PDOQuery;
use \glyphic\tools\MySQLQueryBuilder;
use \glyphic\tools\IDGenerator;
use \glyphic\tools\DateManager;

$request = new Request;
Accept::method('PATCH');
Accept::payload(['token','profile']);

$token = $request->payload()->token;
$jwt = new Token($token);

if (!$jwt->isValid()) {
    Response::unauthorized(
        'Token is invalid'
    );
}

$requester    = $jwt->payload();
$setArguments = [];
$prepareArguments = [
    ':publicKey' => TypeOf::alphanum(
        'Public Key',
        $requester['publicKey'] ?? null
    ),
    ':userId' => TypeOf::alphanum(
        'User Id',
        $requester['userId'] ?? null
    )
];

if (isset($request->payload()->profile->firstname)) {
    $setArguments['firstName'] = ':firstName';
    $prepareArguments[':firstName'] = TypeOf::alpha(
        'First name',
        $request->payload()->profile->firstname ?? null
    );
}

if (isset($request->payload()->profile->lastname)) {
    $setArguments['lastName'] = ':lastName';
    $prepareArguments[':lastName'] = TypeOf::alpha(
        'Last name',
        $request->payload()->profile->lastname ?? null
    );
}

if (isset($request->payload()->profile->middlename)) {
    $setArguments['middleName'] = ':middleName';
    $prepareArguments[':middleName'] = TypeOf::alpha(
        'Middle name',
        $request->payload()->profile->middlename ?? null
    );
}

$queryBuilder = new MySQLQueryBuilder('users/update/profile');
$queryBuilder->set($setArguments);

try {
    $query = new PDOQuery($queryBuilder->build(),TRUE);
    $query->prepare($prepareArguments);
    $query->post();
} catch (\Exception $e) {

}

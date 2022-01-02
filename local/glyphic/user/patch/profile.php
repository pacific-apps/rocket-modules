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

if (property_exists($request->payload()->profile,'middlename')) {
    $setArguments['middleName'] = ':middleName';
    $prepareArguments[':middleName'] = TypeOf::alpha(
        'Middle name',
        $request->payload()->profile->middlename ?? null,
        'NULLABLE'
    );
}

if (property_exists($request->payload()->profile,'nametitle')) {
    $setArguments['nameTitle'] = ':nameTitle';
    $prepareArguments[':nameTitle'] = TypeOf::alpha(
        'Name title',
        $request->payload()->profile->nametitle ?? null,
        'NULLABLE'
    );
}

if (property_exists($request->payload()->profile,'suffix')) {
    $setArguments['suffix'] = ':suffix';
    $prepareArguments[':suffix'] = TypeOf::alpha(
        'Name suffix',
        $request->payload()->profile->suffix ?? null,
        'NULLABLE'
    );
}

if (property_exists($request->payload()->profile,'gender')) {
    $setArguments['gender'] = ':gender';
    $prepareArguments[':gender'] = TypeOf::alpha(
        'Gender',
        $request->payload()->profile->gender ?? null,
        'NULLABLE'
    );
}

if (property_exists($request->payload()->profile,'profilephoto')) {
    $setArguments['profilePhoto'] = ':profilePhoto';
    $prepareArguments[':profilePhoto'] = TypeOf::url(
        'Profile Photo',
        $request->payload()->profile->profilephoto ?? null,
        'NULLABLE'
    );
}

$queryBuilder = new MySQLQueryBuilder('users/update/profile');
$queryBuilder->set($setArguments);

try {
    $query = new PDOQuery($queryBuilder->build(),TRUE);
    $query->prepare($prepareArguments);
    //$query->post();
} catch (\Exception $e) {

}

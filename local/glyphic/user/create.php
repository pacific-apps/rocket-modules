<?php

declare(strict_types=1);

/**
 * Handles user creation
 * @param string token - should be retrieved using the tennant api
 * @param array profile details
 */


require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \glyphic\tools\TypeOf;
use \glyphic\tools\PDOQuery;
use \glyphic\tools\IDGenerator;
use \glyphic\tools\DateManager;

$request = new Request;
$response = new Response;

/**
 * 1. Defining Pre-requisites for this endpoint
 * This endpoint only accepts POST request
 * and requires token and profile payload
 */

Accept::method('POST');
Accept::payload(['token','profile']);

$token = $request->payload()->token;
$jwt = new Token($token);

if (!$jwt->isValid()) {
    Response::unauthorized(
        'Token is invalid'
    );
}

$password = $request->payload()->profile->password ?? null;
if (null===$password) {
    Response::abort('Requires password');
}

# Get Tennant Id
$tennantId = $jwt->payload()['tid'];

try {

    $newUserId       = IDGenerator::create32BitKey(IDGenerator::BETANUMERIC);
    $dateNow         = DateManager::now('Y-m-d H:i:s');
    $verificationKey = IDGenerator::create64BitKey(IDGenerator::ALPHANUMERIC);

    $main = new PDOQuery('users/create/user');
    $main->prepare([
        ':userId' => $newUserId,
        ':username' => TypeOf::alphanum(
            'username',
            $request->payload()->profile->username ?? null
        ),
        ':email' => TypeOf::email(
            'email',
            $request->payload()->profile->email ?? null
        ),
        ':password' => password_hash($password,PASSWORD_DEFAULT),
        ':createdAt' => $dateNow,
        ':activatedAt' => null,
        ':role' => 'user',
        ':permissions' => 'WEB',
        ':tennantId' => $tennantId,
        ':status' => 'NEW'
    ]);

    $profile = new PDOQuery('users/create/profile');
    $profile->prepare([
        ':userId' => $newUserId,
        ':firstName' => TypeOf::alpha(
            'First name',
            $request->payload()->profile->firstname ?? null
        ),
        ':lastName' => TypeOf::alpha(
            'Last name',
            $request->payload()->profile->lastname ?? null
        ),
        ':tennantId' => $tennantId,
        ':recordType' => 'user'
    ]);

    $activation = new PDOQuery('users/create/activation');
    $activation->prepare([
        ':userId' => $newUserId,
        ':createdAt' => $dateNow,
        ':verfKey' => $verificationKey,
        ':createdBy' => 'users/create',
        ':createdFor' => 'user_activation',
        ':toExpireAt' => DateManager::add($dateNow, "2 days"),
        ':tennantId' => $tennantId,
        ':recordType' => 'user'
    ]);

    $main->post();
    $profile->post();
    $activation->post();

} catch (\PDOException $e) {
    Response::error();
    exit();
}

Response::transmit([
    'code' => 200,
    'payload' => [
        'message' => 'User has been created',
        'userId' => $newUserId,
        'verificationKey' => $verificationKey
    ]
]);

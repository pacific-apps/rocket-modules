<?php

declare(strict_types=1);

/**
 * Handles all user authentications
 * @method POST
 * @param string username/email
 * @param string password
 *
 * NOTE: Accepts both username or email
 *
 * @return JSON with JWT Token
 *
 * @since v1.0
 */

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \glyphic\tools\TypeOf;
use \glyphic\tools\PDOQuery;

Accept::method('POST');
Accept::payload(['password','publickey']);

$request  = new Request;
$email    = $request->payload()->email ?? null;
$username = $request->payload()->username ?? null;

if (null==$email && null==$username) {
    Response::abort('Invalid payload');
}
if (null==$email && !null==$username) {
    $queryFile = 'user.by.username';
}
else {
    $queryFile = 'user.by.email';
}

try {

    $query = new PDOQuery('users/get/user');
    $query->prepare([
        ':email' => TypeOf::email(
            'Email',
            $request->payload()->email ?? 'u@a.com'
        ),
        ':username' => TypeOf::alphanum(
            'Username',
            $request->payload()->username ?? 'u'
        ),
        ':publicKey' => TypeOf::alphanum(
            'Public Key',
            $request->payload()->publickey ?? null
        )
    ]);

    $user = $query->get();

} catch (\PDOException $e) {
    Response::error();
    exit();
}

if (!$user['hasRecord']) {
    Response::unknown('User not found');
}

if ($user['status']!=='ACTIVE') {
    Response::unauthorized('User is inactive');
}

if (!password_verify(
    $request->payload()->password,
    $user['password']
    )
) {
    Response::unauthorized('Invalid password');
}

$token = new Token();
$token->payload([
    'userId' => $user['userId'],
    'publicKey' => $request->payload()->publickey
]);

Response::transmit([
    'code' => 200,
    'payload' => [
        'message' => 'User authenticated',
        'token' => $token->create(),
        'exp' => '7min',
        'firstName' => $user['firstName'],
        'lastName' => $user['lastName'],
        'profilePhoto' => $user['profilePhoto']
    ]
]);

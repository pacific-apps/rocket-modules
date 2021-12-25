<?php

declare(strict_types=1);

# API/Auth/Create
# Create new JWT token

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \toolkit\Validator;
use \toolkit\PsqlBuilder;
use \toolkit\Database;
use \jwt\Token;

$request = new Request;
$response = new Response;

# Specifying this api call's request method, payload, and query params
Accept::method('POST');
Accept::query(['auth=user']);


/**
 * ===================================================================
 * Email and Username
 * Allows either email and username to be used for
 * authentication
 *
 */

$email    = $request->payload()->email ?? null;
$username = $request->payload()->username ?? null;
$psql     = [];

if (null==$email) {
    if (null==$username) {
        # Abort when there is no email and username payload
        Response::abort('Requires username or email');
    }
    $psql['whereKey'] = 'username';
    # Validates username input
    if (!Validator::isCorrectData('alphanum',$username)) {
        Response::abort('Invalid username');
    }
    $psql['whereValue'] = $username;
}
else {
    $psql['whereKey'] = 'email';
    if (!Validator::isCorrectData('email',$email)) {
        Response::abort('Invalid email');
    }
    $psql['whereValue'] = $email;
}


/**
 * ===================================================================
 * User Password
 *
 */
$password = $request->payload()->password ?? null;
if (null==$password) Response::abort('Requires password');

# Checking user data from the user primary database
$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.auth')
         ->data($psql);
$result = Database::get($sqlQuery->build());

# Checking if the user exists
if (!$result['hasRecord']) {
    Response::unknown('User not found');
}

# Verify password
if (!password_verify($password,$result['password'])) {
    Response::unauthorized();
}

# Checking user profile
$where['whereKey']   = 'user_id';
$where['whereValue'] = $result['user_id'];

$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/get/user.get.profile')
         ->data($where);
$profile = Database::get($sqlQuery->build());

# Populating data for the token payload
$tokenPayload = [
    'userId'       => $result['user_id'],
    'role'         => $result['role'],
    'permissions'  => $result['permissions'],
    'firstName'    => $profile['first_name'],
    'lastName'     => $profile['last_name'],
    'profilePhoto' => $profile['profile_photo']
];

Response::transmit([
    'payload' => [
        'status'=>'200 OK',
        'message' => 'authenticated',
        'token' => Token::create($tokenPayload)
    ]
]);

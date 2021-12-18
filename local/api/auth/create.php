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
Accept::query($request,['auth=user']);

if (!isset($request->payload()->email)) {
    if (!isset($request->payload()->username)) {
        return Response::abort('Requires username or email');
    }
    $key   = 'username';
    $value = $request->payload()->username;
    if (!Validator::isCorrectData('alphanum',$value)) {
        Response::abort('Invalid username');
    }
}
else {
    $key   = 'email';
    $value = $request->payload()->email;
    if (!Validator::isCorrectData('email',$value)) {
        Response::abort('Invalid email');
    }
}

$where['whereKey']   = $key;
$where['whereValue'] = $value;

if (!isset($request->payload()->password)) {
    return Response::abort('Requires password');
}

# Checking user data from the user primary database
$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.auth')
         ->data($where);
$result = Database::get($sqlQuery->build());

# Checking if the user exists
if (!$result['hasRecord']) {
    Response::unknown('User not found');
}

# Verify password
if (!password_verify($request->payload()->password,$result['password'])) {
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

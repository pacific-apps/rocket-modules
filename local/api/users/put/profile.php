<?php

declare(strict_types=1);

# API/Users/Put/Profile
# Create edit/modify user profile

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \jwt\Token;
use \toolkit\Validator;
use \toolkit\PermissionManager;
use \toolkit\PsqlBuilder;
use \toolkit\Database;

$request = new Request;
$response = new Response;

# Specifying this API call's request method, payload, and query params
Accept::method('PUT');
Accept::payload($request,['token','profile']);

/**
 * User reference
 * By default, we set the user reference to user id
 * The user reference is what being used by this endpoint
 * for the following actions:
 *
 * 1. Determine if the user exists
 * 2. Used a reference to JOIN tables
 *
 */
if (!isset($request->payload()->profile->id)) {
    Response::abort();
}
$userReference = $request->payload()->profile->id;


/**
 * Token validity checkpoint
 * Checks the validity of the token
 * Returns 401 status code when token is invalid
 */
if (!Token::verify($request->payload()->token)) {
    Response::unauthorized();
}


/**
 * Working with payloads
 * Extracting the payload from the token
 * Allow you to check on the permission and
 *
 */
$tokenRequester = Token::getPayload($request->payload()->token);

# Specifying permissions
if (!PermissionManager::allow($tokenRequester,'ADMIN')) {
    Response::unauthorized();
}

/**
 * Does the user exist?
 * Next, we need to check if the user do exist,
 * and deny request if we have no record of
 * the queried user id
 */
$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.do.exist')
         ->data([
             'condition' => 'user_id',
             'value' => $userReference
         ]);
$userDoesExist = Database::doExist($sqlQuery->build());

if (!$userDoesExist) {
    Response::unknown();
}


$editables = [];


/**
 * Data typing and verifications
 */
# First name
if (isset($request->payload()->profile->firstname)) {
    $firstName = $request->payload()->profile->firstname;
    if (!Validator::isCorrectData('alpha',$firstName)) {
        Response::abort('Invalid first name');
    }
    $editables['first_name'] = "'{$firstName}'";
}

# Last name
if (isset($request->payload()->profile->lastname)) {
    $lastName = $request->payload()->profile->lastname;
    if (!Validator::isCorrectData('alpha',$lastName)) {
        Response::abort('Invalid last name');
    }
    $editables['last_name'] = "'{$lastName}'";
}



$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/update/user.global.update.profile')
         ->set($editables);

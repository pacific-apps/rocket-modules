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
use \toolkit\DateHelper;
use \toolkit\UniqueId;

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
    Response::unauthorized('Requires neccessary permission to perform the request');
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
    Response::unknown('User does not exist');
}

$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.is.active')
         ->data(['userId'=>$userReference]);
$userStatus = Database::get($sqlQuery->build())['status'];

if ('NEW'===$userStatus) {
    if (!PermissionManager::allow($tokenRequester,'ADMIN')) {
        Response::unauthorized('User needs to be activated');
    }
}

if ('BANNED'===$userStatus) {
    Response::unauthorized('User cannot update profile');
}


/**
 * Holds all the key value pair of profile
 * data to edit/modify
 */
$PROFILE_EDITABLES = [];


/**
 * Data specification and verifications
 * Here, we carefully verify each and every external
 * input relating to the database column
 */

/**
 * First Name
 * A person's first name
 * @var string
 * Max: 32
 * Min: 3
 */
 $firstName = $request->payload()->profile->firstname ?? null;

if (null!==$firstName) {

    if (strlen($firstName)>32) {
        Response::abort('First name too long');
    }

    if (strlen($firstName)<3) {
        Response::abort('First name too short');
    }

    # Only alphabets are being allowed to be saved as first name
    if (!Validator::isCorrectData('alpha',$firstName)) {
        Response::abort('Invalid first name');
    }

    $PROFILE_EDITABLES['first_name'] = "'{$firstName}'";
}


/**
 * Last Name
 * A person's last name
 * @var string
 * Max: 32
 * Min: 2
 */

$lastName = $request->payload()->profile->lastname ?? null;

if (null!==$lastName) {

    if (strlen($lastName)>32) {
        Response::abort('Last name too long');
    }

    if (strlen($lastName)<2) {
        Response::abort('Last name too short');
    }

    # Only alphabets are being allowed under the last name string
    if (!Validator::isCorrectData('alpha',$lastName)) {
        Response::abort('Invalid last name');
    }

    $PROFILE_EDITABLES['last_name'] = "'{$lastName}'";
}

/**
 * Last Name
 * A person's middle name
 * @var string
 * NOTE: Can be a null value
 * Max: 32
 * Min: 2
 */

$middleName = $request->payload()->profile->middlename ?? null;

if (null!==$middleName) {

    if (strlen($middleName)>32) {
        Response::abort('Middle name too long');
    }

    if (strlen($middleName)<2) {
        Response::abort('Middle name too short');
    }

    # Allow middlename to be removed/null
    if ($middleName==='(remove)') {
        $middleName = 'NULL';
    }
    if (!Validator::isCorrectData('alpha',$middleName)) {
        Response::abort('Invalid middle name');
    }

    $PROFILE_EDITABLES['middle_name'] = "'{$middleName}'";
}

# Name Suffix
$suffix = $request->payload()->profile->suffix ?? null;
if (null!==$suffix) {

    # Allow suffix to be removed
    if ($suffix==='(remove)') {
        $suffix = 'NULL';
    }
    if (!Validator::isCorrectData('alpha',$suffix)) {
        Response::abort('Invalid suffix');
    }

    $PROFILE_EDITABLES['suffix'] = "'{$suffix}'";
}

# Profile Photo
$profilePhoto = $request->payload()->profile->profilephoto ?? null;
if (null!==$profilePhoto) {

    # Allow profile photo to be removed
    if ($profilePhoto==='(remove)') {
        $profilePhoto = 'NULL';
    }
    if (!Validator::isCorrectData('alpha',$profilePhoto)) {
        Response::abort('Invalid profile photo link');
    }

    $PROFILE_EDITABLES['profile_photo'] = "'{$profilePhoto}'";
}

/**
 * When a new user is created, gender is null
 * However, when a value is added, it cannot be reverted
 * back to null
 */
$gender = $request->payload()->profile->gender ?? null;
if (null!==$gender) {

    if (!Validator::isCorrectData('alpha',$gender)) {
        Response::abort('Invalid gender');
    }
    $tmp = strtolower($gender);
    $PROFILE_EDITABLES['gender'] = "'{$tmp }'";
}

/**
 * Saving the update to the database
 */
$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/update/user.global.update.profile')
         ->set($PROFILE_EDITABLES)
         ->data([
             'id' => $tokenRequester['userId']
         ]);
Database::save($sqlQuery->build());


# Logging action to tk_users_logs
$logId = UniqueId::create32bitKey(UniqueId::BETANUMERIC);
$logCreatedAt = DateHelper::now("Y-m-d H:i:s");
$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/logs/user.create.log')
         ->data([
             'userId' => $userReference,
             'logId' => $logId,
             'logType' => 'user_modify',
             'logBy' => 'Method:Put,Table:Profile',
             'logAt' => DateHelper::now("Y-m-d H:i:s")
         ]);
Database::save($sqlQuery->build());

file_put_contents($_SERVER['DOCUMENT_ROOT']."/logs/toolkit/{$logId}.json",json_encode([
    'log_id' => $logId,
    'log_type' => 'user_modify',
    'created_at' => $logCreatedAt,
    'requester_token_payload' => $tokenRequester,
    'requester_ip'=> [
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'http_x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null
    ],
    'profile_payload' => json_encode($request->payload()->profile)
]));

Response::transmit([
    'payload'=>[
        'message' => 'User profile updated'
    ]
]);

<?php

declare(strict_types=1);

# API/Users/Create
# Create new user per API Call

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \toolkit\Validator;
use \toolkit\PsqlBuilder;
use \toolkit\UniqueId;
use \toolkit\DateHelper;
use \toolkit\Database;

$request = new Request;
$response = new Response;

# Specifying this api call's request method, payload, and query params
Accept::method('POST');
Accept::query($request,['new=user']);
Accept::payload(
    $request,
    ['firstname','lastname','email','username','password'],
    function($key,$value){

        # First Name
        if ($key==='firstname') {
            if (!Validator::isCorrectData('alpha',$value)) {
                Response::abort('Invalid first name');
            }
        }

        # Last Name
        if ($key==='lastname') {
            if (!Validator::isCorrectData('alpha',$value)) {
                Response::abort('Invalid last name');
            }
        }

        # Username
        if ($key==='username') {
            if (!Validator::isCorrectData('alphanum',$value)) {
                Response::abort('Invalid username');
            }
        }

        # Email
        if ($key==='email') {
            if (!Validator::isCorrectData('email',$value)) {
                Response::abort('Invalid email');
            }
        }

    }
);

$userData = json_decode(json_encode($request->payload()),TRUE);

# Creating a new user ID:
$userData['userId'] = UniqueId::create32bitKey(UniqueId::BETANUMERIC);

# User Status
# Data type: String
# Specify the status of the newly-created user account
$userData['status']    = 'NEW';
$userData['createdAt'] = DateHelper::now("Y-m-d H:i:s");

# User Role and Permissions
$userData['role']        = 'User';
$userData['permissions'] = 'WEB';

# Password Hashing
$userData['password'] = password_hash($request->payload()->password,PASSWORD_DEFAULT);

$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.create.primary')
         ->data($userData);
$userDataQuery = $sqlQuery->build();
Database::save($userDataQuery);

# Creating Profile Data
$profileData = [
    'userId' => $userData['userId'],
    'firstName' => $request->payload()->firstname,
    'lastName' => $request->payload()->lastname
];

$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.create.profiles')
         ->data($profileData);
$profileDataQuery = $sqlQuery->build();
Database::save($profileDataQuery);

# Creating User Activation Process
$activationData = [
    'userId' => $userData['userId'],
    'verificationKey' => UniqueId::create64bitKey(UniqueId::ALPHANUMERIC),
    'verificationFor' => 'new_user_activation',
    'validUntil' => (new \DateTime($userData['createdAt']))
                    ->modify("+2 days")
                    ->format("Y-m-d H:i:s"),
    'isCompleted' => 'FALSE'
];


$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.create.verf')
         ->data($activationData);
$activationDataQuery = $sqlQuery->build();
Database::save($activationDataQuery);

# Creating user Address Entry
$addressData = [
    'userId' => $userData['userId'],
    'addressLabel' => 'primary'
];

$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.create.address')
         ->data($addressData);
Database::save($sqlQuery->build());

Response::transmit([
    'payload' => [
        'status'=>'200 OK',
        'message' => 'User has been added'
    ]
]);

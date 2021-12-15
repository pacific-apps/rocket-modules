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

$sqlQuery = new PsqlBuilder();
$sqlQuery->use('users/user.create.primary');
$sqlQuery->data($userData);
echo $sqlQuery->build();

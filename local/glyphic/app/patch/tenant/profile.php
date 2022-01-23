<?php

/**
 * Modifies the user profile table
 * Has to include profile information
 * @param string token - User authentication token
 */

 require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
 use \core\http\Request;
 use \core\http\Response;
 use \core\exceptions\UnauthorizedAccessException;
 use \core\exceptions\BadRequestException;
 use \core\exceptions\AlreadyExistsException;
 use \core\exceptions\RecordNotFoundException;
 use \core\exceptions\ResourceAccessForbiddenException;
 use \glyphic\RequireApiEndpoint;
 use \glyphic\models\Tenant;
 use \glyphic\models\User;
 use \glyphic\TypeOf;
 use \glyphic\PDOQueryController;
 use \glyphic\QueryBuilder;
 use \glyphic\TimeStamp;
 use \jwt\Token;

 try {

     $request = new Request;
     RequireApiEndpoint::header();
     RequireApiEndpoint::method('PATCH');
     RequireApiEndpoint::payload([
         'token',
         'publickey',
         'profile',
         'uid',
         'type'
     ]);

     $jwt = new Token($request->payload()->token);

     if (!$jwt->isValid()) {
         throw new UnauthorizedAccessException(
             'Token provided is either expired or invalid'
         );
     }

     $payload = $jwt->payload();
     if (!isset($payload['requester'])||$payload['requester']!=='root') {
         throw new UnauthorizedAccessException(
             'Token provided is either expired or invalid'
         );
     }

     $query = new PDOQueryController(
         (new QueryBuilder('tenants/get/tenant'))->build()
     );
     $query->prepare([
         'publicKey' => TypeOf::alphanum(
             'Tenant Public Key',
             $request->payload()->publickey ?? null
            )
     ]);

     $tenant = $query->get();

     if (!$tenant['doExist']) {
         throw new RecordNotFoundException(
             'Tenant not found'
         );
     }

     if ($request->payload()->type!=='basic' &&
         $request->payload()->type!=='profile_only'
     ) {
         throw new BadRequestException(
             'Update type must be basic or profile_only'
         );
     }

     /**
      * Basic update includes first name, last name,
      * And email address
      */
     if ($request->payload()->type==='basic') {
         $setArguments = [];
         if (isset($request->payload()->profile->email)&&
             isset($request->payload()->profile->username)
         ) {

             $emailUpdater = new PDOQueryController(
                 (new QueryBuilder('tenants/update/user.email'))->build()
             );
             $emailUpdater->prepare([
                 ':email' => TypeOf::email(
                     'Tenant User Email',
                     $request->payload()->profile->email
                 ),
                 ':username' => TypeOf::alphanum(
                     'Tenant Username',
                     $request->payload()->profile->username
                 ),
                 ':userId' => TypeOf::alphanum(
                     'Tenant User Id',
                     $request->payload()->uid
                 ),
                 ':publicKey' => $request->payload()->publickey
             ]);
             $emailUpdater->post();
         }

         if (isset($request->payload()->profile->firstname)&&
             isset($request->payload()->profile->lastname)
            ) {
                $namesUpdater = new PDOQueryController(
                    (new QueryBuilder('tenants/update/user.names'))->build()
                );
                $namesUpdater->prepare([
                    ':userId' => TypeOf::alphanum(
                        'Tenant User Id',
                        $request->payload()->uid
                    ),
                    ':firstName' => TypeOf::alpha(
                        'Tenant User First Name',
                        $request->payload()->profile->firstname
                    ),
                    ':lastName' => TypeOf::alpha(
                        'Tenant User Last Name',
                        $request->payload()->profile->lastname
                    ),
                    ':publicKey' => $request->payload()->publickey
                ]);
                $namesUpdater->post();
         }

     }

     /**
      * Profile only update includes all the table from the
      * Users profile
      */
     if ($request->payload()->type==='profile_only') {

         $setArguments = [];
         $prepareArguments = [
             ':publicKey' => $request->payload()->publickey,
             ':userId' => TypeOf::alphanum(
                 'Tenant User Id',
                 $request->payload()->uid
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

         $queryBuilder = new QueryBuilder('tenants/update/user.profile');
         $queryBuilder->set($setArguments);

         $query = new PDOQueryController(
             $queryBuilder->build()
         );
         $query->prepare($prepareArguments);
         $query->post();

     }








     Response::transmit([
         'code' => 200,
         'payload' => [
             'message' => 'Tenant profile updated'
         ]
     ]);

 } catch (\Exception $e) {
     if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
         Response::transmit([
             'code' => $e->code(),
             'exception' => 'RocketExceptionsInterface::'.$e->exception(),
             //'exception'=>$e->getMessage()
         ]);
         exit();
     }
     Response::transmit([
         'code' => 400,
         'exception' => $e->getMessage()
     ]);
 }

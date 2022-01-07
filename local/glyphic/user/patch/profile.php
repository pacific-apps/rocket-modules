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
     RequireApiEndpoint::method('PATCH');
     RequireApiEndpoint::payload([
         'token',
         'profile'
     ]);

     $jwt = new Token($request->payload()->token);

     if (!$jwt->isValid()) {
         throw new UnauthorizedAccessException (
             'Token is either invalid or expired'
         );
     }

     $requester = $jwt->payload();

     # The user Id of the user profile to modify
     $userIdOfConcern = TypeOf::alphanum(
         'User Id from token',
         $requester['userId'] ?? null
     );

     $tenant = new Tenant(
         TypeOf::alphanum(
             'Public Key',
             $requester['publicKey'] ?? null
         )
     );

     if ($tenant->getStatus()!=='ACTIVE') {

         /**
          * If the tenant is not active, the flow would then
          * require a token, and requester who can allow the creation
          * would be the following:
          * SUPERUSER, ADMIN, TENANTADMIN
          * and/or special tenant staff permission
          */

          if ($requester['permissions']==='WEB') {
              throw new ResourceAccessForbiddenException(
                  'Tenant is not active'
              );
          }

          if ($requester['permissions']==='ADMIN') {
              // code...
          }

     }

     $query = new PDOQueryController(
         (new QueryBuilder('users/get/user.by.id'))->build()
     );

     $query->prepare([
         ':userId' => $userIdOfConcern,
         ':publicKey' => $requester['publicKey']
     ]);

     $user = $query->get();

     if (!$user['hasRecord']) {
         throw new RecordNotFoundException(
             'User not found'
         );
     }

     if ($user['status']!=='ACTIVE') {
         throw new ResourceAccessForbiddenException(
             'User is not active'
         );
     }

     $setArguments = [];
     $prepareArguments = [
         ':publicKey' => $requester['publicKey'],
         ':userId' => $userIdOfConcern
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

     $queryBuilder = new QueryBuilder('users/update/profile');
     $queryBuilder->set($setArguments);

     $query = new PDOQueryController(
         $queryBuilder->build()
     );
     $query->prepare($prepareArguments);
     $query->post();

     Response::transmit([
         'code' => 200,
         'payload' => [
             'message' => 'User profile modified'
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

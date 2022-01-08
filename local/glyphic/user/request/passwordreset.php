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
 use \glyphic\UniqueId;
 use \glyphic\TimeStamp;
 use \jwt\Token;

 try {

     $request = new Request;
     RequireApiEndpoint::method('PUT');
     RequireApiEndpoint::payload([
         'token'
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

     # Checking if verification key for the same action is requested
     $query = new PDOQueryController(
         (new QueryBuilder('users/get/verification.by.action'))->build()
     );
     $query->prepare([
         ':userId' => TypeOf::alphanum(
             'User Id',
             $userIdOfConcern
         ),
         ':createdBy' => 'users/request/passwordreset',
         ':createdFor' => 'user_password_reset',
         ':publicKey' => TypeOf::alphanum(
             'Public Key',
             $requester['publicKey']
         )
     ]);

     $verifPasswordReset = $query->get();

     if ($verifPasswordReset['hasRecord']) {
         if (TimeStamp::now() > $verifPasswordReset['toExpireAt']) {
             # Verification key has already expired, therefore
             # create a new one
             $query = new PDOQueryController(
                 (new QueryBuilder('users/actions/delete.verification.key'))->build()
             );
             $query->prepare([
                 ':userId' => $userIdOfConcern,
                 ':verfKey' => $verifPasswordReset['verfKey'],
                 ':publicKey' => $requester['publicKey']
             ]);
             $query->post();
         }

         else {
             # Verification key is not yet expired
             if ($verifPasswordReset['hasRecord']) {
                 Response::transmit([
                     'code' => 200,
                     'payload' => [
                         'message' => 'Password reset request is active',
                         'verificationKey' => $verifPasswordReset['verfKey']
                     ]
                 ]);
                 exit();
             }
         }
     }



     $dateNow         = TimeStamp::now();
     $verificationKey = UniqueId::create64BitKey(UniqueId::ALPHANUMERIC);

     $verification = new PDOQueryController(
         (new QueryBuilder('users/create/verification.key'))->build()
     );

     $verification->prepare([
         ':userId' => $userIdOfConcern,
         ':createdAt' => $dateNow,
         ':verfKey' => $verificationKey,
         ':createdBy' => 'users/request/passwordreset',
         ':createdFor' => 'user_password_reset',
         ':toExpireAt' => TimeStamp::add($dateNow, "24 hours"),
         ':tenantId' => $tenant->getTenantId(),
         ':recordType' => 'user'
     ]);

     $verification->post();

     Response::transmit([
         'code' => 200,
         'payload' => [
             'message' => 'Password reset requested',
             'verificationKey' => $verificationKey
         ]
     ]);

 } catch (\Exception $e) {
     if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
         Response::transmit([
             'code' => $e->code(),
             'exception' => 'RocketExceptionsInterface::'.$e->exception(),
             // 'exception'=>$e->getMessage()
         ]);
         exit();
     }
     Response::transmit([
         'code' => 400,
         'exception' => $e->getMessage()
     ]);
 }

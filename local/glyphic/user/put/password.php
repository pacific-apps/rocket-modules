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
     RequireApiEndpoint::method('PUT');
     RequireApiEndpoint::payload([
         'token',
         'verfkey',
         'password'
     ]);

     if (null==$request->payload()->password) {
         throw new BadRequestException (
             'Requires password payload'
         );
     }

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

     # Checking if verification key for reset password is correct
     $query = new PDOQueryController(
         (new QueryBuilder('users/get/verification.by.action'))->build()
     );
     $query->prepare([
         ':userId' => $userIdOfConcern,
         ':createdBy' => 'users/request/passwordreset',
         ':createdFor' => 'user_password_reset',
         ':publicKey' => $requester['publicKey']
     ]);

     $verifPasswordReset = $query->get();

     if (!$verifPasswordReset['hasRecord']) {
         throw new RecordNotFoundException(
             'No request password request found'
         );
     }

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

         throw new ResourceAccessForbiddenException(
             'Reset password request has already expired'
         );

     }

     if ($verifPasswordReset['verfKey']!==$request->payload()->verfkey) {
         throw new UnauthorizedAccessException(
             'Invalid reset password request verification key'
         );
     }

     $passwordChangeQuery = new PDOQueryController(
         (new QueryBuilder('users/update/password'))->build()
     );
     
     $passwordChangeQuery->prepare([
         ':userId' => $userIdOfConcern,
         ':publicKey' => $requester['publicKey'],
         ':password' => password_hash(
             $request->payload()->password,
             PASSWORD_DEFAULT
         )
     ]);

     $verificationKeyRemoval = new PDOQueryController(
         (new QueryBuilder('users/actions/delete.verification.key'))->build()
     );

     $verificationKeyRemoval->prepare([
         ':userId' => $userIdOfConcern,
         ':verfKey' => $verifPasswordReset['verfKey'],
         ':publicKey' => $requester['publicKey']
     ]);

     $verificationKeyRemoval->post();
     $passwordChangeQuery->post();

     Response::transmit([
         'code' => 200,
         'payload' => [
             'message' => 'Password has been updated'
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
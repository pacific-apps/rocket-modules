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
         'password',
         'uid'
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

     $newPassword = $request->payload()->password;

     if ($newPassword==""||$newPassword==" ") {
         throw new BadRequestException(
             'Invalid Password'
         );
     }

     if (strlen($newPassword)<8) {
         throw new BadRequestException(
             'New password cannot be less than 8 characters'
         );
     }

     $hashedNewPassword = password_hash($newPassword,PASSWORD_DEFAULT);

     $passwordUpdater = new PDOQueryController(
         (new QueryBuilder('tenants/update/user.password'))->build()
     );
     $passwordUpdater->prepare([
         ':password' => $hashedNewPassword,
         ':publicKey' => TypeOf::alphanum(
             'Tenant Public Key',
             $request->payload()->publickey
         ),
         ':userId' => TypeOf::alphanum(
             'Tenant User Id',
             $request->payload()->uid
         ),
     ]);
     $passwordUpdater->post();

     Response::transmit([
         'code' => 200,
         'payload' => [
             'message' => 'Tenant password updated'
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

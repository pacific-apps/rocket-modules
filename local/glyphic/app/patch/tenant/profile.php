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
     RequireApiEndpoint::method('GET');
     RequireApiEndpoint::payload([
         'token',
         'publickey',
         'profile'
     ]);

     $jwt = new Token($request->query()->token);

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







     $setArguments = [];
     $prepareArguments = [
         ':publicKey' => $request->payload()->publickey,
         ':userId' => $userIdOfConcern
     ];








     Response::transmit([
         'code' => 200,
         'payload' => [

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

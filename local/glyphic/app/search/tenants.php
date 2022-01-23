<?php

/**
 * Search All Tenants
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
     RequireApiEndpoint::query([
         'token'
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
         (new QueryBuilder('tenants/get/all.tenants'))->build()
     );
     $query->prepare([]);
     $tenants = $query->getAll();

     Response::transmit([
         'code' => 200,
         'payload' => [
             'result' => $tenants
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

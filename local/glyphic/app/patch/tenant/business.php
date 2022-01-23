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
         'uid',
         'business',
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

     if($request->payload()->type=='business_name_only'){
         if (!isset($request->payload()->business->name)) {
             throw new BadRequestException(
                 'Type business_name_only requires Business name payload'
             );
         }
         $businessNameUpdater = new PDOQueryController(
             (new QueryBuilder('tenants/update/business.name'))->build()
         );
         $businessNameUpdater->prepare([
             ':businessName' => TypeOf::alphanumwithspace(
                 'Tenant Business Name',
                 $request->payload()->business->name
             ),
             ':userId' => TypeOf::alphanum(
                 'Tenant User Id',
                 $request->payload()->uid
             ),
             ':publicKey' => $request->payload()->publickey
         ]);
         $businessNameUpdater->post();
     }










     Response::transmit([
         'code' => 200,
         'payload' => [
             'message' => 'Tenant business information updated'
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

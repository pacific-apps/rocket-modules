<?php

/**
 * Modifies the user address table
 * Has to include address information
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
         'address'
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

     if (property_exists($request->payload()->address,'addressline1')) {
         $setArguments['addressLine1'] = ':addressLine1';
         $prepareArguments[':addressLine1'] = TypeOf::all(
             'User address line 1',
             $request->payload()->address->addressline1 ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'addressline2')) {
         $setArguments['addressLine2'] = ':addressLine2';
         $prepareArguments[':addressLine2'] = TypeOf::all(
             'User address line 2',
             $request->payload()->address->addressline2 ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'apartment')) {
         $setArguments['apartment'] = ':apartment';
         $prepareArguments[':apartment'] = TypeOf::alphanumwithspace(
             'User apartment address',
             $request->payload()->address->apartment ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'building')) {
         $setArguments['building'] = ':building';
         $prepareArguments[':building'] = TypeOf::alphanumwithspace(
             'User building address',
             $request->payload()->address->building ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'street')) {
         $setArguments['street'] = ':street';
         $prepareArguments[':street'] = TypeOf::alphanumwithspace(
             'User street address',
             $request->payload()->address->street ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'zone')) {
         $setArguments['zone'] = ':zone';
         $prepareArguments[':zone'] = TypeOf::alphanumwithspace(
             'User zone address',
             $request->payload()->address->zone ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'barangay')) {
         $setArguments['barangay'] = ':barangay';
         $prepareArguments[':barangay'] = TypeOf::alphanumwithspace(
             'User barangay address',
             $request->payload()->address->barangay ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'town')) {
         $setArguments['town'] = ':town';
         $prepareArguments[':town'] = TypeOf::alphanumwithspace(
             'User town address',
             $request->payload()->address->town ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'city')) {
         $setArguments['city'] = ':city';
         $prepareArguments[':city'] = TypeOf::alphanumwithspace(
             'User city address',
             $request->payload()->address->city ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'province')) {
         $setArguments['province'] = ':province';
         $prepareArguments[':province'] = TypeOf::alphanumwithspace(
             'User province address',
             $request->payload()->address->province ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'region')) {
         $setArguments['region'] = ':region';
         $prepareArguments[':region'] = TypeOf::alphanumwithspace(
             'User region address',
             $request->payload()->address->region ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'island')) {
         $setArguments['island'] = ':island';
         $prepareArguments[':island'] = TypeOf::alphanumwithspace(
             'User island address',
             $request->payload()->address->island ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'country')) {
         $setArguments['country'] = ':country';
         $prepareArguments[':country'] = TypeOf::alphanumwithspace(
             'User country address',
             $request->payload()->address->country ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->address,'zipcode')) {
         $setArguments['zipcode'] = ':zipcode';
         $prepareArguments[':zipcode'] = TypeOf::alphanumwithspace(
             'User zipcode address',
             $request->payload()->address->zipcode ?? null,
             'NULLABLE'
         );
     }

     $queryBuilder = new QueryBuilder('users/update/address');
     $queryBuilder->set($setArguments);

     $query = new PDOQueryController(
         $queryBuilder->build()
     );
     $query->prepare($prepareArguments);
     $query->post();

     Response::transmit([
         'code' => 200,
         'payload' => [
             'message' => 'User address modified'
         ]
     ]);




     /*

     $queryBuilder = new QueryBuilder('users/update/contact');
     $queryBuilder->set($setArguments);

     $query = new PDOQueryController(
         $queryBuilder->build()
     );
     $query->prepare($prepareArguments);
     $query->post();

     Response::transmit([
         'code' => 200,
         'payload' => [
             'message' => 'User contacts modified'
         ]
     ]);

     */

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

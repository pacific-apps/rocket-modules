<?php

/**
 * Modifies the user contacts table
 * Has to include contact information
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
         'contact'
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

     if (property_exists($request->payload()->contact,'secondaryemail')) {
         $setArguments['secondaryEmail'] = ':secondaryEmail';
         $prepareArguments[':secondaryEmail'] = TypeOf::email(
             'User secondary email',
             $request->payload()->contact->secondaryemail ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->contact,'businessemail')) {
         $setArguments['businessEmail'] = ':businessEmail';
         $prepareArguments[':businessEmail'] = TypeOf::email(
             'User business email',
             $request->payload()->contact->businessemail ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->contact,'primaryphone')) {
         $setArguments['primaryPhone'] = ':primaryPhone';
         $prepareArguments[':primaryPhone'] = TypeOf::all(
             'User primary phone',
             $request->payload()->contact->primaryphone ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->contact,'secondaryphone')) {
         $setArguments['secondaryPhone'] = ':secondaryPhone';
         $prepareArguments[':secondaryPhone'] = TypeOf::all(
             'User secondary phone',
             $request->payload()->contact->secondaryphone ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->contact,'businessphone')) {
         $setArguments['businessPhone'] = ':businessPhone';
         $prepareArguments[':businessPhone'] = TypeOf::all(
             'User business phone',
             $request->payload()->contact->businessphone ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->contact,'faxnumber')) {
         $setArguments['faxNumber'] = ':faxNumber';
         $prepareArguments[':faxNumber'] = TypeOf::all(
             'User fax number',
             $request->payload()->contact->faxnumber ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->contact,'emercontperson')) {
         $setArguments['emerContPerson'] = ':emerContPerson';
         $prepareArguments[':emerContPerson'] = TypeOf::all(
             'User emergency contact person',
             $request->payload()->contact->emercontperson ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->contact,'emercontperson')) {
         $setArguments['emerContPerson'] = ':emerContPerson';
         $prepareArguments[':emerContPerson'] = TypeOf::fullname(
             'User emergency contact person',
             $request->payload()->contact->emercontperson ?? null,
             'NULLABLE'
         );
     }

     if (property_exists($request->payload()->contact,'emercontnumber')) {
         $setArguments['emerContNumber'] = ':emerContNumber';
         $prepareArguments[':emercontnumber'] = TypeOf::all(
             'User emergency contact person number',
             $request->payload()->contact->emercontperson ?? null,
             'NULLABLE'
         );
     }

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

     /*

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

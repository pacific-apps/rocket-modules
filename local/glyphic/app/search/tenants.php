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
 use \glyphic\Paginator;
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

     # Sort Logic
     if (!isset($request->query()->sort)) {
         $sortBy = 'DESC';
     } else {
         $sortBy = $request->query()->sort;
         if ($sortBy!=='desc'&&$sortBy!=='asc') {
             throw new BadRequestException(
                 'Invalid sort option provided'
             );
         }
     }

     # Order by Logic
     if (!isset($request->query()->order_by)) {
         $orderBy = 'createdAt';
     } else {
         $orderBy = $request->query()->order_by;

         # Allowed Order By Options
         if (
             $orderBy!=='createdAt'&&
             $orderBy!=='nameTitle'
             )
        {
             throw new BadRequestException(
                 'Invalid sort option provided'
             );
         }
     }


     # Query Settings
     $resultsPerPage = TypeOf::integer(
         'Results per page',
         $request->query()->results_per_page,
         'NULLABLE'
     ) ?? 5;

     $pageQuery = TypeOf::integer(
         'Page Query',
         $request->query()->page,
         'NULLABLE'
     ) ?? 1;


     $queryBuilder = new QueryBuilder('tenants/get/all.tenants');
     $queryBuilder->data([
         'orderBy' => $orderBy,
         'sortBy' => $sortBy
     ]);

     $query = new PDOQueryController(
         $queryBuilder->build()
     );
     $query->prepare([]);
     $tenants = $query->getAll();

     $results = Paginator::paginate(
         $tenants, $resultsPerPage, $pageQuery
     );

     Response::transmit([
         'code' => 200,
         'payload' => [
             'result' => $results['aggregated'],
             'pages' => $results['totalPage']
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

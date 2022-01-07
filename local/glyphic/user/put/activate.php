<?php

declare(strict_types=1);

/**
 * Handles user activation
 * Permission requires:
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
use \glyphic\TypeOf;
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;
use \glyphic\TimeStamp;

try {

    $request = new Request;

    RequireApiEndpoint::method('PUT');
    RequireApiEndpoint::payload([
        'uid',
        'vfk',
        'publickey'
    ]);

    $requester = [
        'permissions' => 'WEB'
    ];

    $tenant = new Tenant(
        TypeOf::alphanum(
            'Public Key',
            $request->payload()->publickey ?? null
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

         if (!isset($request->payload()->token)) {
             throw new ResourceAccessForbiddenException(
                 'Requires token'
             );
         }


    }

    $query = new PDOQueryController(
        (new QueryBuilder('users/get/verification.key'))->build()
    );
    $query->prepare([
        ':verfKey' => TypeOf::alphanum(
            'Verification Key',
            $request->payload()->vfk ?? null
        ),
        ':userId' => TypeOf::alphanum(
            'User Id',
            $request->payload()->uid ?? null
        ),
        ':publicKey' => TypeOf::alphanum(
            'Public Key',
            $request->payload()->publickey ?? null
        )
    ]);

    $verifUser = $query->get();

    if (!$verifUser['hasRecord']) {
        throw new RecordNotFoundException(
            'User, tenant, or verification key not found'
        );
    }

    if ($verifUser['createdFor']!=='user_activation') {
        throw new RecordNotFoundException(
            'Verification key must be for user_activation'
        );
    }

    if ($verifUser['status']!=='NEW') {
        throw new AlreadyExistsException(
            'User already activated'
        );
    }

    # User Activation
    $userActivation = new PDOQueryController(
        (new QueryBuilder('users/actions/activate'))->build()
    );
    $userActivation->prepare([
        ':activationDate' => TimeStamp::now(),
        ':userId' => $request->payload()->uid
    ]);

    # Remove Verification Key
    $removal = new PDOQueryController(
        (new QueryBuilder('users/actions/delete.activation.key'))->build()
    );
    $removal->prepare([
        ':verfKey' => $request->payload()->vfk,
        ':userId' => $request->payload()->uid,
        ':publicKey' => $request->payload()->publickey
    ]);


    $userActivation->post();
    $removal->post();

    Response::transmit([
        'code' => 200,
        'payload' => [
            'message' => 'User activated'
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

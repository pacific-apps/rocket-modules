<?php

declare(strict_types=1);

/**
 * Handles user activation
 * Permission requires:
 */

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
use \core\http\Request;
use \core\http\Response;
use \core\http\Accept;
use \glyphic\tools\PDOQuery;
use \glyphic\tools\TypeOf;
use \glyphic\tools\DateManager;

$request = new Request;

Accept::method('PUT');
Accept::payload(['uid','vfk','tid']);

try {

    $query = new PDOQuery('users/get/verification.key');
    $query->prepare([
        ':verfKey' => TypeOf::alphanum(
            'Verification Key',
            $request->payload()->vfk ?? null
        ),
        ':userId' => TypeOf::alphanum(
            'User Id',
            $request->payload()->uid ?? null
        ),
        ':tennantId' => TypeOf::alphanum(
            'Tennant Id',
            $request->payload()->tid ?? null
        )
    ]);
    $result = $query->get();

    if (!$result['hasRecord']) {
        Response::abort('User or verification key not found');
    }

    if ($result['createdFor']!=='user_activation') {
        Response::abort('Invalid verification key');
    }

    if ($result['status']!=='NEW') {
        Response::abort('User already activated');
    }

    if ($result['toExpireAt'] < DateManager::now('Y-m-d H:i:s')) {
        # Remove the verification key from the database

        Response::abort('Verification key already expired');
    }

    # User Activation
    $user = new PDOQuery('users/actions/activate');
    $user->prepare([
        ':activationDate' => DateManager::now('Y-m-d H:i:s'),
        ':userId' => $request->payload()->uid
    ]);
    $user->post();

    # Remove Verification Key
    $removal = new PDOQuery('users/actions/delete.activation.key');
    $removal->prepare([
        ':verfKey' => $request->payload()->vfk,
        ':userId' => $request->payload()->uid,
        ':tennantId' => $request->payload()->tid
    ]);
    $removal->post();

} catch (\PDOException $e) {
    echo $e->getMessage();
}

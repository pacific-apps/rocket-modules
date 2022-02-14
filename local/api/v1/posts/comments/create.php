<?php

/**
 * API Description Here
 *
 */

declare(strict_types=1);

# Error displaying, has to be removed on production
ini_set('error_reporting','E_ALL');
ini_set( 'display_errors','1');
error_reporting(E_ALL ^ E_STRICT);

# Common libraries
use \core\http\Request;
use \core\http\Response;
use \core\exceptions\UnauthorizedAccessException;
use \core\exceptions\BadRequestException;
use \core\exceptions\AlreadyExistsException;
use \core\exceptions\ConfigurationErrorException;
use \core\exceptions\RecordNotFoundException;
use \core\exceptions\ResourceAccessForbiddenException;
use \jwt\Token;
use \glyphic\RequireApiEndpoint;
use \glyphic\PDOQueryController;
use \glyphic\PDOTransaction;
use \glyphic\QueryBuilder;
use \glyphic\TimeStamp;
use \glyphic\TypeOf;
use \glyphic\UniqueId;

require $_SERVER['DOCUMENT_ROOT'].'/imports.php';
$request = new Request;
$response = new Response;

try {

    # Declare all your database queries here
    $queries = [
        "create comment" => "
            INSERT INTO s_glyf_post_cmt
            (commentId, parentId, postId, userId, content, createdAt, updatedAt, status, tenantId, recordType)
            VALUES (:commentId, :parentId, :postId, :userId, :content, :createdAt, :updatedAt, 'ACTIVE', (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey), 'tenant.user.post.comment')
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('POST');

    # Require API payload
    RequireApiEndpoint::payload([
        'token',
        'postId',
        'parentId',
        'content'
    ]);


    # Requester validation
    $jwt = new Token($request->payload()->token);

    if (!$jwt->isValid()) {
        throw new UnauthorizedAccessException(
            'Token provided is either expired or invalid'
        );
    }

    $payload = $jwt->payload();
    $requester = [];

    # Getting the requester User Id
    $requester['userId'] = TypeOf::alphanum(
        'User Id',
        $payload['userId'] ?? null
    );

    # Getting the requester Public Key
    $requester['publicKey'] = TypeOf::alphanum(
        'Public key',
        $payload['publicKey'] ?? null
    );

    # Making sure that the user is on active status
    if ('ACTIVE'!==TypeOf::alpha(
        'User status',
        $payload['status'] ?? null
    )) {
        throw new UnauthorizedAccessException(
            'Requester status is not active'
        );
    }

    $comment = [
        ':commentId' => UniqueId::create32bitKey(UniqueId::BETANUMERIC),
        ':postId' => TypeOf::alphanum(
            'Post Id',
            $request->payload()->postId
        ),
        ':parentId' => TypeOf::alphanum(
            'Comment Parent Id',
            $request->payload()->parentId
        ),
        ':userId' => TypeOf::alphanum(
            'User Id',
            $requester['userId']
        ),
        ':publicKey' => TypeOf::alphanum(
            'Public Key',
            $requester['publicKey']
        ),
        ':content' => TypeOf::all(
            'Comment Content',
            $request->payload()->content,
            'NOT EMPTY'
        ),
        ':createdAt' => TimeStamp::now(),
        ':updatedAt' => TimeStamp::now()
    ];

    $query = new PDOQueryController(
        $queries['create comment']
    );
    $query->prepare($comment);
    $query->post();


    Response::transmit([
        'payload' => [
            'status'=>'200 OK',
            'message' => 'Comment has been posted'
        ]
    ]);

} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),

            # Provides only generic error message
            //'exception' => 'RocketExceptionsInterface::'.$e->exception(),

            # Allows you to see the exact error message passed on the throw statement
            'exception'=>$e->getMessage()
        ]);
        exit();
    }
    Response::transmit([
        'code' => 400,
        //'exception' => 'Unhandled Exception'

        # Allows you to see the exact error message passed on the throw statement
        'exception'=>$e->getMessage()
    ]);
}

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
        "create review on posts" => "
            INSERT INTO m_glyf_reviews
            (reviewId, reviewFor, reviewerId, score, title, content, createdAt, updatedAt, status, tenantId, recordType)
            VALUES
            (:reviewId, :reviewFor, :reviewerId, :score, :title, :content, :createdAt, :updatedAt, 'ACTIVE', (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey), 'tenant.user.post.review')
        ",
        "has reviewed the post" => "
            SELECT reviewId
            FROM m_glyf_reviews
            WHERE reviewFor = :reviewFor
            AND reviewerId = :reviewerId
            AND status = 'ACTIVE'
            AND tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND recordType = 'tenant.user.post.review'
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('POST');

    # Require API payload
    RequireApiEndpoint::payload([
        'token',
        'reviewFor',
        'title',
        'content',
        'score'
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

    // (reviewId, reviewFor, reviewerId, score, title, content, createdAt, updatedAt, status, tenantId, recordType)
    $query = new PDOQueryController(
        $queries['has reviewed the post']
    );
    $query->prepare([
        ':reviewFor' => TypeOf::alphanum(
            'Post Id (review for)',
            $request->payload()->reviewFor
        ),
        ':reviewerId' => TypeOf::alphanum(
            'User Id',
            $requester['userId']
        ),
        ':publicKey' => TypeOf::alphanum(
            'Public Key',
            $requester['publicKey']
        )
    ]);

    $pastReview = $query->get();

    if ($pastReview['hasRecord']) {
        throw new AlreadyExistsException(
            'User already reviewed this post'
        );
    }

    // Preparing the review data
    $review = [
        ':reviewId' => UniqueId::create32bitKey(UniqueId::BETANUMERIC),
        ':reviewFor' => $request->payload()->reviewFor,
        ':reviewerId' => $requester['userId'],
        ':publicKey' => $requester['publicKey'],
        ':score' => TypeOf::integer(
            'Review Score',
            $request->payload()->score
        ),
        ':title' => TypeOf::all(
            'Review Title',
            $request->payload()->title,
            'NULLABLE'
        ),
        ':content' => TypeOf::all(
            'Review Content',
            $request->payload()->content,
            'NOT EMPTY'
        ),
        ':createdAt' => TimeStamp::now(),
        ':updatedAt' => TimeStamp::now()
    ];


    try {

        $transactions = new PDOTransaction();

        $transactions->query($queries['create review on posts']);
        $transactions->prepare($review);
        $transactions->post();

        $transactions->commit();

    } catch (\PDOException $e) {

        $transactions->rollBack();

        Response::transmit([
            'code' => 500,
            'exception' => 'Exception::'.$e->getMessage()
        ]);

    }


    Response::transmit([
        'code' => 201
        'payload' => [
            'status'=>'201',
            'message' => 'Review created'
        ]
    ]);

} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),

            # Provides only generic error message
            // 'exception' => 'RocketExceptionsInterface::'.$e->exception(),

            # Allows you to see the exact error message passed on the throw statement
            'exception'=>$e->getMessage()
        ]);
        exit();
    }
    Response::transmit([
        'code' => 400,
        // 'exception' => 'Unhandled Exception'

        # Allows you to see the exact error message passed on the throw statement
        'exception'=>$e->getMessage()
    ]);
}

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
            (reviewId, reviewFor, reviewerId, score, title, visibility, content, createdAt, updatedAt, status, tenantId, recordType)
            VALUES
            (:reviewId, :reviewFor, :reviewerId, :score, :title, :visibility, :content, :createdAt, :updatedAt, 'ACTIVE', (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey), 'tenant.user.post.review')
        ",
        "has reviewed the post and post visibility" => "
            SELECT r.reviewId, p.status, p.visibility, p.postId
            FROM m_glyf_reviews r
            LEFT OUTER JOIN m_glyf_posts p ON p.postId = r.reviewFor
                AND p.recordType = 'tenant.user.post'
            WHERE r.reviewFor = :reviewFor
            AND r.reviewerId = :reviewerId
            AND r.status = 'ACTIVE'
            AND r.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND r.recordType = 'tenant.user.post.review'
        ",
        "post visibility and has reviewed this post" => "
            SELECT p.postId, p.userId, p.status, p.visibility, r.reviewId
            FROM m_glyf_posts p
            LEFT OUTER JOIN m_glyf_reviews r ON p.postId = r.reviewFor
                AND r.reviewerId = :reviewerId
                AND r.status = 'ACTIVE'
                AND r.recordType = 'tenant.user.post.review'
            WHERE p.postId = :reviewFor
            AND p.recordType = 'tenant.user.post'
            AND p.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
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
        $queries['post visibility and has reviewed this post']
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

    $post = $query->get();

    // header('Content-Type: application/json');
    // echo json_encode($post);
    //
    // exit();

    # Checkpoints
    if (!isset($post['postId'])) {
        throw new RecordNotFoundException(
            'Post ID not found'
        );
    }

    if ($post['visibility']<50) {
        throw new UnauthorizedAccessException(
            'Unable to create review on this post, denied by visibility'
        );

    }

    // if ($post['userId']===$requester['userId']) {
    //     throw new ResourceAccessForbiddenException(
    //         'Users can not review their own posts'
    //     );
    // }

    if ($post['reviewId']!==null) {
        throw new AlreadyExistsException(
            'User already reviewed this post'
        );
    }

    # Post Visibility
    if (isset($request->payload()->visibility)) {
        $visibility = $request->payload()->visibility;
        if ($visibility==='public') {
            $visibilityScore = 99;
        }
        if ($visibility==='private') {
            $visibilityScore = 1;
        }
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
        ':visibility' => $visibilityScore ?? 99,
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
        'code' => 201,
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

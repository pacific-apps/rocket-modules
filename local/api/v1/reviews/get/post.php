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
        "get post data" => "
            SELECT postId, postTitle, postBody, createdAt,
                (SELECT COUNT(reviewId) FROM m_glyf_reviews
                     WHERE reviewFor = :postId
                     AND status = 'ACTIVE'
                ) as totalReviewCount,
                (SELECT SUM(score) FROM m_glyf_reviews
                     WHERE reviewFor = :postId
                     AND status = 'ACTIVE'
                ) as totalReviewScore,
                (SELECT reviewerId FROM m_glyf_reviews
                     WHERE reviewerId = :userId
                     AND status = 'ACTIVE'
                     LIMIT 1
                ) as hasRequesterReviewed
            FROM m_glyf_posts
            WHERE postId = :postId
            AND status = 'ACTIVE'
            AND tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
        ",
        "get post reviews" => "
            SELECT r.reviewId, r.reviewerId, r.title, r.content, r.score, r.createdAt, u.username, p.firstName, p.lastName, p.profilePhoto
            FROM m_glyf_reviews r
            LEFT OUTER JOIN m_glyf_user u ON r.reviewerId = u.userId
                AND u.recordType = 'tenant.user'
            LEFT OUTER JOIN s_glyf_profile p ON r.reviewerId = p.userId
                AND p.recordType = 'tenant.user'
            WHERE r.reviewFor = :postId
            AND r.status = 'ACTIVE'
            AND r.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('GET');

    # Require API query parameters
    RequireApiEndpoint::query([
        'id',
        'publickey'
    ]);

    $requester['userId'] = 'nonexistent';
    $requester['type'] = 'public';

    if (isset($request->query()->token)) {

        if ($request->query()->token!=='public') {

            $requester['type'] = 'user';

            # Requester validation
            $jwt = new Token($request->query()->token);

            if (!$jwt->isValid()) {
                throw new UnauthorizedAccessException(
                    'Token provided is either expired or invalid'
                );
            }

            $payload = $jwt->payload();

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

        }

    }

    $query = new PDOQueryController(
        $queries['get post data']
    );

    $query->prepare([
        ':postId' => TypeOf::alphanum(
            'Post Id',
            $request->query()->id
        ),
        ':publicKey'=>TypeOf::alphanum(
            'Public Key',
            $request->query()->publickey
        ),
        'userId' => TypeOf::alphanum(
            'Requester User Id',
            $requester['userId']
        )
    ]);

    $post = $query->get();

    if (!$post['hasRecord']) {
        throw new RecordNotFoundException(
            'Post not found'
        );
    }

    $query = new PDOQueryController(
        $queries['get post reviews']
    );

    $query->prepare([
        ':postId' => $request->query()->id,
        ':publicKey' => $request->query()->publickey,
    ]);

    $reviews = $query->getAll();

    Response::transmit([
        'payload' => [
            'id' => $post['postId'],
            'title' =>$post['postTitle'],
            'body' => $post['postBody'],
            'createdAt' => $post['createdAt'],
            'bottomLine' => [
                'totalReviews' => $post['totalReviewCount'],
                'totalReviewScore' => $post['totalReviewScore']
            ],
            'reviews' => $reviews,
            'requester' => [
                'type' => $requester['type'],
                'hasReviewed' => $post['hasRequesterReviewed']
            ]
        ]
    ]);

} catch (\Exception $e) {
    if ($e instanceof \core\exceptions\RocketExceptionsInterface) {
        Response::transmit([
            'code' => $e->code(),

            # Provides only generic error message
            'exception' => 'RocketExceptionsInterface::'.$e->exception(),

            # Allows you to see the exact error message passed on the throw statement
            //'exception'=>$e->getMessage()
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

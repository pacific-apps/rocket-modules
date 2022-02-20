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
        "get user with username" => "
            SELECT u.username, u.userId, p.firstName, p.lastName, p.profilePhoto
            FROM m_glyf_user u
            LEFT JOIN s_glyf_profile p ON p.userId = u.userId
            WHERE u.username = :username
            AND u.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND u.recordType = 'tenant.user'
            AND u.status = 'ACTIVE'
        ",
        "get user posts" => "
            SELECT p.postId, p.postTitle, p.postBody, p.createdAt, p.status,
                (SELECT COUNT(parentId) FROM s_glyf_post_cmt
                    WHERE parentId = p.postId
                    AND recordType = 'tenant.user.post.comment'
                ) as totalComments
            FROM m_glyf_posts p
            WHERE p.userId = :userId
            AND p.recordType = 'tenant.user.post'
            AND p.visibility > :minVisibilityScore
            AND p.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND p.status = 'ACTIVE'
        ",
        "get user posts with reviews" => "
            SELECT p.postId, p.postTitle, p.postBody, p.createdAt, p.status, p.visibility,
                (SELECT COUNT(reviewFor) FROM m_glyf_reviews
                    WHERE reviewFor = p.postId
                    AND status = 'ACTIVE'
                    AND recordType = 'tenant.user.post.review'
                ) as totalReviewsCount,
                COALESCE ((SELECT SUM(score) FROM m_glyf_reviews
                    WHERE reviewFor = p.postId
                    AND status = 'ACTIVE'
                    AND recordType = 'tenant.user.post.review'
                ),0) as totalReviewsScore
            FROM m_glyf_posts p
            WHERE p.userId = :userId
            AND p.recordType = 'tenant.user.post'
            AND p.visibility > :minVisibilityScore
            AND p.tenantId IN (SELECT tenantId FROM m_glyf_tnt WHERE publicKey = :publicKey)
            AND p.status = 'ACTIVE'
        ",
        "get user reviews bottomline" => "
            SELECT COUNT(reviewFor) as totalReviews, SUM(score) as totalScore
            FROM m_glyf_reviews r
            WHERE r.reviewFor IN (SELECT postId FROM m_glyf_posts WHERE userId = :userId)
            AND r.status = 'ACTIVE'
        "
    ];

    # Require headers
    RequireApiEndpoint::header();

    # Require API Method endpoint
    RequireApiEndpoint::method('GET');

    # Require API query parameters
    RequireApiEndpoint::query([
        'profile',
        'publickey'
    ]);


    $requester['userId'] = 'nonexistent';
    $requester['type'] = 'public';
    $minVisibilityScore = 50;

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
        $queries['get user with username']
    );
    $query->prepare([
        ':username'=>TypeOf::alphanum(
            'Username',
            $request->query()->profile
        ),
        ':publicKey'=>TypeOf::alphanum(
            'Public Key',
            $request->query()->publickey
        )
    ]);
    $user = $query->get();

    if (!$user['hasRecord']) {
        throw new RecordNotFoundException(
            'User not found'
        );
    }

    $query = new PDOQueryController(
        $queries['get user reviews bottomline']
    );
    $query->prepare([
        ':userId' => $user['userId']
    ]);
    $bottomline = $query->get();

    // header('Content-Type: application/json');
    // echo json_encode($bottomline);
    // exit();

    if ($requester['type']!=='public') {
        if ($user['userId']==$requester['userId']) {
            $minVisibilityScore = 0;
        }
    }

    $query = new PDOQueryController(
        $queries['get user posts with reviews']
    );
    $query->prepare([
        ':userId'=>$user['userId'],
        ':publicKey'=>$request->query()->publickey,
        ':minVisibilityScore' => $minVisibilityScore
    ]);
    $posts = $query->getAll();

    if (!empty($posts)) {
        foreach ($posts as $key => $post) {
            if ($posts[$key]['visibility']>50) {
                $posts[$key]['visibility'] = 'public';
            } else {
                $posts[$key]['visibility'] = 'private';
            }
        }
    }

    Response::transmit([
        'payload' => [
            'username' => $user['username'],
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'profilePhoto' => $user['profilePhoto'],
            'bottomLine' => [
                'totalReviews' => intval($bottomline['totalReviews']),
                'averageScore' => $bottomline['totalScore'] / $bottomline['totalReviews']
            ],
            'posts' => [
                'page'=>1,
                'list'=>$posts
            ]
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
        //'exception' => 'Unhandled Exception'

        # Allows you to see the exact error message passed on the throw statement
        'exception'=>$e->getMessage()
    ]);
}

<?php

declare(strict_types=1);
namespace glyphic\models;
use \glyphic\models\Tenant;
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;
use \glyphic\UniqueId;
use \glyphic\TimeStamp;
use \core\exceptions\RecordNotFoundException;
use \core\exceptions\ResourceAccessForbiddenException;

class User {

    public function __construct(
        string $tennantId,
        string $userId = null
        )
    {

    }

    public function create(
        array $data
        )
    {
        $newUserId       = UniqueId::create32BitKey(UniqueId::BETANUMERIC);
        $dateNow         = TimeStamp::now();
        $verificationKey = UniqueId::create64BitKey(UniqueId::ALPHANUMERIC);

        $main = new PDOQuery('users/create/user');
        $main->prepare([
            ':userId' => $newUserId,
            ':username' => TypeOf::alphanum(
                'username',
                $data['username'] ?? null
            ),
            ':email' => TypeOf::email(
                'email',
                $data['email'] ?? null
            ),
            ':password' => password_hash($data['password'],PASSWORD_DEFAULT),
            ':createdAt' => $dateNow,
            ':activatedAt' => null,
            ':role' => 'user',
            ':permissions' => 'WEB',
            ':tennantId' => $tennantId,
            ':status' => 'NEW'
        ]);

        $profile = new PDOQuery('users/create/profile');
        $profile->prepare([
            ':userId' => $newUserId,
            ':firstName' => TypeOf::alpha(
                'First name',
                $data['firstName'] ?? null
            ),
            ':lastName' => TypeOf::alpha(
                'Last name',
                $data['lastName'] ?? null
            ),
            ':tennantId' => $tennantId,
            ':recordType' => 'user'
        ]);

        $activation = new PDOQuery('users/create/activation');
        $activation->prepare([
            ':userId' => $newUserId,
            ':createdAt' => $dateNow,
            ':verfKey' => $verificationKey,
            ':createdBy' => 'users/create',
            ':createdFor' => 'user_activation',
            ':toExpireAt' => DateManager::add($dateNow, "2 days"),
            ':tennantId' => $tennantId,
            ':recordType' => 'user'
        ]);

        $main->post();
        $profile->post();
        $activation->post();
    }

}

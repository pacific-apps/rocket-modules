<?php

declare(strict_types=1);
namespace glyphic\models;
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;
use \glyphic\UniqueId;
use \glyphic\TimeStamp;
use \jwt\Token;

class Glyphic
{

    private $publicKey;
    private $secretKey;

    public function __construct()
    {
        $this->publicKey  = getenv('GLYPHIC_PUBLIC');
        $this->privateKey = getenv('GLYPHIC_SECRET');
    }

    public function verifyPublicKey(
        string $publicKey
        )
    {
        return ($this->publicKey===$publicKey);
    }

    public function verifyPrivateKey(
        string $privateKey
        )
    {
        return ($this->privateKey===$privateKey);
    }

    public static function auth(
        string $jwToken
        )
    {
        $jwt = new Token($jwToken);

        if (!$jwt->isValid()) {
            return false;
        }

        $payload = $jwt->payload();
        if (!isset($payload['requester'])&&$payload['requester']!=='root') {
            return false;
        }

        return true;
    }

}

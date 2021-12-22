<?php

declare(strict_types=1);

namespace jwt;
use \jwt\Generator;
use \jwt\Validator;
use \jwt\JWTAbstract;

class Token extends Validator {

    public static function create(array $payload)
    {
        JWTAbstract::loadEnvSecret();
        $generate = new Generator();
        $payload['exp'] = ((new \DateTime())->modify('+10 minutes')->getTimestamp());
        return $generate->setPayload($payload)->generateToken();
    }

    public static function verify(string $token)
    {
        JWTAbstract::loadEnvSecret();
        return Validator::token($token);
    }

    public static function getPayload(string $token)
    {
        $tokenParts = explode('.', $token);

        if (!isset($tokenParts[1])) return [];
        $tokenPayload = base64_decode($tokenParts[1]);

        return json_decode($tokenPayload,TRUE);

    }

}

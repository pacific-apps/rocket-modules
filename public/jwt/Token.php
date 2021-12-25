<?php

declare(strict_types=1);

namespace jwt;
use \jwt\Generator;
use \jwt\Validator;
use \jwt\JWTAbstract;

class Token extends Validator {

    private $token;
    private $payload;

    public function __construct(
        string $token = null
        )
    {
        $this->token = $token;
    }

    public function get()
    {
        return $this->token;
    }

    public function set(
        string $token
        )
    {
        $this->token = $token;
        return $this;
    }

    public function payload(
        array $payload = null
        )
    {
        if (null!==$payload) {
            $this->payload = $payload;
            return $this;
        }

        $parts = explode('.', $this->token);
        if (!isset($parts[1])) return [];
        return json_decode(base64_decode($parts[1]),TRUE);

    }

    public function isValid()
    {
        return Validator::token($this->token);
    }

    public function create()
    {
        $generate             = new Generator();
        $this->payload['exp'] = ((new \DateTime())->modify('+10 minutes')->getTimestamp());
        $this->token          = $generate->setPayload($this->payload)->generateToken();
        return $this->token;
    }

    //
    // public static function create(array $payload)
    // {
    //     $generate       = new Generator();
    //     $payload['exp'] = ((new \DateTime())->modify('+10 minutes')->getTimestamp());
    //     return $generate->setPayload($payload)->generateToken();
    // }
    //
    // public static function verify(string $token)
    // {
    //     return Validator::token($token);
    // }
    //
    // public static function getPayload(string $token)
    // {
    //     $tokenParts = explode('.', $token);
    //
    //     if (!isset($tokenParts[1])) return [];
    //     $tokenPayload = base64_decode($tokenParts[1]);
    //
    //     return json_decode($tokenPayload,TRUE);
    //
    // }

}

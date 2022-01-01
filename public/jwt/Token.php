<?php

declare(strict_types=1);

namespace jwt;
use \jwt\Generator;
use \jwt\Validator;
use \jwt\JWTAbstract;

class Token extends Validator {

    private $token;
    private $payload;
    private $exp;

    public function __construct(
        string $token = null
        )
    {
        $this->token = $token;
        $this->exp = null;
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

}

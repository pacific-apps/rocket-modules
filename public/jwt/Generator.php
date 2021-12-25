<?php

declare(strict_types=1);

namespace jwt;
use \jwt\JWTAbstract;

class Generator extends JWTAbstract{

    /**
    * Time expired
    */
    const TOKEN_EXPIRED = 0;

    /**
    * Token is Valid
    */
    const TOKEN_VALID = 1;

    /**
    * Invalid Signature
    */
    const TOKEN_INVALID_SIGNATURE = 2;

    /**
    * @var string secret key
    */
    protected $secret = 'brs57d089h3e21508tncv9rsad6bc1f0d2e53v428t71h2esnu6v81624842536u';

    /**
    * @var null token
    */
    protected $token = null;

    /**
    * @var array payload
    */
    protected $payload = array();

    /**
    * Set secret key
    * @param $secret
    * @return $this
    */
    public function setSecret($secret) {
        $this->secret = $secret;
        return $this;
    }

    /**
    * get secret key
    * @return string
    */
    protected function getSecret() {
        return getenv('JWT_SECRET');
    }

    /**
    * Set data to be encoded
    * @param array $payload
    * @return $this
    */
    public function setPayload($payload = array()) {
        $this->payload = $payload;
        return $this;
    }

    /**
    * return payload Data
    * @return false|string
    */
    protected function getPayload()
    {
        return json_encode($this->payload);
    }

    protected function getSignature($header, $payload)
    {
        return hash_hmac(
            'sha256',
            "{$header}.{$payload}",
            $this->getSecret(),
            true
        );
    }

    protected function getHeader()
    {
        return json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);
    }

    /**
    * Generate a new token
    * @return null
    */
    public function generateToken()
    {
        $base64UrlHeader    = $this->base64UrlEncode($this->getHeader());
        $base64UrlPayload   = $this->base64UrlEncode($this->getPayload());
        $base64UrlSignature = $this->base64UrlEncode($this->getSignature($base64UrlHeader, $base64UrlPayload));

        $this->setToken("{$base64UrlHeader}.{$base64UrlPayload}.{$base64UrlSignature}");
        return $this->getToken();
    }

    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
    * Check if current token in the this class is valid
    * @return int see class const for more information
    */
    public function validate()
    {
        $tokenParts = explode('.', $this->getToken());

        $tokenHeader = base64_decode($tokenParts[0]);

        if (!isset($tokenParts[1])) {
            return self::TOKEN_EXPIRED;
        }

        $tokenPayload = base64_decode($tokenParts[1]);

        if (!isset($tokenParts[2])) {
            return self::TOKEN_EXPIRED;
        }

        $signatureProvided = $tokenParts[2];

        if (json_decode($tokenPayload) === null) {
            return self::TOKEN_EXPIRED;
        }

        // Token expired?
        if ($this->isTokenExpired(json_decode($tokenPayload)->exp)) {
            return self::TOKEN_EXPIRED;
        }

        // build signature from header and payload
        $base64UrlHeader    = $this->base64UrlEncode($tokenHeader);
        $base64UrlPayload   = $this->base64UrlEncode($tokenPayload);
        $signature          = $this->getSignature($base64UrlHeader, $base64UrlPayload);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        if (($base64UrlSignature === $signatureProvided) === false) {
            return self::TOKEN_INVALID_SIGNATURE;
        }

        return self::TOKEN_VALID;
    }

    /**
    * check if token has been expired
    * @param int $expireTime
    * @return bool
    */
    protected function isTokenExpired($expireTime = 0)
    {
        $now = (new \DateTime('now'))->getTimestamp();
        return ($expireTime - $now < 0);
    }

}

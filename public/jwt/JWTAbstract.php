<?php

declare(strict_types=1);

namespace jwt;

class JWTAbstract {

    /**
    * Useful function to create a secure key.
    * This key should preferably be kept in an .env file
    *
    * @return string - random secret key
    * @throws Exception
    */
    public function generateSecretKey()
    {
        $randomBytes = function_exists('random_bytes')
        ? random_bytes(32)
        : openssl_random_pseudo_bytes(32);
        return bin2hex($randomBytes);
    }

    /**
    * Method used to create a base 64 URL encode
    * @param string $text
    * @return string
    */
    public function base64UrlEncode($text)
    {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($text)
        );
    }

    /**
    * obtain the Secret Key from within the .env file
    *
    * @return string - Secret Key
    */
    public static function loadEnvSecret()
    {

        $envPath = $_SERVER['DOCUMENT_ROOT'].'/.env';

        if (!is_readable($envPath)) {
            throw new \Exception("Env unable to load", 1);
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

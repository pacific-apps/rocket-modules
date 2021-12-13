<?php

declare(strict_types=1);

namespace core\http;

class Response
{
    private $statusCode;
    private $exception;

    public function __construct() {
    }

    public function code(
        int $code
        )
    {
        $this->statusCode = $code;
        return $this;
    }

    public function exception(
        string $exception = null
        )
    {
        $this->exception = $exception;
        return $this;
    }

    public function json(
        array $json = null
        )
    {
        header('Content-Type: application/json');
        $json['error'] = $this->exception;
        echo json_encode($json);
        return $this;
    }

    public function send()
    {
        http_response_code($this->statusCode);
    }

    public static function abort(
        string $message = null
        )
    {
        return Self::transmit([
            'code' => 400,
            'exception' => $message ?? 'Invalid request'
        ]);
    }

    public static function unauthorized(
        string $message = null
        )
    {
        return Self::transmit([
            'code' => 401,
            'exception' => $message ?? 'Unauthorized request'
        ]);
    }

    public static function unknown(
        string $message = null
        )
    {
        return Self::transmit([
            'code' => 404,
            'exception' => $message ?? 'Not found'
        ]);
    }

    public static function transmit(
        array $args
        )
    {
        $response = new Response;
        $response->code($args['code'] ?? 200)
                 ->exception($args['exception'] ?? null)
                 ->json($args['payload'] ?? null)
                 ->send();
        return;
    }

    public static function dispatch(
        int $code = 200,
        string $exception = null,
        array $payload = null
        )
    {
        $response = new Response;
        $response->code($code)
                 ->exception($exception)
                 ->json($payload)
                 ->send();
        return;
    }

}

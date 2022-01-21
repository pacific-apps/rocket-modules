<?php

declare(strict_types=1);
namespace glyphic;
use \core\exceptions\BadRequestException;

class RequireApiEndpoint {

    /**
     * @param string method - Method being used in the api call
     * @throws BadRequestException
     */
    public static function method(
        string $method
        )
    {
        if ($_SERVER["REQUEST_METHOD"]!==$method) {
            throw new BadRequestException (
                'Invalid API Method'
            );
        }
    }

    /**
     * @param array $queries
     * @param Closure $closure
     * @throws BadRequestException
     */
    public static function query(
        array $queries,
        \Closure $closure = null
        )
    {

        $request = new \core\http\Request;

        foreach ($queries as $query) {
            $acceptQuery = Self::parseAcceptNotation($request,$query,'query');
            if (!$acceptQuery["result"]) {

                # Modify your API response here if there is no required
                # query being sent together with the request
                throw new BadRequestException (
                    'Invalid query parameter passed'
                );

            }

            if (null!==$closure) {
                $closure($acceptQuery["key"],$acceptQuery["value"]);
            }

        }
    }

    /**
     * @param array $payload
     * @param Closure $closure
     * @throws BadRequestException
     */
    public static function payload(
        array $payloads,
        \Closure $closure = null
        )
    {

        $request = new \core\http\Request;

        foreach ($payloads as $payload) {
            $acceptPayload = Self::parseAcceptNotation($request,$payload,'payload');
            if (!$acceptPayload["result"]) {

                # Modify your API response here if there is no required
                # payload being sent together with the request
                throw new BadRequestException (
                    'Invalid payload sent'
                );

            }

            if (null!==$closure) {
                $closure($acceptPayload["key"],$acceptPayload["value"]);
            }

        }
    }

    private static function parseAcceptNotation(
        \core\http\Request $request,
        string $notation,
        string $type
        )
    {
        # Parsing accept notation
        $notationAssignment = explode("=",$notation);
        $key                = $notationAssignment[0];

        # Checking if the predefined value in the accept notation
        # matches with what has been given during the call
        if (isset($notationAssignment[1])&&isset($request->$type()->$key)) {
            $value = $notationAssignment[1];
            return [
                "result" => ($request->$type()->$key === $value),
                "key" => $key,
                "value" => $value
            ];
        }

        # If no predefined value is given
        return [
            "result" => isset($request->$type()->$key),
            "key" => $key,
            "value" => $request->$type()->$key??null
        ];

    }

    public static function header()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: token, Content-Type');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTION');
        header('Access-Control-Max-Age: 1728000');
        if (!defined('LOCAL')) header('Content-Length: 0');
        header('Access-Control-Allow-Credentials: true');
        if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
            header("HTTP/1.1 200 OK");
            exit();
        }
    }

}

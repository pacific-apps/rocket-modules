<?php

declare(strict_types=1);

namespace core\http;

class Accept {


    public static function method(
        string $method
        )
    {
        if ($_SERVER["REQUEST_METHOD"]!==$method) {

            # Modify your API response here if the method
            # do not match
            return Response::abort();

        }
    }

    public static function query(
        \core\http\Request $request,
        array $queries,
        \Closure $closure = null
        )
    {
        foreach ($queries as $query) {
            $acceptQuery = Self::parseAcceptNotation($request,$query,'query');
            if (!$acceptQuery["result"]) {

                # Modify your API response here if there is no required
                # query being sent together with the request
                return Response::abort("Invalid query");

            }

            if (null!==$closure) {
                $closure($acceptQuery["key"],$acceptQuery["value"]);
            }

        }
    }

    public static function payload(
        \core\http\Request $request,
        array $payloads,
        \Closure $closure = null
        )
    {
        foreach ($payloads as $payload) {
            $acceptPayload = Self::parseAcceptNotation($request,$payload,'payload');
            if (!$acceptPayload["result"]) {

                # Modify your API response here if there is no required
                # payload being sent together with the request
                return Response::abort("Invalid payload");

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


}

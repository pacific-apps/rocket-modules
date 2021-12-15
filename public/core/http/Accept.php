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
        array $queries
        )
    {
        foreach ($queries as $query) {
            if (!Self::parseAcceptNotation($request,$query,'query')) {

                # Modify your API response here if there is no required
                # query being sent together with the request
                return Response::abort("Invalid query");

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
            if (!Self::parseAcceptNotation($request,$payload,'payload',$closure)) {

                # Modify your API response here if there is no required
                # payload being sent together with the request
                return Response::abort("Invalid payload");

            }

        }
    }

    private static function parseAcceptNotation(
        \core\http\Request $request,
        string $notation,
        string $type,
        \Closure $closure = null
        )
    {
        $notationAssignment = explode("=",$notation);
        $key = $notationAssignment[0];
        if (isset($notationAssignment[1])&&isset($request->$type()->$key)) {
            $value = $notationAssignment[1];
            if (null!==$closure) {
                $closure($notationAssignment[0],$notationAssignment[1]);
            }
            return ($request->$type()->$key === $value);
        }
        if (null!==$closure) {
            $closure($notationAssignment[0],$request->$type()->$key??null);
        }
        return isset($request->$type()->$key);
    }


}

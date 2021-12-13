<?php

declare(strict_types=1);

namespace core\http;

class Parser {

    protected static function parseQuery(
        Factory $factory,
        string $query
        )
    {
        parse_str($query, $data);
        if (empty($data)) return;
        Self::map (
            data: $data,
            object: $factory->query()
        );
    }

    protected static function parsePayload (
        Factory $factory,
        string $payload
        )
    {
        $data = json_decode(urldecode($payload));

        $factory->setPayload($data ?? new Class {});

        /*
        # Uses parse_str for url-encoded form data
        if (!is_array($data)) parse_str($payload, $data);

        Self::map (
            data: $data,
            object: $factory->payload()
        );
        */
        return;
    }

    protected static function parseFiles (
        Factory $factory,
        array $files
        )
    {
        Self::map(
            data: $files,
            object: $factory->files()
        );
        return;
    }

    private static function map (
        array $data,
        Object $object
        )
    {
        foreach ($data as $key => $value) {
            $object->$key = htmlspecialchars($value);
        }
    }


}

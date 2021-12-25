<?php

namespace glyphic\tools;

class IdGenerator
{

    public const NUMERIC = "1234567890";
    public const ALPHANUMERIC = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    public const BETANUMERIC = "1234567890abcdef";

    public static function create32bitKey(
        string $type = Self::NUMERIC
        )
    {
        return Self::createUniqueKey(4,$type);
    }

    public static function create64bitKey(
        string $type = Self::NUMERIC
        )
    {
        return Self::createUniqueKey(12,$type);
    }

    public static function createUniqueKey(
        int $def,
        string $type
       )
    {
        $randoms   = [
            (date("Y")-2000),
            (date("m")*1),
            (date("d")*1),
            substr(str_shuffle(time().$type),(-10))
        ];
        $uniqueKey = "";
        foreach ($randoms as $key) {
            $uniqueKey = $uniqueKey.Self::subshuffle($type,$def).$key;
        }
        return $uniqueKey;
    }

    public static function subshuffle(
        string $type,
        int $def
        )
    {
        return substr(str_shuffle($type),(-1*$def));
    }

}

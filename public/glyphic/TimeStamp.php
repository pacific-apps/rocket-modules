<?php

declare(strict_types=1);
namespace glyphic;

class TimeStamp
{

    # Returns the current timestamp in the time zone
    public static function now (
        string $format = 'Y-m-d H:i:s'
        )
    {
        $date = new \DateTime(
            "now",
            new \DateTimeZone("Asia/Manila")
        );
        return $date->format($format);
    }

    public static function add (
        string $date,
        string $addParam
        )
    {
        return (new \DateTime($date))
                ->modify("+{$addParam}")
                ->format("Y-m-d H:i:s");
    }
}

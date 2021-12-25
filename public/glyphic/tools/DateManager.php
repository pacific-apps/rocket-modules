<?php

namespace glyphic\tools;

class DateManager
{

    # Returns the current timestamp in the time zone
    public static function now (
        string $format
        )
    {
        $date = new \DateTime(
            "now",
            new \DateTimeZone("Asia/Manila")
        );
        return $date->format($format);
    }
}

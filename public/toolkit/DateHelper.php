<?php

namespace toolkit;

class DateHelper
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

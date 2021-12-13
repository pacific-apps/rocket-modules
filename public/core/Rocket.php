<?php

declare(strict_types=1);
namespace core;
use \core\Config;

class Rocket {

    public static function root()
    {
        return $_SERVER['DOCUMENT_ROOT'];
    }

    public static function now()
    {
        $date = new \DateTime("now", new \DateTimeZone(Config::APP_TIMEZONE));
        return $date->format(Config::DATE_FORMAT);
    }

}

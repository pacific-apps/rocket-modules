<?php

declare(strict_types=1);
namespace glyphic\core;

use \glyphic\core\Config as GlyphicConfig;

class Installer {

    private $config;

    public static function hasInstance()
    {
        $config = new GlyphicConfig();
        
        return true;
    }

}

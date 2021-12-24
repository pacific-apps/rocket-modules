<?php

declare(strict_types=1);
namespace glyphic\core;

use \core\http\Response;

class Config {

    public function __construct()
    {
        try {
            $path = ROOT.'/data/glyphic/config.json';
            if (!file_exists($path)) {
                throw new \Exception('Glyphic config not found at: '.$path, 1);
            }
            $config = json_decode(file_get_contents(ROOT.'/data/glyphic/config.json'),TRUE);
        } catch (\Exception $e) {
            Response::error('Internal Server Error');
        }
    }
}

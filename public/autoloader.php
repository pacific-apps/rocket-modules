<?php
spl_autoload_register ( 'autoloader' );
function autoloader ( string $class ) {
    $path = __DIR__.'/'.str_replace('\\', '/', $class).'.php';
    if (file_exists($path)) require_once $path;
}

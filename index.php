<?php

/**
 * Used for PHP built-in server
 * NOTE: For testing only!
 *
 * php -S 127.0.0.1:8000 index.php if using VPN
 */

chdir(__dir__);
define('SROOT',__dir__);
define('URI',$_SERVER['REQUEST_URI']);
define('LOCAL',true);

$endpoints = explode('/',explode('?',URI)[0]);
$endpoint  = explode('.',end($endpoints))[0];
array_pop($endpoints);
$resolvedUri = implode('/',$endpoints);

$public   = SROOT.'/local';
$resource = $public.$resolvedUri.'/'.$endpoint.'.php';
if (!file_exists($resource)) {
    require $public.'/404.php';
    exit();
}

require $resource;

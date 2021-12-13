<?php

declare(strict_types=1);

ini_set('error_reporting','E_ALL');
ini_set( 'display_errors','1');

require '../../imports.php';

$request = new \core\http\Request;
$response = new \core\http\Response;

echo $request->query()->name;

<?php

declare(strict_types=1);

require '../../imports.php';

$request = new \core\http\Request;
$response = new \core\http\Response;

use \jwt\Token;

var_dump(Token::verify($request->query()->token));

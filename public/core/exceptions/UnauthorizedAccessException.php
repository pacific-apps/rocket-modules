<?php
declare(strict_types=1);
namespace core\exceptions;
use core\exceptions\RocketExceptionsInterface;

class UnauthorizedAccessException extends \Exception implements RocketExceptionsInterface {

    public function code()
    {
        return 401;
    }

    public function exception()
    {
        return 'Unauthorized';
    }

}

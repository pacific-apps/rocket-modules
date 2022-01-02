<?php
declare(strict_types=1);
namespace core\exceptions;
use core\exceptions\RocketExceptionsInterface;

class AlreadyExistsException extends \Exception implements RocketExceptionsInterface {

    public function code()
    {
        return 409;
    }

    public function exception()
    {
        return 'Conflict';
    }

}

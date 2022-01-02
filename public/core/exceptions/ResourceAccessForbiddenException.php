<?php
declare(strict_types=1);
namespace core\exceptions;
use core\exceptions\RocketExceptionsInterface;

class ResourceAccessForbiddenException extends \Exception implements RocketExceptionsInterface {

    public function code()
    {
        return 403;
    }

    public function exception()
    {
        return 'Forbidden';
    }

}

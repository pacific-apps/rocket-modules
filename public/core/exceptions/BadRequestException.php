<?php
declare(strict_types=1);
namespace core\exceptions;
use core\exceptions\RocketExceptionsInterface;

class BadRequestException extends \Exception implements RocketExceptionsInterface {

    public function code()
    {
        return 400;
    }

    public function exception()
    {
        return 'Bad Request';
    }

}

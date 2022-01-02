<?php
declare(strict_types=1);
namespace core\exceptions;
use core\exceptions\RocketExceptionsInterface;

class RecordNotFoundException extends \Exception implements RocketExceptionsInterface {

    public function code()
    {
        return 404;
    }

    public function exception()
    {
        return 'Not Found';
    }

}

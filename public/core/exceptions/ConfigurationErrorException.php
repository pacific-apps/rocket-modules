<?php
declare(strict_types=1);
namespace core\exceptions;
use core\exceptions\RocketExceptionsInterface;

class ConfigurationErrorException extends \Exception implements RocketExceptionsInterface {

    public function code()
    {
        return 500;
    }

    public function exception()
    {
        return 'Configuration Error';
    }

}

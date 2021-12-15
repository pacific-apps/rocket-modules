<?php

declare(strict_types=1);

namespace core\http;
use \core\http\Parser;
use \core\http\Factory;

class Request extends Parser
{

    private object $factory;

    public function __construct()
    {

        $this->factory = new Factory;
        $this->bootQuery();
        $this->bootPayload();
        $this->bootFiles();
    }

    public function query()
    {
        return $this->factory->query();
    }

    public function payload(
        string $payload = null
        )
    {
        # Returns the entire payload object if no argument is passed
        if (null===$payload) return $this->factory->payload();

        # Returns FALSE if the no payload under the name key: $payload is passed
        if (!isset($this->factory->payload()->$payload)) {
            \core\http\Response::abort();
            exit();
        }

        # Returns the actual value of the payload based on the key $payload;
        return $this->factory->payload()->$payload;
    }

    private function bootQuery()
    {
        Parser::parseQuery(
            factory: $this->factory,
            query: $_SERVER["QUERY_STRING"]
        );
    }

    private function bootPayload()
    {
        Parser::parsePayload(
            factory: $this->factory,
            payload: file_get_contents('php://input')
        );
    }

    private function bootFiles()
    {
        Parser::parseFiles(
           factory: $this->factory,
           files: $_FILES
       );
    }

    public static function method(
        string $method = null
        )
    {
        if (null===$method) return $_SERVER["REQUEST_METHOD"];
        return ($_SERVER["REQUEST_METHOD"]===$method);
    }


}

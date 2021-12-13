<?php

declare(strict_types=1);

namespace core\http;

class Factory
{

    private object $query;
    private object $payload;
    private object $files;

    public function __construct()
    {
        $this->query = new Class {};
        $this->payload = new Class {};
        $this->files = new Class {};
    }

    public function query()
    {
        return $this->query;
    }

    public function payload()
    {
        return $this->payload;
    }

    public function setPayload(
        object $payload
        )
    {
        $this->payload = $payload;
    }

    public function files()
    {
        return $this->files;
    }
}

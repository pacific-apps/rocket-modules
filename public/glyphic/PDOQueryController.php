<?php

declare(strict_types=1);
namespace glyphic;

class PDOQueryController
{

    public function __construct(
        string $pSqlQueryString
        )
    {
        $this->pdoDriver = new \PDO("mysql:host=".getenv('GLYPHIC_HOST').";dbname=".getenv('GLYPHIC_DATABASE'), getenv('GLYPHIC_USERNAME'), getenv('GLYPHIC_PASSWORD'));
        $this->pdoDriver->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdoStatement = $this->pdoDriver->prepare($pSqlQueryString);
    }

    public function param(
        string $forPrep,
        $data
        )
    {
        $this->pdoStatement->bindParam($forPrep,$value);
        $value = $data;
        return $this;
    }

    public function prepare(
        array $data
        )
    {
        foreach ($data as $key => $value) {
            $this->param($key,$value);
        }
        return $this;
    }

    public function post()
    {
        return $this->pdoStatement->execute();
    }

    public function get()
    {
        $this->pdoStatement->execute();
        $this->pdoStatement->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $this->pdoStatement->fetchAll();
        if (empty($result)) {
            return [
                'hasRecord' => false
            ];
        }
        else {
            $result[0]['hasRecord'] = true;
            return $result[0];
        }
    }




}

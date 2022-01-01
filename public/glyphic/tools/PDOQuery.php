<?php

namespace glyphic\tools;

class PDOQuery {

    private $pSqlPath;
    private $pdoDriver;
    private $pdoStatement;

    public function __construct(
        string $pSqlFile,
        bool $isQueryString = false
        )
    {
        $this->pSqlPath  = ROOT."/data/glyphic/queries/{$pSqlFile}.psql";
        $this->pdoDriver = new \PDO("mysql:host=".getenv('GLYPHIC_HOST').";dbname=".getenv('GLYPHIC_DATABASE'), getenv('GLYPHIC_USERNAME'), getenv('GLYPHIC_PASSWORD'));
        $this->pdoDriver->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        if (!$isQueryString) {
            if (!file_exists($this->pSqlPath)) {
                throw new \Exception("Unable to locate psql file: {$this->pSqlPath}");
            }
            $this->pdoStatement = $this->pdoDriver->prepare(file_get_contents($this->pSqlPath));
        }
        else {
            $this->pdoStatement = $this->pdoDriver->prepare($pSqlFile);
        }

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

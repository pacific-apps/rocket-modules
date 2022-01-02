<?php

declare(strict_types=1);
namespace glyphic\models;
use \glyphic\PDOQueryController;
use \glyphic\QueryBuilder;

class Table {

    private $tableName;
    private $doExist;

    public function __construct(
        string $tableName,
        string $flag = null
        )
    {
        $this->tableName = $tableName;
        $this->doExist = false;
        if ($flag==='GET') $this->get();
    }

    public function get()
    {
        $query = new PDOQueryController($this->getQuery());
        $query->prepare([
            ':dbName' => getenv('GLYPHIC_DATABASE'),
            ':tableName' => $this->tableName
        ]);
        $result = $query->get();
        if (!$result['hasRecord']) {
            $this->doExist = false;
            return;
        }
        $this->doExist = true;
    }

    public function create(
        string $sqlFilePath
        )
    {
        $query = new PDOQueryController(
            (new QueryBuilder($sqlFilePath))->build()
        );
        $query->post();
    }

    public function doExist()
    {
        return $this->doExist;
    }

    public function getQuery()
    {
        return "
            SELECT *
            FROM information_schema.tables
            WHERE table_schema = :dbName
                AND table_name = :tableName
            LIMIT 1;
        ";
    }



}

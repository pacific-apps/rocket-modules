<?php

namespace toolkit;

class PsqlBuilder
{

    private $storagePath;
    private $psqlPath;
    private $data;

    public function __construct()
    {
        $this->storagePath = $_SERVER["DOCUMENT_ROOT"]."/psql/";
    }

    public function use(
        string $psqlFile
        )
    {
        $this->psqlPath = $this->storagePath.$psqlFile.'.psql';
    }

    public function data(
        array $data
        )
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->parse(
            $this->data,
            file_get_contents($this->psqlPath)
        );
    }

    private static function parse(array $data, string $tmp)
    {
        $doExist = true;
        while ($doExist) {
            preg_match('#\{{(.*?)\}}#', $tmp, $res);
            if (!isset($res[1])) {
                $doExist = false;
                break;
            }
            $tmp = Self::release($data, $tmp, $res);
        }
        return $tmp;
    }

    private static function release(array $data, string $tmp, array $res): string
    {
        return str_replace($res[0], $data[$res[1]] ?? " ", $tmp);
    }



}

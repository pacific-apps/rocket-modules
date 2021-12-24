<?php

namespace toolkit;

class PsqlBuilder
{

    private $storagePath;
    private $psqlPath;
    private $data;
    private $setArguments;

    public function __construct()
    {
        $this->storagePath = $_SERVER["DOCUMENT_ROOT"]."/psql/";
        $this->setArguments = null;
    }

    public function use(
        string $psqlFile
        )
    {
        $this->psqlPath = $this->storagePath.$psqlFile.'.psql';
        return $this;
    }

    public function data(
        array $data
        )
    {
        $this->data = $data;
        $this->data['setArguments'] = $this->setArguments;
        return $this;
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
        return str_replace($res[0], $data[$res[1]] ?? 'NULL', $tmp);
    }

    public function set(
        array $args
        )
    {
        $i = 0;
        $tmp = '';
        # Requires associative array
        foreach ($args as $key => $value) {
            if ($i>0) {
                $tmp = $tmp.', ';
            }
            $tmp = $tmp.$key.'='.$value;
            $i++;
        }
        $this->setArguments = $tmp;
        return $this;
    }


}

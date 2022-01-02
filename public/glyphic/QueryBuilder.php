<?php

declare(strict_types=1);
namespace glyphic;
use \core\exceptions\ConfigurationErrorException;

class QueryBuilder
{

    private $storagePath;
    private $pSqlPath;
    private $data;
    private $setArguments;

    public function __construct(
        string $pSqlFile = '',
        array $data = null
        )
    {
        $this->storagePath  = ROOT."/data/glyphic/queries/";
        $this->pSqlPath     = $this->storagePath.$pSqlFile;

        if (!file_exists($this->pSqlPath.'.psql')) {
            throw new ConfigurationErrorException(
                'Unable to locate pSql File: '.$this->pSqlPath
            );
        }

        $this->setArguments = null;
        $this->data = $data ?? [];

    }

    public function use(
        string $pSqlFile
        )
    {
        $this->pSqlPath = $this->storagePath.$pSqlFile;
        return $this;
    }

    public function data(
        array $data
        )
    {
        $this->data = $data;
        return $this;
    }

    public function build()
    {
        $this->data['setArguments'] = $this->setArguments;
        return $this->parse(
            $this->data,
            file_get_contents($this->pSqlPath.'.psql')
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
            $tmp = $tmp.$key.' = '.$value;
            $i++;
        }
        $this->setArguments = $tmp;
        return $this;
    }


}

<?php

namespace glyphic;
use \core\exceptions\BadRequestException;

class TypeOf
{

    public static function alpha (
        string $label,
        $data,
        string $flag = null
        )
    {
        if ($flag==='NULLABLE'&&null===$data) {
            return null;
        }

        if (!preg_match('/^[a-zA-Z]+$/', $data)) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function alphanum (
        string $label,
        $data,
        string $flag = null
        )
    {

        if ($flag==='NULLABLE'&&null===$data) {
            return null;
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $data)) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function all (
        string $label,
        $data,
        string $flag = null
        )
    {

        if ($flag==='NULLABLE'&&null===$data) {
            return null;
        }

        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function integer (
        string $label,
        $data,
        string $flag = null
        )
    {

        if ($flag==='NULLABLE'&&null===$data) {
            return null;
        }

        if (!is_numeric($data)) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }
        return (int)$data;
    }

    public static function url (
        string $label,
        $data,
        string $flag = null
        )
    {

        if ($flag==='NULLABLE'&&null===$data) {
            return null;
        }

        if (!filter_var($data, FILTER_VALIDATE_URL)) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }

        return $data;
    }

    public static function email (
        string $label,
        $data,
        string $flag = null
        )
    {
        if ($flag==='NULLABLE'&&null===$data) {
            return null;
        }

        if (!str_contains($data,'@')) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }
        if (!str_contains($data,'.')) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }
        if (!preg_match('/^[a-zA-Z0-9.@]+$/', $data)) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function fullname (
        string $label,
        $data,
        string $flag = null
        )
    {
        if ($flag==='NULLABLE'&&null===$data) {
            return null;
        }

        if (!preg_match('/^[a-zA-Z ]+$/', $data)) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function alphanumwithspace (
        string $label,
        $data,
        string $flag = null
        )
    {

        if ($flag==='NULLABLE'&&null===$data) {
            return null;
        }

        if (!preg_match('/^[a-zA-Z0-9 ]+$/', $data)) {
            throw new BadRequestException (
                'Invalid Data Type: '.$label
            );
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    }



    public static function escaped(
        string $label,
        $data
        )
    {
        $escs = Self::getEsc();
        foreach ($escs as $esc => $val) {
            $data = str_replace($esc,$val,$data);
        }
        return $data;
    }


    public static function getEsc()
    {
        return [
            ";"=>"8H16QGu",
            "'"=>"rNVtfST",
            "/"=>"fkQiGMt",
            "\/"=>"COZoVaT",
            '"'=>"6ReKoZm",
            "."=>"CdRWWZ3",
            ","=>"nPybl1A",
            "="=>"uo0HjRg",
            "-"=>"pRYg95l"
        ];
    }

    public static function isAlphanum( $data ) {
        return preg_match('/^[a-zA-Z0-9]+$/', $data);
    }

    public static function isLink ( $data ) {
        return preg_match('/^[a-zA-Z\-:\/.]+$/', $data);
    }



    public static function isStreetAddress ( $data ) {
        return preg_match('/^[a-zA-Z0-9. ]+$/', $data);
    }

    public static function isEmail( $data ) {
        if (!str_contains($data,'@')) return false;
        if (!str_contains($data,'.')) return false;
        return preg_match('/^[a-zA-Z0-9.@]+$/', $data);
    }

    public static function isUEmail( $data ) {
        return preg_match('/^[a-zA-Z0-9.@]+$/', $data);
    }

}

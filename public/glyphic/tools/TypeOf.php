<?php

namespace glyphic\tools;
use \core\http\Response;

class TypeOf
{

    public static function alpha (
        string $label,
        $data
        )
    {
        if (!preg_match('/^[a-zA-Z]+$/', $data)) {
            Response::abort("Invalid data type: {$label}");
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function alphanum (
        string $label,
        $data
        )
    {
        if (!preg_match('/^[a-zA-Z0-9]+$/', $data)) {
            Response::abort("Invalid data type: {$label}");
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function all (
        string $label,
        $data
        )
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function integer (
        string $label,
        $data
        )
    {
        if (!is_numeric($data)) {
            Response::abort("Invalid data type: {$label}");
        }
        return (int)$data;
    }

    public static function email (
        string $label,
        $data
        )
    {
        if (!str_contains($data,'@')) {
            Response::abort("Invalid email format: {$data}");
        }
        if (!str_contains($data,'.')) {
            Response::abort("Invalid email format: {$data}");
        }
        if (!preg_match('/^[a-zA-Z0-9.@]+$/', $data)) {
            Response::abort("Invalid email format: {$data}");
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

<?php

namespace glyphic\tools;

class MySQLDatabase {

     /**
     * Returns an array with has_record key either set to true or false
     * @param string $query
     * @return array
     *
     * @since v1.0.0
     */
    public static function get($query,$flag=null)
    {
        $data = [];
        $result = Self::connect()->query($query);

        if (!$result) {
            $data["hasRecord"] = false;
        }
        else {
            if ($result->num_rows === 0 || $result->num_rows === '0') {
                $data["hasRecord"] = false;
            }
            else {
                $data["hasRecord"] = true;
                $rows = $result->fetch_assoc();
                foreach ($rows as $key => $value) {
                    $data[$key] = $value;
                }
            }
        }

        if (null===$flag) {
            echo "string";
            return $data;
        }

        if ('--OBJECT'===$flag) {
            $result = new Class{};
            foreach ($data as $key => $value) {
                $result->$key = $value;
            }
            return $result;
        }

    }

    /**
     * Checks if a certain row exists in a table based on a certain condition
     * @param string $query
     * @return bool
     */
    public static function doExist($query)
    {
        $result = Self::connect()->query($query);
        if (false===$result) {
            return false;
        }
        return filter_var(
            $result->fetch_row()[0],
            FILTER_VALIDATE_BOOLEAN
        );
    }


    /**
     * Runs query, but takes no data from the database
     * @param string $query
     * @return bool
     *
     * @since v1.0.0
     */
    public static function save($query)
    {
        return Self::connect()->query($query);
    }

    private static function connect()
    {
        return new \mysqli(
            getenv('GLYPHIC_HOST'),
            getenv('GLYPHIC_USERNAME'),
            getenv('GLYPHIC_PASSWORD'),
            getenv('GLYPHIC_DATABASE'));
    }


}

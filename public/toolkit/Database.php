<?php

    namespace toolkit;

    class Database {

        /**
         * All PeopleORM Configuration should be modified here
         */

         /**
          * @var string
          */
         public const SERVER_NAME = "localhost";

         /**
          * @var string
          */
         public const USERNAME = "root";

         /**
          * @var string
          */
         public const DATABASE_NAME = "rocket_db";

         /**
          * @var string
          */
         public const PASSWORD = "";

         /**
         * Returns an array with has_record key either set to true or false
         * @param string $query
         * @return array
         *
         * @since v1.0.0
         */
        public static function get($query)
        {
            $data = [];
            $result = Self::connect()->query($query);

            if (!$result) {
                $data["hasRecord"] = false;
                return $data;
            }

            if ($result->num_rows === 0 || $result->num_rows === '0') {
                $data["hasRecord"] = false;
                return $data;
            }
            $data["hasRecord"] = true;
            $rows = $result->fetch_assoc();
            foreach ($rows as $key => $value) {
                $data[$key] = $value;
            }

            return $data;
        }

        /**
         * Checks if a certain row exists in a table based on a certain condition
         * @param string $query
         * @return bool 
         */
        public static function doExist($query)
        {
            return filter_var(
                Self::connect()->query($query)->fetch_row()[0],
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
                Self::SERVER_NAME,
                Self::USERNAME,
                Self::PASSWORD,
                Self::DATABASE_NAME);
        }


    }

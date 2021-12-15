<?php

    namespace toolkit;

    class Database {

        /**
         * All PeopleORM Configuration should be modified here
         */

         /**
          * @var string
          */
         protected const SERVER_NAME = "localhost";

         /**
          * @var string
          */
         protected const USERNAME = "root";

         /**
          * @var string
          */
         protected const DATABASE_NAME = "lake_prod_db";

         /**
          * @var string
          */
         protected const PASSWORD = "";

         /**
         * Returns an array with has_record key either set to true or false
         * @param string $query
         * @return array
         *
         * @since v1.0.0
         */
        protected static function get($query)
        {
            $data = [];
            $result = Self::connect()->query($query);
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
         * Runs query, but takes no data from the database
         * @param string $query
         * @return bool
         *
         * @since v1.0.0
         */
        protected static function save($query)
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

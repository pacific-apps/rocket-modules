<?php

    namespace toolkit;

    class Validator {

        public static function isCorrectData ( string $dataType, $data ) {
            switch ( $dataType ) {
                case 'alpha':
                    # Accepts only alphabet characters
                    return Self::isAlpha($data);
                    break;

                case 'link':
                    return Self::isLink($data);
                    break;

                case 'number':
                    # Accepts ONLY numeric characters
                    return Self::isNumeric($data);
                    break;

                case 'streetaddress':
                    return Self::isStreetAddress($data);
                    break;

                case 'email':
                    return Self::isEmail($data);
                    break;

                case 'alphanum':
                    # Accepts EITHER alphabet or numeric characters
                    return Self::isAlphanum($data);
                    break;

                case 'uemail':
                    return Self::isUEmail($data);
                    break;

                default:
                    return false;
                    break;
            }
        }

        private static function isAlpha ( $data ): bool {
            return preg_match('/^[a-zA-Z]+$/', $data);
        }

        private static function isAlphanum( $data ): bool {
            return preg_match('/^[a-zA-Z0-9]+$/', $data);
        }

        private static function isLink ( $data ): bool {
            return preg_match('/^[a-zA-Z\-:\/.]+$/', $data);
        }

        private static function isNumeric ( $data ): bool {
            return is_numeric($data);
        }

        private static function isStreetAddress ( $data ): bool {
            return preg_match('/^[a-zA-Z0-9. ]+$/', $data);
        }

        private static function isEmail( $data ): bool {
            if (!str_contains($data,'@')) return false;
            if (!str_contains($data,'.')) return false;
            return preg_match('/^[a-zA-Z0-9.@]+$/', $data);
        }

        private static function isUEmail( $data ): bool {
            return preg_match('/^[a-zA-Z0-9.@]+$/', $data);
        }

    }

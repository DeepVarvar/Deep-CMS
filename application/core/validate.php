<?php



/**
 * validation class
 */

abstract class validate {


    /**
     * check value for is number,
     * from 0 to +infinity
     */

    public static function isNumber($value) {

        $value = (string) $value;
        if ($value === "0") {
            return true;
        }

        return (substr(trim($value), 0, 1) === "0")
                    ? false : preg_match("/^\d+$/", $value);

    }


    /**
     * check value for like string
     */

    public static function likeString($v) {
        return ($v !== null and !is_array($v) and !is_object($v) and !is_resource($v) and !is_bool($v));
    }


}




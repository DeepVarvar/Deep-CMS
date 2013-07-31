<?php



/**
 * text plain output context builder class
 */

abstract class textPlainOutput {


    /**
     * build plain text output string
     */

    public static function buildString($vars, $lastPad = 0) {


        /**
         * fix input data format
         */

        if (!is_array($vars)) {
            $vars = array($vars);
        }


        $output = "";
        $currentPad = self::getPadSize(array_keys($vars));
        $leftPad = str_repeat(" ", $lastPad);


        foreach ($vars as $k => $v) {


            if (is_object($v)) {
                $v = (array) $v;
            }


            $k = (utils::isNumber($k)) ? "" : ($k . ": ");
            $output .= EOL . $leftPad . str_pad($k, $currentPad, " ", STR_PAD_RIGHT);

            if (is_array($v)) {
                $output .= self::buildString($v, $currentPad);
            } else {
                $output .= $v;
            }


        }


        return $output;


    }


    /**
     * return max padding value
     */

    private static function getPadSize($names) {


        $len = array();
        foreach ($names as $name) {
            array_push($len, mb_strlen($name, "UTF-8"));
        }

        return max($len) + 2;


    }


}




<?php



/**
 * main helper class
 */

abstract class helper {


    protected static


        /**
         * helper custom strftime (strfDateTime) current timestamp
         */

        $strfTimeStamp = null;


    /**
     * crible crable booms!
     * return triple md5 hash of string
     */

    public static function getHash($str) {
        return md5(md5(md5($str)));
    }


    /**
     * return current value of $strfTimeStamp
     */

    public static function getCurrentStrfTimeStamp() {
        return self::$strfTimeStamp;
    }


    /**
     * strftime with plural on language
     */

    public static function strfTime($format, $string) {


        if (!utils::isNumber($string)) {

            if (!$string = @ strtotime($string)) {
                throw new systemErrorException("Helper error", "String is not date or time");
            }

        }


        if (!utils::isNumber($string)) {
            throw new systemErrorException("Helper error", "Timestamp is not number");
        }


        $formattedString = @ strftime($format, $string);
        self::$strfTimeStamp = $string;

        return preg_replace_callback("/%\w/", "strftimeReplaceCallback", $formattedString);


    }


    /**
     * return humanity bytes size
     */

    public static function humanityByteSize($size) {


        /**
         * set types, fix input data
         */

        $types = array("B", "KiB", "MiB", "GiB", "TiB");
        $size = $size < 1 ? 1 : (int) $size;


        /**
         * maybe need division by 1000?
         * see http://en.wikipedia.org/wiki/Binary_prefix
         */

        return round( $size/pow(1024, ($type = floor(log($size, 1024)))) , 1 ) . " " . $types[$type];


    }


    /**
     * get memory usage of script
     */

    public static function getMemoryUsage() {
        $size = memory_get_peak_usage() - view::getInitializedMemoryValue();
        return "~" . self::humanityByteSize($size) . " ({$size} bytes)";
    }


    /**
     * get generation time of script
     */

    public static function getGenerationTime() {
        return round(microtime(true) - view::getInitializedTimerValue(), 3);
    }


    /**
     * return multidimensional array
     */

    public static function makeTreeArray(& $lineArray, $parent = 0) {


        $branch = array();
        if ($lineArray) {


            foreach ($lineArray as $k => $item) {


                if ($item['parent_id'] == $parent) {


                    /**
                     * unset array item
                     */

                    unset($lineArray[$k]);


                    $current = array(

                        "children"   => self::makeTreeArray($lineArray, $item['id']),
                        "page_alias" => $item['page_alias'],
                        "page_name"  => $item['page_name']

                    );


                    array_push($branch, $current);


                }

            }


        }


        return $branch;


    }


    /**
     * return plural name of number
     */

    public static function plural($n, $f1, $f3, $f5) {


        if (!utils::isNumber($n)) {
            throw new systemErrorException("Helper error", "Plural argument is not number");
        }


        return $n%10==1&&$n%100!=11?$f1:($n%10>=2&&$n%10<=4&&($n%100<10||$n%100>=20)?$f3:$f5);


    }


    /**
     * change original ULR string with input parameters
     */

    public static function changeOriginURL($newParams) {


        if (!is_array($newParams)) {
            throw new systemErrorException("Helper error", "URL parameters is not array");
        }


        $parts = explode("?", request::getOriginURL());
        array_shift($parts);

        $query = join("", $parts);
        parse_str($query, $parts);

        $parts = array_merge($parts, $newParams);
        $query = http_build_query($parts);


        return request::getURI() . ($query ? "?{$query}" : "");


    }


}



/**
 * strftime replace callback,
 * use function because need compatible
 * for php versions older than 5.2.3
 */

function strftimeReplaceCallback($pattern) {


    $pattern = $pattern[0];
    $timestamp = helper::getCurrentStrfTimeStamp();
    $output = "[undefined pattern {$pattern}]";

    switch ($pattern) {


        /**
         * set plural month name
         */

        case "%B":

            $monthNumber = date("n", $timestamp);
            $output = view::$language->{"%B"}[$monthNumber];

        break;


        /**
         * set one number month format
         */

        case "%j":
            $output = date("j", $timestamp);
        break;


    }


    return $output;


}



<?php


/**
 * main helper class
 */

abstract class helper {


    /**
     * helper custom strftime (strfDateTime) current timestamp
     */

    protected static $strfTimeStamp = null;


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

        if (!validate::isNumber($string) or !$string = @ strtotime($string)) {
            throw new systemErrorException(
                'Helper error', 'String is not date or time'
            );
        }

        if (!validate::isNumber($string)) {
            throw new systemErrorException(
                'Helper error', 'Timestamp is not number'
            );
        }

        $formattedString = @ strftime($format, $string);
        self::$strfTimeStamp = $string;

        return preg_replace_callback(
            '/%\w/', 'strftimeReplaceCallback', $formattedString
        );

    }


    /**
     * return humanity bytes size,
     * maybe need division by 1000?
     * see http://en.wikipedia.org/wiki/Binary_prefix
     */

    public static function humanityByteSize($size) {

        $types = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
        $size = $size < 1 ? 1 : (int) $size;
        return round($size/pow(1024,
            ($type = floor(log($size, 1024)))) , 1 ) . ' ' . $types[$type];

    }


    /**
     * get memory usage of script
     */

    public static function getMemoryUsage() {
        $size = memory_get_peak_usage() - view::getInitializedMemoryValue();
        return '~' . self::humanityByteSize($size) . ' (' . $size . ' bytes)';
    }


    /**
     * get generation time of script
     */

    public static function getGenerationTime() {
        return round(microtime(true) - view::getInitializedTimerValue(), 3);
    }


    /**
     * return plural name of number
     */

    public static function plural($n, $f1, $f3, $f5) {

        if (!validate::isNumber($n)) {
            throw new systemErrorException(
                'Helper error', 'Plural argument is not number'
            );
        }

        return $n%10==1&&$n%100!=11?$f1:
                    ($n%10>=2&&$n%10<=4&&($n%100<10||$n%100>=20)?$f3:$f5);

    }


    /**
     * wordwrap multibytes string
     */

    public static function wordWrap($inputString, $limit = 10) {

        $outputString = '';
        $pattern = "/[^\r\S]?[^\n\S]+/u";
        $words = preg_split($pattern, $inputString, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $wordLen = mb_strlen($word);
            if ($wordLen <= $limit) {
                $outputString .= $word . ' ';
            } else {
                $swLen = ceil($wordLen / $limit);
                $pos = 0;
                while ($swLen > 0 or $pos < $wordLen) {
                    $outputString .= mb_substr($word, $pos, $limit) . ' ';
                    $pos += $limit;
                    $swLen --;
                }
            }
        }

        return trim($outputString);

    }


    /**
     * content preview, text limiter
     */

    public static function contentPreview($inputString, $limit = 200) {

        $inputString = strip_tags($inputString);
        $inputString = preg_replace('/\s+|&nbsp;/', ' ', $inputString);
        return preg_match('#^(.{' . $limit . ',}?)\s+#su', $inputString, $match)
            ? rtrim($match[1], ',.!?') . '...' : $inputString;

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
    $output = '[undefined pattern ' . $pattern . ']';

    switch ($pattern) {

        // plural month name
        case '%B':
            $monthNumber = date('n', $timestamp);
            $output = view::$language->{'%B'}[$monthNumber];
        break;

        // one number month format
        case '%j':
            $output = date('j', $timestamp);
        break;

    }

    return $output;

}



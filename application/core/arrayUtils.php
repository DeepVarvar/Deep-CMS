<?php


/**
 * array utilites
 */

abstract class arrayUtils {


    /**
     * recursive change array key case
     */

    public static function
        arrayChangeKeyCaseRecursive($arr, $type = CASE_LOWER) {

        foreach ($arr as $k => $item) {
            if (is_array($item)) {
                $arr[$k] = self::arrayChangeKeyCaseRecursive($item);
            }
        }
        return array_change_key_case($arr, $type);

    }


    /**
     * this function exists only for autoload call
     * and compatible php version older than 5.2.3
     */

    public static function loadSortArrays() {}


}


/**
 * sort arrays callback,
 * use function because need compatible
 * for php versions older than 5.2.3
 */

function sortArrays($a, $b) {
    return $a['sort'] == $b['sort'] ? 0 : ($a['sort'] < $b['sort'] ? -1 : 1);
}



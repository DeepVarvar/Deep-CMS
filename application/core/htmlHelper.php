<?php



/**
 * helper html elements class
 */

abstract class htmlHelper {


    /**
     * draw multidimentional ul-li-a links list by level
     */

    public static function drawTreeLinksList($arr) {

        $output = '';
        $lvl    = null;

        foreach ($arr as $k => $i) {

            if ($lvl === null) {

                $output .= ' <ul> <li> ' . $i['node_name'];
                $lvl     = $i['lvl'];
                $first   = $lvl;

            } else if ($lvl < $i['lvl']) {

                $lvl     = $i['lvl'];
                $output .= ' <ul> <li> ' . $i['node_name'];

            } else if ($lvl > $i['lvl']) {

                $diff    = $lvl - $i['lvl'];
                $lvl     = $i['lvl'];
                $output .= ' </li> ' . str_repeat(' </ul> </li> ', $diff);
                $output .= ' <li> ' . $i['node_name'];

            } else {
                $output .= ' </li> <li> ' . $i['node_name'];
            }

            if (!isset($arr[$k+1])) {
                $output .= str_repeat(' </li> </ul> ', $lvl - $first + 1);
            }

        }

        return $output;

    }


    /**
     * return options string from options array
     */

    public static function drawOptionList($options) {

        $optionList = "";
        foreach ($options as $option) {

            $option = (array) $option;
            if (!array_key_exists("selected", $option)) {
                $option['selected'] = false;
            }

            $optionList .=

                ' <option value="' . $option['value'] .
                '"' . ($option['selected']?' selected="selected"':'') .
                '> ' . $option['description'] . ' </option> ';

        }

        return $optionList;

    }


}




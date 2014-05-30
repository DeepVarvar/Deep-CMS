<?php


/**
 * helper html elements class
 */

abstract class htmlHelper {


    /**
     * draw multidimensional ul-li-a links list
     * by level without recursion
     */

    public static function drawTreeLinksList($arr, $currentURL = '', $noStrict = false) {

        $currLvl = null;
        $output  = '';

        foreach ($arr as $k => $i) {

            if ($noStrict) {
                if ($i['page_alias'] == $currentURL) {
                    $current = true;
                } else if (strpos($currentURL, $i['page_alias']) !== false) {
                    $alias   = preg_quote($i['page_alias'], '/');
                    $suffix  = preg_replace('/^' . $alias . '/u', '', $currentURL);
                    $current = preg_match('/\//', $suffix);
                } else {
                    $current = false;
                }
            } else {
                $current = ($i['page_alias'] == $currentURL);
            }

            $link = '<a' . ($current ? ' class="current"' : '') . ' href="'
                . $i['page_alias'] . '">' . $i['node_name'] . '</a>';

            if ($currLvl === null) {

                $output .= str_repeat('<ul><li>', $i['lvl']) . $link;
                $currLvl = $i['lvl'];

            } else if ($currLvl < $i['lvl']) {

                $diff    = $i['lvl'] - $currLvl;
                $output .= str_repeat('<ul><li>', $diff) . $link;
                $currLvl = $i['lvl'];

            } else if ($currLvl > $i['lvl']) {

                $diff    = $currLvl - $i['lvl'];
                $output .= str_repeat('</li></ul>', $diff) . '</li><li>' . $link;
                $currLvl = $i['lvl'];

            } else {
                $output .= '</li><li>' . $link;
            }

            if (!isset($arr[$k+1])) {
                $output .= str_repeat('</li></ul>', $currLvl);
            }

        }

        return $output;

    }


    /**
     * return options string from options array
     */

    public static function drawOptionList($options) {

        $optionList = '';
        foreach ($options as $option) {

            $option = (array) $option;
            if (!array_key_exists('selected', $option)) {
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



<?php



/**
 * helper html elements class
 */

abstract class htmlHelper {


    /**
     * draw multidimensional ul-li-a links list
     * by level without recursion
     */

    public static function drawTreeLinksList(
                    $arr, $currentURL = '', $noStrict = false) {


        $output = '';
        $lvl    = null;

        foreach ($arr as $k => $i) {

            $current = $noStrict
                ? (strstr($i['page_alias'], $currentURL))
                : ($i['page_alias'] == $currentURL);

            $link = '<a' . ($current ? ' class="current"' : '') . ' href="'
                . $i['page_alias'] . '">' . $i['node_name'] . '</a>';

            if ($lvl === null) {

                $output .= '<ul><li>' . $link;
                $lvl     = $i['lvl'];
                $first   = $lvl;

            } else if ($lvl < $i['lvl']) {

                $lvl     = $i['lvl'];
                $output .= '<ul><li>' . $link;

            } else if ($lvl > $i['lvl']) {

                $diff    = $lvl - $i['lvl'];
                $lvl     = $i['lvl'];
                $output .= '</li>' . str_repeat('</ul></li>', $diff);
                $output .= '<li>' . $link;

            } else {
                $output .= '</li><li>' . $link;
            }

            if (!isset($arr[$k+1])) {
                $output .= str_repeat('</li></ul>', $lvl - $first + 1);
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




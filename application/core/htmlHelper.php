<?php



/**
 * helper html elements class
 */

abstract class htmlHelper {


    /**
     * draw multidimentional ul-li-a links list
     *
     * this need multidimensional array
     *
     * array (
     *
     *     "page_alias"    => "/url/path",
     *     "node_name"     => "Name of node",
     *     "children" => array(LIKE PARENT ARRAY OR EMPTY ARRAY),
     *
     * )
     */

    public static function drawTreeLinksList($arr) {

        $branch = "";
        foreach ($arr as $k => $item) {

            $link = ' <a title="' . $item['node_name'] . '" href="'
                . $item['page_alias'] . '">' . $item['node_name'] . '</a> ';

            $branch .= ' <li> ' . $link
                . self::drawTreeLinksList($item['children']) . ' </li> ';

        }

        return ($branch) ? ' <ul> ' . $branch . ' </ul> ' : $branch;

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




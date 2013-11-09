<?php



/**
 * helper html elements class
 */

abstract class htmlHelper {


    /**
     * draw multidimentional ul-li-a links list
     */

    public static function drawTreeLinksList($inputArr) {

        /*$branch = "";
        foreach ($arr as $k => $item) {

            $link = ' <a title="' . $item['node_name'] . '" href="'
                . $item['page_alias'] . '">' . $item['node_name'] . '</a> ';

            $branch .= ' <li> ' . $link
                . self::drawTreeLinksList($item['children']) . ' </li> ';

        }

        return ($branch) ? ' <ul> ' . $branch . ' </ul> ' : $branch;*/

        $level = 0;
        $outputList = ' <ul> ';
        foreach ($inputArr as $k => $item) {

            $link = ' <li> <a href="' . $item['page_alias']
                . '">' . $item['node_name'] . '</a> ';

            $next = $k + 1;
            $outputList .= $link;

            if (isset($inputArr[$next])) {

                if ($inputArr[$next]['lvl'] > $item['lvl']) {

                    $level += 1;
                    $outputList .= ' <ul> ';

                } else if ($inputArr[$next]['lvl'] < $item['lvl']) {
                    $outputList .= ' </li> </ul> ';
                } else {
                    $outputList .= ' </li> ';
                }

            } else {
                $outputList .= ' </li> ';
            }

        }

        return $outputList . ' </ul> ';

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




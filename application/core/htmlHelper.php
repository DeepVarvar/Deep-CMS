<?php



/**
 * helper html elements class
 */

abstract class htmlHelper {


    /**
     * return optional attributes string with options
     */

    private static function drawOptionalElementAttributes($props) {


        $attributes = "";

        if (array_key_exists("id", $props)) {
            $attributes .= ' id="' . $props['id'] . '"';
        }

        $attributes .= ' class="';
        if (array_key_exists("class", $props)) {
            $attributes .= " {$props['class']}";
        }

        if ($props['type'] == "minitext") {
            $attributes .= " mini";
        }

        $attributes .= '"';
        if (array_key_exists("name", $props)) {
            $attributes .= ' name="' . $props['name'] . '"';
        }


        return $attributes;


    }


    /**
     * create textarea string with options
     */

    public static function drawTextarea($props) {


        $textarea = ' <textarea' . self::drawOptionalElementAttributes($props) . '>';
        if (array_key_exists("value", $props)) {
            $textarea .= $props['value'];
        }


        $textarea .= ' </textarea> ';
        return $textarea;


    }


    /**
     * create input string with options
     */

    public static function drawInput($props) {


        $input = ' <input' . self::drawOptionalElementAttributes($props)
            . ' type="' . (strpos($props['type'],"text")?"text":$props['type']) . '"';



        if (array_key_exists("value", $props) and $props['type'] != "reset") {
            $input .= ' value="' . $props['value'] . '"';
        }


        if (in_array($props['type'], array("radio", "checkbox"))) {

            if (array_key_exists("checked", $props) and $props['checked'] === true) {
                $input .= ' checked="checked"';
            }

        }


        $input .= ' /> ';
        return $input;


    }


    /**
     * draw string of DOM element with options
     */

    public static function drawElement($props) {


        $element = "";
        switch(true) {


            case ($props['type'] == "textarea"):
                $element = self::drawTextarea($props);
            break;


            default:
                $element = self::drawInput($props);
            break;


        }


        return $element;


    }


    /**
     * draw multidimentional ul-li-a links list
     *
     * this need multidimensional array
     *
     * array (
     *
     *     "page_alias"    => "/url/path/do/document",
     *     "page_name"     => "Name of document",
     *     "children" => array(LIKE PARENT ARRAY OR EMPTY ARRAY),
     *
     * )
     */

    public static function drawTreeLinksList($arr) {


        $branch = "";
        foreach ($arr as $k => $item) {


            $link = ' <a title="' . $item['page_name'] . '" href="'
                . $item['page_alias'] . '">' . $item['page_name'] . '</a> ';

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




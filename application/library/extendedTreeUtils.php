<?php


/**
 * extended tree utils helper class
 */

abstract class extendedTreeUtils {


    /**
     * return multidimentional ul-li-a links list string
     */

    public static function getTreeLinksList($arr, $parent = 0) {
        return self::drawTreeLinksList(self::makeTreeArray($arr, $parent));
    }


    /**
     * return multidimensional array
     */

    public static function makeTreeArray(& $lineArray, $parent = 0) {

        $branch = array();
        if ($lineArray) {
            foreach ($lineArray as $k => $item) {
                if ($item['parent_id'] == $parent) {
                    $item['children'] = self::makeTreeArray($lineArray, $item['id']);
                    $branch[] = $item;
                }
            }
        }
        return $branch;

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

        $branch = '';
        foreach ($arr as $k => $item) {
            $link = '<a title="' . $item['page_name'] . '" href="'
                . $item['page_alias'] . '">' . $item['page_name'] . '</a>';
            $branch .= '<li>' . $link . self::drawTreeLinksList($item['children']) . '</li>';
        }
        return ($branch) ? '<ul>' . $branch . '</ul>' : $branch;

    }


}



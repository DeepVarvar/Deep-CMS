<?php



/**
 * simple link prototype
 */

class simpleLink extends baseTreeNode {


    protected $publicFields = array(

        // base
        "id",
        "parent_id",
        "lvl",
        "prototype",
        "creation_date",
        "node_name",

        // individual
        "page_alias"

    );


    public function getHumanityName() {
        return view::$language->simple_link_prototype_name;
    }


}




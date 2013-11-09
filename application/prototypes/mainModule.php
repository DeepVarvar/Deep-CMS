<?php



/**
 * main module prototype
 */

class mainModule extends baseTreeNode {


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
        return view::$language->main_module_prototype_name;
    }


}




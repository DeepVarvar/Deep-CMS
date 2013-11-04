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
        "page_alias",

        // SEO
        "page_title",
        "page_h1",
        "meta_keywords",
        "meta_description"

    );


    public function getHumanityName() {
        return view::$language->main_module_prototype_name;
    }


}




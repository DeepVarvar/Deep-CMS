<?php



/**
 * simple page prototype
 */

class simplePage extends baseTreeNode {


    protected $publicFields = array(

        // base
        "id",
        "parent_id",
        "lvl",
        "prototype",
        "creation_date",
        "last_modified",
        "node_name",

        // individual
        "layout",
        "page_alias",
        "permanent_redirect",

        // SEO
        "page_title",
        "page_h1",
        "meta_keywords",
        "meta_description",

        // properties
        "page_text"

    );


}




<?php


/**
 * node group prototype
 */

class nodeGroup extends baseTreeNode {


    protected $publicFields = array(

        // base
        'id',
        'parent_id',
        'lvl',
        'prototype',
        'creation_date',
        'last_modified',
        'node_name',

        // individual
        'layout',
        'page_alias',

        // SEO
        'page_title',
        'page_h1',
        'meta_keywords',
        'meta_description'

    );

    protected $searchedFields = array(
        'node_name',
        'page_title',
        'page_h1',
        'meta_keywords',
        'meta_description'
    );

    public function getHumanityName() {
        return view::$language->node_group_prototype_name;
    }


}



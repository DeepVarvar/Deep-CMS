<?php


/**
 * simple link prototype
 */

class simpleLink extends baseTreeNode {


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
        'page_alias'

    );

    protected $searchedFields = array(
        'node_name'
    );

    public function getHumanityName() {
        return view::$language->simple_link_prototype_name;
    }


}



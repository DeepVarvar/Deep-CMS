<?php


/**
 * page with announce prototype
 */

class pageWithAnnounce extends baseTreeNode {


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
        'permanent_redirect',

        // SEO
        'page_title',
        'page_h1',
        'meta_keywords',
        'meta_description',

        // properties
        'page_text'

    );

    protected $searchedFields = array(
        'node_name',
        'page_title',
        'page_h1',
        'meta_keywords',
        'meta_description',
        'page_announce',
        'page_text'
    );

    public function getHumanityName() {
        return view::$language->page_with_announce_prototype_name;
    }


}



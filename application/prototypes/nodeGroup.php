<?php


/**
 * node group prototype
 */

class nodeGroup extends baseTreeNode {


    protected $publicFields = array();
    protected $searchedFields = array();

    public function getHumanityName() {
        return view::$language->node_group_prototype_name;
    }


}



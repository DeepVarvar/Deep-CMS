<?php



class baseTreeNode {

    protected $publicFields = array();

    public function getPublicFields() {
        return $this->publicFields;
    }

    public function getHumanityName() {
        return "Unnamed node type";
    }

}




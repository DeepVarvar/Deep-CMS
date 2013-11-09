<?php



class baseTreeNode {

    protected $publicFields   = array();
    protected $searchedFields = array();

    public function getPublicFields() {
        return $this->publicFields;
    }

    public function getSearchedFields() {
        return $this->searchedFields;
    }

    public function getHumanityName() {
        return "Unnamed node type";
    }

}




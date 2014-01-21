<?php


/**
 * node group prototype model
 */

class nodeGroupProtoModel extends basePrototypeModel {


    protected $nodeID = null;
    protected $returnedFields = array(
        'is_publish'     => 1,
        'in_sitemap'     => 0,
        'in_sitemap_xml' => 0,
        'in_search'      => 0,
        'with_menu'      => 0,
        'with_images'    => 0,
        'with_features'  => 0
    );


    /**
     * data getters
     */

    protected function is_publishGetData( & $f) {

        $f['type'] = 'hidden';
        $f['value'] = 1;

    }

    protected function in_sitemapGetData( & $f) {
        $this->getHiddenOffedField($f);
    }

    protected function in_sitemap_xmlGetData( & $f) {
        $this->getHiddenOffedField($f);
    }

    protected function in_searchGetData( & $f) {
        $this->getHiddenOffedField($f);
    }

    protected function with_menuGetData( & $f) {
        $this->getHiddenOffedField($f);
    }

    protected function with_imagesGetData( & $f) {
        $this->getHiddenOffedField($f);
    }

    protected function with_featuresGetData( & $f) {
        $this->getHiddenOffedField($f);
    }

    private function getHiddenOffedField( & $f) {
        $f['type'] = 'hidden';
        $f['required'] = true;
        $f['value'] = 0;
    }


    /**
     * data preparation
     */

    protected function is_publishPrepare( & $data) {
        $data = 1;
    }

    protected function in_sitemapPrepare( & $data) {
        $data = 0;
    }

    protected function in_sitemap_xmlPrepare( & $data) {
        $data = 0;
    }

    protected function in_searchPrepare( & $data) {
        $data = 0;
    }

    protected function with_menuPrepare( & $data) {
        $data = 0;
    }

    protected function with_imagesPrepare( & $data) {
        $data = 0;
    }

    protected function with_featuresPrepare( & $data) {
        $data = 0;
    }


}



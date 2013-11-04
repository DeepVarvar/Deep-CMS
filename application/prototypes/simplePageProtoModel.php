<?php



/**
 * simple page prototype model
 */

class simplePageProtoModel extends baseProtoTypeModel {


    protected $returnedFields = array(

        "in_sitemap"         => 0,
        "layout"             => "",
        "page_alias"         => "",
        "permanent_redirect" => "",
        "change_freq"        => "",
        "search_priority"    => "",
        "page_title"         => "",
        "page_h1"            => "",
        "meta_keywords"      => "",
        "meta_description"   => "",
        "page_text"          => ""

    );


    protected function in_sitemapGetData( & $f) {

        $f['description'] = view::$language->simple_page_in_sitemap;
        $f['type']        = "checkbox";

    }

    protected function layoutGetData( & $f) {

        $f['required']    = true;
        $f['type']        = "select";
        $f['description'] = view::$language->simple_page_layout;
        $f['value']       = utils::makeOptionsArray(
            utils::getAvailablePublicLayouts(), $f['value']
        );

    }

    protected function page_aliasGetData( & $f) {

        $f['required']    = true;
        $f['value']       = rawurldecode($f['value']);
        $f['type']        = "longtext";
        $f['description'] = view::$language->simple_page_page_alias;

    }

    protected function permanent_redirectGetData( & $f) {

        $f['value']       = rawurldecode($f['value']);
        $f['type']        = "longtext";
        $f['description'] = view::$language->simple_page_permanent_redirect;

    }

    protected function change_freqGetData( & $f) {

        $f['type']        = "select";
        $f['description'] = view::$language->simple_page_change_freq;
        $f['value']       = utils::makeOptionsArray(
            utils::getAvailableChangeFreq(), $f['value']
        );

    }

    protected function search_priorityGetData( & $f) {

        $f['type']        = "select";
        $f['description'] = view::$language->simple_page_search_priority;
        $f['value']       = utils::makeOptionsArray(
            utils::getAvailableSearchPriority(), $f['value']
        );

    }

    protected function page_titleGetData( & $f) {

        $f['type']        = "longtext";
        $f['description'] = view::$language->simple_page_page_title;

    }

    protected function page_h1GetData( & $f) {

        $f['type']        = "longtext";
        $f['description'] = view::$language->simple_page_page_h1;

    }

    protected function meta_keywordsGetData( & $f) {

        $f['type']        = "longtext";
        $f['description'] = view::$language->simple_page_meta_keywords;

    }

    protected function meta_descriptionGetData( & $f) {

        $f['type']        = "longtext";
        $f['description'] = view::$language->simple_page_meta_description;

    }

    protected function page_textGetData( & $f) {

        $f['type']        = "textarea";
        $f['editor']      = true;
        $f['description'] = view::$language->simple_page_page_text;

    }


}




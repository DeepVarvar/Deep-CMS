<?php


/**
 * simple page prototype model
 */

class simplePageProtoModel extends baseProtoTypeModel {


    protected $nodeID = null;
    protected $returnedFields = array(

        'in_sitemap'         => 0,
        'in_sitemap_xml'     => 1,
        'in_search'          => 1,
        'page_alias'         => '',
        'permanent_redirect' => '',
        'layout'             => '',
        'page_h1'            => '',
        'page_title'         => '',
        'meta_keywords'      => '',
        'meta_description'   => '',
        'change_freq'        => '',
        'searchers_priority' => '',
        'page_text'          => ''

    );


    /**
     * data getters
     */

    protected function in_sitemapGetData( & $f) {

        $f['description'] = view::$language->show_in_sitemap;
        $f['type'] = 'checkbox';

    }

    protected function in_sitemap_xmlGetData( & $f) {
        $f['type'] = 'hidden';
    }

    protected function in_searchGetData( & $f) {

        $f['description'] = view::$language->show_in_search;
        $f['type'] = 'checkbox';

    }

    protected function layoutGetData( & $f) {

        $f['top']  = 20;
        $f['type'] = 'select';
        $f['required'] = true;
        $f['description'] = view::$language->layout;
        $f['value'] = protoUtils::makeOptionsArray(
            layoutUtils::getAvailablePublicLayouts(), $f['value']
        );

    }

    protected function page_aliasGetData( & $f) {

        $f['top'] = 20;
        $f['selector'] = 'pagealias';
        $f['required'] = true;
        $f['value'] = rawurldecode($f['value']);
        $f['type']  = 'longtext';
        $f['description'] = view::$language->page_alias;

    }

    protected function permanent_redirectGetData( & $f) {

        $f['value'] = rawurldecode($f['value']);
        $f['type']  = 'longtext';
        $f['description'] = view::$language->permanent_redirect;

    }

    protected function change_freqGetData( & $f) {

        $f['top']  = 20;
        $f['type'] = 'select';
        $f['description'] = view::$language->change_freq;
        $f['value'] = protoUtils::makeOptionsArray(
            protoUtils::getAvailableChangeFreq(), $f['value']
        );

    }

    protected function searchers_priorityGetData( & $f) {

        $f['type'] = 'select';
        $f['description'] = view::$language->searchers_priority;
        $f['value'] = protoUtils::makeOptionsArray(
            protoUtils::getAvailableSearchersPriority(), $f['value']
        );

    }

    protected function page_titleGetData( & $f) {

        $f['type'] = 'longtext';
        $f['description'] = view::$language->page_title;

    }

    protected function page_h1GetData( & $f) {

        $f['top']  = 20;
        $f['type'] = 'longtext';
        $f['description'] = view::$language->h1;

    }

    protected function meta_keywordsGetData( & $f) {

        $f['type'] = 'longtext';
        $f['description'] = view::$language->meta_keywords;

    }

    protected function meta_descriptionGetData( & $f) {

        $f['type'] = 'longtext';
        $f['description'] = view::$language->meta_description;

    }

    protected function page_textGetData( & $f) {

        $f['top'] = 20;
        $f['node_id'] = ($this->nodeID?$this->nodeID:'new');
        $f['type']    = 'textarea';
        $f['editor']  = true;
        $f['description'] = view::$language->simple_page_page_text;

    }


    /**
     * data preparation
     */

    protected function in_sitemapPrepare( & $data) {
        $data = !$data ? 0 : 1;
    }

    protected function in_sitemap_xmlPrepare( & $data) {
        $data = 1;
    }

    protected function in_searchPrepare( & $data) {
        $data = !$data ? 0 : 1;
    }

    protected function page_aliasPrepare( & $data) {

        $data = (string) $data;
        if (!$data) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->page_alias_invalid
            );
        }

        $data = protoUtils::normalizeInputUrl(
            $data, view::$language->page_alias_invalid
        );

    }

    protected function permanent_redirectPrepare( & $data) {

        $data = (string) $data;
        if ($data) {
            $data = protoUtils::normalizeInputUrl(
                $data, view::$language->permanent_redirect_invalid
            );
        }

    }

    protected function layoutPrepare( & $data) {

        $data = (string) $data;
        if (!in_array($data, layoutUtils::getAvailablePublicLayouts())) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->layout_not_found
            );
        }

    }

    protected function page_h1Prepare( & $data) {
        $data = filter::input($data)->stripTags()->typoGraph(true)->getData();
    }

    protected function page_titlePrepare( & $data) {
        $data = filter::input($data)->stripTags()->typoGraph(true)->getData();
    }

    protected function meta_keywordsPrepare( & $data) {
        $data = filter::input($data)->stripTags()->typoGraph(true)->getData();
    }

    protected function meta_descriptionPrepare( & $data) {
        $data = filter::input($data)->stripTags()->typoGraph(true)->getData();
    }

    protected function change_freqPrepare( & $data) {

        $data = (string) $data;
        if ($data == '---') {
            $data = 'NULL';
        } else if (!in_array($data, protoUtils::getAvailableChangeFreq(), true)) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->change_freq_invalid
            );
        }

    }

    protected function searchers_priorityPrepare( & $data) {

        $data = (string) $data;
        if ($data == '---') {
            $data = 'NULL';
        } else if (!in_array($data, protoUtils::getAvailableSearchersPriority(), true)) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->searchers_priority_invalid
            );
        }

    }

    protected function page_textPrepare( & $data) {
        $data = filter::input($data)->cleanRichText()->typoGraph()->getData();
    }


}



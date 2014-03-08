<?php


/**
 * pageWithAnnounce prototype model
 */

class pageWithAnnounceProtoModel extends basePrototypeModel {


    protected $nodeID = null;
    protected $returnedFields = array(

        // editable
        'is_publish'         => 1,
        'in_sitemap'         => 0,
        'in_sitemap_xml'     => 1,
        'in_search'          => 1,
        'page_alias'         => '',
        'layout'             => '',
        'page_h1'            => '',
        'page_title'         => '',
        'meta_keywords'      => '',
        'meta_description'   => '',
        'change_freq'        => '',
        'searchers_priority' => '',
        'page_announce'      => '',
        'page_text'          => '',

        // reseted
        'permanent_redirect' => '',
        'with_menu'          => 1,
        'with_images'        => 1,
        'with_features'      => 1

    );


    /**
     * data getters
     */

    // editable
    protected function is_publishGetData( & $f) {

        $f['top'] = 20;
        $f['description'] = view::$language->page_with_announce_prototype_publish;
        $f['type'] = 'checkbox';

    }

    protected function in_sitemapGetData( & $f) {

        $f['description'] = view::$language->page_with_announce_prototype_show_in_sitemap;
        $f['type'] = 'checkbox';

    }

    protected function in_sitemap_xmlGetData( & $f) {
        $f['type'] = 'hidden';
    }

    protected function in_searchGetData( & $f) {

        $f['description'] = view::$language->page_with_announce_prototype_show_in_search;
        $f['type'] = 'checkbox';

    }

    protected function page_aliasGetData( & $f) {

        $f['top'] = 20;
        $f['selector'] = 'pagealias';
        $f['required'] = true;
        $f['value'] = rawurldecode($f['value']);
        $f['type']  = 'longtext';
        $f['description'] = view::$language->page_with_announce_prototype_page_alias;

    }

    protected function layoutGetData( & $f) {

        $f['top']  = 20;
        $f['type'] = 'select';
        $f['required'] = true;
        $f['description'] = view::$language->page_with_announce_prototype_layout;
        $f['value'] = protoUtils::makeOptionsArray(
            layoutUtils::getAvailablePublicLayouts(), $f['value']
        );

    }

    protected function page_h1GetData( & $f) {

        $f['top']  = 20;
        $f['type'] = 'longtext';
        $f['description'] = view::$language->page_with_announce_prototype_h1;

    }

    protected function page_titleGetData( & $f) {

        $f['type'] = 'longtext';
        $f['description'] = view::$language->page_with_announce_prototype_page_title;

    }

    protected function meta_keywordsGetData( & $f) {

        $f['type'] = 'longtext';
        $f['description'] = view::$language->page_with_announce_prototype_meta_keywords;

    }

    protected function meta_descriptionGetData( & $f) {

        $f['type'] = 'longtext';
        $f['description'] = view::$language->page_with_announce_prototype_meta_description;

    }

    protected function change_freqGetData( & $f) {

        $f['top']  = 20;
        $f['type'] = 'select';
        $f['description'] = view::$language->page_with_announce_prototype_change_freq;
        $f['value'] = protoUtils::makeOptionsArray(
            protoUtils::getAvailableChangeFreq(), $f['value']
        );

    }

    protected function searchers_priorityGetData( & $f) {

        $f['type'] = 'select';
        $f['description'] = view::$language->page_with_announce_prototype_searchers_priority;
        $f['value'] = protoUtils::makeOptionsArray(
            protoUtils::getAvailableSearchersPriority(), $f['value']
        );

    }

    protected function page_announceGetData( & $f) {

        $f['top'] = 20;
        $f['node_id'] = ($this->nodeID?$this->nodeID:'new');
        $f['type']    = 'textarea';
        $f['description'] = view::$language->page_with_announce_prototype_page_announce;

    }

    protected function page_textGetData( & $f) {

        $f['top'] = 20;
        $f['node_id'] = ($this->nodeID?$this->nodeID:'new');
        $f['type']    = 'textarea';
        $f['editor']  = true;
        $f['description'] = view::$language->page_with_announce_prototype_page_text;

    }

    // reseted
    protected function permanent_redirectGetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = '';
    }
    protected function with_menuGetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = 1;
    }
    protected function with_imagesGetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = 1;
    }
    protected function with_featuresGetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = 1;
    }


    /**
     * data preparation
     */

    // editable
    protected function is_publishPrepare( & $data) {
        $data = !$data ? 0 : 1;
    }

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
                view::$language->page_with_announce_prototype_error,
                view::$language->page_with_announce_prototype_page_alias_invalid
            );
        }

        $data = protoUtils::normalizeInputUrl(
            $data, view::$language->page_with_announce_prototype_page_alias_invalid
        );

    }

    protected function layoutPrepare( & $data) {

        $data = (string) $data;
        if (!in_array($data, layoutUtils::getAvailablePublicLayouts())) {
            throw new memberErrorException(
                view::$language->page_with_announce_prototype_error,
                view::$language->page_with_announce_prototype_layout_not_found
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
                view::$language->page_with_announce_prototype_error,
                view::$language->page_with_announce_prototype_change_freq_invalid
            );
        }

    }

    protected function searchers_priorityPrepare( & $data) {

        $data = (string) $data;
        if ($data == '---') {
            $data = 'NULL';
        } else if (!in_array($data, protoUtils::getAvailableSearchersPriority(), true)) {
            throw new memberErrorException(
                view::$language->page_with_announce_prototype_error,
                view::$language->page_with_announce_prototype_searchers_priority_invalid
            );
        }

    }

    protected function page_announcePrepare( & $data) {
        $data = filter::input($data)->stripTags()->getData();
    }

    protected function page_textPrepare( & $data) {
        $data = filter::input($data)->cleanRichText()->typoGraph()->getData();
    }

    // reseted
    protected function permanent_redirectPrepare( & $data) {
        $data = 'NULL';
    }
    protected function with_menuPrepare( & $data) {
        $data = 1;
    }
    protected function with_imagesPrepare( & $data) {
        $data = 1;
    }
    protected function with_featuresPrepare( & $data) {
        $data = 1;
    }


}



<?php


/**
 * simple link prototype model
 */

class simpleLinkProtoModel extends basePrototypeModel {


    protected $nodeID = null;
    protected $returnedFields = array(

        // editable
        'is_publish'         => 1,
        'in_sitemap'         => 0,
        'in_sitemap_xml'     => 0,
        'in_search'          => 0,
        'page_alias'         => '',
        'permanent_redirect' => '',

        // reseted
        'change_freq'        => '',
        'searchers_priority' => '',
        'with_menu'          => 1,
        'with_images'        => 1,
        'with_features'      => 0,
        'page_h1'            => '',
        'page_title'         => '',
        'meta_keywords'      => '',
        'meta_description'   => ''

    );


    /**
     * data getters
     */

    // editable
    protected function is_publishGetData( & $f) {

        $f['top'] = 20;
        $f['description'] = view::$language->simple_link_prototype_publish;
        $f['type'] = 'checkbox';

    }

    protected function in_sitemapGetData( & $f) {

        $f['description'] = view::$language->simple_link_prototype_show_in_sitemap;
        $f['type'] = 'checkbox';

    }

    protected function in_sitemap_xmlGetData( & $f) {

        $f['description'] = view::$language->simple_link_prototype_show_in_sitemap_xml;
        $f['type'] = 'checkbox';

    }

    protected function in_searchGetData( & $f) {

        $f['description'] = view::$language->simple_link_prototype_show_in_search;
        $f['type'] = 'checkbox';

    }

    protected function page_aliasGetData( & $f) {

        $f['top'] = 20;
        $f['selector'] = 'pagealias';
        $f['required'] = true;
        $f['value'] = rawurldecode($f['value']);
        $f['type']  = 'longtext';
        $f['description'] = view::$language->simple_link_prototype_link_alias;

    }

    protected function permanent_redirectGetData( & $f) {

        $f['value'] = rawurldecode($f['value']);
        $f['type']  = 'longtext';
        $f['description'] = view::$language->simple_link_prototype_permanent_redirect;

    }

    // reseted
    protected function change_freqGetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = '';
    }
    protected function searchers_priorityGetData( & $f) {
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
        $f['value'] = 0;
    }
    protected function page_h1GetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = '';
    }
    protected function page_titleGetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = '';
    }
    protected function meta_keywordsGetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = '';
    }
    protected function meta_descriptionGetData( & $f) {
        $f['type']  = 'hidden';
        $f['value'] = '';
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
        $data = !$data ? 0 : 1;
    }

    protected function in_searchPrepare( & $data) {
        $data = !$data ? 0 : 1;
    }

    protected function page_aliasPrepare( & $data) {

        $data = (string) $data;
        if (!$data) {
            throw new memberErrorException(
                view::$language->simple_link_prototype_error,
                view::$language->simple_link_prototype_link_alias_invalid
            );
        }

        $data = protoUtils::normalizeInputUrl(
            $data, view::$language->simple_link_prototype_link_alias_invalid
        );

    }

    protected function permanent_redirectPrepare( & $data) {

        $data = (string) $data;
        if ($data) {
            $data = protoUtils::normalizeInputUrl(
                $data, view::$language->simple_link_prototype_permanent_redirect_invalid
            );
        }

    }

    // reseted
    protected function change_freqPrepare( & $data) {
        $data = 'NULL';
    }
    protected function searchers_priorityPrepare( & $data) {
        $data = 'NULL';
    }
    protected function with_menuPrepare( & $data) {
        $data = 1;
    }
    protected function with_imagesPrepare( & $data) {
        $data = 1;
    }
    protected function with_featuresPrepare( & $data) {
        $data = 0;
    }
    protected function page_h1Prepare( & $data) {
        $data = '';
    }
    protected function page_titlePrepare( & $data) {
        $data = '';
    }
    protected function meta_keywordsPrepare( & $data) {
        $data = '';
    }
    protected function meta_descriptionPrepare( & $data) {
        $data = '';
    }


}



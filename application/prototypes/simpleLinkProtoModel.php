<?php


/**
 * simple link prototype model
 */

class simpleLinkProtoModel extends basePrototypeModel {


    protected $nodeID = null;
    protected $returnedFields = array(

        'in_sitemap'     => 0,
        'in_sitemap_xml' => 1,
        'in_search'      => 0,
        'page_alias'     => ''

    );


    /**
     * data getters
     */

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


    /**
     * data preparation
     */

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


}



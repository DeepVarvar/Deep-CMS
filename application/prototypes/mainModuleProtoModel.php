<?php


/**
 * main module prototype model
 */

class mainModuleProtoModel extends baseProtoTypeModel {


    protected $nodeID = null;
    protected $returnedFields = array(

        'in_sitemap'     => 0,
        'in_sitemap_xml' => 1,
        'in_search'      => 0,
        'page_alias'     => '',
        'module_name'    => ''

    );


    /**
     * data getters
     */

    protected function in_sitemapGetData( & $f) {

        $f['description'] = view::$language->show_in_sitemap;
        $f['type'] = 'checkbox';

    }

    protected function in_sitemap_xmlGetData( & $f) {

        $f['description'] = view::$language->show_in_sitemap_xml;
        $f['type'] = 'checkbox';

    }

    protected function in_searchGetData( & $f) {

        $f['description'] = view::$language->show_in_search;
        $f['type'] = 'hidden';

    }

    protected function page_aliasGetData( & $f) {

        $f['top']      = 20;
        $f['selector'] = 'pagealias';
        $f['required'] = true;
        $f['value']    = rawurldecode($f['value']);
        $f['type']     = 'longtext';
        $f['description'] = view::$language->main_module_module_alias;

    }

    protected function module_nameGetData( & $f) {

        $options = array();
        foreach (utils::getAvailablePublicModules() as $item) {

            $description = $item;
            if (isset(view::$language->{$item})) {
                $description = view::$language->{$item};
            }
            $option = array('value' => $item, 'description' => $description);
            if ($f['value'] == $item) {
                $option['selected'] = true;
            }
            array_push($options, $option);

        }

        $f['value'] = $options;
        $f['type']  = 'select';
        $f['description'] = view::$language->main_module_connected_module;

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
        $data = 0;
    }

    protected function page_aliasPrepare( & $data) {

        $data = (string) $data;
        if (!$data) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->page_alias_invalid
            );
        }

        $data = utils::normalizeInputUrl(
            $data, view::$language->page_alias_invalid
        );

    }

    protected function module_namePrepare( & $data) {

        $data = (string) $data;
        if ($data == '---') {
            $data = 'NULL';
        } else if (!in_array($data, utils::getAvailablePublicModules(), true)) {
            throw new memberErrorException(
                view::$language->error,
                view::$language->main_module_module_not_found
            );
        }

    }


}



<?php



/**
 * main module prototype model
 */

class mainModuleProtoModel extends baseProtoTypeModel {


    protected $nodeID = null;
    protected $returnedFields = array(

        "in_sitemap"         => 0,
        "page_alias"         => "",
        "search_priority"    => ""

    );


    /**
     * data getters
     */

    protected function in_sitemapGetData( & $f) {

        $f['description'] = view::$language->show_in_sitemap;
        $f['type']        = "checkbox";

    }

    protected function page_aliasGetData( & $f) {

        $f['top']         = 20;
        $f['selector']    = "pagealias";
        $f['required']    = true;
        $f['value']       = rawurldecode($f['value']);
        $f['type']        = "longtext";
        $f['description'] = view::$language->simple_page_page_alias;

    }

    protected function search_priorityGetData( & $f) {

        $f['type']        = "select";
        $f['description'] = view::$language->simple_page_search_priority;
        $f['value']       = utils::makeOptionsArray(
            utils::getAvailableSearchPriority(), $f['value']
        );

    }


    /**
     * data preparation
     */

    protected function in_sitemapPrepare( & $data) {
        $data = !$data ? 0 : 1;
    }

    protected function page_aliasPrepare( & $data) {

        $data = (string) $data;
        if (!$data) {

            throw new memberErrorException(
                view::$language->error,
                view::$language->simple_page_page_alias_invalid
            );

        }

        $data = utils::normalizeInputUrl(
            $data, view::$language->simple_page_page_alias_invalid
        );

    }

    protected function search_priorityPrepare( & $data) {

        $data = (string) $data;
        if ($data == "---") {

            $data = "NULL";

        } else if (
            !in_array($data, utils::getAvailableSearchPriority(), true)) {

            throw new memberErrorException(
                view::$language->error,
                view::$language->sp_invalid_format
            );

        }

    }


}




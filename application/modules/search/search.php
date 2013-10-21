<?php



/**
 * simple search module
 */

class search extends baseController {


    /**
     * max of separated search words
     */

    private $maxSearWordsLength = 3;


    /**
     * result items per page
     */

    private $itemsPerPage = 20;


    /**
     * max size of pagination
     */

    private $sliceSizeByPages = 10;


    /**
     * view search result
     */

    public function index() {


        /**
         * check for exists dependency protected layout
         */

        $layoutName = "simple-search.html";

        if (!utils::isExistsProtectedLayout($layoutName)) {
            throw new memberErrorException("Simple search module error", "Dependency protected layout {$layoutName} is not exists");
        }


        /**
         * get searchwords
         */

        $searchwords = rawurldecode(
            (string) request::shiftParam("searchwords")
        );

        $searchwords = filter::input($searchwords)->stripTags();
        $searchwords = $searchwords->expReplace(
            array("/\++/", "/\s+/"), array(" ", " "))->getData();


        $searchPartsSource = explode(" ", $searchwords);
        $searchParts = array();

        foreach ($searchPartsSource as $v) {
            if (mb_strlen($v, "UTF-8") > 2) {
                array_push($searchParts, db::escapeString($v));
            }
        }

        $searchParts = array_slice($searchParts, 0, $this->maxSearWordsLength);
        if ($searchParts) {


            $searchParts = "(p.page_text LIKE '%%"
                . join("%%' OR p.page_text LIKE '%%", $searchParts) . "%%')";


            $searchQuery = db::buildQueryString("

                SELECT DISTINCT

                    d.id,
                    d.parent_id,
                    d.page_name,
                    d.page_alias,
                    p.page_text

                FROM documents d

                INNER JOIN props_simple_pages p
                    ON (p.id = d.props_id AND d.prototype = 10)

                WHERE d.is_publish = 1 AND {$searchParts}

            ");


            $paginator = new paginator($searchQuery);
            $paginator =

                $paginator->setCurrentPage(request::getCurrentPage())
                    ->setItemsPerPage($this->itemsPerPage)
                    ->setSliceSizeByPages($this->sliceSizeByPages)
                    ->getResult();


            $searchResult = $paginator['items'];
            $pages = $paginator['pages'];


        } else {
            $searchResult = array();
            $pages = array();
        }


        /**
         * assign data into view
         */

        view::assign("pages", $pages);
        view::assign("search_result", $searchResult);
        view::assign("searchwords",   $searchwords);

        view::assign("page_name", view::$language->simple_search_of_site);
        $this->setProtectedLayout($layoutName);


    }


}



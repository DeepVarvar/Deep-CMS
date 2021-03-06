<?php



/**
 * search module
 */

class search extends baseController {


    /**
     * max of separated search words
     */

    private $maxSearWordsLength = 3;


    /**
     * result items per page
     */

    private $itemsPerPage = 10;


    /**
     * max size of pagination
     */

    private $sliceSizeByPages = 10;


    /**
     * view search result
     */

    public function index() {


        $layoutName = "search.html";
        if (!utils::isExistsProtectedLayout($layoutName)) {
            throw new systemErrorException(
                "Simple search module error",
                    "Dependency protected layout {$layoutName} is not exists"
            );
        }

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

        $searchParts = array_slice(
            $searchParts, 0, $this->maxSearWordsLength
        );

        if ($searchParts) {

            if (sizeof($searchParts) > 1) {
                $searchParts = array_merge(
                    array(join(" ", $searchParts)), $searchParts
                );
            }

            $searchedFields = array();
            foreach (utils::getAvailableProtoTypes() as $item) {

                $proto = new $item;
                $protoFields[$item] = $proto->getSearchedFields();
                $searchedFields = array_merge(
                    $searchedFields, $protoFields[$item]
                );

            }

            $searchCondition = array();
            $searchedFields  = array_unique($searchedFields);

            foreach ($searchParts as $sw) {

                $sw   = db::escapeString($sw);
                $join = " LIKE '%%{$sw}%%' OR ";
                $suff = " LIKE '%%{$sw}%%' ";

                array_push(
                    $searchCondition, join($join, $searchedFields) . $suff
                );

            }

            $queryPrefix = "";
            if (sizeof($searchCondition) > 1) {

                $firstCondition = array_shift($searchCondition);
                $queryPrefix = "SELECT id, parent_id, prototype, lvl, lk, rk,
                    page_alias, node_name FROM tree WHERE in_search = 1
                        AND ({$firstCondition}) AND is_publish = 1
                            GROUP BY id UNION DISTINCT";

            }

            $searchCondition = join(" OR ", $searchCondition);
            $searchQuery     = db::buildQueryString(

                "{$queryPrefix} SELECT id, parent_id, prototype, lvl, lk, rk,
                    page_alias, node_name FROM tree WHERE in_search = 1
                        AND ({$searchCondition}) AND is_publish = 1 GROUP BY id"

            );

            $paginator = new paginator($searchQuery);
            $paginator =

                $paginator->setCurrentPage(request::getCurrentPage())
                    ->setItemsPerPage($this->itemsPerPage)
                        ->setSliceSizeByPages($this->sliceSizeByPages)
                            ->getResult();

            $searchResult = $paginator['items'];
            $pages = $paginator['pages'];

            unset($paginator);
            dataHelper::joinExtendedData(
                $searchResult, array("image", "page_text")
            );

        } else {
            $searchResult = array();
            $pages = array();
        }

        view::assign("pages", $pages);
        view::assign("search_result", $searchResult);
        view::assign("searchwords",   $searchwords);
        $this->setProtectedLayout($layoutName);


    }


}




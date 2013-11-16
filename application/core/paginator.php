<?php



/**
 * pagination class
 */

class paginator {


    /**
     * empty default result
     */

    private static $empty = array(

        "number_of_items" => 0,
        "number_of_pages" => 1,
        "current_page" => 1,
        "items" => array(),

        "pages" => array(
            array("number" => 1, "current" => true)
        )

    );


    private


        /**
         * source query of pages without limits
         */

        $sourceQuery = null,


        /**
         * source query of count items
         */

        $countQuery = null,


        /**
         * number of items per page
         */

        $itemsPerPage = 10,


        /**
         * slice size by pages
         */

        $sliceSizeByPages = 10,


        /**
         * number of current page
         */

        $currentPage = 1,


        /**
         * output result
         */

        $result = null;


    /**
     * return default empty result
     */

    public static function getEmptyDefault() {
        return self::$empty;
    }


    /**
     * create pagination object example from source query without limits
     */

    public function __construct($sourceQuery) {

        $sourceQuery = str_replace("%", "%%", $sourceQuery);
        $this->sourceQuery = $sourceQuery;
        $this->countQuery  = $sourceQuery;

        $this->result = self::$empty;
        return $this;

    }


    /**
     * set current desired page number
     */

    public function setCurrentPage($number) {

        if (!validate::isNumber($number)) {
            throw new systemErrorException("Pagination error", "Current page is not number");
        }

        if ($number == 0) {
            throw new systemErrorException("Pagination error", "Current page can't be zero");
        }

        $this->currentPage = (int) $number;
        return $this;

    }


    /**
     * set number of items per page
     */

    public function setItemsPerPage($number) {

        if (!validate::isNumber($number)) {
            throw new systemErrorException("Pagination error", "Items per page is not number");
        }

        if ($number == 0) {
            throw new systemErrorException("Pagination error", "Items per page can't be zero");
        }

        $this->itemsPerPage = $number;
        return $this;

    }


    /**
     * set slice size by pages number
     */

    public function setSliceSizeByPages($number) {

        if (!validate::isNumber($number)) {
            throw new systemErrorException("Pagination error", "Slice size by pages is not number");
        }

        if ($number == 0) {
            throw new systemErrorException("Pagination error", "Slice size by pages can't be zero");
        }

        $this->sliceSizeByPages = $number;
        return $this;

    }


    /**
     * get result
     */

    public function getResult() {

        $this->getNumberOfAllItems();
        $this->getNumberOfAllPages();
        $availableNumber = (int) ($this->result['number_of_pages'] > 0
            ? $this->result['number_of_pages'] : 1);

        if ($availableNumber < $this->currentPage) {
            throw new systemErrorException("Pagination error", "Current page is more maximum page number");
        }

        $this->getPages();
        $this->getItems();

        return $this->result;

    }


    /**
     * set custom count query
     */

    public function setCountQuery($query) {
        $this->countQuery = $query;
    }


    /**
     * get number of all items
     */

    private function getNumberOfAllItems() {
        $this->countQuery = "SELECT COUNT(1) cnt FROM ({$this->countQuery}) tmp_table";
        $this->result['number_of_items'] = db::cachedNormalizeQuery($this->countQuery);
    }


    /**
     * get number of all pages
     */

    private function getNumberOfAllPages() {
        $this->result['number_of_pages'] = ceil($this->result['number_of_items'] / $this->itemsPerPage);
    }


    /**
     * get pages array
     */

    private function getPages() {

        if ($this->sliceSizeByPages % 2 != 0) {
            $this->sliceSizeByPages += 1;
        }

        $half = $this->sliceSizeByPages/2;
        $rangeStart = $this->currentPage - $half;
        $rangeStop  = $this->currentPage + $half;
        $this->result['pages'] = array();
        $this->result['current_page'] = $this->currentPage;
        $pagesRange = range($rangeStart, $rangeStop);

        foreach ($pagesRange as $item) {
            if ($item > 0 and $item <= $this->result['number_of_pages']) {
                $page = array("number" => $item, "current" => ($item == $this->currentPage));
                array_push($this->result['pages'], $page);
            }
        }

    }


    /**
     * get items for current slice
     */

    private function getItems() {

        $offset = ($this->currentPage - 1) * $this->itemsPerPage;
        $this->result['items'] = db::query(
            "{$this->sourceQuery} LIMIT {$offset}, {$this->itemsPerPage}"
        );

    }


}




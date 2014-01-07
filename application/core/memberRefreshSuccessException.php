<?php


/**
 * member success exception with show message
 */

class memberRefreshSuccessException extends systemException {


    protected $type = 'success';
    protected $expects = array('title', 'message', 'refresh_location');


    /**
     * return report after clear all cached filed
     */

    public function getReport() {

        utils::clearMainCache();
        return $this->report;

    }


}



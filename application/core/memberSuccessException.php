<?php


/**
 * member success exception type
 */

class memberSuccessException extends memberException {


    protected $type = 'success';


    /**
     * return report after clear all cached filed
     */

    public function getReport() {

        utils::clearMainCache();
        return $this->report;

    }


}



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

        fsUtils::clearMainCache();
        return $this->report;

    }


}



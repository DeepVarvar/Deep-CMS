<?php


/**
 * member error exception with show message
 */

class memberRefreshErrorException extends systemException {

    protected $type = 'error';
    protected $expects = array('title', 'message', 'refresh_location');

}



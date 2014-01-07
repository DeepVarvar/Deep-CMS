<?php


/**
 * system exception class
 */

class systemException extends Exception {


    /**
     * expected order of expected parameters
     * and empty report
     */

    protected $expects = array('code', 'title', 'message');
    protected $report  = array();


    /**
     * exception type, use for identify on client side,
     * "system" value by default
     */

    protected $type = 'system';


    public function __construct() {

        $args = func_get_args();
        $size = sizeof($args);

        $expects = array_slice($this->expects, 3 - $size, $size);
        foreach ($expects as $k => $name) {
            $this->report[$name] = $args[$k];
        }

        if (!isset($this->report['title'])) {
            $this->report['title'] = 'Untitled exception';
        }

        if (!isset($this->report['code'])) {
            $this->report['code'] = 0;
        }

        if (!isset($this->report['message'])) {
            $this->report['message'] = '[empty exception message]';
        }

        $profile = member::getProfile();

        $this->report['initiator_id'] = $profile['id'];
        $this->report['initiator'] = $profile['login'];
        $this->report['initiator_group_priority'] = $profile['group_priority'];

        $this->report['datetime'] = date('Y-m-d H:i:s');
        $this->report['type'] = $this->type;

        $this->report['file']  = $this->file;
        $this->report['line']  = $this->line;
        $this->report['trace'] = parent::getTrace();

        if (app::config()->system->write_log) {
            utils::writeLog(array_merge(
                $this->report,
                request::getClientInfo(),
                array('url' => request::getOriginURL())
            ));
        }

    }


    /**
     * return type of exception
     */

    public function getType() {
        return $this->type;
    }


    /**
     * return report
     */

    public function getReport() {
        return $this->report;
    }


}



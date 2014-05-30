<?php


/**
 * admin submodule, site events history
 */

class events extends baseController {


    /**
     * set permissions for this controller
     */

    public function setPermissions() {

        $this->permissions = array(
            array(
                'action'      => null,
                'permission'  => 'events_view',
                'description' => view::$language->permission_events_view
            )
        );

    }


    public function index() {

        if (!$logTarget = request::getParam('log')) {
            $logTarget = 'main.log';
        }

        $logFile = APPLICATION . 'logs/' . $logTarget;
        if (!is_file($logFile)) {
            $logFile = APPLICATION . 'logs/main.log';
        }

        $events = @ file_get_contents($logFile);
        if (!$events) {
            $events = array();
        } else {
            $events = array_reverse(
                json_decode('[' . $events . ']', true)
            );
        }

        $showEvents = array();
        foreach ($events as $event) {
            if ($this->checkEventShowPermission($event)) {
                $showEvents[] = $event;
            }
        }

        $logs = array();
        foreach (array_reverse(fsUtils::glob(APPLICATION . 'logs/*.log')) as $log) {
            $log = basename($log);
            $logs[] = array(
                'value'       => $log,
                'description' => $log,
                'selected'    => ($log == $logTarget)
                
            );
        }

        view::assign('node_name', view::$language->events_title);
        view::assign('events', $showEvents);
        view::assign('logs', htmlHelper::drawOptionList($logs));
        $this->setProtectedLayout('events.html');

    }


    private function checkEventShowPermission( & $event) {

        if (member::isProtected()) {
            return true;
        } else if ($event['initiator_group_priority'] === null) {
            return true;
        } else if (member::getPriority() <= $event['initiator_group_priority']) {
            return true;
        } else {
            return false;
        }

    }


}



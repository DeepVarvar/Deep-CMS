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

        $events = @ file_get_contents(APPLICATION . 'logs/main.log');
        if (!$events) {
            $events = array();
        } else {
            $events = array_reverse(
                json_decode('[' . $events . ']', true)
            );
        }

        view::assign('node_name', view::$language->events_title);
        view::assign('events', $events);
        $this->setProtectedLayout('events.html');

    }


}



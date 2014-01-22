<?php


/**
 * based properties and methods of controller
 */

class baseController {


    /**
     * permissions array of controller,
     * allowed all actions when array is empty
     *
     * permission format for extends of baseController:
     *
     *     public function setPermission() {
     *         $this->permissions = array(
     *             array(
     *                 'action'      => 'publicActionName',
     *                 'permission'  => 'nameOfPermission',
     *                 'description' => 'Description of permission'
     *             )
     *         );
     *     }
     *
     * if you need set permission for controller without actions,
     * like global permission of controller, set 'action' => null
     */

    protected $permissions = array();


    /**
     * denied execute public actions of controller,
     * add into array this names of actions,
     * WARNING! set names only into extends of baseController!
     */

    protected $denyActions = array();


    /**
     * preloader, only in/for extends
     */

    public function preLoad() {
        $this->setPermissions();
    }


    /**
     * set permissions of controller,
     * only in/for extends
     */

    public function setPermissions() {}


    /**
     * run before action, only in/for extends
     */

    public function runBefore() {}


    /**
     * run after action, only in/for extends
     */

    public function runAfter() {}


    /**
     * return permissions array of controller,
     * only in/for extends
     */

    public function getPermissions() {
        return $this->permissions;
    }


    /**
     * return custom denied actions array of controller,
     * only in/for extends
     */

    public function getDenyActions() {
        return $this->denyActions;
    }


    /**
     * set protected layout relative path
     */

    public function setProtectedLayout($name) {
        view::setLayout('protected/' . $name);
    }


    /**
     * set public layout relative path
     */

    public function setPublicLayout($name) {
        view::setLayout('public/' . $name);
    }


    /**
     * this message show only after permanent redirect to same origin URL,
     * used for reset browser POST request,
     * store member before redirection data
     */

    protected function redirectMessage($type, $title, $message, $refresh_location = null) {

        member::storeData();
        if (view::getOutputContext() == 'html') {

            storage::write(
                '__message',
                array(
                    'type'    => $type,
                    'title'   => $title,
                    'message' => $message,
                    'refresh_location' => $refresh_location
                )
            );

            if ($refresh_location) {
                request::redirect($refresh_location);
            } else {
                request::sameOriginRedirect();
            }

        } else {

            if ($type == SUCCESS_EXCEPTION) {
                if ($refresh_location) {
                    throw new memberRefreshSuccessException(
                        $title, $message, $refresh_location
                    );
                } else {
                    throw new memberSuccessException($title, $message);
                }
            } else {
                if ($refresh_location) {
                    throw new memberRefreshErrorException(
                        $title, $message, $refresh_location
                    );
                } else {
                    throw new memberErrorException($title, $message);
                }
            }

        }

    }


}



<?php


/**
 * remote verification module
 */

class deep_cms_info extends baseController {


    public function index() {

        $context = request::getParam('format');
        if (!$context or !in_array($context, array('json', 'xml'), true)) {
            throw new systemErrorException(
                'Verification error', 'Invalid format parameter'
            );
        }

        view::setOutputContext($context);
        view::lockOutputContext();
        view::clearPublicVariables();

        $app = app::config()->application;
        $app = array('name' => $app->name, 'version' => $app->version);

        if ($context == 'xml') {
            view::setXSDSchema(array('name' => 'application'));
            view::assign('application', $app);
        } else {
            view::assign($app);
        }

    }


}



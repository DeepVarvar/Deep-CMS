<?php



/**
 * admin submodule, javascript variables
 */

class variables_js extends baseController {


    public function index() {

        view::setOutputContext("html");
        view::lockOutputContext();

        $c = app::config();
        request::validateReferer($c->site->admin_tools_link . ".*", true);

        $variables = array(
            "language"         => member::getLanguage(),
            "admin_tools_link" => $c->site->admin_tools_link,
            "admin_resources"  => $c->site->admin_resources,
            "session_name"     => session_name(),
            "session_id"       => session_id()
        );

        view::assign("variables", json_encode($variables));
        view::assign("language",  json_encode(view::$language));

        request::addHeader("Content-Type: application/x-javascript");
        $this->setProtectedLayout("variables.html");

    }


}




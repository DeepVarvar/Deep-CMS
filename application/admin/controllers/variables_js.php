<?php



/**
 * admin submodule, javascript variables
 */

class variables_js extends baseController {


    public function index() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . ".*", true);


        /**
         * set main output context
         * and disable changes
         */

        view::setOutputContext("html");
        view::lockOutputContext();


        /**
         * get variables
         */

        $c = app::config();
        $variables = array(
            "language"         => member::getLanguage(),
            "admin_tools_link" => $c->site->admin_tools_link
        );


        $language = array(

          "document_delete_confirm"          => view::$language->document_delete_confirm,
          "document_tree_expand_collapse"    => view::$language->document_tree_expand_collapse,
          "edit_now"                         => view::$language->edit_now,
          "save"                             => view::$language->save,
          "delete_now"                       => view::$language->delete_now,
          "replace_now"                      => view::$language->replace_now,
          "image_upload"                     => view::$language->image_upload,
          "image_replace"                    => view::$language->image_replace,
          "make_is_master"                   => view::$language->make_is_master,
          "document_create_new"              => view::$language->document_create_new,
          "document_set_as_parent"           => view::$language->document_set_as_parent,
          "document_tree_show_only_tb"       => view::$language->document_tree_show_only_tb

        );


        /**
         * assign to view
         */

        view::assign("variables", json_encode($variables));
        view::assign("language", json_encode($language));

        request::addHeader("Content-Type: application/x-javascript");
        $this->setProtectedLayout("variables.html");


    }


}




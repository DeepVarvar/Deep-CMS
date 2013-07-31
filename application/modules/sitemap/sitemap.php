<?php



/**
 * sitemap module
 */

class sitemap extends baseController {


    /**
     * get sitemap data
     */

    public function index() {


        /**
         * check for exists dependency protected layout
         */

        $layoutName = "sitemap.html";

        if (!utils::isExistsProtectedLayout($layoutName)) {
            throw new memberErrorException("Sitemap error", "Dependency protected layout {$layoutName} is not exists");
        }


        /**
         * build sitemap
         */

        $documents = db::query("

            SELECT

                d.id,
                d.parent_id,
                d.page_name,
                d.page_alias

            FROM documents d

            WHERE d.is_publish = 1 AND d.in_sitemap = 1
            ORDER BY d.parent_id ASC, d.sort ASC, d.creation_date ASC

        ");



        /**
         * assign data into view
         */

        view::assign("sitemap", helper::makeTreeArray($documents));
        view::assign("page_name", view::$language->sitemap);

        $this->setProtectedLayout($layoutName);


    }


}




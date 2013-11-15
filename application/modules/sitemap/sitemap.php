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
         * check for exists dependency protected layout,
         * get sitemap,
         * assign data into view
         */

        $layoutName = "sitemap.html";
        if (!utils::isExistsProtectedLayout($layoutName)) {

            throw new systemErrorException(
                "Sitemap error",
                    "Dependency protected layout {$layoutName} is not exists"
            );

        }

        view::assign("sitemap_nodes", db::query(

            "SELECT id, lvl, lk, rk, parent_id, node_name, page_alias
                FROM tree WHERE is_publish = 1
                    AND in_sitemap = 1 ORDER BY lk ASC"

        ));

        $this->setProtectedLayout($layoutName);


    }


}




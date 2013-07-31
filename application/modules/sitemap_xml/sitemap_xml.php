<?php



/**
 * sitemap.xml module
 */

class sitemap_xml extends baseController {


    public function index() {


        /**
         * set unlimited working time
         */

        @ set_time_limit(0);
        @ ignore_user_abort();


        /**
         * attempt to set highly memory limit for generation long file
         * 512 megabytes is more for over 60000 url-items
         */

        @ ini_set("memory_limit", "512M");

        /**
         * set main output context
         * and disable changes
         */

        view::setOutputContext("xml");
        view::lockOutputContext();


        /**
         * available parameters on <url> item:
         *
         * <loc>        : url of document, REQUIRED!
         * <lastmod>    : (date) YYYY.MM.DD
         * <changefreq> : always|hourly|daily|weekly|monthly|yearly|never
         * <priority>   : (float) 0.0-1.0
         *
         * more info: http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
         */

        $sitemap = db::query("

            SELECT

                CONCAT('', '%s', d.page_alias) loc,
                DATE_FORMAT(last_modified,'%s') lastmod,
                d.change_freq changefreq,
                ROUND(d.search_priority,1) priority

            FROM documents d

            WHERE d.is_publish = 1
                AND d.page_alias NOT LIKE '%%http://%%'

            ORDER BY d.parent_id ASC, d.sort ASC

            ",

            app::config()->site->protocol . "://" . app::config()->site->domain,
            "%Y-%m-%d"

        );


        /**
         * assign sitemap into view
         */

        view::assign("urlset", $sitemap);


        /**
         * set custom sitemap XSD schema
         */

        view::setXSDSchema(

            array(

                "name" => "urlset",

                "attributes" => array(

                    array(
                        "name" => "xmlns",
                        "value" => "http://www.sitemaps.org/schemas/sitemap/0.9"
                    )

                ),

                "children" => array(

                    array(

                        "name" => "url",
                        "children" => array(

                            array("name" => "lastmod"),
                            array("name" => "changefreq"),
                            array("name" => "loc"),
                            array("name" => "priority")

                        )

                    )

                )

            )

        );


    }


}




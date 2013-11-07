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
         * and disable changes,
         * clear all before added public variables
         */

        view::setOutputContext("xml");
        view::lockOutputContext();
        view::clearPublicVariables();


        /**
         * available parameters on <url> item:
         *
         * <loc>        : url - REQUIRED!
         * <lastmod>    : (date) YYYY.MM.DD
         * <changefreq> : always|hourly|daily|weekly|monthly|yearly|never
         * <priority>   : (float) 0.0-1.0
         *
         * more info: http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
         */

        $sitemap = db::query("

            SELECT

                CONCAT('', '%s', page_alias) loc,
                DATE_FORMAT(last_modified,'%%Y-%%m-%%d') lastmod,
                change_freq changefreq,
                ROUND(searchers_priority,1) priority

            FROM tree

            WHERE is_publish = 1
                AND page_alias NOT LIKE '%%http://%%'

            ",

            app::config()->site->protocol
                . "://" . app::config()->site->domain

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

                "name"       => "urlset",
                "attributes" => array(

                    array(
                        "name"  => "xmlns",
                        "value" => "http://www.sitemaps.org/schemas/sitemap/0.9"
                    )

                ),

                "children" => array(

                    array(

                        "name"     => "url",
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




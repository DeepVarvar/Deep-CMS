<?php



/**
 * database class, mysqli wrapper
 */

abstract class db {


    /**
     * link to inside object, cache for all queryes
     * and count queries
     */

    protected static


        /**
         * mysqli object
         */

        $mysqli = null,


        /**
         * cache of results
         */

        $cache = array(
            "source"     => array(),
            "normalized" => array()
        ),


        /**
         * status of show single query for debug
         */

        $showQuery = false,


        /**
         * query counters
         */

        $c = array(

            "read"      => 0,
            "readcache" => 0,
            "change"    => 0

        );


    /**
     * set for single show query string for debug
     */

    public static function showQueryString() {
        self::$showQuery = true;
    }


    /**
     * connection to MySQL server
     */

    public static function connect($host, $user, $pass, $dbname, $port = null) {


        /**
         * WARNING for php 5.3.3!
         * if $port is a string, such as "3306",
         * mysqli::query() will not work,
         * even though mysqli_connect_errno() reports no error (value 0)!
         */

        self::$mysqli = @ new mysqli($host, $user, $pass, $dbname, (int) $port);

        if (self::$mysqli->connect_errno) {
           throw new systemErrorException(self::$mysqli->connect_errno, "Connect to MySQL server", self::$mysqli->connect_error);
        }

    }


    /**
     * set character set for connection
     */

    public static function setCharset($charset) {


        /**
         * my frend want show me bug of $mysqli->set_charset() function
         * hm.. maybe
         * but i'm don't take this bug.
         * OK, if you have this bug, write it:
         *
         * self::set("SET NAMES '{$charset}'");
         */

        self::$mysqli->set_charset($charset);

        if (self::$mysqli->errno) {
            throw new systemErrorException(self::$mysqli->errno, "Database error", self::$mysqli->error);
        }


    }


    /**
     * return escaped string
     */

    public static function escapeString($str) {

        if (self::$mysqli === null) {
           throw new systemErrorException("Database error", "Server is disconnected");
        }

        return self::$mysqli->real_escape_string(str_replace("%", "%%", $str));

    }


    /**
     * escape array items,
     * return escaped separated string
     */

    private static function escapeArray($arr) {

        foreach ($arr as $k => $item) {
            $arr[$k] = "'" . self::$mysqli->real_escape_string(str_replace("%", "%%", $item)) . "'";
        }

        return join(", ", $arr);

    }


    /**
     * method returned escaped and parsed query string
     */

    private static function buildQuery() {


        /**
         * get query string and params from arguments
         * escaped params before query
         */

        $args = func_get_args();
        $args = $args[0];

        $params = array();

        foreach ($args as $key => $arg) {

            if ($key == 0) {
                $query = $arg;
            } else {
                $params[] = is_array($arg) ? self::escapeArray($arg) : self::$mysqli->real_escape_string($arg);
            }

        }

        if (!isset($query)) {
            throw new systemErrorException("Database error", "Query is empty");
        }


        $queryString = vsprintf($query, $params);
        if (self::$showQuery) {

            self::$showQuery = false;
            echo "\n\n {$queryString} \n\n";

        }

        return $queryString;


    }


    /**
     * start transaction isolation
     */

    public static function begin() {
        self::$mysqli->query("START TRANSACTION");
    }


    /**
     * commit transaction
     */

    public static function commit() {
        self::$mysqli->query("COMMIT");
    }


    /**
     * private simple update or insert query,
     * return number of affected rows
     */

    private static function sendSetQuery($queryString) {


        /**
         * init single query to database
         */

        @ self::$mysqli->query($queryString);

        if (self::$mysqli->errno) {
            throw new systemErrorException(self::$mysqli->errno, "Database error", self::$mysqli->error . " ::: {$queryString}");
        }


        self::$c['change']++;
        return self::affectedRows();

    }


    /**
     * private simple query
     */

    private static function sendQuery($queryString) {


        /**
         * init multi query to database
         */

        self::$mysqli->multi_query($queryString);


        if (self::$mysqli->errno) {
            throw new systemErrorException(self::$mysqli->errno, "Database error", self::$mysqli->error . " ::: {$queryString}");
        }


        $result = array();
        self::$c['read']++;


        do {


            if ($res = self::$mysqli->store_result()) {
                while ($row = $res->fetch_assoc()) {
                    $result[] = $row;
                }
                $res->free();
            }


        } while (self::$mysqli->more_results() && self::$mysqli->next_result());


        return $result;


    }


    /**
     * method returned result from cache or empty array
     * type: "source" or "normalized"
     * source as default
     */

    private static function getResultFromCache($key, $type = "source") {


        if (array_key_exists($key, self::$cache[$type])) {

            self::$c['readcache']++;
            return self::$cache[$type][$key];

        }


        return array();


    }


    /**
     * result normalizer
     */

    private static function normalize($result) {


        if (sizeof($result) == 1) {

            $result = array_shift($result);

            if (sizeof($result) == 1) {
                $result = array_shift($result);
            }

        } else if (isset($result[0]) and sizeof($result[0]) == 1) {


            $output = array();
            foreach ($result as $item) {
                $output[] = array_shift($item);
            }

            $result = $output;
            unset($output);


        }


        return $result;


    }


    /**
     * public single simple update or insert query to DB
     */

    public static function set() {


        /**
         * get escaped query string from args
         * and receive query result
         */

        $query = self::buildQuery(func_get_args());


        /**
         * return result as true or false
         * for affected rows
         */

        return self::sendSetQuery($query);


    }


    /**
     * public single simple query without cache
     */

    public static function query() {


        /**
         * get escaped query string from args
         * and receive query result
         */

        $query = self::buildQuery(func_get_args());
        return self::sendQuery($query);


    }


    /**
     * return escaped and parsed query string
     */

    public static function buildQueryString() {
        return self::buildQuery(func_get_args());
    }


    /**
     * public single simple query used cache
     */

    public static function cachedQuery() {


        /**
         * get escaped query string from args
         * and receive query result
         */

        $query = self::buildQuery(func_get_args());


        /**
         * find result inside cache
         */

        $withCache = self::getResultFromCache($query);

        if ($withCache) {
            return $withCache;
        }


        /**
         * result on cache not found
         * receive result of query from DB
         */

        $result = self::sendQuery($query);


        /**
         * save result into local cache without normalize
         */

        self::$cache['source'][$query] = $result;
        return $result;


    }


    /**
     * public single simple normalized query used cache
     */

    public static function cachedNormalizeQuery() {


        /**
         * get escaped query string from args
         * and receive query result
         */

        $query = self::buildQuery(func_get_args());


        /**
         * find result inside cache
         */

        $withCache = self::getResultFromCache($query, "normalized");

        if ($withCache) {
            return $withCache;
        }


        /**
         * result on cache not found
         * receive query result from DB
         */

        $result = self::sendQuery($query);


        /**
         * save result into local cache with normalize
         */

        $result = self::normalize($result);
        self::$cache['source'][$query] = $result;
        return $result;


    }


    /**
     * public single simple normalized query without cache
     */

    public static function normalizeQuery() {


        /**
         * get escaped query string from args
         * and receive query result
         */

        $query = self::buildQuery(func_get_args());
        $result = self::sendQuery($query);
        return self::normalize($result);


    }


    /**
     * returned last affected count rows
     */

    public static function affectedRows() {
        return self::$mysqli->affected_rows;
    }


    /**
     * returned last insert ID
     */

    public static function lastID() {
        return self::$mysqli->insert_id;
    }


    /**
     * get counters
     */

    public static function readQCount() {
        return self::$c['read'];
    }

    public static function readCacheQCount() {
        return self::$c['readcache'];
    }

    public static function changeQCount() {
        return self::$c['change'];
    }

    public static function sumQCount() {
        return self::$c['read'] + self::$c['change'];
    }


    /**
     * close current connection
     */

    public static function close() {

        if (!self::$mysqli->connect_errno) {
            self::$mysqli->close();
        }

    }


}




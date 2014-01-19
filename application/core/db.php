<?php


/**
 * database class, mysqli wrapper
 */

abstract class db {


    /**
     * link to inside object, cache for all queryes
     * and count queries
     */

    protected static $mysqli = null;
    protected static $showQuery = false;

    protected static $cache = array(
        'source'     => array(),
        'normalized' => array()
    );

    protected static $c = array(
        'read'      => 0,
        'readcache' => 0,
        'change'    => 0
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

        self::$mysqli = new mysqli($host, $user, $pass, $dbname, (int) $port);
        if (self::$mysqli->connect_errno) {
           throw new systemErrorException(
                self::$mysqli->connect_errno,
                'Connect to MySQL server',
                self::$mysqli->connect_error
            );
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
            throw new systemErrorException(
                self::$mysqli->errno, 'Database error', self::$mysqli->error
            );
        }


    }


    /**
     * return escaped string
     */

    public static function escapeString($str) {
        return self::$mysqli->real_escape_string(str_replace('%', '%%', $str));
    }


    /**
     * escape array items,
     * return escaped separated string
     */

    public static function escapeArray($arr) {

        foreach ($arr as $k => $item) {
            $arr[$k] = "'" . self::$mysqli->real_escape_string(str_replace('%', '%%', $item)) . "'";
        }
        return join(',', $arr);

    }


    /**
     * method returned escaped and parsed query string
     */

    public static function buildQuery() {

        $args   = func_get_args();
        $args   = $args[0];
        $params = array();

        foreach ($args as $key => $arg) {
            if ($key == 0) {
                $query = $arg;
            } else {
                $params[] = is_array($arg)
                    ? self::escapeArray($arg)
                    : self::$mysqli->real_escape_string($arg);
            }
        }

        if (!isset($query)) {
            throw new systemErrorException('Database error', 'Query is empty');
        }

        $queryString = vsprintf($query, $params);
        if (self::$showQuery) {
            self::$showQuery = false;
            echo "\n\n" . $queryString . "\n\n";
        }
        return $queryString;

    }


    /**
     * private simple update or insert query,
     * return number of affected rows
     */

    private static function sendSetQuery($queryString) {

        self::$mysqli->query($queryString);
        if (self::$mysqli->errno) {
            throw new systemErrorException(
                self::$mysqli->errno,
                'Database error',
                self::$mysqli->error . ' ::: ' . $queryString
            );
        }

        self::$c['change']++;
        return self::affectedRows();

    }


    /**
     * private silent simple update or insert query,
     * return number of affected rows
     */

    private static function sendSilentSetQuery($queryString) {

        @ self::$mysqli->query($queryString);
        self::$c['change']++;
        return self::affectedRows();

    }


    /**
     * private simple query
     */

    private static function sendQuery($queryString) {

        self::$mysqli->multi_query($queryString);
        if (self::$mysqli->errno) {
            throw new systemErrorException(
                self::$mysqli->errno,
                'Database error',
                self::$mysqli->error . ' ::: ' . $queryString
            );
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

    private static function getResultFromCache($key, $type = 'source') {

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

        $query = self::buildQuery(func_get_args());
        return self::sendSetQuery($query);

    }


    /**
     * public silent single simple update or insert query to DB
     */

    public static function silentSet() {

        $query = self::buildQuery(func_get_args());
        return self::sendSilentSetQuery($query);

    }


    /**
     * public single simple query without cache
     */

    public static function query() {

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

        $query = self::buildQuery(func_get_args());
        if ($withCache = self::getResultFromCache($query)) {
            return $withCache;
        }

        $result = self::sendQuery($query);
        self::$cache['source'][$query] = $result;
        return $result;

    }


    /**
     * public single simple normalized query used cache
     */

    public static function cachedNormalizeQuery() {

        $query = self::buildQuery(func_get_args());
        if ($withCache = self::getResultFromCache($query, 'normalized')) {
            return $withCache;
        }

        $result = self::sendQuery($query);
        $result = self::normalize($result);
        self::$cache['source'][$query] = $result;
        return $result;

    }


    /**
     * public single simple normalized query without cache
     */

    public static function normalizeQuery() {

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



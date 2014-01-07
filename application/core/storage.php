<?php


/**
 * session storage class
 */

abstract class storage {


    /**
     * storage key of session array
     */

    protected static $storageKey = '__storage';


    /**
     * initialization session storage
     */

    public static function init() {

        $sessionName = app::config()->system->session_name;
        session_name($sessionName);

        if (array_key_exists($sessionName, $_POST)
                and $sessionID == ((string) $_POST[$sessionName])) {

            session_id($sessionID);

        }


        /**
         * php.ini used to have session.gc_probability=0 with the comment:
         *
         * "This is disabled in the Debian packages,
         * due to the strict permissions on /var/lib/php5".
         * The strict permissions remain, but session.gc_probability is now enabled.
         *
         * By default there's a 0.1% chance that a call to session_start()
         * will trigger this, but setting session.gc_divisor=1
         * makes this easily reproducible.
         *
         * http://somethingemporium.com/2007/06/obscure-error-with-php5-on-debian-ubuntu-session-phpini-garbage
         *
         */

        @ session_start();
        if (!isset($_SESSION[self::$storageKey])) {
            self::clear();
        }

    }


    /**
     * save data into storage
     */

    public static function write($key, $data) {
        $_SESSION[self::$storageKey][$key] = $data;
    }


    /**
     * remove storage data with key
     */

    public static function remove($key) {

        if (isset($_SESSION[self::$storageKey][$key])) {
            unset($_SESSION[self::$storageKey][$key]);
        }

    }


    /**
     * read storage data,
     * return data or null if not exists data
     */

    public static function read($key) {
        return self::exists($key) ? $_SESSION[self::$storageKey][$key] : null;
    }


    /**
     * read and unset (like shift stack) storage data,
     * return data or null if not exists data
     */

    public static function shift($key) {

        $data = self::read($key);
        self::remove($key);
        return $data;

    }


    /**
     * check for exsists data
     */

    public static function exists($key) {
        return array_key_exists($key, $_SESSION[self::$storageKey]);
    }


    /**
     * clear all storage data
     */

    public static function clear() {

        $_SESSION = array();
        $_SESSION[self::$storageKey] = array();

    }


}



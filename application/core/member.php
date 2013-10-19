<?php



/**
 * member environment class,
 * permission, profile, language
 */

abstract class member {


    protected static


        /**
         * main working cache storage key
         */

        $storageKey = "__member_cache",


        /**
         * default member profile
         *
         * status:
         *
         * 0 - free
         * 1 - readonly
         * 2 - banned
         * 3 - not confirm from email (not exists, like deleted)
         *
         * language and member name set only after init,
         * because the value of language is not yet known,
         * may be member name will be the "Guest" with selected language
         */

        $profile = array(

            "auth"           => false,
            "hash"           => null,
            "id"             => null,
            "group_id"       => null,
            "group_priority" => 1001,
            "status"         => 0,
            "language"       => null,
            "timezone"       => null,
            "login"          => null,
            "email"          => null,
            "password"       => null,
            "avatar"         => null

        ),


        /**
         * default member permissions
         * empty array denied from all delegated actions
         */

        $permissions = array();


    /**
     * init member
     */

    public static function init() {


        $config = app::config();


        /**
         * init member cache into storage
         */

        if (!storage::exists(self::$storageKey)) {
            storage::write(self::$storageKey, array());
        }


        /**
         * set default guest dependency values
         */

        self::$profile['timezone'] = $config->site->default_timezone;
        self::$profile['language'] = $config->site->default_language;

        self::$profile['login']  = view::$language->guest;
        self::$profile['avatar'] = $config->site->no_avatar;


        /**
         * check auth with main inner cookie, openID, etc.
         */

        switch (true) {


            /**
             * main inner cookie
             */

            case isset($_COOKIE["{$config->system->session_name}member"]):
                self::cookieAuth();
            break;


        }


    }


    /**
     * is attempt sign in now?
     */

    public static function isAttemptLogin() {


        $attempt = false;

        if (isset($_POST['login'], $_POST['password'], $_POST['sign_in'])) {

            self::logout();
            $attempt = true;

        }


        return $attempt;


    }


    /**
     * global member sign in action,
     * return true or false
     */

    public static function logged() {


        /**
         * lead data
         */

        $login = filter::input((string) $_POST['login'])
            ->htmlSpecialChars()
            ->getData();

        $password = helper::getHash((string) $_POST['password']);


        /**
         * get member
         */

        $member = db::normalizeQuery("

            SELECT

                u.id,
                u.group_id,
                u.status,
                u.timezone,
                u.language,
                u.login,
                u.avatar,
                u.email,
                u.password,
                u.working_cache,
                g.priority group_priority

            FROM users u
            LEFT JOIN groups g ON g.id = u.group_id

            WHERE (u.login = '%s' OR u.email = '%s')
            AND u.status < 3 AND u.password = '%s'
            LIMIT 1

            ",

            $login,
            $login,
            $password

        );


        /**
         * wrong request
         */

        if (!$member) {
            return false;
        }


        /**
         * set member data for database
         */

        self::setData($member);


        /**
         * fix return bool type
         */

        return true;


    }


    /**
     * set main profile data
     */

    private static function setData($data) {


        foreach (array_keys(self::$profile) as $k) {

            if (array_key_exists($k, $data)) {
                self::$profile[$k] = $data[$k];
            }

        }


        self::$profile['auth'] = true;
        self::$profile['hash'] = self::getMainHash();

        self::setPermissions();
        storage::write("__member_cache", json_decode($data['working_cache'], true));


        $c = app::config();

        if ($c->system->cookie_expires_time >= 2147483646) {
            throw new systemErrorException(view::$language->error, view::$language->cookie_expires_is_too_long);
        }

        $featureTime = time() + $c->system->cookie_expires_time;
        if ($featureTime >= 2147483646) {
            throw new systemErrorException(view::$language->error, view::$language->cookie_expires_is_too_long);
        }

        setcookie("{$c->system->session_name}member", self::$profile['hash'], $featureTime, "/");


        /**
         * timezone and language settings
         */

        if (!self::$profile['timezone']) {
            self::$profile['timezone'] = $c->site->default_timezone;
        }

        if (!self::$profile['language']) {
            self::$profile['language'] = $c->site->default_language;
        }

        view::setLanguage(self::$profile['language']);


    }


    /**
     * return member hash value
     */

    private static function getMainHash() {


        $p = self::$profile;

        return helper::getHash(

            $p['id'] . $p['login'] . $p['password'] .
            $p['group_id'] . $p['group_priority'] . $p['email']

        );


    }


    /**
     * set member role permissions with group_id
     */

    private static function setPermissions() {


        if (self::$profile['group_id'] !== null) {


            self::$permissions = db::query("

                SELECT

                    p.name

                FROM permissions p, group_permissions gp
                WHERE p.id = gp.permission_id
                AND gp.group_id = %u

                ",

                self::$profile['group_id']

            );


        }


    }


    /**
     * main cookie auth
     */

    private static function cookieAuth() {


        /**
         * get member
         */

        $member = db::normalizeQuery("

            SELECT

                u.id,
                u.group_id,
                u.status,
                u.timezone,
                u.language,
                u.login,
                u.avatar,
                u.email,
                u.password,
                u.working_cache,
                g.priority group_priority

            FROM users u
            LEFT JOIN groups g ON g.id = u.group_id

            WHERE u.hash = '%s'
            AND u.status < 3
            LIMIT 1

            ",

            htmlspecialchars((string) $_COOKIE[app::config()->system->session_name . "member"])

        );


        /**
         * wrong cookie
         */

        if (!$member) {
            self::flushLogout();
        }


        /**
         * set member data for database
         */

        self::setData($member);


    }


    /**
     * store member data on database
     */

    public static function storeData() {


        /**
         * get main internal cache data from session storage
         */

        $memberCache = @ json_encode(storage::read("__member_cache"));
        if (!$memberCache) {
            $memberCache = "[]";
        }


        /**
         * update main data on database
         */

        if (self::$profile['auth']) {


            db::set("

                UPDATE users SET

                    last_visit = NOW(),
                    last_ip = '%s',
                    working_cache = '%s'

                WHERE id = %u

                ",

                request::getClientIP(),
                $memberCache,
                self::$profile['id']

            );


        }


    }


    /**
     * get main working cache data from storage with key
     */

    public static function getStorageData($key) {


        if (!utils::likeString($key)) {
            throw new systemErrorException("Member error", "Storage data key is not string");
        }


        $cache = storage::read(self::$storageKey);

        return $cache

            ? ( array_key_exists($key, $cache) ? $cache[$key] : array() )
            : array();


    }


    /**
     * set main working cache data into storage with key
     */

    public static function setStorageData($key, $data) {


        if (!utils::likeString($key)) {
            throw new systemErrorException("Member error", "Storage data key is not string");
        }


        $cache = storage::read(self::$storageKey);

        if (!$cache) {
            $cache = array();
        }

        $cache[$key] = $data;
        storage::write(self::$storageKey, $cache);


    }


    /**
     * logout, clean member session and cookie
     */

    public static function logout() {
        setcookie(app::config()->system->session_name . "member", "", -1, "/");
    }


    /**
     * flush logout with redirect, jump to homepage
     */

    public static function flushLogout() {
        self::logout();
        request::redirect("/");
    }


    /**
     * return full profile data of member
     */

    public static function getProfile() {
        return self::$profile;
    }


    /**
     * return existst permissions
     */

    public static function getPermissions() {
        return self::$permissions;
    }


    /**
     * return existst of one permission
     */

    public static function isPermission($name) {

        foreach (self::$permissions as $item) {
            if ($item['name'] == $name) {
                return true;
            }
        }

        return false;

    }


    /**
     * return auth status of member
     */

    public static function isAuth() {
        return self::$profile['auth'];
    }


    /**
     * return root status of member
     */

    public static function isRoot() {
        return (self::$profile['id'] === "0");
    }


    /**
     * return priority number of member
     */

    public static function getPriority() {
        return self::$profile['group_priority'] !== null ? self::$profile['group_priority'] : 1001;
    }


    /**
     * return #ID of member
     */

    public static function getID() {
        return self::$profile['id'];
    }


    /**
     * return group #ID of member
     */

    public static function getGroupID() {
        return self::$profile['group_id'];
    }


    /**
     * return member timezone
     */

    public static function getTimezone() {
        return self::$profile['timezone'];
    }


    /**
     * return member language
     */

    public static function getLanguage() {
        return self::$profile['language'];
    }


}




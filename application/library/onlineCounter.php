<?php



/**
 * online counter component class
 */

abstract class onlineCounter {


    private static $onlineInterval = 10; // minute
    private static $counterData    = null;

    public static function init() {

        if (!member::isAuth()) {
            db::set(
                "REPLACE INTO online_guests (session_id, last_visit)
                    VALUES ('%s', NOW())", session_id()
            );
        }

    }

    public static function getInterval() {
        return self::$onlineInterval;
    }

    public static function getMembersCount() {
        self::shakeCounterData();
        return self::$counterData[0]['cnt'];
    }

    public static function getGuestsCount() {
        self::shakeCounterData();
        return self::$counterData[1]['cnt'];
    }

    public static function getAllCount() {
        return self::getGuestsCount() + self::getMembersCount();
    }

    public static function getInfo() {

        $i = self::getInterval();
        $g = self::getGuestsCount();
        $m = self::getMembersCount();
        $a = $g + $m;

        $min = helper::plural($i, view::$language->ocounter_min1,
            view::$language->ocounter_min3,
                view::$language->ocounter_min5);

        $peo = helper::plural($a, view::$language->ocounter_people1,
            view::$language->ocounter_people3,
                view::$language->ocounter_people5);

        $gue = helper::plural($g, view::$language->ocounter_guest1,
            view::$language->ocounter_guest3,
                view::$language->ocounter_guest5);

        $mem = helper::plural($m, view::$language->ocounter_member1,
            view::$language->ocounter_member3,
                view::$language->ocounter_member5);

        return view::$language->ocounter_on_last . " " . $i . " " . $min . " "
                . view::$language->ocounter_visited_on_site . ": " . $a . " "
                . $peo . ", " . view::$language->ocounter_of_them . " "
                . $g . " " . $gue . " " . view::$language->{"and"} . " "
                . $m . " " . $mem . ".";

    }

    public static function getExtendedMembersList($withIDs = false) {

        $withIDs = $withIDs ? "id," : "";
        $members = db::cachedQuery(
            "SELECT {$withIDs} login FROM users WHERE (last_visit + INTERVAL "
                . self::$onlineInterval . " MINUTE) > NOW() AND id > 0"
        );

        if (!$withIDs) {
            foreach ($members as $k => $item) {
                $members[$k] = $item['login'];
            }
        }

        return $members;

    }

    private static function shakeCounterData() {

        if (self::$counterData === null) {

            $lastInterval = "last_visit + INTERVAL "
                . self::$onlineInterval . " MINUTE";

            self::$counterData = db::query(
                "SELECT COUNT(1) cnt FROM users
                    WHERE ({$lastInterval}) > NOW() AND id > 0 UNION ALL
                    SELECT COUNT(1) cnt FROM online_guests
                        WHERE ({$lastInterval}) > NOW()"
            );

        }

    }


}




<?php


/**
 * online counter component class
 */

abstract class onlineCounter {


    private static $onlineInterval = 10; // minutes
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

        $min = helper::plural(
            $i,
            view::$language->online_counter_min1,
            view::$language->online_counter_min3,
            view::$language->online_counter_min5
        );

        $peo = helper::plural(
            $a,
            view::$language->online_counter_people1,
            view::$language->online_counter_people3,
            view::$language->online_counter_people5
        );

        $gue = helper::plural(
            $g,
            view::$language->online_counter_guest1,
            view::$language->online_counter_guest3,
            view::$language->online_counter_guest5
        );

        $mem = helper::plural(
            $m,
            view::$language->online_counter_member1,
            view::$language->online_counter_member3,
            view::$language->online_counter_member5
        );

        return view::$language->online_counter_on_last . ' ' . $i . ' ' . $min
            . ' ' . view::$language->online_counter_visited_on_site . ': ' . $a
            . ' ' . $peo . ', ' . view::$language->online_counter_of_them
            . ' ' . $g . ' ' . $gue . ' ' . view::$language->online_counter_and
            . ' ' . $m . ' ' . $mem . '.';

    }

    public static function getExtendedMembersList($withIDs = false) {

        $withIDs = $withIDs ? 'u.id,' : '';
        $members = db::cachedQuery(
            'SELECT ' . $withIDs . ' u.login FROM users u
                LEFT JOIN groups g ON g.id = u.group_id
                WHERE (u.last_visit + INTERVAL ' . self::$onlineInterval . ' MINUTE) > NOW()
                    AND (g.priority IS NULL OR g.priority > 0)'
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

            $lastInterval = 'last_visit + INTERVAL '
                . self::$onlineInterval . ' MINUTE';

            self::$counterData = db::query(
                'SELECT COUNT(1) cnt FROM users u
                    LEFT JOIN groups g ON g.id = u.group_id
                        WHERE (u.' . $lastInterval . ') > NOW()
                            AND (g.priority IS NULL OR g.priority > 0)
                    UNION ALL SELECT COUNT(1) cnt FROM online_guests
                        WHERE (' . $lastInterval . ') > NOW()'
            );

        }

    }


}



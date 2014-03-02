<?php


/**
 * guests online count runner
 */

abstract class queue200_guestsOnlineCounter {

    public static function run() {
        onlineCounter::init();
    }

}



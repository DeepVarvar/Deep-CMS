<?php



/**
 * guests online count runner
 */

abstract class B_guestsOnlineCounter {

    public static function run() {
        onlineCounter::init();
    }

}




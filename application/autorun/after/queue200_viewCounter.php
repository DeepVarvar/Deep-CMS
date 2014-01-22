<?php


/**
 * view count runner
 */

abstract class queue200_viewCounter {

    public static function run() {
        viewCounter::init();
    }

}



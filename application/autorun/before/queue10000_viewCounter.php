<?php


/**
 * view count runner
 */

abstract class queue10000_viewCounter {

    public static function run() {
        viewCounter::loadMainLanguage();
    }

}



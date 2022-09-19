<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/13
 * Time: 10:34
 */

class AllInstrumentsTests {

    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite("Instrument");
        $suite->addTestSuite("InstrumentTest");

        return $suite;
    }

}
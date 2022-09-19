<?php
class AllServiceTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Service');
        $suite->addTestSuite('EmailServiceTest');

        return $suite;
    }
}

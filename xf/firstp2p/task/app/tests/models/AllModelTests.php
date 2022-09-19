<?php
class AllModelTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Model');
        $suite->addTestSuite('TaskTest');

        return $suite;
    }
}

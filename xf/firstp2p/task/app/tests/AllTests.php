<?php
include __DIR__.'/../scripts/init.php';

$loader = new \Phalcon\Loader();
$loader->registerDirs(array(
    APP_MODULE_DIR . "/app/tests/services",
    APP_MODULE_DIR . "/app/tests/daos",
    APP_MODULE_DIR . "/app/tests/models",
    APP_MODULE_DIR . "/app/tests/instruments",
))->register();

class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();
        $suite->addTest(AllServiceTests::suite());
        $suite->addTest(AllModelTests::suite());
        $suite->addTest(AllInstrumentsTests::suite());
        return $suite;
    }
}

<?php

require dirname(__DIR__).'/AbstractTestCase.php';

class LoggerTest extends AbstractTestCase
{

    public function testDebug()
    {
        Logger::debug('This is an debug log');
        Logger::debug($_SERVER);
    }

    public function testInfo()
    {
        Logger::info('This is an info log');
    }

    public function testWarn()
    {
        Logger::warn('This is an warn log');
    }

    public function testError()
    {
        Logger::error('This is an error log');
    }

}

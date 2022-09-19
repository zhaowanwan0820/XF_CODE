<?php
include __DIR__.'/../Instrument/EnvHandler.php';

use NCFGroup\Task\Services\EmailService;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Task\Instrument\EnvHandler;

class TestTask
{
    public static function testSendAsync()
    {
        EnvHandler::requireInit();
        $mailSvc = new EmailService();
        $mailSvc->sendAsync(array('jingxu@ucfgroup.com','dengyi@ucfgroup.com'), 'test task host:'.gethostname(), XDateTime::now()->toString().gethostname());
    }

    public static function testSendSync() {
        EnvHandler::requireInit();
        $mailSvc = new EmailService();
        $mailSvc->sendSync(array('jingxu@ucfgroup.com','dengyi@ucfgroup.com'), 'test task host:'.gethostname(), XDateTime::now()->toString().gethostname());
    }
}

//TestTask::testSendAsync();
TestTask::testSendSync();


<?php

use NCFGroup\Task\Services\EmailService;

/**
 * @author jingxu
 *
 * @backupGlobals disabled
 */
class EmailServiceTest extends PHPUnit_Framework_TestCase
{

    public function testSend()
    {
        $mailSvc = new EmailService();
        $testAttachmentFilePath = '/tmp/testemail.txt';
        file_put_contents($testAttachmentFilePath, 'testemail');
        $mailSvc->sendSync(array('dengyi@ucfgroup.com'), 'jx', 'email service test 同步', array(array('name' => 'jxsb', 'path' => $testAttachmentFilePath)));
    }

    public function testSendAsync()
    {
        $mailSvc = new EmailService();
        $mailSvc->sendAsync(array('jingxu@ucfgroup.com'), 'jx', 'email service test 异步');
    }

}

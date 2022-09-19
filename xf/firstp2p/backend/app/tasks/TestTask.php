<?php
/**
 * testTask
 * 根据库中表生成model对象
 * php init.php model main DB_NAME
 *
 * Created by guweigang@, Updated by wangjiansong@
 * @package default
 */
use NCFGroup\Ptp\services\PtpMsgBoxService;
use NCFGroup\Protos\Ptp\RequestMsgBoxSend;
class TestTask extends \Phalcon\CLI\Task
{
    public function mainAction()
    {
        $request = new RequestMsgBoxSend();
        $request->setUserId(40);
        $request->setType(36);
        $request->setTitle('121212');
        $request->setContent('sadasdasd');
        $service = new PtpMsgBoxService();
        var_dump($service->msgBoxSend($request));

    }
}

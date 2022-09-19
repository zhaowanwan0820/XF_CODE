<?php

//daemon  cd /apps/product/nginx/htdocs/firstp2p/scripts && nohup /apps/product/php/bin/php email_worker.php &

ini_set('memory_limit', '512M');
require_once(dirname(__FILE__) . '/../app/init.php');
set_time_limit(0);
require_once(dirname(__FILE__) . '/../system/utils/logger.php');
//require_once(dirname(__FILE__) . '/../system/utils/es_mail.php');
require_once dirname(__FILE__).'/../libs/common/functions.php';
FP::import("libs.common.dict");
//error_reporting(E_ALL);ini_set('display_errors', 1);
use libs\db\MysqlDb;
use core\service\AttachmentService;
use libs\mail\Mail;
use core\service\DealMsgListService;

class EmailWorker {
    //处理队列 发送邮件业务
    function run() {
        while (true) {
            if ($msg = SiteApp::init()->prior_queue->receive()) {
                $this->_issucc = false;
                if (empty($msg['address']) || empty($msg['content']) || empty($msg['title'])) {
                    $this->_errmsg = "address or content or title emtpy";
                } else {
                    $DealMsg = new DealMsgListService();
                    $rs = $DealMsg->sendMsg($msg);
                    unset($DealMsg);
                }
                SiteApp::init()->prior_queue->delete();
            } else {
                sleep(1);
            }
        }
    }
}

$ew = new EmailWorker();
$ew->run();

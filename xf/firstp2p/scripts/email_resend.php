<?php
/** 大概只会执行一次 不用加crontab
 * @author zhanglei5 2014-6-1
 */

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

use core\service\DealMsgListService;
use libs\utils\Logger;
set_time_limit(0);

/**
 * 根据日期重发邮件
 */
class EmailResend {
    const COUNT_SEND_LIMIT = 10;

    function resend($datetime) {
        $service = new DealMsgListService();
        $time = strtotime($datetime);
        if (!$time) {
            echo "error";
            exit;
        }
        
        // 给title预留的字段
        $y = date("Y", $time);
        $m = date("m", $time);

        // 减8小时
        $list_no_send = $service->getNotSendByTime($time-28800);

        $cnt = 0;
        foreach ($list_no_send as $val) {
            if ($cnt % self::COUNT_SEND_LIMIT == 0) {
                sleep(1);
            }

            $data = array(
                "content" => stripslashes($val['content']), 
                "title" => $val['title'],
                "is_html" => true,
                "address" => $val['dest'],
                "id" => $val['id'],
            );
            SiteApp::init()->prior_queue->send($data);

            $log = array(
                "title" => $val['title'],
                "email" => $val['dest'],
            );
            Logger::wLog($log);
            $cnt++;
        }
        $msgcneter->save();
    }
}

$datetime = $argv[1];
$obj = new EmailResend();
$obj->resend($datetime);

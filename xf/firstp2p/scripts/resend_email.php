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
//error_reporting(E_ALL);ini_set('display_error', 1);
set_time_limit(0);
class resend_email {
    public $deal_msg_list_service;

    function resend_email() {
        $this->deal_msg_list_service  = new DealMsgListService();
    }

    function run() {
            $time = strtotime('2014-06-19 16:00:00');   //线上 昨天晚上12点的时间戳
            // 6月20号凌晨0点之后 没有发送的邮件(包括对账单)
            $list_not_send = $this->deal_msg_list_service->getNotSendByTime($time);
            //var_dump($list_not_send);
            // 6月20号凌晨0点之前  对账单没有发送的邮件
            $title = '网信理财电子对账单 - 2014年5月份';
            $list_not_send_month = $this->deal_msg_list_service->getNotSendByTime($time,'<',$title);
            //var_dump($list_not_send_month);
            // 对账单发送失败的
            $list_faild = $this->deal_msg_list_service->getFaildByTitle($title);
           // var_dump($list_faild);  die;
            $list = array_merge($list_faild,$list_not_send,$list_not_send_month);
            $cnt = 0;
			//var_dump($list);	die;
            foreach ($list as $val) {
                if($cnt % 100 == 0) {
                	if(isset($msgcenter)){
                	    $msgcenter->save();
                        unset($msgcenter);
                        sleep(1);
                	}
                	$msgcenter = new msgcenter();

                }

                $title = $val['title'];
                $log['content'] = $content = $val['content'];
                $log['email'] = $email = $val['dest'];
                $msgcenter->setMsg($email, 0, $content, false, $title);
                Logger::wLog($log); //  写入日志  以防止意外，可以追踪
                $cnt++;
            }
            $msgcenter->save();
    }
}

$obj = new resend_email();
$obj->run();





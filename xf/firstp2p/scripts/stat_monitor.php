<?php
/**
 * 每天8点  查看指定类型(日报周报 投资 借款)是否有 发送失败的邮件 如果有则发送报警邮件
 * 0 8 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php email_monitor.php
 * @author zhanglei5 2014-8-18
 */

//error_reporting(E_ALL);ini_set('display_errors', 1);
require_once(dirname(__FILE__) . '/../app/init.php');
set_time_limit(0);
require_once(dirname(__FILE__) . '/../system/utils/logger.php');
require_once dirname(__FILE__).'/../libs/common/functions.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
FP::import("libs.common.dict");

use libs\mail\Mail;

class email_monitor
{
    private $_reportList;   // 获取日报接收人
    private $_loadList;     // 投资数据
    private $_bidList;      //  借款数据
    private $_warnList;
    private $_keyHash;
    private $_notReceive;
    private $_notSend;
    private $_day;
    private $_haveFaild;    //是否有失败的记录，如果有才发送邮件

    function __construct() {
        $this->_reportList = dict::get('STATISTICS_EMAIL_NEW');       
        $this->_loadList = dict::get('DEAL_LOAD_EMAIL');    
        $this->_bidList = dict::get('DEAL_BID_EMAIL'); 
        $this->_warnList = array();
        $this->_notReceive = array();
        $this->_notSend= array();
        $this->_keyHash = array('report'=>'日报','bid'=>'借款数据','load'=>'投资数据','week'=>'周报');
        $this->_haveFaild = 0;
    }

    function run(){
        
        $this->_day = $day = to_date(get_gmtime()-24 * 60 * 60, "Y年m月d日"); //昨天的时间 //day = date('Y年m月d日',time());
        $dayTitle = "网信理财 {$day} 日报";
        $bidTitle = "网信理财 {$day} 借款数据概况";
        $loadTitle = "网信理财 {$day} 投资数据概况";

        FP::import("libs.common.dict");
        
        $msg = new \core\service\DealMsgListService();
        // 得到日报发送失败 "或没有发送的
        $reportList = $msg->getListByTitleAndEmail($dayTitle,$this->_reportList);
        $this->_warnList['report'] = $this->_compare($reportList,$this->_reportList,'report');

        // 得到借款 发送失败 或没有发送的
        $bidList = $msg->getListByTitleAndEmail($bidTitle,$this->_bidList); 
        $this->_warnList['bid'] = $this->_compare($bidList,$this->_bidList,'bid');

        // 得到投资 发送失败 或没有发送的
        $loadList = $msg->getListByTitleAndEmail($loadTitle,$this->_loadList);
        $this->_warnList['load'] = $this->_compare($loadList,$this->_loadList,'load');

        //判断是否为周日 如果是周日则 比较周报
        //$isSunday = date('N',get_gmtime()); //$isSunday = 7;
        $isSunday = date('N',time()); 
        if($isSunday == 7) {
            $weekTitle = "网信理财 ";
            $weekTitle .= to_date(get_gmtime() - 7*24 * 60 * 60, "Y年第W周(".to_date(get_gmtime()-7*24 * 60 * 60, "Y年m月d日").'-'.to_date(get_gmtime()-24*60*60,                "Y年m月d日").") 周报");
            $weekList = $msg->getListByTitleAndEmail($weekTitle,$this->_reportList);
            $this->_warnList['week'] = $this->_compare($weekList,$this->_reportList,'week');
        }
        $this->_send();
    }

    /**
     * _compare 
     * 比较出来需要报警的邮箱
     * @author zhanglei5 <zhanglei5@group.com> 
     * 
     * @param array $list 从数据库查出来的记录集合
     * @param array $emails 从配置中读取出来的邮箱集合
     * @access private
     * @return void
     */
    private function _compare($list,$emails,$key) {
        $warnMails = array();   // 需要发送报警的邮箱
        $receivedMails = array();   // 已经接受的邮箱
        foreach($list as $v) {
            $mail = $v['dest'];
            if ($v['is_send'] == 0) {   //没发送的
                $this->_notSend[$key][$mail] = 1;
            } elseif ($v['is_received'] == 1) { //已发送 并成功收到
                $receivedMails[] = $mail;
            } elseif ($v['is_received'] == 0) { //已发送 没收到的
                $this->_notReceive[$key][$mail] = 1;
            }
        }
        $warnMails = array_diff($emails,$receivedMails);    //返回的是 没发送的和发送没收到的
        if(count($warnMails)) { // 如果有 则发送邮件
            $this->_haveFaild = 1;
        }
        return $warnMails;
    } 

    private function _send() {
        if ($this->_haveFaild) {    //如果有失败的邮件才发送
            //开始写入邮件队列
            $msgcenter = new Msgcenter();
            $msgList = dict::get('MSG_WARN_EMAIL');// 邮件发送失败报警接收人
            $smsList = dict::get('MSG_WARN_MOBILE');// 短信发送失败报警接收人
            $content = '发送失败的邮箱如下:<br>';
            foreach($this->_warnList as $key => $sub) {
                if(is_array($sub) && count($sub)) {
                    $content .= $this->_keyHash[$key].':<br>';
                    foreach($sub as $email) {
                        $content .= " $email ";
                        if (isset($this->_notSend[$key][$email]) && $this->_notSend[$key][$email] == 1) {
                            $content .= " 记录已生成,未发送";
                        } elseif (isset($this->_notReceive[$key][$email]) && $this->_notReceive[$key][$email] == 1) {
                            $content .= " 邮件已发送,未到达用户";
                        } else {
                            $content .= " 记录未生成";
                        }
                        $content .= "<br>";
                    }
                }
            }
            //发送给需要报警的人
            $title = '邮件报警-'.$this->_day;
            foreach ($msgList as $msgMail) {
                $msgcenter->setMsg($msgMail, 0, $content, false, $title);
            }

            /*
             * 发送短信
            $sms_content = str_replace('<br>',' ',$content);
            foreach ($smsList as $mobile) {
                $msgcenter->setMsg($mobile,0,$sms_content,'','');
            }
             */
            $msgcenter->save();
        }
    }
}

$obj = new email_monitor();
$obj->run();

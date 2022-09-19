<?php
/**
 * @desc .....
 * User: jinhaidong
 * Date: 2015/9/7 18:51
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__) . '/../system/libs/msgcenter.php';
\FP::import("libs.utils.logger");
\FP::import("libs.common.dict");

class JfBillDetail {

    public function run($date=false) {
        $day = empty($date) ? date('Y-m-d 00:00:00', strtotime('-1 day')) : $date;
        $startTime = strtotime($day);
        $endTime = strtotime($day)+86399;

        $res = $this->work($startTime, $endTime);
        echo $res ? "success" : "fail";
    }

    // 每天15:00执行的方法
    public function run15() {
        $day = date('Ymd', time());
        
        // 前一天的23:00-14:30
        $startTime = strtotime($day) - 3600;
        $endTime = strtotime($day) + 14.5*3600 -1;

        $res = $this->work($startTime, $endTime);
        echo $res ? "success" : "fail";
    }

    // 每天23:30执行的方法
    public function run23() {
        $day = date('Ymd', time());
        
        // 当天的14:30-23:00
        $startTime = strtotime($day) + 14.5*3600;
        $endTime = strtotime($day) + 23*3600 -1;

        $res = $this->work($startTime, $endTime);
        echo $res ? "success" : "fail";
    }

    private function work($startTime, $endTime) {
        /** 投资数据汇总 */
        $bidTotalData = $this->getJfOrderData($startTime,$endTime);

        /** 回款数据汇总 */
        $repayTotalData = $this->getJfRepayData($startTime,$endTime);

        if ($bidTotalData['total'] == '0' && $repayTotalData['total'] == '0') {
            // 无投资、回款记录则不发送邮件
            return true;
        }

        $subject = '即付每日对账汇总  '.date('Y-m-d H:i',$startTime) . '-' . date('Y-m-d H:i',$endTime);
        $content = $this->formatData($bidTotalData,$repayTotalData);
        $to = dict::get("JF_BILL_EMAIL");

        return $this->sendMail($subject, $content, $to);
    }

    private function formatData($bidTotalData,$repayTotalData) {
        $str="投资笔数:".$bidTotalData['total'] ."<br />";
        $str.="投资总额:".$bidTotalData['money'] ."<br />";
        $str.="回款笔数:".$repayTotalData['total'] ."<br />";
        $str.="回款总额:".$repayTotalData['totalMoney'] ."<br />";
        $str.="回款本金:".$repayTotalData['money'] ."<br />";
        $str.="回款利息:".$repayTotalData['interest'] ."<br />";
        $str.="提前还款补偿金:".$repayTotalData['prepayClaim'] ."<br />";
        $str.="逾期罚息:".$repayTotalData['overDue'] ."<br />";
        //$str.="总差额:" . ($bidTotalData['money'] - $repayTotalData['totalMoney'])  ."<br />";
        //$str.="本金差额:" . ($bidTotalData['money'] - $repayTotalData['money']);
        return $str;
    }

    private function sendMail($subject, $content, $to) {
        $msgcenter = new msgcenter();
        foreach ($to as $email) {
            $msg_count = $msgcenter->setMsg($email, 0, $content, false, $subject);
        }
        return $msgcenter->save();
    }

    private function getJfOrderData($beginTime,$endTime) {
        $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST']['jifubao'];
        $sql = "SELECT count(*) AS cn,sum(buy_amount) AS money  FROM `firstp2p_thirdparty_order`  WHERE site_id=".$siteId." and create_time BETWEEN $beginTime  AND $endTime";
        $rows = $GLOBALS['db']->get_slave()->getRow($sql);
        return array('total'=>$rows['cn'],'money'=>empty($rows['money']) ? 0 : $rows['money']);
    }

    private function getJfRepayData($beginTime,$endTime) {
        $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST']['jifubao'];
        $data = array('total'=>0,'totalMoney'=>0,'money'=>0,'interest'=>0,'prepayClaim'=>0,'overDue'=>0);

        $sql = "SELECT DISTINCT(deal_id) FROM `firstp2p_thirdparty_order`  WHERE site_id=".$siteId." and update_time BETWEEN $beginTime AND $endTime";
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);

        /** 解决loan_repay 表相差8小时问题 */
        $beginTime2 = $beginTime-8*3600;
        $endTime2 = $endTime-8*3600;

        $deal_model = new \core\dao\DealModel();

        foreach($rows as $row) {
            $deal_id = $row['deal_id'];
            if(!$deal_id) {
                continue;
            }

            $deal = $deal_model->find($deal_id, 'deal_status', true); 
            if ($deal['deal_status'] == 3) {
                $_sql = "SELECT COUNT(*) AS `cn`, SUM(`buy_amount`) AS `money`, 1 AS `type` FROM `firstp2p_thirdparty_order` WHERE `deal_id` = '{$deal_id}' AND `update_time` BETWEEN '{$beginTime}' AND '{$endTime}'";
            } else {
                $_sql = "SELECT count(*) AS cn ,sum(money) AS money ,type FROM `firstp2p_deal_loan_repay` WHERE deal_id=".$deal_id .
                        " AND deal_loan_id in(SELECT DISTINCT(deal_loan_id) FROM `firstp2p_thirdparty_order` WHERE deal_id=".$deal_id.") ";
                $_sql.=" AND real_time BETWEEN {$beginTime2} AND {$endTime2} group by type";
            }
             
            $res = $GLOBALS['db']->get_slave()->getAll($_sql);
            foreach($res as $_row) {
                if(in_array($_row['type'],array(1,3,8))) {
                    $data['money']+=$_row['money'];
                    $data['total']+=$_row['cn'];
                    $data['totalMoney']+=$_row['money'];
                }
                if(in_array($_row['type'],array(2,7,9))) {
                    $data['interest']+=$_row['money'];
                    $data['totalMoney']+=$_row['money'];
                }
                if(in_array($_row['type'],array(4))) {
                    $data['prepayClaim']+=$_row['money'];
                    $data['totalMoney']+=$_row['money'];
                }
                if(in_array($_row['type'],array(5))) {
                    $data['overDue']+=$_row['money'];
                    $data['totalMoney']+=$_row['money'];
                }
            }
        }
        return $data;
    }

}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);

set_time_limit(0);
ini_set('memory_limit', '1024M');

$type = $argv[1];
$date = $argv[2];
$obj = new JfBillDetail();

if ($type == 1) {
    $res = $obj->run15();
} elseif ($type == 2) {
    $res = $obj->run23();
} else {
    $res = $obj->run($date);
}

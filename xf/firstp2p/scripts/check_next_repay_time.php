<?php

require_once dirname(__FILE__).'/../app/init.php';

set_time_limit(0);

class CheckNextRepayTime {

    public function run() {
        $deal_model = new \core\dao\DealModel();
        $deal_repay_model = new \core\dao\DealRepayModel();
        $deal_list = $deal_model->findAll("`is_effect`='1' AND `is_delete`='0' AND `deal_status`='4'");

        $arr_tmp = array();
        foreach ($deal_list as $deal) {
            $deal_id = $deal['id'];
            $next_repay = $deal_repay_model->getNextRepayByDealId($deal_id);
            if ($deal['next_repay_time'] != $next_repay['repay_time']) {
                $arr_tmp[$deal_id] = array(
                    "next_time" => $deal['next_repay_time'],
                    "repay_time" => $next_repay['repay_time'],
                );
                $deal->next_repay_time = $next_repay['repay_time'];
                $arr_tmp[$deal_id]['result'] = $deal->save();
            }
        }

        if ($arr_tmp) {
            $this->_send_alert($arr_tmp);
        }
    }

    private function _send_alert($arr) {
        $content = "";
        foreach ($arr as $k => $val) {
            $content .= "订单id：{$k} 订单表下次还款时间：{$val['next_time']} 还款表下次还款时间：{$val['repay_time']}，";
            if (!$val['result']) {
                $content .= "数据修复失败！<br>";
            } else {
                $content .= "数据修复成功！<br>";
            }
        }

        FP::import("libs.common.dict");
        $email_arr = dict::get("CHECK_USER_LOG_EMAIL");
        if ($email_arr) {
            $msgcenter = new msgcenter();
            foreach ($email_arr as $email) {
                $msgcenter->setMsg($email, 0, $content, false, "【警报】下期还款时间出现错误！");
            }
            $msgcenter->save();
        }
    }
}

$obj = new CheckNextRepayTime();
$obj->run();

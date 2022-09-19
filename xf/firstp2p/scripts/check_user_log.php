<?php

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

set_time_limit(0);

class CheckUserLog {

    public function run() {

        $content = "";
        $i = 0;

        while (true) {
            $limit = 100;
            $start = $i * $limit;
            $i++;

            $user_list = \core\dao\UserModel::instance()->findAll("`is_effect`=1 AND `is_delete`=0 ORDER BY `id` LIMIT {$start}, {$limit}", "id,money,lock_money");
            
            if (!$user_list) {
                break;
            }
 
            foreach ($user_list as $v) {
                $user_id = $v['id'];
                $money_user = round($v['money'], 2);
                $lock_money_user = round($v['lock_money'], 2);
            
                $user_log_list = \core\dao\UserLogModel::instance()->findAll("`user_id`='{$user_id}' ORDER BY `id`");
                $money_log = 0;
                $lock_money_log = 0;
                foreach ($user_log_list as $val) {
                    $money_log += $val['money'];
                    $lock_money_log += $val['lock_money'];
                }
 
                $money_log = round($money_log, 2);
                $lock_money_log = round($lock_money_log, 2);
 
                if ( abs($money_user - $money_log) > 0.01 || abs($lock_money_user - $lock_money_log) > 0.01 ) {
                    $content .= "用户id:{$user_id} 用户名:{$v['user_name']} 真实姓名:{$v['real_name']} 用户账户余额:{$money_user} 资金结算余额:{$money_log} 用户账户冻结金额:{$lock_money_user} 资金结算冻结金额:{$lock_money_log} <br>";
                }
             }
        }
 
        if ($content) {
            $content = "以下用户账户金额有出入: <br>" . $content;
            $this->_send_alert($content);
            echo "Done!\n";
        } else {
            echo "线上用户账户正常\n";
        }
    }

    private function _send_alert($content) {
        FP::import("libs.common.dict");
        $email_arr = dict::get("CHECK_USER_LOG_EMAIL");
        if ($email_arr) {
            $msgcenter = new msgcenter();
            foreach ($email_arr as $email) {
                $msgcenter->setMsg($email, 0, $content, false, "用户余额对账单");
            }
            $msgcenter->save();
        }
    }
}

$obj = new CheckUserLog();
$obj->run();

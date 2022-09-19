<?php

/**
 * 用户密保问题——设置密保问题html页第二步
 * @author 刘振鹏<liuzhenpeng@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Block;
use core\service\user\BOFactory;
use libs\utils\Logger;

class ProtectPwdNext extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $answer = isset($_SESSION['user_protect']) ? unserialize($_SESSION['user_protect']) : '';
        if($_SESSION['user_protion_is_mobile'] !=1 && $_SESSION['user_protion_is_answer'] != 1){
            $protectionResult['errorCode'] = -2;
            $protectionResult['errorMsg']  = "无法访问";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }

        $this->tpl->assign("list1_1", $answer[0][0]);
        $this->tpl->assign("list2_1", $answer[1][0]);
        $this->tpl->assign("list3_1", $answer[2][0]);
        $this->template = "web/views/v2/account/setpwdprotect_2.html";
    }
}













?>

<?php

/**
 * 用户密保问题——回答密保问题
 * @author 刘振鹏<liuzhenpeng@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Block;
use core\service\user\BOFactory;
use libs\utils\Logger;

class ProtectAnswerPwd extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_id = intval($GLOBALS['user_info']['id']);
        if(!$user_id){
            $protectionResult['errorCode'] = -2;
            $protectionResult['errorMsg']  = "请登录后再操作";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }
 
        $user_protect = get_user_security($user_id);
        if(!$user_protect){
            $protectionResult['errorCode'] = -3;
            $protectionResult['errorMsg']  = "没有设置密保信息";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }
        $i = 0;
        foreach($user_protect['data'] as $key => $values){
            $protect_data[] = $values[$i][0];
            $i++;
        }
        $this->tpl->assign("list1_1", isset($protect_data[0]) ? $protect_data[0] : '');
        $this->tpl->assign("list2_1", isset($protect_data[1]) ? $protect_data[1] : '');
        $this->tpl->assign("list3_1", isset($protect_data[2]) ? $protect_data[2] : '');

        $this->template = "web/views/v2/account/chkpwdprotect.html";
    }
}













?>

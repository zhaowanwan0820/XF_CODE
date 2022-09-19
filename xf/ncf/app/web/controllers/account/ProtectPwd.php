<?php
/**
 * 用户密保问题——设置密保问题html页
 * @author 刘振鹏<liuzhenpeng@ucfgroup.com>
 */
namespace web\controllers\account;

use web\controllers\BaseAction;

class ProtectPwd extends BaseAction {

    public function init() {
    }

    public function invoke() {
        $user_id = intval($GLOBALS['user_info']['id']);
        if(!$user_id){
            $protectionResult['errorCode'] = -1;
            $protectionResult['errorMsg']  = "尚未登录";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }
        if($_SESSION['user_protion_is_mobile'] !=1 && $_SESSION['user_protion_is_answer'] != 1){
            $protectionResult['errorCode'] = -2;
            $protectionResult['errorMsg']  = "无法访问";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }

        $user_protect = isset($_SESSION['user_protect']) ? unserialize($_SESSION['user_protect']) : '';

        $_SESSION['user_protect'] = '';

        $this->tpl->assign("list1_1", isset($user_protect[0][0]) ? $user_protect[0][0] : '');
        $this->tpl->assign("list1_2", isset($user_protect[0][1]) ? $user_protect[0][1] : '');
        $this->tpl->assign("list2_1", isset($user_protect[1][0]) ? $user_protect[1][0] : '');
        $this->tpl->assign("list2_2", isset($user_protect[1][1]) ? $user_protect[1][1] : '');
        $this->tpl->assign("list3_1", isset($user_protect[2][0]) ? $user_protect[2][0] : '');
        $this->tpl->assign("list3_2", isset($user_protect[2][1]) ? $user_protect[2][1] : '');

        $this->template = "web/views/account/setpwdprotect_1.html";
    }
}
<?php
/**
 * 登录密码检查
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;

class PasswordCheck extends BaseAction {
    private $blacklist=null;
    public function init() {
        $this->form = new Form("post");
        $this->form->rules = array(
            'pwd' => array('filter'=>"required", 'message'=> '密码不能为空！'),
            'flag' => array('filter' => 'string',"option"=>array("optional"=>true)),
            'mobile' =>array('filter' => 'string',"option"=>array("optional"=>true)),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
                echo json_encode(array(
                        'errorCode' => 2,
                        'errorMsg' => $this->_error,
                ));
            return false;
        }
    }

    public function invoke() {
        \FP::import("libs.common.dict");
        $blacklist = \dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
        $data = $this->form->data;
        $password = stripslashes($data["pwd"]);
        $login_alter_pwd_flag = isset($data["flag"]) ? $data["flag"] : '';
        $len = strlen($password);
        if ($login_alter_pwd_flag == '1') {
            $mobile = !empty($GLOBALS['user_info']['mobile']) ? $GLOBALS['user_info']['mobile'] : '';
            //基本规则判断
            $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
            if ($base_rule_result){
                echo json_encode($base_rule_result);
                return false;
            }
            //黑名单判断,禁用密码判断
            $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
            if ($forbid_black_result) {
                echo json_encode($forbid_black_result);
                return false;
            }
            //安全程度判断
            $safe_result = login_pwd_safe($len,$password);
            if ($safe_result) {
                echo json_encode($safe_result);
                return true;
            }
        } else {
            //基本规则判断
            $mobile=$data["mobile"];
            $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
            if ($base_rule_result){
                echo json_encode($base_rule_result);
            return false;
            }
            //黑名单判断,禁用密码判断
            $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
            if ($forbid_black_result) {
                echo json_encode($forbid_black_result);
            return false;
            }

            //安全程度判断
            $safe_result = login_pwd_safe($len,$password);
            if ($safe_result) {
                echo json_encode($safe_result);
                return true;
            }
        }
    }
}

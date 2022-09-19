<?php

/**
 * @abstract openapi  身份认证接口
 * @author yutao <yutao@ucfgroup.com>
 * @date 2015-03-20
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;

/**
 * 个人身份认证
 *
 * Class IdnoPass
 * @package openapi\controllers\account
 */
class IdnoPass extends BaseAction {

    private $_minAge = 18;
    private $_maxAge = 70;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            'name' => array("filter" => "required", "message" => "name is required"),
            'idno' => array("filter" => "required", "message" => "idno is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $idno = htmlspecialchars(trim($data['idno']));
        $name = htmlspecialchars(trim($data['name']));
        //$site_id = ($data['site_id']) ? $data['site_id'] : 1;
        //$site = array_search($site_id, $GLOBALS['sys_config']['TEMPLATE_LIST']);
        $sitename = $GLOBALS['sys_config']['SITE_LIST_TITLE']['firstp2p'];
//        if ($site) {
//            if (isset($GLOBALS['sys_config']['SITE_LIST_TITLE'][$site])) {
//                $sitename = $GLOBALS['sys_config']['SITE_LIST_TITLE'][$site];
//            }
//        }

        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $idno)) {
            $msg = '身份认证失败，' . $sitename . '平台仅支持二代身份证';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }

        if (strpos($name, ' ') !== false) {
            $msg = '身份认证失败，用户真实姓名不能包含空格';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }

        $age = $this->getAgeByID($idno);
        if (($age < $this->_minAge) || (($age > $this->_maxAge) && $this->checkReferee($userInfo->referUserId))) {
            $msg = '身份认证失败，' . $sitename . '平台仅支持年龄为18-70周岁的用户进行投资';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
        $user = $this->rpc->local('UserService\getUserByIdno', array($data['idno'], $userInfo->id));
        if (!empty($user)) {
            $msg = "身份验证失败,如需帮助请联系客服";
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }

        // 已经认证的不可再操作
        if ($userInfo->idcardpassed == 1) {
            $msg = '用户已经认证过';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        } elseif ($this->rpc->local('UserService\id5CheckUser', array('uid' => $userInfo->userId, $name, $idno))) {
            $ret['success'] = '您已通过身份验证';
            // 认证之后调用支付端用户注册接口，不关心是否注册成功，只需要去调用即可
            $this->rpc->local("PaymentService\mobileRegister", array($userInfo->userId));
        } else {
            $msg = '身份验证失败';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
        $this->json_data = $ret;
        return true;
    }

    /**
     * 根据身份证号获得用户年龄
     * @param type $id
     * @return string
     */
    public function getAgeByID($id) {
        if (empty($id))
            return '';
        $date = substr($id, 6, 8);
        $today = date("Ymd");
        $diff = substr($today, 0, 4) - substr($date, 0, 4);
        $age = substr($date, 4) > substr($today, 4) ? ($diff - 1) : $diff;

        return $age;
    }

    /**
     * 根据配置判定是否允许70岁以上的用户注册
     * @param int $refer_user_id
     * @return bool
     */
    private function checkReferee($refer_user_id) {
        if (!$refer_user_id) {
            return true;
        }

        $groups = explode(',', app_conf('INVEST_CONFIG_AGE_SEVENTY'));
        if (!$groups) {
            return true;
        }

        $refer_user_info = $this->rpc->local('UserService\getUser', array($refer_user_id));
        if (in_array($refer_user_info['group_id'], $groups)) {
            return false;
        }
        return true;
    }

}

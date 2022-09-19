<?php

/**
 * 嘉年华奖品选择提交
 *
 * @author yutao
 * @date 2014-10-28
 */

namespace web\controllers\event;

use web\controllers\BaseAction;
use libs\web\Form;

class DoCarnivalChoice extends BaseAction {

    private $_errCode = NULL;
    private $_errMsg = NULL;

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'user_id' => array('filter' => 'string'),
            'award_type' => array('filter' => 'string'),
            'recipient_name' => array('filter' => 'string'),
            'mobile' => array('filter' => 'string'),
            'province' => array('filter' => 'string'),
            'user_city' => array('filter' => 'string'),
            'country' => array('filter' => 'string'),
            'address' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
            $this->printResult($this->_error);
        }
    }

    public function invoke() {
        if ($this->form->data['user_id'] > 0) {
            //get user info
            $userInfo = $this->rpc->local("ActivityCarnivalService\getUserInfo", array($this->form->data['user_id']));
            if (empty($userInfo)) {
                $this->_errCode = -1;
                $this->_errMsg = "提交失败";
                $this->showError();
                return;
            }
            if (1 == $userInfo->is_commit) {
                $this->_errCode = -2;
                $this->_errMsg = "您已经提交，不可重复提交";
                $this->showError();
                return;
            }
            $giftPratival = array();
            $giftVirtual = array();
            if ($userInfo->gift_practical != '') {
                $giftPratival = explode("/", $userInfo->gift_practical);
            }
            if ($userInfo->gift_virtual != '') {
                $giftVirtual = explode("/", $userInfo->gift_virtual);
            }

            $gift = array_merge($giftPratival, $giftVirtual);
            $award_type = $this->form->data['award_type'];
            if (!array_key_exists($award_type, $gift)) {
                $this->_errCode = -3;
                $this->_errMsg = "抱歉，无此奖品，请您稍后重试";
                $this->showError();
                return;
            }

            $giftChoice = $gift[$award_type];
            $return = $this->rpc->local("ActivityCarnivalService\updateUserInfo", array($this->form->data['user_id'], $giftChoice, $this->form->data['recipient_name'], $this->form->data['mobile'], $this->form->data['province'], $this->form->data['user_city'], $this->form->data['country'], $this->form->data['address']));
            if ($return !== 1) {
                $this->_errCode = -5;
                $this->_errMsg = "您好，提交失败，请你稍后重试";
                $this->showError();
                return;
            }
            $ret = array();
            $ret['state'] = '1';
            $ret['succ'] = '提交成功！';
            $ret['msg'] = '奖品将于12月初统一寄出，届时请注意查收！';
            echo json_encode($ret);
            return;
        }
        $this->_errCode = -4;
        $this->_errMsg = "user_id ERROR";
        $this->showError();
        return;
    }

    public function showError() {
        $ret = array();
        $ret['state'] = '0';
        $ret['code'] = $this->_errCode;
        $ret['msg'] = $this->_errMsg;
        echo json_encode($ret);
    }

}

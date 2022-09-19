<?php

/**
 * 验证当前用户登录状态 jsonp 
 * @author yangqing<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;

class CheckLogin extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'callback' => array('filter' => 'reg', "message" => "callback error", "option" => array("regexp" => "/^[A-Za-z0-9\_\.]+$/")),
        );
        if (!$this->form->validate()) {
            echo 'error';
            return false;
        }
    }

    public function invoke() {
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        if (empty($GLOBALS ['user_info'])) {
            $json = array('status' => false);
        } else {
            $user = $GLOBALS['user_info'];
            $realname = ($user['idcardpassed'] == '1') ? $user['real_name'] : '';
            $username = $user['user_name'];
            $rspn = $this->rpc->local('CouponService\getOneUserCoupon', array($user['id']));

            //增加msg消息列表
            $msg = array('msgCount' => 0, 'msgTitle' => $GLOBALS['dict']['MSG_NOTICE_TITLE'], 'msgList' => array());
            $msgList = $this->rpc->local('MsgBoxService\getUserTipMsgList', array($user['id'], $this->is_firstp2p));
            if (is_array($msgList) && count($msgList) >= 1) {
                $msg['msgList'] = $msgList;
                foreach ($msg['msgList'] as $key => &$value) {
                    $value = $value->getRow();
                    $msg['msgCount'] += $value['total'];
                    $value['url'] = "/message/deal/" . $value['group_key'];
                }
            }

            $json = array(
                'status' => true,
                'username' => $username,
                'realname' => $realname,
                'coupon' => $rspn['short_alias'],
                'idcardpassed' => $user['idcardpassed'],
                'msg' => $msg,
            );
        }
        $json = json_encode($json);
        $callback = $this->form->data['callback'];
        echo "{$callback}({$json})";
        return true;
    }

    public function _after_invoke() {
        return true;
    }

}

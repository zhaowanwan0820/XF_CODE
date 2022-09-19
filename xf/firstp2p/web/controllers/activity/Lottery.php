<?php

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;

/**
 * 游戏抽奖
 */
class Lottery extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'event_id' => array('filter' => 'required', "message" => '活动id不能为空'),
            'timestamp' => array('filter'=>'required', "message" => '时间戳不能为空'),
            'nonStr' => array('filter'=>'required', "message" => 'nonStr不能为空'),
            'sign' => array('filter'=>'required', "message" => '签名值不能为空')
        );

        if (!$this->form->validate()) {
            $ret = array('error' => 2000, 'msg' => $this->form->getErrorMsg());
            return ajax_return($ret);
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $token = $data['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        $ret = array(
            'error' => 0,
            'msg' => 'success',
            'data' => 1
        );

        if (empty($GLOBALS['user_info'])) {
            $ret['error'] = 401;
            $ret['msg'] = '请登录';
            return ajax_return($ret);
        }

        $userId = intval($GLOBALS['user_info']['id']);
        // 获取decode之后的eventId
        $eventId = $this->rpc->local('GameService\decodeEventId', array(trim($data['event_id'])));
        if ($eventId === false) {
            $ret['error'] = 1;
            $ret['msg'] = '非法活动';
            return ajax_return($ret);
        }

        $timestamp = intval($data['timestamp']);
        $nonStr = trim($data['nonStr']);
        $sign = trim($data['sign']);
        $params = array($userId, $eventId, $timestamp, $nonStr, $sign);
        $res = $this->rpc->local('GameService\lottery', $params);
        if ($res === false) {
            $errCode = $this->rpc->local('GameService\getErrorCode');
            $ret['error'] = $errCode ? $errCode : 1;
            $ret['msg'] = $this->rpc->local('GameService\getErrorMsg');
        } else {
            $ret['data'] = $res;
        }

        return ajax_return($ret);
    }
}
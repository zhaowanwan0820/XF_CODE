<?php

namespace openapi\controllers\activity;

use libs\web\Form;
use openapi\controllers\PageBaseAction;

/**
 * 游戏抽奖
 */
class Lottery extends PageBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'event_id' => array('filter' => 'required', "message" => '活动id不能为空'),
            'timestamp' => array('filter'=>'required', "message" => '时间戳不能为空'),
            'nonStr' => array('filter'=>'required', "message" => 'nonStr不能为空'),
            'sign' => array('filter'=>'required', "message" => '签名值不能为空'),
            'token' => array("filter" => "required", "message" => "token is required")
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        // 这里进行参数转换，是为了和api，web，openapi三端保持统一，可能会存在底层统一处理的兼容性问题
        $this->form->data['oauth_token'] = $data['token'];
        $userInfo = $this->getUserByAccessToken();

        $ret = array(
            'error' => 0,
            'msg' => 'success',
            'data' => 1
        );

        if (!$userInfo) {
            $ret['error'] = 401;
            $ret['msg'] = '请登录';
            return ajax_return($ret);
        }

        $userInfo = $userInfo->toArray();
        $userId = intval($userInfo['userId']);
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

    // 不要删除
    public function _after_invoke() {}
}
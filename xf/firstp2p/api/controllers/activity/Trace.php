<?php

namespace api\controllers\activity;

use libs\web\Form;
use api\controllers\BaseAction;

/**
 * 游戏跟踪
 */
class Trace extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'event_id' => array('filter' => 'string'),
            'type' => array('filter' => 'int'),
            'value' => array('filter' => 'string')
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();

        $ret = array(
            'error' => 0,
            'msg' => 'success',
            'data' => 1
        );

        if (!$userInfo) {
            $ret['error'] = 1;
            $ret['msg'] = '请登录';
            return ajax_return($ret);
        }

        $userId = intval($userInfo['id']);
        // 获取decode之后的eventId
        $eventId = $this->rpc->local('GameService\decodeEventId', array(trim($data['event_id'])));
        if ($eventId === false) {
            $ret['error'] = 1;
            $ret['msg'] = '非法活动';
            return ajax_return($ret);
        }

        $type = isset($data['type']) ? intval($data['type']) : 0;
        $value = isset($data['value']) ? trim($data['value']) : '';
        $params = array($userId, $eventId, $type, $value);
        $res = $this->rpc->local('GameService\trace', $params);
        if ($res === false) {
            $ret['error'] = 1;
            $ret['msg'] = $this->rpc->local('GameService\getErrorMsg', array());
        } else {
            $ret['data'] = $res;
        }

        return ajax_return($ret);
    }

    // 不要删除
    public function _after_invoke() {}
}
<?php

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;

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
}
<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use libs\utils\Logger;

/**
 * 2018世界杯活动
 */
class AjaxGuessLog extends WorldcupBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'pageNo' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        $this->form->validate();
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

        $uid = intval($GLOBALS['user_info']['id']);
        $pageNo = isset($data['pageNo']) ? intval($data['pageNo']) : 1;
        $pageSize = 10;
        $list = $this->rpc->local('GameService\getUserGuessLogList', array($uid, $pageNo, $pageSize));

        $ret['data'] = $list;
        return ajax_return($ret);
    }
}

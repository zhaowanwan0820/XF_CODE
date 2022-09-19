<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use libs\utils\Logger;

/**
 * 2018世界杯活动，积分列表
 */
class AjaxScoreLog extends WorldcupBaseAction {
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
        $pageNo = intval($data['pageNo']);
        $pageSize = 10;
        $list = $this->rpc->local('GameService\getUserPointsLogList', array($uid, $pageNo, $pageSize));

        $logs = array();
        foreach($list as $log) {
            $item = array();
            $item['times'] = $log['points'];
            $item['note'] = $log['note'];
            $item['date'] = date('Y-m-d', $log['createTime']);
            $item['time'] = date('H:i:s', $log['createTime']);

            $logs[] = $item;
        }

        $ret['data'] = $logs;
        return ajax_return($ret);
    }
}

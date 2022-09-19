<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use libs\utils\Logger;

/**
 * 2018世界杯活动
 */
class ScoreLog extends WorldcupBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'isApp' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $this->isApp = intval($data['isApp']);
        $token = $data['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        if (!$this->_check_login()) {
            return false;
        }

        $uid = intval($GLOBALS['user_info']['id']);
        $info = $this->rpc->local('GameService\getUserPointsRank', array($uid));
        $user = array(
            'pic' => $this->_getUserImage($uid, $GLOBALS['user_info']['mobile']),
            'totalScore' => $info['points'],
            'totalRank' => $info['rank'],
            'successTimes' => $info['times'],
            'updateTime' => date('Y-m-d H:i:s')
        );

        $this->tpl->assign("token", $token);
        $this->tpl->assign('user', $user);
        $this->template = 'web/views/worldcup2018/integral_detail.html';
    }
}

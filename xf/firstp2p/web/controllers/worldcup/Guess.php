<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use core\service\UserService;
use NCFGroup\Protos\O2O\Enum\GameEnum;

/**
 * 2018世界杯活动
 */
class Guess extends WorldcupBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'matchId' => array('filter' => 'required', "message" => '活动id不能为空'),
            'choice' => array('filter' => 'required', "message" => '竞猜队伍不能为空'),
            'points' => array('filter' => 'required', "message" => '积分不能为空'),
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

        if (!$this->_check_login()) {
            return false;
        }

        $ret = array(
            'error' => 0,
            'msg' => 'success',
            'data' => 1
        );
        $uid = intval($GLOBALS['user_info']['id']);
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($uid) || in_array($GLOBALS['user_info']['group_id'], GameEnum::$WORLDCUP_BLACKLIST_USERGROUP)) {
            $ret['error'] = -1;
            $ret['msg'] = '本活动仅针对个人用户。';
            return ajax_return($ret);
        }

        $params = array($uid, $data['matchId'], $data['choice'], $data['points']);
        $event = $this->rpc->local('GameService\guessMatch', $params);
        if ($event === false) {
            $ret['msg'] = $this->rpc->local('GameService\getErrorMsg');
            $ret['error'] = -1;
        } else {
            $ret['data'] = $event['data'];
        }
        ajax_return($ret);
    }
}

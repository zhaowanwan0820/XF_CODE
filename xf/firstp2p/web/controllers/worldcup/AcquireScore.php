<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use NCFGroup\Protos\O2O\Enum\GameEnum;
use core\service\UserService;

/**
 * 2018世界杯活动
 */
class AcquireScore extends WorldcupBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
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

        if (empty($GLOBALS['user_info'])) {
            $ret['error'] = 401;
            $ret['msg'] = '请登录';
            return ajax_return($ret);
        }
        $isAddWorldcupScore = ((time() >= strtotime(GameEnum::GUESS_POINTS_START_TIME)) && (time()<= strtotime(GameEnum::GUESS_POINTS_END_TIME))) ? true :false;
        if (!$isAddWorldcupScore) {
            $ret['error'] = -1;
            $ret['msg'] = '不在积分领取有效时间内';
            return ajax_return($ret);
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

        $params = array($uid);
        $event = $this->rpc->local('GameService\acquireScore', $params);
        if ($event === false) {
            $ret['msg'] = $this->rpc->local('GameService\getErrorMsg');
            $ret['error'] = -1;
        } else {
            $ret['data'] = $event['data'];
        }
        ajax_return($ret);
    }
}

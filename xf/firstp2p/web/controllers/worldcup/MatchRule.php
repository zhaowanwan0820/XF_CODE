<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use libs\utils\Logger;

/**
 * 2018世界杯活动
 */
class MatchRule extends WorldcupBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'matchId' => array('filter' => 'required', "message" => '活动id不能为空'),
            'isApp' => array('filter' => 'string'),
            'isPeakNight' => array('filter' => 'string'),
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
        // 获取规则详情
        $error = '';
        $event = $this->rpc->local('GameService\getUserMatchDetail', array($uid,$data['matchId']));
        $ruleDetail = isset($event['ruleDetail']) ? $event['ruleDetail'] : '无规则说明';
        $this->tpl->assign('ruleDetail', $ruleDetail);
        if ($data['isPeakNight']) {
            $this->template = 'web/views/worldcup2018/peak_night.html';
        } else {
            $this->template = 'web/views/worldcup2018/rule_description.html';
        }
    }
}

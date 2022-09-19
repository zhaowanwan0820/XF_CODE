<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\O2O\Enum\GameEnum;

/**
 * 2018世界杯活动
 */
class MatchDetail extends WorldcupBaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'matchId' => array('filter' => 'required', "message" => '活动id不能为空'),
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
        $userPic = $this->_getUserImage($uid, $GLOBALS['user_info']['mobile']);
        $this->tpl->assign('userPic', $userPic);

        $userRank = $this->rpc->local('GameService\getUserPointsRank', array($uid));
        $this->tpl->assign('userRank', $userRank);

        $match = $this->rpc->local('GameService\getUserMatchDetail', array($uid, $data['matchId']));
        $this->tpl->assign("token", $token);
        if ($match['guessMode'] == GameEnum::MATCH_MODE_ONE_TWO) {
            $teamList = array();
            $userChoiceList = array();
            $userChoices = explode(',', $match['userChoice']);
            foreach(GameEnum::$TEAMS_MAP as $k => $v) {
                $teamList[$v] = $match['guessTeams'][$k]['name'];
            }
            foreach($userChoices as $choice) {
                $userChoiceList[$choice] = $match['guessTeams']['team'.$choice];
            }
            $this->tpl->assign('userChoiceList', $userChoiceList);
            $this->tpl->assign('teamList', $teamList);
            $this->tpl->assign('match', $match);
            $this->template = 'web/views/worldcup2018/eight_team_detail.html';
        } else {
            $match['userChoiceName'] = $match['guessTeams']['team'.$match['userChoice']]['name'];
            $this->tpl->assign('match', $match);
            $this->template = 'web/views/worldcup2018/team_detail.html';
        }
    }
}

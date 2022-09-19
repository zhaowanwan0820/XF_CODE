<?php

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\worldcup\WorldcupBaseAction;
use libs\utils\Logger;
use core\service\WeiXinService;
use NCFGroup\Protos\O2O\Enum\GameEnum;
use core\service\UserTagService;

/**
 * 2018世界杯活动
 */
class Index extends WorldcupBaseAction {
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

        //获取用户头像
        $userPic = $this->_getUserImage($uid, $GLOBALS['user_info']['mobile']);
        $this->tpl->assign('userPic', $userPic);

        //获取积分排行榜
        $userRank = $this->rpc->local('GameService\getUserPointsRank', array($uid));
        $this->tpl->assign('userRank', $userRank);
        //分享的内容
        $shareDetail = $this->rpc->local('GameService\getMatchEventDetail', array());
        $this->tpl->assign('shareIcon', $shareDetail['shareIcon']);
        $this->tpl->assign('shareLink', '/worldcup/');
        $this->tpl->assign('shareSummary', $shareDetail['shareDesc']);
        $this->tpl->assign('shareTitle', $shareDetail['shareTitle']);

        //获取比赛列表
        $pageNo = 1;
        $matchRes = $this->rpc->local('GameService\getMatchList', array($uid,$pageNo));
        $list = $this->formatMatchList($matchRes['list']);
        $this->tpl->assign('res', $list);
        $this->tpl->assign('isGiven', $matchRes['isGiven']);
        //判断巅峰之夜是否开启
        $isPeakNight = ((time() >= strtotime(GameEnum::GUESS_PEAK_NIGHT_START_TIME)) && time() <= strtotime(GameEnum::GUESS_PEAK_NIGHT_END_TIME)) ? 1 : 0;
        $this->tpl->assign('isPeakNight', $isPeakNight);

        // BID_MORE 判断
        $tagService = new UserTagService();
        $isOldCustom = ($tagService->getTagByConstNameUserId('BID_ONE', $uid) || $tagService->getTagByConstNameUserId('BID_MORE', $uid)) ? 1 : 0;
        $this->tpl->assign('isOldCustom', $isOldCustom);
        

        $isApp = isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) > 100 ? 1 : 0;
        $isShare = $isApp;
        if ($isShare && isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) <= 440
            && isset($_SERVER['HTTP_OS']) && strtolower(trim($_SERVER['HTTP_OS'])) != 'android') {
            $isShare = 0;
        }

        // 微信分享js签名
        $wxService = new WeiXinService();
        $isWeixin = $wxService->isWinXin();

        $this->tpl->assign("isApp", $isApp);
        $this->tpl->assign('isShare', $isShare || $isWeixin);
        $this->tpl->assign("token", $token);

        $jsApiSingature = $wxService->getJsApiSignature();
        $this->tpl->assign('appid', $jsApiSingature['appid']);
        $this->tpl->assign('timeStamp', $jsApiSingature['timeStamp']);
        $this->tpl->assign('nonceStr', $jsApiSingature['nonceStr']);
        $this->tpl->assign('signature', $jsApiSingature['signature']);

        $this->template = 'web/views/worldcup2018/index.html';
    }

    private function formatMatchList($list) {
        $result = array(
            'teamMatchList' => array(),
        );
        foreach($list as $item) {
            $tmp = $item;
            $tmp['statusDesc'] = GameEnum::$MATCH_STATUS[$item['matchStatus']];
            $tmp['userStatusDesc'] = GameEnum::$GUESS_STATUS[$item['userStatus']];
            if ($item['guessMode'] == GameEnum::MATCH_MODE_ONE_TWO) {
                //八强赛
                $choices = explode(',', $item['result']);
                foreach ($item['guessTeams'] as $teamKey=>$team) {
                    $key = str_replace('team', '', $teamKey);
                    if ($choices && in_array($key, $choices)) {
                        $tmp['guessTeams'][$teamKey]['isChosen'] = true;
                    } else {
                        $tmp['guessTeams'][$teamKey]['isChosen'] = false;
                    }
                }
            }
            $result['teamMatchList'][] = $tmp;
        }
        return $result;
    }
}

<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyActivityService;
use libs\web\Form;
use libs\utils\ABControl;
use core\service\DealCustomUserService;
use core\service\BwlistService;

class TaskIntro extends AppBaseAction {
    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = $loginUser['id'];
        $activityService = new CandyActivityService();
        $activityConf = $activityService->getActivityKeyConf();

        $dealCustomService = new DealCustomUserService();
        // 黑名单或者用户不可投专享，干掉专享提示和统计
        $showConfig = [];
        foreach ($activityConf as $key => $value) {
            $showConfig[$key] = true;
        }
        if ($dealCustomService->checkBlackList($userId) == true || !$dealCustomService->canLoanZx($userId)) {
            $showConfig['ZHUANXIANG'] = false;
        }

        // 信力红包开关
        $showConfig['BONUS_ON'] = app_conf('CANDY_BONUS_ON');
        $showConfig['BONUS_ON'] = empty($showConfig['BONUS_ON']) ? 0 : trim($showConfig['BONUS_ON']);
        // WHITE:仅白名单可见 ALL:所有用户可见
        if ($showConfig['BONUS_ON'] == 'WHITE') {
            if (!ABControl::getInstance()->hit('candy_bonus')) {
                $bwlistService = new BwlistService();
                $isWhiteList = $bwlistService->inList('CANDY_BONUS_WHITE', $loginUser['id']);
                if (empty($isWhiteList)) {
                    $showConfig['BONUS_ON'] = 0;
                }
            }
        }

        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('showConfig', $showConfig);
        // var_dump($showConfig, "aaa" ,$activityConf);
        $this->tpl->assign('activityConf', $activityConf);
    }
}

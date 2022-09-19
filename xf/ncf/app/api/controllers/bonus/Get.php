<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\bonus\BonusService;

class Get extends AppBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/bonus/get';

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_GET_USER_FAIL'
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            ),
            'wxb' => array(
                'filter' => 'string',
                'option' => array('optional' => true)
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $site_id = isset($data['site_id']) ? $data['site_id'] : $this->defaultSiteId;
        $loginUser = $this->user;

        // $result = BonusService::bonusGet($loginUser['id']);
        // if ($result === false) {
        //     $this->setErr(BonusService::getErrorData(), BonusService::getErrorMsg());
        //     return false;
        // }

        $result = [];
        $result['shareCount'] = BonusService::getUsableBonus($loginUser['id']);
        $result['userInfo'] = BonusService::getUserBonusInfo($loginUser['id']);
        $rsp = BonusService::getBonusLogList($loginUser['id'], 1, 10, $loginUser['is_enterprise_user']);
        $result['all'] = [
            'list' => $rsp['list'],
            'count' => $rsp['page']['total'],
        ];

        try {
            BonusService::delIncomeStatus($loginUser['id']);
        } catch (\Exception $e) {
            //no log
        }

        // 红包
        $result['new_bonus_title'] = app_conf('NEW_BONUS_TITLE');
        $result['new_bonus_unit'] = app_conf('NEW_BONUS_UNIT');

        $result['isFirstp2p'] = 1;
        $result['wxb'] = true;
        $result['site_id'] = $site_id;
        $result['showShare'] = 1;
        $result['token'] = $data['token'];
        $this->json_data = $result;
    }
}

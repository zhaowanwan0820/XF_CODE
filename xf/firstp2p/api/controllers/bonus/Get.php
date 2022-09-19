<?php

namespace api\controllers\bonus;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\bonus\BonusUser;

class Get extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'wxb' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $site_id = isset($data['site_id']) ? $data['site_id'] : 1 ;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }


        /**
         * 临时代码
         * 我的红包页面移动到wap站 app发版后移除
         */
        $wapUrl = get_http().app_conf('FIRSTP2P_WAP_DOMAIN').'/bonus/get?'.http_build_query($data);
        return app_redirect($wapUrl);


        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        $page = 0;
        $pageSize = 10;

        $showWxb = true;//isset($data['wxb']) && $data['wxb'] == 'true' ? true : false ;
        if (!$showWxb) {
            if ($this->app_version >= 320) {
                $all = $this->rpc->local('BonusService\get_list', array($loginUser['id'], 0, true, $page, $pageSize, true, true));
                $getResult = (new BonusUser())->getUserByUid($loginUser['id']);
                $new_all = array();
                foreach ($all['list'] as $bonus_value) {
                    if (intval($bonus_value['status']) <> 2) {
                        $bonus_value['status'] = ($bonus_value['expired_at'] < time()) ? '3' : '1';
                    }
                    $new_all[] = $bonus_value;
                }
                $all['list'] = $new_all;
                $this->tpl->assign('unuse', $getResult['get_unused_money']);
                $this->tpl->assign('used', $getResult['get_used_money']);
                $this->tpl->assign('all', $all);
            } else {
                $usefulList = $this->rpc->local('BonusService\get_list', array($loginUser['id'], 1, true, $page, $pageSize, true, true));
                $usedList = $this->rpc->local('BonusService\get_list', array($loginUser['id'], 2, true, $page, $pageSize, true, true));
                $expiredList = $this->rpc->local('BonusService\get_list', array($loginUser['id'], 3, true, $page, $pageSize, true, true));
                $this->tpl->assign('usefulList', $usefulList);
                $this->tpl->assign('usedList', $usedList);
                $this->tpl->assign('expiredList', $expiredList);
            }

            //TODO 汇源DEMO
            $hyjkMember = $this->rpc->local('UserTagService\getTagByConstNameUserId', array('LWHYJK_MEMBER', $loginUser['id']));
            $this->tpl->assign('hyjkMember', $hyjkMember);
            if ($hyjkMember) {
                $regGiftTag = $this->rpc->local('UserTagService\getTagByConstNameUserId', array('LWHYJK_PRIZE_1_0', $loginUser['id']));
                $regGiftRevTag = $this->rpc->local('UserTagService\getTagByConstNameUserId', array('LWHYJK_PRIZE_1_1', $loginUser['id']));
                $dealGiftTag = $this->rpc->local('UserTagService\getTagByConstNameUserId', array('LWHYJK_PRIZE_2_0', $loginUser['id']));
                $dealGiftRevTag = $this->rpc->local('UserTagService\getTagByConstNameUserId', array('LWHYJK_PRIZE_2_1', $loginUser['id']));
                $this->tpl->assign('regGiftTag', $regGiftTag);
                $this->tpl->assign('regGiftRevTag', $regGiftRevTag);
                $this->tpl->assign('dealGiftTag', $dealGiftTag);
                $this->tpl->assign('dealGiftRevTag', $dealGiftRevTag);
            }

            // 增加app H5 买红包的入口配置
            $showBuyBtnConf = intval(app_conf('APP_SHOW_BUY_BTN'));
            $showBuyBtn = !empty($showBuyBtnConf)?1:0;
            $this->tpl->assign('show_btn', $showBuyBtn);
        } else {

            $this->template = $this->getTemplate('log');
            $this->tpl->assign('wxb', $showWxb);
            $response = $this->rpc->local('BonusService\getBonusLogList', [$loginUser['id'], $page + 1, 10]);
            $list = $response['list'];
            $this->tpl->assign('all', ['list' => $list, 'count' => $response['page']['total']]);

            $response = $this->rpc->local('BonusService\getUserBonusInfo', [$loginUser['id']]);
            $this->tpl->assign('userInfo', $response);

            $shareCount = $this->rpc->local('BonusService\getUnsendCount', [$loginUser['id']]);
            $this->tpl->assign('shareCount', $shareCount);

            $this->tpl->assign('sendUrl', urlencode($this->getHost(). '/bonus/send?wxb=true'));
        }

        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('site_id', $site_id);
        if ($site_id == 1) {
            $this->tpl->assign('showShare', ($this->app_version >= 440 ? 1 : 0));
        } else {
            $this->tpl->assign('showShare', ($this->app_version >= 359 ? 1 : 0));
        }
        $this->rpc->local('WXBonusService\delIncomeStatus', array($loginUser['id']));//清除红包的状态
        //if ($this->rpc->local('BonusService\encrypt', array(app_conf('BONUS_HAPPY_NEW_YEAR'))) && $site_id == 1) {
        //    $this->tpl->assign('happy_new_year_status', 1);
        //}
    }

    public function _after_invoke() {
        $this->afterInvoke();
        $this->tpl->display($this->template);
    }
}

<?php

/**
 * 变现确认页
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use NCFGroup\Common\Library\Idworker;

class Withraw extends GoldBaseAction {

    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'token is required'),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR",$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }
        //判断是否是交易时段
        $isTradDay = check_trading_day(time());
        if (!$isTradDay || !$this->check_trade_time()) {
            $this->tpl->assign('trad_flag', true);
        }
        //获取实时金价
        $goldPrice = $this->rpc->local('GoldService\getGoldPrice', array());
        if (empty($goldPrice) || $goldPrice['errCode'] != 0 || $goldPrice['data']['gold_price'] == 0) {
            $this->template = 'api/views/_v46/gold/gold_price_error.html';
            return false;
        }
        $goldPrice = floorfix($goldPrice['data']['gold_price'],2);

        //获取用户变现信息
        $userWithrawInfoRes = $this->rpc->local('GoldService\getUserWithrawInfo', array($user['id']));
        if ($userWithrawInfoRes['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$userWithrawInfoRes['errMsg']);
            return false;
        }
        //用户投资ticket
        $ticket = Idworker::instance()->getId();
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $ticketRes = $redis->setex($ticket,'300',$user['id']);
        $this->tpl->assign('ticket', $ticket);

        $userWithrawInfo = $userWithrawInfoRes['data'];
        $maxGoldCurrentConf = app_conf('GOLD_MAX_WITHDRAW_PER_DAY');
        $maxGoldCurrent = $maxGoldCurrentConf === '' ? 1000 : $maxGoldCurrentConf;//每人每日最大变现黄金克重
        $dealInfo = array();
        $dealInfo['WithdrawGold'] = number_format($userWithrawInfo['gold'],3);//可变现克重
        //今日可变现克重
        $WithdrawGoldToday = $maxGoldCurrent == 0 ? 0 : number_format(floorfix(($maxGoldCurrent - $userWithrawInfo['applyWithdrawGold']),3,6),3);
        if ($WithdrawGoldToday < 0) {
            $dealInfo['WithdrawGoldToday'] = floorfix(0,3);
        } elseif ($WithdrawGoldToday == 0 && $maxGoldCurrent == 0) {
            $dealInfo['WithdrawGoldToday'] = number_format($userWithrawInfo['gold'],3);//可变现克重
        } else {
            $dealInfo['WithdrawGoldToday'] = $WithdrawGoldToday;
        }
        $withdrawMinFeeConf = app_conf('GOLD_WITHDRAW_MIN_FEE');
        $withdrawMinFee = $withdrawMinFeeConf === '' ? 0.01 : $withdrawMinFeeConf;//单笔变现最低手续费
        $dealInfo['withdrawMinFee'] = number_format($withdrawMinFee,2);
        $dealInfo['priceRate'] = number_format(app_conf('GOLD_PRICE_RATE')?app_conf('GOLD_PRICE_RATE'):0.5,2);//浮动利率
        $dealInfo['maxPrice'] = number_format(floorfix($goldPrice - $dealInfo['priceRate'],2),2);//成交瞬间系统价格不高于此值
        $dealInfo['maxGoldCurrent'] = number_format($maxGoldCurrent,3);//每人每日最大变现黄金克重
        $dealInfo['feeMoney'] = floorfix($userWithrawInfo['withdrawFeeRate'],2);//变现手续费
        $this->tpl->assign('dealInfo', $dealInfo);
        $this->tpl->assign('usertoken', $data["token"]);
        $this->tpl->assign('goldPrice',$goldPrice);//实时金价
    }
    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

}

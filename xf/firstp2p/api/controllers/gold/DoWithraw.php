<?php

/**
 * 变现接口
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use core\data\GoldUserData;
use libs\utils\Logger;

class DoWithraw extends GoldBaseAction {

    public static $fatal;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'token is required'),
                'ticket' => array('filter' => 'required', 'message' => 'ticket is required'),
                'gold' => array('filter' => 'required', 'message' => 'gold is required'),
                'goldPrice' => array('filter' => 'required', 'message' => 'goldPrice is required'),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR",$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        //判断是否非交易日
        $isTradDay = check_trading_day(time());
        if (!$isTradDay || !$this->check_trade_time()) {
            $this->setErr('ERR_MANUAL_REASON','当前为非交易时段');
            return false;
        }
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (empty($data['ticket']) || empty($data['gold'])) {
            $this->setErr('ERR_MANUAL_REASON','请不要重复提交订单');
            return false;
        }

        //检查是否授权
        $res = $this->rpc->local('GoldService\isAuth', array($user['id']));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON','获取用户授权信息失败');
            return false;
        }

        if ($res['errCode'] == 0 && !$res['data']) {
            $this->setErr('ERR_MANUAL_REASON','用户未授权，不能变现');
            return false;
        }
        //验证ticket
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $ticketRes = $redis->get($data['ticket']);
        if ($ticketRes != $user['id'] || empty($ticketRes)) {
            $this->setErr('ERR_MANUAL_REASON','请不要重复提交订单');
            return false;
        }
        //每人每日最大变现黄金克重
        $maxGoldCurrentConf = app_conf('GOLD_MAX_WITHDRAW_PER_DAY');
        $maxGoldCurrent = $maxGoldCurrentConf === '' ? 1000 : $maxGoldCurrentConf;
        //获取用户变现信息
        $userWithrawInfoRes = $this->rpc->local('GoldService\getUserWithrawInfo', array($user['id']));
        if ($userWithrawInfoRes['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$userWithrawInfoRes['errMsg']);
            return false;
        }
        if ($userWithrawInfoRes['data']['gold'] < $data['gold']) {
            $this->setErr('ERR_MANUAL_REASON','变现克重大于可变现克重，请重新申请');
            return false;
        }
        $priceRate = number_format(app_conf('GOLD_PRICE_RATE') ? app_conf('GOLD_PRICE_RATE') : 0.5, 2);//浮动利率
        $withdrawMinFeeConf = app_conf('GOLD_WITHDRAW_MIN_FEE');
        $withdrawMinFee = $withdrawMinFeeConf === '' ? 0.01 : $withdrawMinFeeConf;//单笔变现最低手续费
        //变现申请
        self::$fatal = 1;
        $deliverData = new GoldUserData();
        $lock = $deliverData->enterPool($user['id']);
        if ($lock === false) {
            $this->setErr('ERR_MANUAL_REASON','人数过多，请稍后再试');
            return false;
        }
        register_shutdown_function(array($this, "errCatch"),$user['id']);
        $withdrawApplyRes = $this->rpc->local('GoldService\withdrawApply', array($user['id'],$data['ticket'],$data['gold'],$maxGoldCurrent,$data['goldPrice'],$priceRate,$withdrawMinFee));
        if ($withdrawApplyRes['errCode'] != 0 || empty($withdrawApplyRes)) {
            $this->setErr('ERR_MANUAL_REASON',$withdrawApplyRes['errMsg']);
            self::$fatal = 0;
            $deliverData->leavePool($user['id']);
            return false;
        }
        self::$fatal = 0;
        $deliverData->leavePool($user['id']);
        $result = array();
        $result['gold'] = number_format($withdrawApplyRes['data']['gold'],3).'克';//变现克重
        $result['gold_price'] = number_format($withdrawApplyRes['data']['goldPrice'],2).'元/克';//变现金价
        $result['money'] = number_format($withdrawApplyRes['data']['money'],2).'元';//变现金额
        $result['fee_money'] = number_format($withdrawApplyRes['data']['feeMoney'],2).'元';//变现手续费
        $result['expect_money'] = number_format(($withdrawApplyRes['data']['money'] - $withdrawApplyRes['data']['feeMoney']),2).'元';//预计到账金额（变现金额 - 变现手续费)
        $result['tip'] = "您的变现申请预计在1个工作日内处理\n变现金额将存放在您的网信账户";//变现手续费
        $redis->del($data['ticket']);
        $this->json_data = $result;
    }

    public function errCatch($userId){
        $fatal = self::$fatal;
        if(!empty($userId) && !empty($fatal)){
            self::$fatal = 0;
            $deliverData = new GoldUserData();
            $deliverData->leavePool($userId);
            $lastErr = error_get_last();
            Logger::info("bid err catch" ." lastErr: ". json_encode($lastErr) . " trace: ".json_encode(debug_backtrace()));
        }
    }


}

<?php

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\PaymentService;
use core\service\BankService;
use core\service\AdunionDealService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\BonusService;
use libs\utils\PaymentApi;
use libs\payment\supervision\Supervision;

/**
 *
 * @date 2014年4月8日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 * @actionLock
 */
class Savebank extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
                'id'=>array("filter"=>'int'),
                'user_bank_id'=>array("filter"=>'int'),
                'bank_id'=>array("filter"=>'int'),
                'card_name'=>array("filter"=>'string'),
                'certStatus' => array("filter" => 'int'),

                'region_lv1'=>array("filter"=>'int'),
                'region_lv2'=>array("filter"=>'int'),
                'region_lv3'=>array("filter"=>'int'),
                'region_lv4'=>array("filter"=>'int'),

                'bankzone'=>array("filter"=>'string'),
                'bankzone_1'=>array("filter"=>'string'),
                'bankcard'=>array("filter"=>'string'),
                'bankcardSignature' => array('filter' => 'string'),
                'reBankcard'=>array("filter"=>'string'),
                'isnew'=>array("filter"=>'int'),
                'image'=>array("filter"=>'int'),
                'token_id'=>array("filter"=>'string'),
        );
        $this->form->validate();
    }

    public function invoke() {

        // 验证表单令牌
        if(!check_token()){
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR']);
        }

        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            return $this->show_tips(\libs\utils\PaymentApi::maintainMessage(), '温馨提示', 0, '', '/');
        }

        $user_id = $GLOBALS['user_info']['id'];

        //存管服务降级
        if ($this->rpc->local('SupervisionAccountService\isSupervisionUser', [$user_id]) && Supervision::isServiceDown()) {
            return $this->show_tips(Supervision::maintainMessage(), '温馨提示', 0, '', '/');
        }

        //查询有无修改正在审核中
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($user_id));
        if ($bankcard['is_audit'] == 1) {
            return $this->show_error("您已提交了一次修改申请，不能重复提交，请耐心等待审核结果!", "", 0, 0, "/account");
        }

        $data = $this->form->data;
        $data['image_id']   = $data['image'];
        $bankService = new BankService();
        $data = $bankService->bankInfoXssFilter($data);
        if($data['reBankcard'] != $data['bankcard']){
            return $this->show_error('两次输入的银行卡不同');
        }

        //if(empty($data['region_lv3'])) {
        //    return $this->show_error('请选择所在地区！');
        //}

        if(empty($data['bank_id'])) {
            return $this->show_error($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK']);
        }
        //if(empty($data['bankzone'])){
        //    return $this->show_error("请选择开户行所在地");
        //}
        if($data['bankzone_1']){
            $data['bankzone'] = $data['bankzone_1'];
            unset($data['bankzone_1']);
        }
        if(empty($data['bankcard'])) {
            return $this->show_error($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK_CODE']);
        }
        //去除空格
        $data['bankcard'] = str_replace(" ", "", $data['bankcard']);

        // 验证银行卡签名
        if (!empty($data['bankcardSignature']))
        {
            $cardNoSignature = PaymentApi::instance()->getGateway()->getSignature(['cardNo' => $data['bankcard'], 'certStatus' => $data['certStatus']]);
            if ($cardNoSignature !== $data['bankcardSignature'])
            {
                return $this->show_error('您的银行卡数据发生变化');
            }
        }

        if(!in_array(strlen($data['bankcard']), array(12,15,16,17,18,19))) {
            return $this->show_error("银行卡号长度不正确");
        }

        //新增或重置
        $is_new = $data['isnew'];

        //查询银行卡已绑定的信息
        $can_bind = $this->rpc->local('BankService\checkBankCardCanBind',array($data['bankcard'], $user_id));

        if(!$can_bind){
            return $this->show_error("该银行卡已被其他用户绑定，请重新设置提现银行卡。");
        }

        $data['bankcard'] = trim($data['bankcard']);
        $data['create_time'] = get_gmtime();
        $data['status'] = 1;    //审核中
        if (!empty($data['certStatus'])) {
            $data['fastpay_cert_status'] = $data['certStatus']; //四要素审核结果
        } else {
            $data['fastpay_cert_status'] = 0;
        }
        $data['user_id'] = $GLOBALS['user_info']['id'];
        $data['bankzone'] = htmlspecialchars($data['bankzone'], ENT_QUOTES);
        unset($data['isnew']);

        if($is_new){
            if ($bankcard['bankcard'] && $bankcard['status'] == 1) {
                return $this->show_error("您已有绑定银行卡，不能重复添加，若想变更，请去修改！", "", 0, 0, "/account");
            } else {
                try {
                    if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {
                        // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
                        $paymentService = new PaymentService();
                        $bankcardInfo = $paymentService->getBankcardInfo($data, true);
                        // 发送请求
                        $paymentService->bankcardSync($user_id, $bankcardInfo);
                    }
                    $rs = $this->rpc->local('BankService\saveBank',array($data,true));
                    new \core\service\DigService('bindBankCard', array(
                        'bankCardNo' => $bankcardInfo['cardNo'],
                        'bankcardSn' => $bankcardInfo['bankCode'],
                        'id' => $GLOBALS['user_info']['id'],
                        'mobile' => $GLOBALS['user_info']['mobile'],
                        'cardName' => $GLOBALS['user_info']['real_name'],
                        'cn' => $GLOBALS['user_info']['invite_code'],
                    ));
                }
                catch (\Exception $e) {
                    return $this->show_error("绑定银行卡失败！", "", 0, 0);
                }
            }
            //$this->rpc->local('AdunionDealService\triggerAdRecord', array($GLOBALS['user_info']['id'], 3));//广告联盟
            $event = new \core\event\BonusEvent('bindCard', $user_id, $GLOBALS['user_info']['invite_code']);
            $task_obj = new GTaskService();
            $task_id = $task_obj->doBackground($event, 20);
            if (!$task_id) {
                Logger::wLog('绑卡添加返利失败|' .$user_id. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            }
            $bonusService = new BonusService();
            $bonusService->transCashBonus($user_id);
            // 清除验卡状态
            $redisKey = 'authcard_result_'.$user_id;
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $redis->del($redisKey);
            return $this->show_success('银行卡信息修改成功！', '', 0, 0,'/account');
        }else{
            $rs = $this->rpc->local('BankService\saveBank',array($data));
            // 清除验卡状态
            $redisKey = 'authcard_result_'.$user_id;
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $redis->del($redisKey);
            return $this->show_success('平台将在3个工作日内完成信息审核。审核结果将以短信、站内信或电子邮件等方式通知您', '', 0, 0,'/account');
        }
    }
}

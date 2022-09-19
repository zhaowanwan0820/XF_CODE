<?php
/**
 * 修改银行卡页面
 * @author yanjun<yanjun5@ucfgroup.com>
 */

namespace api\controllers\payment;
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class Editbank extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token不能为空'),
            'data' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
        }

        if (PaymentApi::isServiceDown()) {
            $this->setErr('ERR_MANUAL_REASON', PaymentApi::maintainMessage());
        }

        //如果未绑定手机
        if(intval($userInfo['mobilepassed'])==0 || empty($userInfo['mobile'])){
            $this->setErr('ERR_MANUAL_REASON', '未绑定手机号');
        }

        if(!$userInfo['real_name'] || $userInfo['idcardpassed'] !=1){
            $this->setErr('ERR_IDENTITY_NO_VERIFY');
        }

        $redisKey = 'authcard_result_'.$userInfo['id'];
        $redis = \SiteApp::init()->dataCache->getRedisInstance();

        $verifyResult = array();
        $cert_verify_status = 0;
        if (empty($data['data']) && !$redis->get($redisKey)) {
            $this->setErr('ERR_IDENTITY_NO_VERIFY','empty data');
        }

        PaymentApi::log('authcard Request, data='.json_encode($data['data']));
        $encyptString = trim($data['data']);
        $verifyResult = PaymentApi::instance()->getGateway()->decode($encyptString);
        if (empty($verifyResult)) {
            $verifyResult = json_decode($redis->get($redisKey), true);
        }

        if (!empty($verifyResult) && $verifyResult['userId'] != $userInfo['id']) {
            $this->setErr('ERR_MANUAL_REASON', 'mismatch callback');
        }

        // 通过四要素认证记录
        if (!empty($verifyResult) && $verifyResult['status'] == 'S') {
            $cert_verify_status = 1;
        }else{
            $this->setErr('ERR_MANUAL_REASON', '您的银行卡验证失败，请重试或提供其他银行卡再次申请');
        }

        // 写入缓存
        $redis->set($redisKey, json_encode($verifyResult));

        //地区列表
        $region_lv1 = $this->rpc->local('BankService\getRegion',array());
        //获取用户银行卡信息
        $bankcard_info = $this->rpc->local('BankService\userBank',array($userInfo['id'],0));
        $bank_list = $this->rpc->local('BankService\getBankUserByPaymentMethod', array());

        // 默认
        $ucfpay_bank_name = '';
        $ucfpay_bank_id = '';
        $hideExtraBanks = array();
        if (is_array($bank_list)) {
            foreach ($bank_list as $bank) {
                if ($bank['short_name'] == $verifyResult['bankCode']) {
                    $ucfpay_bank_name = $bank['name'];
                    $ucfpay_bank_id = $bank['id'];
                }
            }
        }

        // 自动换卡
        $autoUpdateResult = $this->rpc->local('UserBankcardService\autoUpdateUserBankCard', array($verifyResult));
        PaymentApi::log("autoUpdateUserBankCard, result: " . json_encode($autoUpdateResult));
        if (!empty($autoUpdateResult['status']) && $autoUpdateResult['status'] != '02') {
            if ($autoUpdateResult['status'] == '00') {
                $this->tpl->assign('msgTitle','换卡成功');
                $this->template = "api/views/_v472/payment/editbank_success.html";
                return true;
            } else {
                $this->setErr('ERR_MANUAL_REASON', $autoUpdateResult['respMsg']);
            }
        }

        $this->template = 'api/views/_v473/payment/editbank.html';
        $this->tpl->assign('token',$data['token']);
        $this->tpl->assign('certStatus',$cert_verify_status);
        $this->tpl->assign('ucfpay_bank_code',$verifyResult['bankCode']);
        $this->tpl->assign('bankcard',$verifyResult['cardNo']);
        $this->tpl->assign('cardNoFormat',formatBankcard($verifyResult['cardNo']));
        $this->tpl->assign("bankName",$ucfpay_bank_name);
        $this->tpl->assign("bankId",$ucfpay_bank_id);
        $this->tpl->assign("region_lv1",$region_lv1);
        $this->tpl->assign('idno', idnoFormat($userInfo['idno']));
        $this->tpl->assign('realName',nameFormat($userInfo['real_name']));
        $this->tpl->assign('bankzone',$bankcard_info['bankzone']);
        $this->tpl->assign('cardName',nameFormat($bankcard_info['card_name']));
        $this->tpl->assign('cardNoSignature', PaymentApi::instance()->getGateway()->getSignature(['cardNo' => $verifyResult['cardNo'], 'certStatus' => $cert_verify_status]));
    }

    public function _after_invoke() {
        if ($this->errno != 0) {
            $this->tpl->assign('error', $this->error);
            $this->tpl->assign('errno', $this->errno);
            $this->template = 'api/views/_v473/payment/editbank.html';
            Logger::error('_after_invoke error:' . $this->error . '  logId:' .Logger::getLogId());
        }

        $this->tpl->display($this->template);
    }
}

<?php
/**
 * Index.php
 *
 * @date 2014年4月8日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\BankModel;
use core\dao\UserBankcardModel;
use libs\utils\PaymentApi;
use core\service\UserBankcardService;

class Editbank extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'type'=>array("filter"=>'int'),
        );
        $this->form->validate();
    }

    public function invoke() {
        if (PaymentApi::isServiceDown())
        {
            return $this->show_tips(PaymentApi::maintainMessage());
        }

        $user_info = $GLOBALS ['user_info'];
        /**
         * 支付验卡成功同步回跳页面
         */
        $verifyResult = [];
        // 四要素认证状态
        $cert_verify_status = 0;
        $redisKey = 'authcard_result_'.$user_info['id'];
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        try {
            if (isset($_POST['data']) || $redis->get($redisKey))
            {
                PaymentApi::log('authcard Request, data='.json_encode($_POST['data']));
                $encyptString = trim($_POST['data']);
                $verifyResult = PaymentApi::instance()->getGateway()->decode($encyptString);
                if (empty($verifyResult)) {
                    $verifyResult = json_decode($redis->get($redisKey), true);
                }
                if (!empty($verifyResult) && $verifyResult['userId'] != $user_info['id'])
                {
                    throw new \Exception('mismatch callback');
                }
                $logData = $verifyResult;
                $logData['cardNo'] = formatBankcard($logData['cardNo']);
                PaymentApi::log('authcard Request, verifyResult='.json_encode($logData));
                // 通过四要素认证记录
                if (!empty($verifyResult) && $verifyResult['status'] == 'S')
                {
                    $cert_verify_status = 1;
                } else {
                    //四要素验证失败不再走人工换卡
                    return $this->show_error('您的银行卡验证失败，请重试或提供其他银行卡再次申请', '操作失败', 0, 0, '/account', 3);
                }
                // 写入缓存
                $redis->set($redisKey, json_encode($verifyResult));
            } else {
                //针对大陆身份证的用户，必须走验卡流程，不能直接访问申请页。
                if ($user_info['id_type'] == 1) {
                    return $this->show_error('请先验证您的银行卡', '操作失败', 0, 0, '/account', 3);
                }
                throw new \Exception('empty data');
            }
        } catch(\Exception $e) {
            //var_dump($e->getMessage());
            $verifyResult['cardNo'] = '';
            $verifyResult['cardNoFormat'] = '';
            $verifyResult['bankCode'] = 'ICBC';
        }
        $this->tpl->assign('cardNoFormat',formatBankcard($verifyResult['cardNo']));
        $this->tpl->assign('cardNo',$verifyResult['cardNo']);
        $this->tpl->assign('cardNoSignature', PaymentApi::instance()->getGateway()->getSignature(['cardNo' => $verifyResult['cardNo'], 'certStatus' => $cert_verify_status]));
        // 查询银行，默认选中银行
        $this->tpl->assign('ucfpay_bank_code',$verifyResult['bankCode']);
        $this->tpl->assign('certStatus',$cert_verify_status);
        //如果未绑定手机
        if(intval($GLOBALS['user_info']['mobilepassed'])==0 || intval($GLOBALS['user_info']['idcardpassed'])==0){
            $idTypes = getIdTypes();
            $this->tpl->assign("idTypes", $idTypes);
            $this->tpl->assign("page_title","成为投资者");
            $this->tpl->assign("agrant_id",200);
            $this->template = "web/views/account/mobilepaseed.html";
            return;
        }
        //如果没有实名认证
        if(!$GLOBALS['user_info']['real_name'] || $GLOBALS['user_info']['idcardpassed'] !=1){
            $idTypes = getIdTypes();
            $this->tpl->assign("idTypes", $idTypes);
            $this->tpl->assign("page_title","成为投资者");
            $this->tpl->assign("agrant_id",200);
            $this->template = "web/views/account/mobilepaseed.html";
            return;
        }
        make_delivery_region_js();

        //查询有无修改正在审核中
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($GLOBALS['user_info']['id']));
        if ($bankcard['is_audit'] == 1) {
            return $this->show_error("您已提交了一次修改申请，不能重复修改，请耐心等待审核结果!", "", 0, 0, "/account");
        }

        //新的修改逻辑页面
        $data = $this->form->data;
        $type = $data['type'];
        $is_new = true;
        if($type){
           // $is_new = true;
            $this->template = "web/views/account/ebank.html";
        }


        $bank_list = $this->rpc->local('BankService\getBankUserByPaymentMethod', array());
        $hideExtra = true;
        // 默认
        $ucfpay_bank_name = '';
        $ucfpay_bank_id = '';
        $hideExtraBanks = array();
        if (is_array($bank_list)) {
            foreach ($bank_list as $bank) {
                $hideExtraBanks[] = $bank['id'];
                if ($bank['short_name'] == $verifyResult['bankCode'])
                {
                    $ucfpay_bank_name = $bank['name'];
                    $ucfpay_bank_id = $bank['id'];
                }
            }
        }
        //$bank_list = BankModel::instance()->getAllByStatusOrderByRecSortId('0');
        //地区列表
        $region_lv1 = $this->rpc->local('BankService\getRegion',array(1));
        //获取用户银行卡信息
        $bankcard_info = $this->rpc->local('BankService\userBank',array($GLOBALS['user_info']['id'],$is_new));
        if (!in_array($bankcard_info['bank_id'], $hideExtraBanks)){
            //$bank_list = BankModel::instance()->getAllByStatusOrderByRecSortId('0');
            $hideExtra = false;
        }

        //自动换卡
        if (!empty($verifyResult['cardNo'])) {
            $userBankcardService = new UserBankcardService();
            $autoUpdateResult = $userBankcardService->autoUpdateUserBankCard($verifyResult);
            PaymentApi::log("autoUpdateUserBankCard, result: " . json_encode($autoUpdateResult));
            if (!empty($autoUpdateResult['status']) && $autoUpdateResult['status'] != '02') {
                if ($autoUpdateResult['status'] == '00') {
                    $this->template = "web/views/v3/account/editbank_success.html";
                    return;
                } else {
                    return $this->show_error($autoUpdateResult['respMsg'], '操作失败', 0, 0, '/account', 3);
                }
            }
        }

        $this->tpl->assign('hideExtra', $hideExtra);
        $this->tpl->assign("bank_list",$bank_list);
        $this->tpl->assign("bankName",$ucfpay_bank_name);
        $this->tpl->assign("bankId",$ucfpay_bank_id);
        $this->tpl->assign("region_lv1",$region_lv1);
        $this->tpl->assign("id",$bankcard_info['id']);
        $this->tpl->assign("user_bank_id",$bankcard_info['user_bank_id']);
        $this->tpl->assign('idno', idnoFormat($user_info['idno']));
        $this->tpl->assign('realName',nameFormat($user_info['real_name']));
        $this->tpl->assign("page_title",$GLOBALS['lang']['UC_BANK']);
        $this->tpl->assign('bankcard_info',$bankcard_info);
    }
}

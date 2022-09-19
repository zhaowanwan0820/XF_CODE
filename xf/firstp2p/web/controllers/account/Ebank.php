<?php

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\PaymentService;
use core\service\BankService;

/**
 *
 * @date 2014年4月8日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 * @actionLock
 */
class EBank extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
                'region_lv1'=>array("filter"=>'int'),
                'region_lv2'=>array("filter"=>'int'),
                'region_lv3'=>array("filter"=>'int'),
                'region_lv4'=>array("filter"=>'int'),

                'bank_id'=>array("filter"=>'int'),

                'bankzone'=>array("filter"=>'string'),
                'bankzone_1'=>array("filter"=>'string'),
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

        // 用户类型-企业用户 Add By guofeng 20160114 18:25
        if (isset($GLOBALS['user_info']['user_type']) && $GLOBALS['user_info']['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
        {
            return $this->show_error('企业用户不能修改银行卡信息！');
        }
        //查询有无修改正在审核中
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($user_id));
        if ($bankcard['is_audit'] == 1) {
            return $this->show_error("您已提交了一次修改申请，不能重复提交，请耐心等待审核结果!", "", 0, 0, "/account");
        }

        $data = $this->form->data;

        $bankService = new BankService();
        $data = $bankService->bankInfoXssFilter($data);
        if(empty($data['bank_id'])) {
            return $this->show_error($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK']);
        }

        if(empty($data['region_lv3'])) {
            return $this->show_error('请选择所在城市！');
        }

        if(empty($data['bankzone'])){
            return $this->show_error("请选择开户行所在地");
        }
        if($data['bankzone_1']){
            $data['bankzone'] = $data['bankzone_1'];
            unset($data['bankzone_1']);
        }
        $rs = false;
        try {
            if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {
                // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
                $paymentService = new PaymentService();
                $bankcardInfo = $paymentService->getBankcardInfo($data);
                // 发送请求
                $paymentService->bankcardSync($user_id, $bankcardInfo);
            }
            $rs = $this->rpc->local('BankService\eBank',array($user_id,$data));
        }
        catch (\Exception $e) {
            return $this->show_error("绑定银行卡失败！", "", 0, 0);
        }
        if($rs){
            return $this->show_success('银行卡信息修改成功', '', 0, 0, '/account');
        }
        return $this->show_error("修改失败！");
    }
}

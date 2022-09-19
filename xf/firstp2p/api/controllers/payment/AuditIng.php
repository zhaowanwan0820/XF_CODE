<?php
/**
 * 换卡待审核页面
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/15
 * Time: 18:21
 */
namespace api\controllers\payment;

use libs\web\Form;
use libs\utils\PaymentApi;
use api\controllers\AppBaseAction;

class AuditIng extends AppBaseAction {
    const IS_H5 = true;

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token不能为空'),
            'data' => array('filter' => 'string', 'option' => array('optional' => true)),
            "verify" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
        }

        return true;
    }

    public function invoke() {
        $formData = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
        }

        $isVerified = 0;
        // 获取提交审核时间
        $verifyTime = date('Y年m月d日');
        if ($formData['verify'] && $formData['data']) {
            // 判断是否采用了人脸识别，这里尽量复用现有的流程
            // 第一步，先验证
            $passed = $this->rpc->local('UserVerifyService\checkVerifyToken', array($formData['verify']));
            if ($passed) {
                $isVerified = 1;
                // 用户人脸已经识别成功，引导用户换卡
                $this->autoUpdateUserBankCard($userInfo, $formData['data']);
            }
        } else {
            // 银行卡信息
            $bankcard = $this->rpc->local('AccountService\getUserBankInfo', array($userInfo['id']));
            $isAudit = $bankcard['is_audit'];//查询是否正在审核中
            if ($isAudit == 1) {
                // 审核中
                $verifyTime = date('Y年m月d日', $bankcard['audit_create_time']);
            } else if ($isAudit == 3) {
                // 审核通过
                $isVerified = 1;
            }
        }

        $this->tpl->assign('verifyTime', $verifyTime);
	    $this->tpl->assign('isVerified', $isVerified);
    }

    private function autoUpdateUserBankCard($userInfo, $data) {
        if (PaymentApi::isServiceDown()) {
            $this->setErr('ERR_MANUAL_REASON', PaymentApi::maintainMessage());
        }

        // 如果未绑定手机
        if(intval($userInfo['mobilepassed']) == 0 || empty($userInfo['mobile'])) {
            $this->setErr('ERR_MANUAL_REASON', '未绑定手机号');
        }

        if (!$userInfo['real_name'] || $userInfo['idcardpassed'] != 1) {
            $this->setErr('ERR_IDENTITY_NO_VERIFY');
        }

        $redisKey = 'authcard_result_'.$userInfo['id'];
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $verifyResult = array();
        $encyptString = trim($data);
        if (!empty($encyptString) || $redis->get($redisKey)) {
            PaymentApi::log('authcard Request, data='.$encyptString);
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
        } else {
            $this->setErr('ERR_IDENTITY_NO_VERIFY','empty data');
        }

        // 自动换卡
        $autoUpdateResult = $this->rpc->local('UserBankcardService\autoUpdateUserBankCard', array($verifyResult, true));
        PaymentApi::log("autoUpdateUserBankCard, result: " . json_encode($autoUpdateResult, JSON_UNESCAPED_UNICODE));
        if (!empty($autoUpdateResult['status'])
            && $autoUpdateResult['status'] != \core\service\SupervisionBaseService::RESPONSE_CODE_SUCCESS) {
            $this->setErr('ERR_MANUAL_REASON', $autoUpdateResult['respMsg']);
        }
    }
}

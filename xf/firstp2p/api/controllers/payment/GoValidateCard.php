<?php
/**
 * 验卡跳转
 * @author yanjun<yanjun5@ucfgroup.com>
 */

namespace api\controllers\payment;
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\PaymentApi;
use core\service\SupervisionAccountService;
use core\service\risk\RiskService;

class GoValidateCard extends AppBaseAction {

    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token不能为空'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        }

        $hasPassport = $this->rpc->local('AccountService\hasPassport', array($userInfo['id']));//是否是港澳台用户
        if($hasPassport){
            $this->setErr('ERR_MANUAL_REASON','非大陆二代身份证件进行认证的客户请拨打客服热线' . $GLOBALS['sys_config']['SHOP_TEL'] . '进行咨询。');
            return false;
        }

        //风控检查
        $extraData = [
            'user_id' => $userInfo['id'],
            'mobile' => $userInfo['mobile'],
        ];
        $checkRet = RiskService::check('CHANGE_CARD', $extraData);
        if (false === $checkRet) {
            $this->setErr('ERR_MANUAL_REASON', '操作失败，请稍后再试');
            return false;
        }

        // 银行卡信息
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($userInfo['id']));
        $isAudit  = $bankcard['is_audit'];//查询是否正在审核中
        if ($isAudit == 1) {//正在审核中
            $result['url'] = $this->getHost() . '/payment/AuditIng';
        } else {
            // 大陆身份证用户前往先锋、验卡
            $returnUrl = $this->getHost();

            // 验证用户资金
            $supervisionAccountService = new SupervisionAccountService();
            $isZero = $supervisionAccountService->isZeroUserAssets($userInfo['id']);

            // 修改银行卡 - 人脸开关
            // 0 关闭人脸检测  1 打开检测
            $riskSwitch = app_conf('RISK_FACE_SWITCHS_CBCARD');
            if ($this->app_version >= 41200 && !$isZero && $riskSwitch) {
                // 开启人脸识别
                $returnUrl .= '/user/ChangeBankCard';
            } else {
                // 人工审核
                $returnUrl .= '/payment/Editbank';
            }

            $result['url'] = sprintf(
                $this->getHost() . "/payment/Transit?params=%s",
                urlencode(json_encode(['srv' => 'h5authCard', 'return_url' => $returnUrl, 'token' => $data['token']]))
            );
        }
        $this->json_data = $result;
    }

}

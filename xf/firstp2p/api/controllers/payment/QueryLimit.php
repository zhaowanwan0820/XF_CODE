<?php

namespace api\controllers\payment;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\PaymentService;
use core\service\PaymentUserAccountService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

class QueryLimit extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => '登录错误，请重新登录'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $result = ['limitDesc'=>''];
        $limitDesc = [];
        $desc = '';
        try {
            $bankInfo = $this->rpc->local('BankService\userBank', array($loginUser['id'], true));
            if (!empty($bankInfo['bank_id'])) {
                $limitRes = $this->rpc->local('PaymentUserAccountService\getChargeLimit',array(intval($bankInfo['bank_id']), $loginUser['id']));
                if (!isset($limitRes['respCode']) || $limitRes['respCode'] != '00') {
                    $errorMsg = !empty($limitRes['respMsg']) ? $limitRes['respMsg'] : '获取充值限额失败';
                    Logger::error(sprintf('userId:%d, bankId:%d, limitRes:%s, GetBankLimitServiceError:%s', $loginUser['id'], $bankInfo['bank_id'], json_encode($limitRes), $errorMsg));
                    throw new \Exception($errorMsg);
                }

                // 单笔最大限额
                $single = round(intval($limitRes['data']['singleLimit']) / 10000, 2, PHP_ROUND_HALF_DOWN);
                if ($single > 0) {
                    $limitDesc[] = $single . '万/笔';
                }

                // 日最大限额
                $day = round(intval($limitRes['data']['dayLimit']) / 10000, 2, PHP_ROUND_HALF_DOWN);
                if ($day > 0) {
                    $limitDesc[] = $day . '万/日';
                }
                // 月最大限额
                $month = round(intval($limitRes['data']['monthLimit']) / 10000, 2, PHP_ROUND_HALF_DOWN);
                if ($month > 0) {
                    $limitDesc[] = $month . '万/月';
                }
                if (!empty($limitDesc)) {
                    $result['limitDesc'] = '充值限额：' . join('，', $limitDesc);
                }
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('userId:%d, GetBankLimitApiException:%s', $loginUser['id'], $e->getMessage()));
        }

        $result['singleLimit'] = strval(intval($limitRes['data']['singleLimit']));
        $result['dayLimit'] = strval(intval($limitRes['data']['dayLimit']));
        $result['monthLimit'] = strval(intval($limitRes['data']['monthLimit']));

        // 获取网信大额充值开关及用户银行白名单
        $result['offChargeTips'] = PaymentService::getOfflineChargeV3Tips($loginUser['id']);
        $accountServ = new PaymentUserAccountService();
        // 获取网贷账户的限额信息
        $phLimitDescInfo = PaymentUserAccountService::getLimitDescByPlatform($loginUser['id'], UserAccountEnum::PLATFORM_SUPERVISION);
        $result['phSingleLimit'] = $phLimitDescInfo['singleMaxLimit'];
        // 网贷当日剩余充值额度
        $result['phDayLimit'] = $phLimitDescInfo['dayRemainLimit'];
        $result['phMonthLimit'] = $phLimitDescInfo['monthTotalLimit'];
        $result['phLimitDesc'] = $phLimitDescInfo['limitDesc'];
        $result['phSinglelimitTips'] = $phLimitDescInfo['singlelimitTips'];
        $result['phDaylimitTips'] = $phLimitDescInfo['daylimitTips'];
        // 获取网信账户的限额信息
        $wxLimitDescInfo = $accountServ->getLimitDescByPlatform($loginUser['id']);
        $result['wxLimitDesc'] = $wxLimitDescInfo['limitDesc'];

        $this->json_data = $result;
    }
}

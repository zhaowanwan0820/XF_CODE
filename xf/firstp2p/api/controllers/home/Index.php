<?php
namespace api\controllers\home;

/**
 * App首页常用接口
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\speedLoan\LoanService;
use core\service\marketing\MarketStrategyService;
use core\service\BonusService;
use libs\utils\ABControl;
use NCFGroup\Protos\Creditloan\Enum\CreditUserEnum;


class Index extends AppBaseAction
{
    private $_user;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $this->_user = $this->getUserByToken();
        if (empty($this->_user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        $this->setCheckin();
        $this->setBonusAndDiscount();
        $this->setMedal();
        $this->setSpeedLoan();
        return true;
    }


    //activity.GetCheckedInfo
    private function setCheckin()
    {
        $checkInfo = null;
        try {
            $checkInfo = $this->rpc->local('CheckinService\getCheckedInfo', array($this->_user['id']));
        } catch (\Exception $e) {
            Logger::error('GetCheckedInfoError:'.$e->getMessage());
        }
        $this->json_data['checkedInfo'] = $checkInfo;
    }

    //discount & bonus & marketing
    private function setBonusAndDiscount()
    {
        $popInfo = '';
        $discountUnused = ['count' => 0];
        $bonusUnused = ['summary' => '可用于投资'];
        if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
            try {
                //marketing.getPopupInfo
                $popRes = (new MarketStrategyService())->popup($this->_user['id']);
                $popInfo = $popRes['data'] ?: '';

                //discount.GetUnusedCount
                $discountUnused['count'] = $this->rpc->local('O2OService\getUserUnusedDiscountCount', [$this->_user['id'], 0, 0]);

                //bonus.GetUnused
                $bonusService = new BonusService();
                $unusedBonusCount = $bonusService->getUnsendCount($this->_user['id']);
                $bonusUnused['summary'] = $unusedBonusCount . "组";
                if ($unusedBonusCount < 1) {
                    $this->_userBonusInfo = $bonusService->getUsableBonus($this->_user['id']);
                    $bonusUnused['summary'] = $this->_userBonusInfo['money'] . app_conf('NEW_BONUS_UNIT');
                }

            } catch (\Exception $e) {
                Logger::error('GetBonusDiscountInfoError:'.$e->getMessage());
            }
        }
        $this->json_data['popInfo'] = $popInfo;
        $this->json_data['discountUnusedCount'] = $discountUnused;
        $this->json_data['bonusUnusedCount'] = $bonusUnused;
    }

    //medal.message & medal.progress
    private function setMedal()
    {
        $medalMessage = null;
        $medalProgress = null;
        try {
            $medalRequest = $this->rpc->local('MedalService\createUserMedalRequestParameter', [$this->_user['id']]);
            $medalMessage = $this->rpc->local('MedalService\fetchMedalMessage', [$medalRequest]);
            $medalProgress = $this->rpc->local('MedalService\getMedalProgress', [$medalRequest]);
        } catch (\Exception $e) {
            Logger::error('GetMedalInfoError:'.$e->getMessage());
        }
        $this->json_data['medalMessage'] = $medalMessage;
        $this->json_data['medalProgress'] = $medalProgress;
    }

    //speedloan.loanInfo
    private function setSpeedLoan()
    {
        $speedResult = null;
        try {
            $service = new LoanService();
            $creditUserInfo = $service->getUserCreditInfo($this->_user['id']);
            $speedResult = array(
                    'speedLoanStatus' => !empty($creditUserInfo) && $creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_SUCCESS ? '1' : '0',
                    'speedLoanMaxAmount' => number_format(app_conf('SPEED_LOAN_USER_LIMIT_AMOUNT'), 2),
                    'speedLoanUrl' => '/speedloan/index',
                    'speedLoanSwitch' => empty($creditUserInfo) || $creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_SIGNED || $creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_PROCESSING ? 0 : app_conf('SPEED_LOAN_SWITCH'),
                    'speedLoanUsableAmount' => $creditUserInfo['usableAmountFormat'],
                    );
            if (!ABControl::getInstance()->hit('speedLoan'))
            {
                $speedResult['speedLoanSwitch'] = 0;
            }
        } catch (\Exception $e) {
            Logger::error('GetSpeedResultError:'.$e->getMessage());
        }
        $this->json_data['speedLoanInfo'] = $speedResult;
    }
}


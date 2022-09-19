<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;
use NCFGroup\Protos\Creditloan\Enum\CreditUserEnum;
use NCFGroup\Protos\Creditloan\Enum\CreditLoanEnum;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Ptp\services\PtpUserService;


/**
 * LoanConfirm
 * 确认借款申请
 *
 * @uses BaseAction
 * @package default
 */
class LoanConfirm extends SpeedLoanBaseAction
{

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'loanAmt' => array('filter' => 'required', 'message' => '借款金额不能为空'),
            'verifyCode' => array('filter' => 'required', 'message' => '验证码不能为空'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo))
        {
            $this->setErr('ERR_AUTH_FAIL');
            return false;
        }
        if (!$this->isServiceTime()) {
            $tomorrow = '';
            if (intval(date('His')) > str_replace(';','', app_conf('SPEED_LOAN_SERVICE_HOUR_END').'00')) {
                $tomorrow = '次日';
            } else if (intval(date('His')) < str_replace(';','', app_conf('SPEED_LOAN_SERVICE_HOUR_START').'00')) {
                $tomorrow = '今日';
            }
            $this->setErr('ERR_MANUAL_REASON', '温馨提示：请于'.$tomorrow.str_replace(';',':', app_conf('SPEED_LOAN_SERVICE_HOUR_START')).'后申请借款');
            return false;
        }
        // 判断用户借款验证方式
        $validateMethod = $this->getLoanValidateMethod($userInfo['id']);
        //if ($validateMethod == 'sms') {
        $verifyService = new \core\service\MobileCodeService();
        $vcode = $verifyService->getMobilePhoneTimeVcode($userInfo['mobile'], null, 0);
        if($vcode != $data['verifyCode'])
        {
            $this->setErr("ERR_SIGNUP_CODE");
            return false;
        }
        $verifyService->delMobileCode($userInfo['mobile']);
        //}
        // || $data['loanAmt'] > app_conf('SPEED_LOAN_MAX_AMOUNT')) {
        // 金额校验
        if ($data['loanAmt'] % 100 != 0) {
            $this->setErr('ERR_MANUAL_REASON', sprintf('借款金额须为100的整数倍', app_conf('SPEED_LOAN_MIN_AMOUNT')));
            return false;
        }
        if ($data['loanAmt'] < app_conf('SPEED_LOAN_MIN_AMOUNT')) {
            $this->setErr('ERR_MANUAL_REASON', sprintf('单笔借款金额最低%s元', app_conf('SPEED_LOAN_MIN_AMOUNT')));
            return false;
        }
        if ($data['loanAmt'] > app_conf('SPEED_LOAN_MAX_AMOUNT')) {
            $this->setErr('ERR_MANUAL_REASON', sprintf('单笔最高可借%s元', app_conf('SPEED_LOAN_MAX_AMOUNT')));
            return false;
        }


        // 构建借款申请数据
        $ptpUser = new ProtoUser();
        $ptpUser->setUserId((int)$userInfo['id']);
        $ptpService = new PtpUserService();
        $responseBankInfo = $ptpService->getCreditLoanInfoByUserId($ptpUser);

        $loanService = new \core\service\speedLoan\LoanService();
        $userCreditInfo = $loanService->getUserCreditInfo($userInfo['id']);
        if (empty($userCreditInfo['account_status']) || $userCreditInfo['account_status'] == CreditUserEnum::ACCOUNT_STATUS_DISABLE) {
            $this->setErr('ERR_SPEEDLOAN_ACCOUNT_DISABLE');
            return false;
        }
        // 总借款上限判断
        if (bcadd(bcdiv($userCreditInfo['loan_amount'], 100), $data['loanAmt']) > app_conf('SPEED_LOAN_USER_LIMIT_AMOUNT')) {
            $this->setErr('ERR_MANUAL_REASON', '超过可用额度');
            return false;
        }
        // 最后一次还款日期
        $loanDays = (new \core\service\speedLoan\LoanService())->getLoanDays($userCreditInfo['repay_date']);
        // 用户总金额
        $summary = (new \core\service\AccountService())->getUserSummary($userInfo['id']);
        $withdrawData = [
            'userId' => $userCreditInfo['jf_user_id'],
            'capApplyNo' => $userCreditInfo['out_order_id'],
            'paymentNo' => Idworker::instance()->getId(),
            'paymentTime' => date('YmdHis'),
            'loanAmt' => bcmul($data['loanAmt'], 100),
            'loanPeriod' => $loanDays+10, // 借款期限，目前只支持传30, 60
            'loanPeriodUnit' => '1',
            'repaymentMethod' =>CreditLoanEnum::$repaymentMethodMap[CreditEnum::REPAYMENT_METHOD_EQUAL_PRINCIPAL],
            'bankNo' => $responseBankInfo->getBankCode(),
            'bankName' => $responseBankInfo->getBank(),
            'ctfType' => '0',
            'ctfNo' => $responseBankInfo->getIdno(),
            'username' => $responseBankInfo->getRealName(),
            'cardNo' => $responseBankInfo->getBankNo(),
            'mobile' => $responseBankInfo->getMobile(),
            'principalAmount' => $userCreditInfo['total_asset'],
            'lastDate' => $userCreditInfo['repay_date'],
        ];
        $serviceRate = bcdiv(app_conf('SPEED_LOAN_SERVICE_FEE_STEP_ONE'), 100, 6); //服务费率
        $creditLoanData = [
            'userId' => $userInfo['id'],
            'orderId' => $withdrawData['paymentNo'],
            'outOrderId' => $withdrawData['paymentNo'],
            'totalAmount' => $withdrawData['loanAmt'],
            'loanRepayMethod' => CreditEnum::REPAYMENT_METHOD_EQUAL_PRINCIPAL,
            'applyTime' => time(),
            'dailyRate' => app_conf('SPEED_LOAN_DAILY_RATE'),
            'dealTplType' => CreditLoanEnum::DEAL_TPL_TYPE_HAIKOU,
            'loanProvider' => CreditLoanEnum::LOAN_PROVIDER_HAIKOU,
            'loanStatus' => CreditLoanEnum::LOAN_STATUS_APPLY,
            'loanType' => CreditLoanEnum::LOAN_TYPE_NORMAL,
            'loanMode' => CreditLoanEnum::LOAN_MODE_ONLINE,
            'loanDays' =>$loanDays,
            'serviceFeeAssess' => intval($withdrawData['loanAmt'] * $serviceRate),
        ];

        // 创建借款记录
        $createResult = $loanService->createCreditLoan($creditLoanData);
        if (isset($createResult['errCode']) && $createResult['errCode'] != 0)
        {
            $this->setErr('ERR_MANUAL_REASON', $createResult['errMsg']);
            return false;
        }
        // 发送提现
        $response = $loanService->withdraw($withdrawData);
        if (isset($resposne['errCode']) && $resposne['errCode'] != 0) {
            $this->setErr($response['errMsg']);
            return false;
        }
        if (isset($response['data']['code']) && $response['data']['code'] != CreditEnum::CODE_SUCCESS) {
            $this->setErr($response['data']['message']);
            return false;
        }

        $this->json_data  = [
            'orderId' => $withdrawData['paymentNo'],
            'token' => $data['token'],
        ];
    }
}

<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;

/**
 * Apply
 * 申请审核页面
 *
 * @uses BaseAction
 * @package default
 */
class Apply extends SpeedLoanBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
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
        if (empty($userInfo)) {
            $this->setErr('ERR_AUTH_FAIL');
            return false;
        }
        $userInfo = $userInfo->getRow();
        $userInfo['idnoFormat'] = idnoFormat($userInfo['idno']);
        $userInfo['mobileFormat'] = format_mobile($userInfo['mobile']);
        // 初始化用户
        $initCreditUserInfo = [
            'userId' => $userInfo['id'],
        ];
        $gateway = new \core\service\speedLoan\LoanService();
        $result = $gateway->createCreditUser($initCreditUserInfo);
        if (!isset($result['errCode']) || $result['errCode'] != 0)
        {
            $this->setErr('ERR_MANUAL_REASON', $result['errMsg']);
            return false;
        }
        $this->tpl->assign('userInfo', $userInfo);
        // 银行卡相关信息
        $bankcardInfo = (new \core\service\UserBankcardService())->getBankcard($userInfo['id']);
        if (empty($bankcardInfo)) {
            $url = sprintf(
                $this->getHost() . "/payment/Transit?token={$data['token']}&params=%s",
                urlencode(json_encode(['srv' => 'bindcard', 'return_url' =>'firstp2p://api?type=native&name=home', 'token' => $data['token']]))
            );
            echo '<script>window.location.href="'.$url.'";</script>';
            return ;

        }
        $bankcardInfo = $bankcardInfo->getRow();
        $bankcardInfo['cardNoFormat'] = formatBankcard($bankcardInfo['bankcard']);
        $bankInfo = (new \core\service\BankService())->getBank($bankcardInfo['bank_id']);
        $bankcardInfo['bankName'] = $bankInfo['name'];
        $this->tpl->assign('bankcardInfo', $bankcardInfo);
        $this->tpl->assign('token', $data['token']);
    }
}

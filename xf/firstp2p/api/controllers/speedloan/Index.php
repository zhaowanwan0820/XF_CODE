<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use libs\utils\Monitor;
use NCFGroup\Protos\Creditloan\Enum\CreditUserEnum;
use core\dao\UserBankcardModel;

/**
 * Index
 * 网信速贷首页
 *
 * @uses BaseAction
 * @package default
 */
class Index extends SpeedLoanBaseAction
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
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 未实名认证 回首页
        if (empty($userInfo['idcardpassed'])) {
            $this->template = 'speedloan/notice.html';
            return ;
        }
        $userInfo = $userInfo->getRow();
        $userId = $userInfo['id'];
        // 用户是否有权限访问速贷服务
        // 四要素校验
        $bankcardInfo = (new \core\service\UserBankcardService())->getBankcard($userInfo['id']);
        if(empty($bankcardInfo)) {
            $url = sprintf(
                $this->getHost() . "/payment/Transit?token={$data['token']}&params=%s",
                urlencode(json_encode(['srv' => 'bindcard', 'return_url' =>$this->getHost().'/speedloan/index?token='.$data['token'], 'token' => $data['token'], 'reqSource' => 1]))
            );
            echo '<title>绑定银行卡</title><script>window.location.href="'.$url.'";</script>';
            return ;

        }
        $bankcardInfo = $bankcardInfo->getRow();
        //echo '<pre>';
        //var_dump($bankcardInfo);
        // 未绑卡或者四要素没有通过，换起四要素认证页面
        if ($bankcardInfo['cert_status'] != UserBankcardModel::$cert_status_map['FASTPAY_CERT']) {

            $url = sprintf(
                $this->getHost() . "/payment/Transit?token={$data['token']}&params=%s",
                urlencode(json_encode(['srv' => 'cardValidate', 'return_url' => $this->getHost().'/speedloan/index?token='.$data['token'], 'token' => $data['token']]))
            );
            echo '<title>银行卡快捷验证</title><script>window.location.href="'.$url.'";</script>';
            return ;
        }
        // 在速贷黑名单
        $creditSerivce = new \core\service\speedLoan\LoanService();
        $creditUserInfo = $creditSerivce->getUserCreditInfo($userId);
        $userService = new \core\service\UserService();
        $userAge = $userService->getAgeByUserId($userId);
        // 银信通黑名单开关打开并且用户id在黑名单中 用户年龄不在22 到 65之间  用户开通速贷失败
        if ((app_conf('CREDIT_LOAN_BLACKLIST_SWITCH') == 1 && in_array($userId, explode(';', app_conf('CREDIT_LOAN_BLACKLIST')))) || (($userAge < 22 || $userAge > 65) && $creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_SIGNED) || $creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_FAILURE) {
            \libs\utils\PaymentApi::log('Speedloan User blocked, userId('.$userId.') blacklistSwitch('.app_conf('CREDIT_LOAN_BLACKLIST_SWITCH').'), blacklist('.app_conf('CREDIT_LOAN_BLACKLIST')    .') userAge('.$userAge.', '.$creditUserInfo['loan_amount'].') creditStatus('.$creditUserInfo['credit_status'].')');
            $this->tpl->assign('tipText', '');
            $this->template = 'speedloan/notice.html';
            return ;
        }

        // 用户开通银信通服务
        $this->tpl->assign('creditUserInfo', $creditUserInfo);
        // 银信通配置信息
        $this->tpl->assign('dailyRate', app_conf('SPEED_LOAN_DAILY_RATE'));
        $this->tpl->assign('amountLimit', number_format(floatval(app_conf('SPEED_LOAN_USER_LIMIT_AMOUNT'))));
        $this->tpl->assign('token', $data['token']);
        if (empty($creditUserInfo['id']))
        {
            // 用户未开通，提示协议页面开通
            //$this->template = 'speedloan/welcome.html';
            // 未开通用户显示初始值
            $creditUserInfo = [
                'usableAmountFormat' => number_format(floatval(0)),
                'totalAmountFormat' => number_format(floatval(app_conf('SPEED_LOAN_USER_LIMIT_AMOUNT'))),
            ];
            $this->tpl->assign('creditUserInfo', $creditUserInfo);
            return;
        }  else if ($creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_SIGNED) {
            //echo '<title>基本信息</title><script> window.location.href="/speedloan/apply?token='.$data['token'].'";</script>';
            $creditUserInfo = [
                'usableAmountFormat' => number_format(floatval(0)),
                'totalAmountFormat' => number_format(floatval(app_conf('SPEED_LOAN_USER_LIMIT_AMOUNT'))),
            ];
            $this->tpl->assign('creditUserInfo', $creditUserInfo);
            return ;
        } else if ($creditUserInfo['credit_status'] == CreditUserEnum::CREDIT_STATUS_PROCESSING || (empty($creditUserInfo['totalAmount']) && $creditUserInfo['isNewApply'])) {
            //$this->template = 'speedloan/apply_wait.html';
            $creditUserInfo = [
                'usableAmountFormat' => number_format(floatval(0)),
                'totalAmountFormat' => number_format(floatval(app_conf('SPEED_LOAN_USER_LIMIT_AMOUNT'))),
            ];
            $this->tpl->assign('creditUserInfo', $creditUserInfo);
            return;
        }

        $serviceNotOpen = 0;
        // 限制用户借款, 前端禁止用户提交借款申请 不在服务时间内， 用户状态被禁用
        if ($creditUserInfo['account_status'] == CreditUserEnum::ACCOUNT_STATUS_DISABLE) {
            $serviceNotOpen = 1;
        }
        $this->tpl->assign('serviceNotOpen', $serviceNotOpen);
        // 用户开通银信通服务
        $this->tpl->assign('creditUserInfo', $creditUserInfo);
        // 银信通配置信息
        $this->tpl->assign('dailyRate', app_conf('SPEED_LOAN_DAILY_RATE'));
        $this->tpl->assign('amountLimit', number_format(floatval(app_conf('SPEED_LOAN_USER_LIMIT_AMOUNT'))));
    }
}

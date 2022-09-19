<?php
/**
 * Index.php
 *
 * @date 2014年4月8日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionService;
use core\service\bonus\BonusService;
use core\service\user\BankService;
use core\service\payment\PaymentUserAccountService;
use core\enum\EnterpriseEnum;

class Index extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $userInfo = $GLOBALS['user_info'];
        $userSex = ($userInfo['sex'] == -1) ? 1 : $userInfo['sex']; //用户行性别
        $userId = $userInfo['id'];
        $accountId = AccountService::getUserAccountId($userId, $userInfo['user_purpose']);
        $userInfo['account_id'] = $accountId;
        $user_last_time = get_user_last_time($userId); //用户最后登录时间

        //用户银行卡信息
        $bankcard = BankService::getUserBankInfo($userId);
        //用户统计
        $user_statics = user_statics($userId);

        // 代收本金增加智多鑫待投本金
        $user_statics['principal'] = bcadd($user_statics['principal'], $user_statics['dt_norepay_principal'], 2);

        $hasPassport = PaymentUserAccountService::hasPassport($userId, $userInfo);
        $bonus = BonusService::getUsableBonus($userId, false, 0, false, $userInfo['is_enterprise_user']);

        // 绑卡表单
        //$paymentService = new PaymentService();
        //$bindCardForm = $paymentService->getBindCardForm(['token' => base64_encode(microtime(true))], true, false, 'bindCardForm');
        //$this->tpl->assign('bindCardForm', $bindCardForm);
        // 如果是企业用户，判断用户的审核状态
        if ($this->isEnterprise) {
            $enterpriseRegisterInfo = UserService::getEnterpriseRegisterInfo($userId);
            $enterprise_verify_status = isset($enterpriseRegisterInfo['verify_status']) ? $enterpriseRegisterInfo['verify_status'] : EnterpriseEnum::VERIFY_STATUS_PASS;
            $this->tpl->assign('enterprise_verify_status', $enterprise_verify_status);
            // 获取企业用户基本信息
            $enterpriseInfo = UserService::getEnterpriseInfo($userId);
            $this->tpl->assign('enterpriseInfo', $enterpriseInfo);
        }

        //是否开户
        $supervisionAccountService = new SupervisionAccountService();
        $userInfo['isSvUser'] = $supervisionAccountService->isSupervisionUser($accountId);

        //账户余额
        $userInfo['svCashMoney'] = $userInfo['svFreezeMoney'] = $userInfo['svTotalMoney'] = 0;
        if (!empty($accountId)) {
            $balanceResult = AccountService::getAccountMoneyById($accountId);
            $userInfo['svCashMoney'] = $balanceResult['money'];
            // 冻结金额减掉智多鑫待投本金
            $userInfo['svFreezeMoney'] = bcsub($balanceResult['lockMoney'], $user_statics['dt_remain'], 2);
            $userInfo['svTotalMoney'] = $balanceResult['totalMoney'];
        }

        //网信普惠
        $moneyInfo = array(
            'totalCashMoney' => $userInfo['svCashMoney'],//现金金额
            'svCashMoney' => $userInfo['svCashMoney'],//网贷P2P账户现金余额
            'bonusMoney' => $bonus['money'],//红包金额
            'freezeMoney' => $userInfo['svFreezeMoney'],//冻结金额
            'principalMoney' => bcadd($user_statics['norepay_principal'],$user_statics['dt_load_money'],2),//待收本金 加上智多鑫
            'interestMoney' => $user_statics['norepay_interest'],//待收收益
        );
        // 存管账户开户弹窗，显示[0:开通]还是[1:升级]
        $hasUnactivatedTag = AccountService::isUnactivated($accountId);
        $this->tpl->assign('openSvButton', (int)$hasUnactivatedTag);

        //存管降级
        $isSvDown = SupervisionService::isServiceDown();
        $this->tpl->assign('isSvDown', $isSvDown);
        $this->tpl->assign('passportNotice', \es_session::get('passportNotice'));
        \es_session::delete('passportNotice');

        $this->tpl->assign('bonus', $bonus['money']);
        $this->tpl->assign('hasPassport', $hasPassport);
        $this->tpl->assign('user_info',$userInfo);
        $this->tpl->assign('user_statics',$user_statics);
        $this->tpl->assign('bankcard',$bankcard);
        $this->tpl->assign("user_sex", $userSex);
        $this->tpl->assign("last_time", $user_last_time);
        $this->tpl->assign("is_duotou_inner_user", is_duotou_inner_user() ? 1 : 0);
        $this->tpl->assign("inc_file","web/views/account/index.html");
        $this->tpl->assign("moneyInfo", $moneyInfo);
        $this->tpl->assign("ppId", \es_session::get('ppId'));
        $this->tpl->assign("hasUnactivatedTag", $hasUnactivatedTag);
        $this->template = "web/views/account/frame.html";
    }
}

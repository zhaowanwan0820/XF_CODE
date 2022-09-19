<?php
/**
 * Index.php
 *
 * @date 2014年4月8日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\utils\Logger;
use web\controllers\BaseAction;
use libs\utils\Finance;
use core\dao\BankModel;
use core\service\SupervisionAccountService;
use libs\utils\Rpc;
use libs\utils\PaymentApi;
use libs\payment\supervision\Supervision;

class Index extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {

        $user_info = $GLOBALS['user_info'];
        $user_sex       = ($user_info['sex'] == -1) ? 1 : $user_info['sex']; //用户行性别
        $user_last_time = get_user_last_time($user_info['id']); //用户最后登录时间

        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($user_info['id']));
        //用户统计
        $user_statics = user_statics($GLOBALS['user_info']['id']);
        //---合并普惠数据
        $user_statics_ncfph = (new \core\service\ncfph\AccountService())->getUserStat($user_info['id']);
        $user_statics = \core\service\ncfph\AccountService::mergeP2P($user_statics, $user_statics_ncfph);
        //资产总额
        $user_statics['money_all'] = Finance::addition(array($user_info['money'], $user_info['lock_money'], $user_statics['new_stay']), 5);

        // 冻结金额减掉智多鑫待投本金，代收本金增加智多鑫待投本金
        $user_info['lock_money'] = bcsub($user_info['lock_money'], $user_statics['dt_remain'], 2);
        $user_statics['principal'] = bcadd($user_statics['principal'], $user_statics['dt_norepay_principal'], 2);

        //资金记录
        $log = $this->rpc->local('UserLogService\get_user_log',array(array(0,5),$user_info['id'],'money', 'web_account_index'));
        //投资概览
        $invest = $this->rpc->local('AccountService\getInvestOverview',array($user_info['id']));
        //投资概览 p2p
        $invest_ncfph = $this->rpc->local('AccountService\getInvestOverview',array($user_info['id']),'ncfph');

        //回款计划
        //$deal_repay = $this->rpc->local('AccountService\getDealRepayOverview',array($user_info['id']));
        //回款计划 p2p
        //$deal_repay_ncfph = $this->rpc->local('AccountService\getDealRepayOverview',array($user_info['id']),'ncfph');
        $i=0;
        do{
            $invest[$i]['counts'] += $invest_ncfph[$i]['counts'];
            $invest[$i]['money'] += $invest_ncfph[$i]['money'];
            //$deal_repay[$i]['counts'] += $deal_repay_ncfph[$i]['counts'];
            //$deal_repay[$i]['money'] += $deal_repay_ncfph[$i]['money'];
            $i++;
        }while($i<4);
        $charge_list = $this->rpc->local('UserLogService\get_charge_list', array($user_info['id']));
        $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_info['id']));
        $usedQuickPay = false;
        if (app_conf('PAYMENT_QUERY_PASSWORD')) {
            $usedQuickPay = $this->rpc->local('AccountService\usedQuickPay', array($user_info['id']));
        }
        else {
            $usedQuickPay = true;
        }

        //是否为18家银行
        $bank_list = $this->rpc->local('BankService\getBankUserByPaymentMethod', array());
        $hideExtra = true;
        $hideExtraBanks = array();
        if (is_array($bank_list)) {
            foreach ($bank_list as $bank) {
                $hideExtraBanks[] = $bank['id'];
            }
        }


        //$bank_list = BankModel::instance()->getAllByStatusOrderByRecSortId('0');
        //地区列表
        //获取用户银行卡信息
        $bankcard_info = $this->rpc->local('BankService\userBank',array($GLOBALS['user_info']['id'],true));
        if (!in_array(@$bankcard_info['bank_id'], $hideExtraBanks)){
            $hideExtra = false;
        }

        //利滚利
        $compound = $user_statics['compound'];

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($user_info['id']));
        $user_info['total_money'] = Finance::addition(array($user_info['money'], $bonus['money']), 2);

        // 绑卡表单
        $bindCardForm = $this->rpc->local('PaymentService\getBindCardForm', [['token' => base64_encode(microtime(true))], true, false, 'bindCardForm']);
        $this->tpl->assign('bindCardForm', $bindCardForm);
        // 如果是企业用户，判断用户的审核状态
        if ($this->isEnterprise) {
            $enterprise_verify_status = $this->rpc->local('EnterpriseService\getVerifyStatus', array($user_info['id']));
            $this->tpl->assign('enterprise_verify_status', $enterprise_verify_status);
        }
        // 资产总额去除红包
        //$user_statics['money_all'] = Finance::addition(array($user_statics['money_all'], $bonus['money']), 2);

        // //是否开户
        // $user_info['isSvUser'] = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($user_info['id']));

        // //资产中心余额
        // $balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($user_info['id']));
        // $user_info['svCashMoney'] = $balanceResult['supervisionBalance'];
        // $user_info['svFreezeMoney'] = $balanceResult['supervisionLockMoney'];
        // $user_info['svTotalMoney'] = $balanceResult['supervisionMoney'];

        $accountInfo = (new \core\service\ncfph\AccountService())->getInfoByUserIdAndType($user_info['id'], $user_info['user_purpose']);
        $user_info['isSvUser'] = $accountInfo['isSupervisionUser'];
        $user_info['svCashMoney'] = $accountInfo['money'];
        $user_info['svFreezeMoney'] = $accountInfo['lockMoney'];
        $user_info['svTotalMoney'] = $accountInfo['totalMoney'];

        $moneyInfo = array(
            'totalCashMoney' => Finance::addition(array($user_info['money'], $user_info['svCashMoney']), 2),//现金金额
            'wxCashMoney' => $user_info['money'],//网信理财账户现金余额
            'svCashMoney' => $user_info['svCashMoney'],//网贷P2P账户现金余额
            'bonusMoney' => $bonus['money'],//红包金额
            'freezeMoney' => Finance::addition(array($user_info['lock_money'], $user_info['svFreezeMoney']), 2),//冻结金额
            'principalMoney' => $user_statics['principal'],//待收本金
            'interestMoney' => $user_statics['interest'],//待收收益
            'allMoney' => Finance::addition(array($user_statics['money_all'], $user_info['svTotalMoney']), 2),//资产总额
        );

        //网信普惠
        if ($this->is_firstp2p) {
            $moneyInfo = array(
                'totalCashMoney' => $user_info['svCashMoney'],//现金金额
                'bonusMoney' => $bonus['money'],//红包金额
                'freezeMoney' => $user_info['svFreezeMoney'],//冻结金额
                'principalMoney' => $user_statics['cg_norepay_principal'],//待收本金
                'interestMoney' => $user_statics['cg_norepay_earnings'],//待收收益
            );
            $result= $this->rpc->local('SupervisionAccountService\memberStandardRegisterPage', [$user_info['id']]);
            $this->tpl->assign('formString', $result['data']['form']);
            $this->tpl->assign('formId', $result['data']['formId']);
        }
        // 存管账户开户弹窗，显示[0:开通]还是[1:升级]
        $hasUnactivatedTag = $this->rpc->local('UserTagService\getTagByConstNameUserId', array('SV_UNACTIVATED_USER', $user_info['id']));
        $userId = isset($user_info['id']) ? (int)$user_info['id'] : 0;
        $openSvButton = $this->rpc->local('SupervisionService\isUpgradeAccount', array($userId));
        $this->tpl->assign('openSvButton', (int)$openSvButton);

        //存管降级
        $isSvDown = Supervision::isServiceDown();
        $this->tpl->assign('isSvDown', $isSvDown);
        $this->tpl->assign('passportNotice', \es_session::get('passportNotice'));
        \es_session::delete('passportNotice');

        $this->tpl->assign("dt_remain_money",$user_statics['dt_norepay_principal']);
        $this->tpl->assign("dt_can_redeem",$user_statics['dt_can_redeem']);
        $this->tpl->assign('hideExtra', $hideExtra);
        $this->tpl->assign('bonus', $bonus['money']);
        $this->tpl->assign('compound', $compound);
        $this->tpl->assign('hasPassport', $hasPassport);
        $this->tpl->assign('usedQuickPay', $usedQuickPay);
        $this->tpl->assign('is_audit',$bankcard['is_audit']);
        $this->tpl->assign('user_info',$user_info);
        $this->tpl->assign('user_statics',$user_statics);
        $this->tpl->assign('bankcard',$bankcard);
        $this->tpl->assign('log',$log['list']);
        $this->tpl->assign('charge_list', $charge_list);
        $this->tpl->assign("invest",$invest);
        //$this->tpl->assign("deal_repay",$deal_repay);
        $this->tpl->assign("user_sex", $user_sex);
        $this->tpl->assign("last_time", $user_last_time);
        $this->tpl->assign("is_duotou_inner_user", is_duotou_inner_user() ? 1 : 0);
        $this->tpl->assign("inc_file","web/views/account/index.html");
        $this->tpl->assign("moneyInfo", $moneyInfo);
        $this->tpl->assign("ppId", \es_session::get('ppId'));
        $this->tpl->assign("hasUnactivatedTag", $hasUnactivatedTag);
        $this->template = "web/views/account/frame.html";
    }
}

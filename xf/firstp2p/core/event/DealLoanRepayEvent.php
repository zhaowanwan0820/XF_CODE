<?php
namespace core\event;

use NCFGroup\Common\Library\Msgbus;
use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\Logger;
use core\event\BaseEvent;

use core\dao\DealModel;
use core\dao\DealLoanRepayModel;
use core\dao\UserModel;
use core\dao\FinanceQueueModel;
use core\dao\JobsModel;
use core\service\DealService;
use core\service\DealCompoundService;
use core\service\UserLoanRepayStatisticsService;
use core\dao\UserLoanRepayStatisticsModel;
use core\service\DealLoanRepayCalendarService;

class DealLoanRepayEvent extends BaseEvent {
    private $_deal_repay;
    private $_deal_loan;
    private $_arr_deal_loan_repay;
    private $_arr_deal_repay;
    private $_principal;
    private $_is_last;

    public function __construct($deal_repay, $deal_loan, $arr_deal_loan_repay, $arr_deal_repay, $principal, $is_last) {
        $this->_deal_repay = $deal_repay;
        $this->_deal_loan = $deal_loan;
        $this->_arr_deal_loan_repay = $arr_deal_loan_repay;
        $this->_arr_deal_repay = $arr_deal_repay;
        $this->_principal = $principal;
        $this->_is_last = $is_last;
    }

    public function execute() {
        $deal_service = new DealService();
        $deal = DealModel::instance()->find($this->_deal_loan['deal_id']);

        $isDealExchange = ($deal['deal_type'] == DealModel::DEAL_TYPE_EXCHANGE) ? true : false;//是不是交易所
        $isDealCG = $deal_service->isP2pPath($deal);

        // 按月、按季等额本息需要检查是否为最后一期，如果为最后一期，则计算出剩余未生成计划的本金
        $special_repay_types = array(
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH'],
        );

        if ($this->_is_last == true && in_array($deal['loantype'], $special_repay_types)) {
            $principal_fix = DealLoanRepayModel::instance()->getFixPrincipalByLoanId($this->_deal_loan, $deal);
            if ($principal_fix === false) {
                throw new \Exception(JobsModel::ERRORMSG_NEEDDELAY, JobsModel::ERRORCODE_NEEDDELAY);
            }
        }

        $model = new DealLoanRepayModel();

        $model->deal_id = $this->_deal_repay['deal_id'];
        $model->deal_repay_id = $this->_deal_repay['id'];
        $model->deal_loan_id = $this->_deal_loan['id'];
        $model->loan_user_id = $this->_deal_loan['user_id'];
        $model->borrow_user_id = $this->_deal_repay['user_id'];
        $model->time = $this->_deal_repay['repay_time'];
        $model->status = DealLoanRepayModel::STATUS_NOTPAYED;
        $model->create_time = get_gmtime();
        $model->update_time = get_gmtime();
        $model->deal_type = $this->_deal_repay['deal_type'];

        $GLOBALS['db']->startTrans();
        try {
            // 通知贷生成一条没有时间的回款计划
            if ($deal['deal_type'] == 1) {
                $arr = DealLoanRepayModel::instance()->getLoanRepayListByLoanId($this->_deal_loan['id']);
                if (!$arr) {
                    $model->time = 0;
                    $model->type = DealLoanRepayModel::MONEY_COMPOUND_PRINCIPAL;
                    $model->money = $this->_arr_deal_loan_repay['principal'];
                    if ($model->insert() === false) {
                        throw new \Exception('deal_loan_repay insert principal fail');
                    }

                    // 即付通知贷自动赎回
                    if ($deal_service->isDealJF($this->_deal_loan['site_id']) === true) {
                        $dc_service = new DealCompoundService();
                        if ($dc_service->redeem($this->_deal_loan['id'], $this->_deal_loan['user_id']) === false) {
                            throw new \Exception('compound auto redeem fail');
                        }
                    }

                    $moneyInfo = array(
                        UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL => $this->_arr_deal_loan_repay['principal'],
                    );
                    if($isDealExchange) {//大金所收集
                        //大金所待回本金
                        $moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_PRINCIPAL] = $this->_arr_deal_loan_repay['principal'];
                    }
                    if ($isDealCG) { // 存管网贷资产
                        $moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_PRINCIPAL] = $this->_arr_deal_loan_repay['principal'];
                    }
                    if (UserLoanRepayStatisticsService::updateUserAssets($model->loan_user_id,$moneyInfo) === false) {
                        throw new \Exception("user loan repay statistic error");
                    }
                }
            } elseif ($deal['deal_type'] != 1 && $deal['loantype'] != $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) {
                // 公益标仅转账不生成回款计划

                // 本金
                $model->type = DealLoanRepayModel::MONEY_PRINCIPAL;

                if (isset($principal_fix) && !empty($principal_fix)) {
                    $model->money = $principal_fix;
                } else {
                    $model->money = $this->_arr_deal_loan_repay['principal'];
                }

                if (bccomp($model->money, 0, 2) >= 0) {
                    if ($model->insert() === false) {
                        throw new \Exception('deal_loan_repay insert principal fail');
                    }
                }

                // 利息
                $model->type = DealLoanRepayModel::MONEY_INTREST;
                $model->money = $this->_arr_deal_loan_repay['interest'];
                if ($model->insert() === false) {
                    throw new \Exception('deal_loan_repay insert interest fail');
                }

                // 管理费
                $model->type = DealLoanRepayModel::MONEY_MANAGE;
                $model->money = $this->_arr_deal_repay['total'] - $this->_arr_deal_loan_repay['total'];
                if (bccomp($model->money, 0, 2) == 1) {
                    if ($model->insert() === false) {
                        throw new \Exception('deal_loan_repay insert manage fail');
                    }
                }

                if ($this->_is_last == true) {
                    $moneyInfo[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL] = $this->_deal_loan['money'];
                    if($isDealExchange) {//交易所收集
                        //交易所待回本金
                        $moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_PRINCIPAL] = $this->_deal_loan['money'];
                    }
                    if($isDealCG) {// 网贷存管
                        $moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_PRINCIPAL] = $this->_deal_loan['money'];
                    }
                }
                $moneyInfo[UserLoanRepayStatisticsService::NOREPAY_INTEREST] = $this->_arr_deal_loan_repay['interest'];
                if($isDealExchange) {//交易所收集
                    //大金所待收收益
                    $moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_EARNINGS] = $this->_arr_deal_loan_repay['interest'];
                }

                if($isDealCG) { //网贷存管
                    $moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_EARNINGS] = $this->_arr_deal_loan_repay['interest'];
                }

                // 智多鑫标的不进入回款日历，不进入总资产
                if ($deal_service->isDealDT($deal['id']) === false) {
                    $infoCal = array(
                        DealLoanRepayCalendarService::NOREPAY_INTEREST => $this->_arr_deal_loan_repay['interest'],
                        DealLoanRepayCalendarService::NOREPAY_PRINCIPAL => (isset($principal_fix) && !empty($principal_fix)) ? $principal_fix : $this->_arr_deal_loan_repay['principal'],
                    );
                    if (DealLoanRepayCalendarService::collect($model->loan_user_id,strtotime(to_date($this->_deal_repay['repay_time'])), $infoCal) === false) {
                        throw new \Exception("save calendar error");
                    }

                    if (UserLoanRepayStatisticsService::updateUserAssets($model->loan_user_id,$moneyInfo) === false) {
                        throw new \Exception("user loan repay statistic error");
                    }
                } else {
                    if ($this->_is_last == true) {
                        $moneyInfo = array(
                            UserLoanRepayStatisticsService::DT_LOAD_MONEY => $this->_deal_loan['money'],
                        );
                        if (UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($model->loan_user_id, $moneyInfo) === false) {
                            throw new \Exception("user loan repay statistic error");
                        }
                    }
                }
            }

            if ($this->_is_last == true) {
                // 转账开始
                $user = UserModel::instance()->find($this->_deal_loan['user_id']);
                //$user->changeMoneyAsyn = true;
                $user->changeMoneyDealType = $deal_service->getDealType($deal);

                $bizToken = [
                    'dealId' => $deal['id'],
                    'dealLoadId' => $this->_deal_loan['id'],
                ];
                $user->changeMoney($this->_deal_loan['money'], '投资放款', "编号{$deal['id']} {$deal['name']}，单号{$this->_deal_loan['id']}", 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY,0,$bizToken);
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }
        if(!empty($moneyInfo)){
            $msg = array('type' => 'grant','uid'=>$model->loan_user_id,'moneyInfo' => $moneyInfo,'time' => time());
            Msgbus::instance()->produce('wx_user_asset_change', $msg);
        }
        return true;
    }

    public function alertMails() {
        return array('wangyiming@ucfgroup.com');
    }
}

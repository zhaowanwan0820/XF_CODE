<?php
/**
 * AccountService.php
 *
 * @date 2014-03-24
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 */

namespace core\service;

use core\dao\DealModel;
use core\dao\BankModel;
use core\dao\UserBankcardModel;
use core\dao\DeliveryRegionModel;
use core\dao\UserBankcardAuditModel;
use core\dao\PaymentNoticeModel;
use core\dao\UserModel;
use core\dao\DealLoanRepayModel;
use core\dao\CouponLogModel;
use core\data\UserData;
use libs\utils\PaymentApi;
use core\service\UserLoanRepayStatisticsService;
use core\service\UserService;
use core\service\FundService;
use core\service\GoldService;
use core\service\SupervisionAccountService;
use core\service\SupervisionService;
use NCFGroup\Protos\Gold\RequestCommon;
use core\service\life\PaymentUserService;
use core\service\ncfph\AccountService as PhAccountService;
use core\service\BwlistService;

use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

/**
 * Class AccountService
 * @package core\service
 */
class AccountService extends BaseService {
    /**
     * 获取用户待还本金
     * @param $user_id
     * @return array
     */
    public function getUserPendingPrincipal($user_id) {
        $dlr_model = new DealLoanRepayModel();
        $deal_model = new DealModel();
        $principal = $deal_model->floorfix($dlr_model->getSumByUserId($user_id, array(1, 8), 0));
        return $principal;
    }

    /**
     * 用户资产信息
     * @param $user_id
     */
    public function getUserSummary($user_id,$is_add_duotou = true) {
        $remain_principal = 0; // 待收本金(含通知贷)
        $remain_interest = 0; // 待还利息(不含通知贷)
        $total_interest = 0; // 总收益 = 已赚利息+预期罚息+提前还款违约金

        $userAsset = UserLoanRepayStatisticsService::getUserAssets($user_id,true);
        $remain_principal = $userAsset['norepay_principal'];
        $remain_interest = $userAsset['norepay_interest'];
        $total_interest = \libs\utils\Finance::addition(array($userAsset['load_earnings'], $userAsset['load_tq_impose'], $userAsset['load_yq_impose']), 2);

        //贴息累计金额
        $extra = $GLOBALS['db']->get_slave()->getOne("SELECT sum(interest) as principal FROM ".DB_PREFIX."interest_extra_log  WHERE user_id={$user_id}");
        $deal_compound =  new \core\service\DealCompoundService();
        $compound =  $deal_compound->getUserCompoundMoney($user_id, get_gmtime());

        //$remain_principal = bcadd($remain_principal, $userAsset['dt_load_money'], 2);
        $total_interest = bcadd($total_interest, $userAsset['dt_repay_interest'], 2);// 总收益
        // 智多鑫待投本金
        $dt_remain = bcsub($userAsset['dt_norepay_principal'], $userAsset['dt_load_money'], 2);
        // 网信充值总金额
        $userService = new UserService();
        $userInfo = $userService->getUser($user_id);
        $dayChargeAmount = PaymentNoticeModel::instance()->sumUserOnlineChargeAmountToday($user_id);

        $ret = array(
            'corpus' => $remain_principal, // 待收本金
            'income' => bcadd($remain_interest, $compound['interest'], 2), // 待收利息
            'earning_all' => bcadd($total_interest, $extra, 2), // 累计收益 = 总收益 + 贴息金额
            'compound_interest' => $compound['interest'],
            'js_norepay_principal' => $userAsset['js_norepay_principal'], // 金锁的代收本金
            'js_norepay_earnings' => $userAsset['js_norepay_earnings'], // 金锁的代收收益
            'js_total_earnings' => $userAsset['js_total_earnings'], // 金锁的累计收益
            'p2p_principal' => $userAsset['norepay_principal'], // 仅p2p 本金

            'cg_principal' => 0, // 存管在投本金
            'cg_income' => 0, // 存管待收收益
            'cg_earnings' => 0, // 存管累计收益

            'dt_norepay_principal' => $userAsset['dt_norepay_principal'],
            'dt_load_money' => $userAsset['dt_load_money'],
            'dt_remain' => $dt_remain, // 智多鑫待投本金

            'dayChargeAmount' => $dayChargeAmount, //用户网信日充值总金额，单位元
        );
        return $ret;
    }

    public function getUserSummaryWX($user_id){
        $userInfo = UserModel::instance()->find($user_id);

        if(!$userInfo){
            throw new \Exception("user {$user_id} not exists!");
        }

        $cash = $userInfo['money']; // 现金余额
        $freeze = $userInfo['lock_money']; // 冻结金额

        $remain_principal = 0; // 待收本金(含通知贷)
        $remain_interest = 0; // 待还利息(不含通知贷)
        $total_interest = 0; // 总收益 = 已赚利息+预期罚息+提前还款违约金

        $userAsset = UserLoanRepayStatisticsService::getUserAssets($user_id,true);
        $remain_principal = $userAsset['norepay_principal'];
        $remain_interest = $userAsset['norepay_interest'];
        $total_interest = \libs\utils\Finance::addition(array($userAsset['load_earnings'], $userAsset['load_tq_impose'], $userAsset['load_yq_impose']), 2);

        //贴息累计金额
        $extra = $GLOBALS['db']->get_slave()->getOne("SELECT sum(interest) as principal FROM ".DB_PREFIX."interest_extra_log  WHERE user_id={$user_id}");
        $deal_compound =  new \core\service\DealCompoundService();
        //$compound =  $deal_compound->getUserCompoundMoney($user_id, get_gmtime());

        //$remain_principal = bcadd($remain_principal, $userAsset['dt_load_money'], 2);
        $total_interest = bcadd($total_interest, $userAsset['dt_repay_interest'], 2);// 总收益
        // 智多鑫待投本金
        $dt_remain = bcsub($userAsset['dt_norepay_principal'], $userAsset['dt_load_money'], 2);
        // 网信充值总金额
        $dayChargeAmount = PaymentNoticeModel::instance()->sumUserChargeAmountToday($user_id);

        $ret = array(
            'cash' => $cash, // 现金余额
            'freeze' => $freeze, // 冻结金额
            'corpus' => $remain_principal, // 待收本金
            'income' => $remain_interest, // 待收利息
            'earning_all' => bcadd($total_interest, $extra, 2), // 累计收益 = 总收益 + 贴息金额
            'compound_interest' => 0,
            'js_norepay_principal' => $userAsset['js_norepay_principal'], // 金锁的代收本金
            'js_norepay_earnings' => $userAsset['js_norepay_earnings'], // 金锁的代收收益
            'js_total_earnings' => $userAsset['js_total_earnings'], // 金锁的累计收益
            'p2p_principal' => $userAsset['norepay_principal'], // 仅p2p 本金

            'cg_principal' => 0, // 存管在投本金
            'cg_income' => 0, // 存管待收收益
            'cg_earnings' => 0, // 存管累计收益

            'dt_norepay_principal' => $userAsset['dt_norepay_principal'],
            'dt_load_money' => $userAsset['dt_load_money'],
            'dt_remain' => $dt_remain, // 智多鑫待投本金

            'dayChargeAmount' => $dayChargeAmount, //用户网信日充值总金额，单位元
        );
        return $ret;
    }

    public function getUserSummaryPH($user_id){
        $ret = (new PhAccountService())->getSummary($user_id);
        return $ret;
    }

    /**
     * 用户资产信息2.0 合规检查过后3端统一迁移到此方法
     * @param $user_id
     */
    public function getUserSummaryNew($user_id,$is_add_duotou = true) {
        $wxData = $this->getUserSummaryWX($user_id);
        $phData = $this->getUserSummaryPH($user_id);
        $ret = $this->mergeP2pData($wxData, $phData);
        return $ret;
    }


    private function mergeP2pData($wxData, $p2pData)
    {
        $fileds = [
            'corpus',
            'income',
            'earning_all',
            'compound_interest',
            'js_norepay_principal',
            'js_norepay_earnings',
            'js_total_earnings',
            'p2p_principal',
            'cg_principal',
            'cg_income',
            'cg_earnings',
            'dt_norepay_principal',
            'dt_load_money',
            'dt_remain'
        ];

        $data = array(
            'wx_cash' => $wxData['cash'],
            'wx_freeze' => $wxData['freeze'],
            'ph_cash' => $p2pData['cash'],
            'ph_freeze' => $p2pData['freeze'],
        );
        foreach ($fileds as $filed) {
            $data[$filed] = bcadd($wxData[$filed], $p2pData[$filed], 2);
        }

        return $data;
    }

    /**
     * 用户资产分别统计
     * @param $user_id
     */
    public function getUserSummaryExt($user_id) {
        $remain_principal = 0; // 待收本金(含通知贷)
        $remain_interest = 0; // 待还利息(不含通知贷)
        $total_interest = 0; // 总收益 = 已赚利息+预期罚息+提前还款违约金

        $userAsset = UserLoanRepayStatisticsService::getUserAssets($user_id);
        $remain_principal = $userAsset['norepay_principal'];
        $remain_interest = $userAsset['norepay_interest'];
        $total_interest = \libs\utils\Finance::addition(array($userAsset['load_earnings'], $userAsset['load_tq_impose'], $userAsset['load_yq_impose']), 2);

        //贴息累计金额
        $extra = $GLOBALS['db']->get_slave()->getOne("SELECT sum(interest) as principal FROM ".DB_PREFIX."interest_extra_log  WHERE user_id={$user_id}");
        $deal_compound =  new \core\service\DealCompoundService();
        $compound =  $deal_compound->getUserCompoundMoney($user_id, get_gmtime());
        //p2p资产统计,包括大金所的
        $p2p_user_asset = array(
                'corpus' => $remain_principal, // 待收本金
                'income' => bcadd($remain_interest, $compound['interest'], 2), // 待收利息
                'earning_all' => bcadd($total_interest, $extra, 2), // 累计收益 = 总收益 + 贴息金额
                'compound_interest' => $compound['interest'],
                'p2p_principal' => $userAsset['norepay_principal'], // 仅p2p 本金
        );

        //dajinsuo资产统计
        $js_user_asset = array(
                'norepay_principal' => $userAsset['js_norepay_principal'], // 金锁的代收本金
                'norepay_earnings' => $userAsset['js_norepay_earnings'], // 金锁的代收收益
                'total_earnings' => $userAsset['js_total_earnings'], // 金锁的累计收益
        );

        //$remain_principal = bcadd($remain_principal,$userAsset['dt_norepay_principal'],2);
        $total_interest = bcadd($total_interest, $userAsset['dt_repay_interest'], 2);
        $dt_remain = bcsub($userAsset['dt_norepay_principal'], $userAsset['dt_load_money'], 2);
        $duotou_user_asset = array(
            'dt_norepay_principal' => $userAsset['dt_norepay_principal'],
            'dt_repay_interest' => $userAsset['dt_repay_interest'],
            'dt_remain' => $dt_remain,
        );

        $user_asset = array(
                'corpus' => $remain_principal, // 待收本金
                'income' => bcadd($remain_interest, $compound['interest'], 2), // 待收利息
                'earning_all' => bcadd($total_interest, $extra, 2), // 累计收益 = 总收益 + 贴息金额
        );

        //存管的资产
        $sv_user_asset = array(
                'norepay_principal' => 0, // 金锁的代收本金
                'norepay_earnings' => 0, // 金锁的代收收益
                'total_earnings' => 0, // 金锁的累计收益
        );

        $ret = array(
                'p2p_user_asset' => $p2p_user_asset,
                'duotou_user_asset' => $duotou_user_asset,
                'js_user_asset' => $js_user_asset,
                'user_asset' => $user_asset,
                'sv_user_asset' => $sv_user_asset,
        );
        return $ret;
    }

    /**
     * 获取用户的代换本金、待收收益、累计收益
     * @param int $user_id
     * @param bool $need_info
     * @return array
     */
//    public function getUserSummary($user_id, $need_info=false) {
//        $dlr_model = new DealLoanRepayModel();
//        $result = $dlr_model->getUserSummary($user_id);
//
//        $user_data = new UserData();
//        $result_cache = $user_data->getUserSummary($user_id);
//        if (!$result_cache) {
//            $user_data->setUserSummary($user_id, $result);
//        } else {
//            if ($result != $result_cache) {
//                \libs\utils\Alarm::push('UserSummary', '资产总额缓存与实际值不符', "userId:{$user_id} 资产总额缓存内容" . json_encode($result_cache));
//                \libs\utils\Logger::warn(implode(" | ", array(__CLASS__, __FUNCTION__, $user_id, 'user summary cache error')));
//            }
//        }
//
//        $remain_principal = 0;
//        $remain_interest = 0;
//        $total_interest = 0;
//
//        $data['load_repay_money'] = 0;
//        $data['load_earnings'] = 0;
//        $data['load_tq_impose'] = 0;
//        $data['load_yq_impose'] = 0;
//        $data['norepay_principal'] = 0;
//        $data['norepay_interest'] = 0;
//
//        foreach ($result as $v) {
//            if (in_array($v['type'], array(1, 8)) && ($v['status'] == 0)) {
//                $remain_principal = bcadd($remain_principal, $v['m'], 2);
//            } elseif (in_array($v['type'], array(2, 4, 5, 7, 9))) {
//                if ($v['status'] == 0) {
//                    $remain_interest = bcadd($remain_interest, $v['m'], 2);
//                } elseif ($v['status'] == 1) {
//                    $total_interest = bcadd($total_interest, $v['m'], 2);
//                }
//            }
//
//            /** 收集用户资产数据 */
//            if (in_array($v['type'], array(1,2,8,9)) && ($v['status'] == 1)) {
//                $data['load_repay_money'] = bcadd($data['load_repay_money'], $v['m'], 2);
//            }
//            if (in_array($v['type'], array(2,7,9)) && ($v['status'] == 1)) {
//                $data['load_earnings'] = bcadd($data['load_earnings'], $v['m'], 2);
//            }
//            if (in_array($v['type'], array(4)) && ($v['status'] == 1)) {
//                $data['load_tq_impose'] = bcadd($data['load_tq_impose'], $v['m'], 2);
//            }
//            if (in_array($v['type'], array(5)) && ($v['status'] == 1)) {
//                $data['load_yq_impose'] = bcadd($data['load_yq_impose'], $v['m'], 2);
//            }
//            if (in_array($v['type'], array(1,8)) && ($v['status'] == 0)) {
//                $data['norepay_principal'] = bcadd($data['norepay_principal'], $v['m'], 2);
//            }
//            if (in_array($v['type'], array(2,9)) && ($v['status'] == 0)) {
//                $data['norepay_interest'] = bcadd($data['norepay_interest'], $v['m'], 2);
//            }
//        }
//
//        // 同步用户资产数据
//        UserLoanRepayStatisticsService::syncUserAssets($user_id,$data);
//
//        //贴息累计金额 ，add by wangzhen3
//        $extra = $GLOBALS['db']->get_slave()->getOne("SELECT sum(interest) as principal FROM ".DB_PREFIX."interest_extra_log  WHERE user_id={$user_id}");
//
//        $deal_compound =  new \core\service\DealCompoundService();
//        $compound =  $deal_compound->getUserCompoundMoney($user_id, get_gmtime());
//
//        $ret = array(
//            'corpus' => $remain_principal,
//            'income' => bcadd($remain_interest, $compound['interest'], 2),
//            'earning_all' => bcadd($total_interest, $extra, 2),
//        );
//
//        if ($need_info == true) {
//            $ret['info'] = $result;
//            $ret['compound'] = $compound;
//            $ret['extra'] = $extra;
//        }
//
//        return $ret;
//    }

    /**
     * 新的资产总额计算方法 适用于App端的个人资产页面
     * @param $user_id
     * @return array
     */
//    public function getUserSummaryForApp($user_id) {
//        $remain_principal = 0;
//        $remain_interest = 0;
//        $total_interest = 0;
//
//        $userAsset = UserLoanRepayStatisticsService::getUserAssets($user_id);
//        $remain_principal = $userAsset['norepay_principal'];
//        $remain_interest = $userAsset['norepay_interest'];
//        $total_interest = bcadd(bcadd($userAsset['load_earnings'],$userAsset['load_tq_impose'],2),$userAsset['load_yq_impose'],2);
//
//        //贴息累计金额
//        $extra = $GLOBALS['db']->get_slave()->getOne("SELECT sum(interest) as principal FROM ".DB_PREFIX."interest_extra_log  WHERE user_id={$user_id}");
//        $deal_compound =  new \core\service\DealCompoundService();
//        $compound =  $deal_compound->getUserCompoundMoney($user_id, get_gmtime());
//
//        $ret = array(
//            'corpus' => $remain_principal,
//            'income' => bcadd($remain_interest, $compound['interest'], 2),
//            'earning_all' => bcadd($total_interest, $extra, 2),
//        );
//        return $ret;
//    }

    /**
     * 获取用户的待还本金息
     * @param $user_id
     * @return array
     */
    public function getUserPendingAmount($user_id) {
        $deal_model = new DealModel();
        $dlr_model = new DealLoanRepayModel();

        if(app_conf('USER_ASSET_NEW') == '1') {
            $userAsset = UserLoanRepayStatisticsService::getUserAssets($user_id);
            $principal = $deal_model->floorfix($userAsset['norepay_principal']);
            $interest = $deal_model->floorfix($userAsset['norepay_interest']);
        }else{
            $principal = $deal_model->floorfix($dlr_model->getSumByUserId($user_id, array(1, 8), 0));
            $interest = $deal_model->floorfix($dlr_model->getSumByUserId($user_id, array(2, 9), 0));
        }

        $deal_compound =  new \core\service\DealCompoundService();
        $compound =  $deal_compound->getUserCompoundMoney($user_id, get_gmtime());
        $interest = bcadd($interest, $compound['interest'] ,2);

        // 代收本金增加智多鑫投资金额
        $principal = bcadd($principal, $userAsset['dt_norepay_principal'], 2);

        $result = array(
            'principal' => $principal,
            'interest' => $interest,
        );

        return $result;
    }

    /**
     * 获取用户统计信息(user_statics需要重写) @todo
     *
     * @param $user_id
     * @return array
     */
    public function getUserStaicsInfo($user_id){

        $return = array();
        $user_id = intval($user_id);

        if($user_id > 0){
            $return = user_statics($user_id);
        }

        return $return;
    }

    /**
     * 获取用户银行卡数据
     *
     * @param $user_id
     * @return array
     */
    public function getUserBankInfo($user_id){

        $bankcard = array();
        $user_id = intval($user_id);

        if($user_id > 0){
            //获取用户银行卡信息(必须走主库)
            $bankcard = UserBankcardModel::instance()->getCardByUser($user_id, '*', false);
            $bankcard = !empty($bankcard) ? $bankcard->getRow() : [];
            if(isset($bankcard['bank_id'])){
                $bank_info = BankModel::instance()->find($bankcard['bank_id'], '*', true);
                $bankcard['name'] = $bank_info['name'];
                $address = '';

                //地区1级
                if($bankcard['region_lv1']){
                    $region_lv1 = DeliveryRegionModel::instance()->find(intval($bankcard['region_lv1']), '*', true);
                    $address .= $region_lv1 ? $region_lv1['name'].'&nbsp;&nbsp;' : '';
                }
                //地区2级
                if($bankcard['region_lv2']){
                    $region_lv2 = DeliveryRegionModel::instance()->find(intval($bankcard['region_lv2']), '*', true);
                    $address .= $region_lv2 ? $region_lv2['name'].'&nbsp;&nbsp;' : '';
                }
                //地区3级
                if($bankcard['region_lv3']){
                    $region_lv3 = DeliveryRegionModel::instance()->find(intval($bankcard['region_lv3']), '*', true);
                    $address .= $region_lv3 ? $region_lv3['name'] : '';
                }
                $bankcard['city'] = $address;
                $bankcard['bankzone'] = str_replace(array('<','>'),array('&lt;','&gt;'),$bankcard['bankzone']);
                // 用户银行卡是否已验证
                $bankcard['is_valid'] = !empty($bankcard['verify_status']) ? true : false;
            }

            //判断是否提交过审核银行卡信息(必须走主库)
            $audit_info = UserBankcardAuditModel::instance()->getlatestCardAuditByUser($user_id, false);

            $bankcard['newbankcard'] = $audit_info['bankcard'];
            $bankcard['is_audit'] = 0;
            if($audit_info){
                $bankcard['is_audit'] = $audit_info['status'];
                $bankcard['audit_create_time'] = $audit_info['create_time'];
                $bankcard['audit_time'] = $audit_info['audit_time'];
            }

            // 查询用户是否已经绑定消费卡
            $bankcard['bind_consume'] = 0;
            // 去掉查询网信生活消费卡的逻辑
//             $paymentUserObj = new PaymentUserService();
//             $userConsumeCardData = $paymentUserObj->getMyConsumeCardCount($user_id);
//             if ($userConsumeCardData['errorCode'] === 0 && (int)$userConsumeCardData['data']['cardNumber'] > 0) {
//                 $bankcard['bind_consume'] = 1;
//             }
        }

        return $bankcard;
    }

    /**
     * 账户总览 -- 用户投资概况
     *
     * @param $user_id
     * @return array
     */
    public function getInvestOverview($user_id){

        $data = array();
        $user_id = intval($user_id);

        if($user_id > 0){

            $deal_model = new DealModel();

            //回款中
            $returning = $deal_model->getInvestOverview($user_id, '4');
            $returning['text'] = '回款中';
            $data[0] = $returning;

            //投标中
            $biding = $deal_model->getInvestOverview($user_id, array(1,2));
            $biding['text'] = '投标中';
            $data[1] = $biding;

            //已回款
            $returned = $deal_model->getInvestOverview($user_id, '5');
            $returned['text'] = '已回款';
            $data[2] = $returned;

            //总额
            $all['text'] = '总计';
            $all['counts'] = $returning['counts'] + $biding['counts'] + $returned['counts'];
            $all['money'] = $returning['money'] + $biding['money'] + $returned['money'];
            $data[3] = $all;
        }

        return $data;
    }

    /**
     * 账户总览 -- 回款计划
     *
     * @param $user_id
     * @return array
     */
    public function getDealRepayOverview($user_id){

        $data = array();
        $user_id = intval($user_id);

        if($user_id > 0){

            $deal_model = new DealModel();

            //本月
            $this_month_begin = mktime(-8,0,0,date('m'),1,date('Y'));
            $this_month_end = mktime(15,59,59,date('m'),date('t'),date('Y'));
            $this_month = DealModel::instance()->getDealRepayOverviewByTime($user_id, $this_month_begin, $this_month_end);
            $this_month['text'] = '本月';
            $data[0] = $this_month;

            //下月
            $next_month_begin = mktime(-8,0,0,date('m')+1,1,date('Y'));
            $next_month_end = mktime(15,59,59,date('m')+1,date('t'),date('Y'));
            $next_month = DealModel::instance()->getDealRepayOverviewByTime($user_id, $next_month_begin, $next_month_end);
            $next_month['text'] = '下月';
            $data[1] = $next_month;

            //本年
            $this_year_begin = mktime(-8,0,0,1,1,date('Y'));
            $this_year_end = mktime(-8,0,-1,1,1,date('Y')+1);
            $this_year = DealModel::instance()->getDealRepayOverviewByTime($user_id, $this_year_begin, $this_year_end);
            $this_year['text'] = '本年';
            $data[2] = $this_year;

            //总计
            $all = DealModel::instance()->getDealRepayOverviewByTime($user_id);
            $all['text'] = '总计';
            $data[] = $all;
        }


		return $data;
	}

    /**
     * 判断用户是否是港澳台、军官证、护照用户
     */
    public function hasPassport($user_id) {
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return false;
        }
        $cnt = UserModel::instance()->countViaSlave('id = :user_id AND id_type > 1', array(':user_id' => $user_id));
        return $cnt >= 1 ?  true : false;
    }

    /**
      * 判断用户是否已经设置交易密码
      */
     public function usedQuickPay($userId) {
         $params = array(
             'source' => 1,
             'userId' => $userId,
         );
         $userInfo = array();
         // 支付降级
         if (PaymentApi::isServiceDown()) {
            return false;
         }
         $userInfo = PaymentApi::instance()->request('searchuserinfo', $params);
         if (isset($userInfo['isSetTransPWD']) && $userInfo['isSetTransPWD'] == '1') {
             return true;
         }
         return false;
     }

    /**
     * 判断用户总资产是否为零
     * @param integer $userId 用户id
     * @return true 不为零， false为零
     */
    public function isUserHasAssets($userId)
    {
        $userAccountSummary = $this->getUserSummaryNew($userId, true);
        $userService = new UserService();
        $userInfo = $userService->getUser($userId);
        $assets = bcadd($userInfo['money'], $userInfo['lock_money'], 2);
        $assets = bcadd($assets, $userAccountSummary['corpus'], 2);

        // 增加基金
        $fundObj = new FundService();
        $fundInfo = $fundObj->getFundTotalAmount($userId);
        if (!empty($fundInfo['totalAssets'])) {
            $assets = bcadd($assets, $fundInfo['totalAssets'], 2);
        }

        // 增加黄金
        $request = new RequestCommon();
        $request->setVars(array('userId'=>$userId, 'type' => 0));
        $goldService = new GoldService();
        $myGold = $goldService->myGold($request);
        if (!empty($myGold) && $myGold['errCode'] == 0) {
            $assets = bcadd($assets, $myGold['data']['hold_gold_market_value'], 2);
        }

        // 增加智多鑫
        $assets = bcadd($assets, $userAccountSummary['dt_load_money'], 2);

        $hasAssets = bccomp($assets, '0.00', 2) <= 0 ? false : true;
        return $hasAssets;
    }

    /**
     * 获取上传的图片信息
     */
    public function getImgId($fileInfo) {
        $res = array(
                'code' => 0,
                'msg' => '操作成功',
        );

        $prefix = $this->getImagePostFix($fileInfo['file']['tmp_name']);
        if(!empty($fileInfo) && in_array($prefix, array('jpg','jpeg','pjpeg','png'))) {
            $result = uploadFile($fileInfo);
            if(!empty($result['aid']) && $result['filename']) {
                $res['imageId'] = $result['aid'];
            }else{
                $res = array(
                        'code' => -1,
                        'msg' => '图片尺寸不能大于3M，请重新上传图片',
                );
            }
        }else{
            $res = array(
                    'code' => -2,
                    'msg' => '图片格式仅限JPG、PNG，请重新上传图片',
            );
        }
        return $res;
    }
    //通过二进制流 读取文件后缀信息
    private function getImagePostFix($filename) {
        $file     = fopen($filename, "rb");
        $bin      = fread($file, 2); //只读2字节
        fclose($file);
        $strinfo  = @unpack("c2chars", $bin);
        $typecode = intval($strinfo['chars1'].$strinfo['chars2']);
        $filetype = "";
        switch ($typecode) {
            case 7790: $filetype = 'exe';break;
            case 7784: $filetype = 'midi';break;
            case 8297: $filetype = 'rar';break;
            case 255216:$filetype = 'jpg';break;
            case 7173: $filetype = 'gif';break;
            case 6677: $filetype = 'bmp';break;
            case 13780:$filetype = 'png';break;
            default:   $filetype = 'unknown'.$typecode;
        }
        if ($strinfo['chars1']=='-1' && $strinfo['chars2']=='-40' ) {
            return 'jpg';
        }
        if ($strinfo['chars1']=='-119' && $strinfo['chars2']=='80' ) {
            return 'png';
        }
        return $filetype;
    }

    /**
     * 获取用户总资产
     *
     * 网信账户+代收本金+基金+黄金+存管
     */
    public function getUserTotalAssets($userId) {
        $userAccountSummary = $this->getUserSummaryNew($userId, true);
        $userService = new UserService();
        $userInfo = $userService->getUser($userId);
        $assets = bcadd($userInfo['money'], $userInfo['lock_money'], 2);
        $assets = bcadd($assets, $userAccountSummary['corpus'], 2);

        // 增加基金
        $fundObj = new FundService();
        $fundInfo = $fundObj->getFundTotalAmount($userId);
        if (!empty($fundInfo['totalAssets'])) {
            $assets = bcadd($assets, $fundInfo['totalAssets'], 2);
        }

        // 增加黄金
        $request = new RequestCommon();
        $request->setVars(array('userId'=>$userId, 'type' => 0));
        $goldService = new GoldService();
        $myGold = $goldService->myGold($request);
        if (!empty($myGold) && $myGold['errCode'] == 0) {
            $assets = bcadd($assets, $myGold['data']['hold_gold_market_value'], 2);
        }

        // 增加智多鑫
        $assets = bcadd($assets, $userAccountSummary['dt_load_money'], 2);

        //存管
        $supervisionService = new SupervisionService();
        $supervisionAccountService = new SupervisionAccountService();
        $isSupervisionUser = $supervisionAccountService->isSupervisionUser($userId);
        //存管开户
        if ($isSupervisionUser || $supervisionService->isUpgradeAccount($userId)) {
            $memberBalance = $supervisionAccountService->balanceSearch($userId);
            // 存管账户的可用余额+冻结金额
            $supervisionBalance = !empty($memberBalance['data']) ? bcadd($memberBalance['data']['availableBalance'], $memberBalance['data']['freezeBalance'], 2) : 0;
            $assets = bcadd($assets, bcdiv($supervisionBalance, 100, 2), 2);
        }

        return $assets;
    }

    /**
     * 获取用户账户所有信息， 聚合账户信息
     * @param integer $userId 用户 id
     */
    public function getAccountList($userId)
    {
        $accounts = [];
        $userInfo =(new UserService())->getUser($userId);
        // 超级账户信息,账户未迁移之前， 超级账户只有一种用户类型
        $accountNcfwx = [
            'accountId' => $userId,
            'amount' => bcmul($userInfo['money'], 100),
            'freezeAmount' => bcmul($userInfo['lock_money'], 100),
            'accountTypeDesc' => $this->getAccountTypeDescription(UserAccountEnum::PLATFORM_WANGXIN, $userInfo['user_purpose']),
            'accountType' => $userInfo['user_purpose'],
            'is_supervision' => $userInfo['payment_user_id'] == $userId,
        ];
        // 如果用户开通超级账户
        $accounts[UserAccountEnum::PLATFORM_WANGXIN] = [$accountNcfwx];

        // 存管账户信息
        $supervisionBalances = (new UserThirdBalanceService())->getAccountList($userId, UserAccountEnum::PLATFORM_SUPERVISION);
        foreach ($supervisionBalances as $supervisionAccount) {
            // 未开通存管
            $accounts[UserAccountEnum::PLATFORM_SUPERVISION][] = [
                'accountId' => $userId,
                'amount' => bcmul($supervisionAccount['money'], 100),
                'freezeAmount' => bcmul($supervisionAccount['lockMoney'], 100),
                'accountTypeDesc' => $this->getAccountTypeDescription(UserAccountEnum::PLATFORM_SUPERVISION, $supervisionAccount['accountType']),
                'accountType' => $supervisionAccount['accountType'],
                'is_supervision' => $userInfo['supervision_user_id'] == $userId,
            ];

        }

        return $accounts;
    }


    /**
     *   返回用户账户标准类型描述 false 为没有匹配的类型
     * @param integer $platform 平台业务类型
     * @param integer $accountType 账户数据库 记录类型
     * @return boolean|integer
     */
    public function getAccountTypeDescription($platform, $accountType)
    {
        switch($platform) {
            case UserAccountEnum::PLATFORM_WANGXIN:
                return isset(UserAccountEnum::$accountWangxinMap[$accountType]) ? UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_WANGXIN][$accountType] : ' 通用账户';
            case UserAccountEnum::PLATFORM_SUPERVISION:
                return isset(UserAccountEnum::$accountSupervisionMap[$accountType])? UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_SUPERVISION][$accountType] : ' 通用账户';
        }
        return '通用账户';
    }


    /**
     *  获取用户某个业务下的某个账户
     * @param integer $userId
     * @param integer $platform
     * @param integer $accountType
     * @return array | boolean
     */
    public function getAccountByPlatformAndAccountType($userId, $platform, $accountType)
    {
        $accountList = $this->getAccountList($userId);
        $platformAccount = isset($accountList[$platform]) ? $accountList[$platform] : array();
        foreach ($platformAccount as $account) {
            if ($account['accountType'] == $accountType) {
                return $account;
            }
        }
        return false;
    }


    /**
     *  返回用户指定业务下的指定账户存管余额， 如果返回 false 则以资产中心余额或者用户余额为准
     * @param integer $accountId 账户 id
     * @param integer $platform 平台业务类型
     * @return boolean | array
     */
    public function getSupervisionAccountInfo($accountId, $platform)
    {
        $accountInfo =[];
        switch ($platform) {
            case UserAccountEnum::PLATFORM_WANGXIN: return false;
            case UserAccountEnum::PLATFORM_SUPERVISION:
                $userBalance = (new SupervisionAccountService())->balanceSearch($accountId);
                if (isset($userBalance['status']) && $userBalance['status'] == SupervisionBaseService::RESPONSE_SUCCESS) {
                    $accountInfo['accountId'] = $accountId;
                    $accountInfo['amount'] = $userBalance['data']['availableBalance'];
                    $accountInfo['freezeAmount'] = $userBalance['data']['freezeBalance'];
                    return $accountInfo;
                }
        }
        return false;
    }

    /**
     * 获取用户日充值总金额
     * @param integer $userId 用户id
     * @param integer $platform 1普惠 2网信
     */
    public function getUserDayChargeAmount($userId, $platform = UserAccountEnum::PLATFORM_WANGXIN, $chargePlatform = []) {
        $result = ['total' => 0];
        if ($platform == UserAccountEnum::PLATFORM_WANGXIN) {
            return PaymentNoticeModel::instance()->groupUserChargeAmountToday($userId, $chargePlatform);
        }
        $result['total'] = (new PhAccountService)->getDayChargeAmount($userId);
        return $result;
    }

    /**
     * 是否是大陆实名认证用户
     * @param mix $user  用户id或者用户信息
     * @return bool
     */
    public function isMainlandRealAuthUser($user) {
        if (is_numeric($user)) {
            $userInfo = UserModel::instance()->find($user);
        } else if (is_object($user) || is_array($user)) {
            $userInfo = $user;
        } else {
            return false;
        }
        if ($userInfo['id_type'] == 1 && $userInfo['idcardpassed'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 是否在多卡白名单里
     * @param mix $user  用户id或者用户信息
     * @return bool
     */
    public function inMultiCardWhite($user) {
        if (is_numeric($user)) {
            $userInfo = UserModel::instance()->find($user);
        } else if (is_object($user) || is_array($user)) {
            $userInfo = $user;
        } else {
            return false;
        }

        if (BwlistService::inList('USER_MULTI_CARD_WHITE', $userInfo['id'])
            || BwlistService::inList('USER_GROUP_MULTI_CARD_WHITE', $userInfo['group_id'])) {
            return true;
        }
        return false;
    }

}

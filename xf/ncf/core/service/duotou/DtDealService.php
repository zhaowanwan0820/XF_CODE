<?php
namespace core\service\duotou;

use core\dao\repay\DealRepayModel;
use core\dao\deal\DealModel;
use core\dao\repay\DealPrepayModel;
use core\dao\repay\DealLoanRepayModel;
use core\service\deal\DealService;
use core\service\duotou\DtEntranceService;
use libs\utils\Logger;
use core\dao\user\UserLoanRepayStatisticsModel;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\enum\UserAccountEnum;
use core\enum\AccountEnum;
use core\enum\UserLoanRepayStatisticsEnum;
use core\service\duotou\DuotouService;
use NCFGroup\Common\Library\Idworker;

/**
 * 多投宝标的相关服务
 *
 * @author wangyiming@ucfgroup.com
 */
class DtDealService extends DuotouService
{
    const REPAY_TYPE_NORMAL = 1;
    const REPAY_TYPE_COMPOUND = 2;
    const REPAY_TYPE_PREPAY = 3;

    const TAG_DT = 'DEAL_DUOTOU';
    const TAG_DT_V3 = 'DEAL_DUOTOU_V3'; // 属于三期清盘的标的，变更回款计划，债权人为指定账户

    /**
     * 获取首页展示的多投宝标的
     * @return array
     */
    public function getIndexDeal($userId=0)
    {
        $response = self::callByObject(array('\NCFGroup\Duotou\Services\Project', "getProjectEffect", array()));
        if ($response['errCode']) {
            Logger::error(implode(" | ", array(__CLASS__,__FUNCTION__,"fail duotou rpc 调用失败")));
            return $response;
        }
        if ($response['data']) {
            $dealModel = new DealModel();
            $response['data']['rateYear'] = $dealModel->floorfix($response['data']['rateYear'], 2);
            $response['data']['minLoanMoney'] = empty($response['data']['minLoanMoney']) ? '0.00' : $dealModel->floorfix($response['data']['minLoanMoney'], 2);
        }
        $project =  $response['data'] ;
        if ($response['data'] && !empty($response['data'])) {
            $bIsEnterprise = false;
            if($userId){
                $bIsEnterprise = UserService::isEnterprise($userId);
            }
            if ($bIsEnterprise) {
                //企业处理
                $project['min_loan_money'] = number_format($project['singleEnterpriseMinLoanMoney'], 0, ",", "");
                $project['max_loan_money'] = number_format($project['singleEnterpriseMaxLoanMoney'], 0, ",", "");
                $project['day_redemption'] = number_format($project['enterpriseMaxDayRedemption'], 0, ",", "");
                $project['single'] = $project['enterpriseLoanCount'];
            } else {
                //个人处理
                $project['min_loan_money'] = number_format($project['singleMinLoanMoney'], 0, ",", "");
                $project['max_loan_money'] = number_format($project['singleMaxLoanMoney'], 0, ",", "");
                $project['day_redemption'] = number_format($project['maxDayRedemption'], 0, ",", "");
                $project['single'] = $project['loanCount'];
            }
            $response['data'] = $project;
        }

        return $response;
    }

    /**
     * 获取多投活动页标的信息
     * @param $siteId
     * @return bool
     */
    public function getActivityIndexDeals($siteId)
    {
        $dtEntranceService = new DtEntranceService();
        $list = $dtEntranceService->getEntranceList($siteId);
        return $list;
    }
    /**
     * 获取多投活动页标的信息,同时返回分活动投资用户人数
     * @param $siteId
     * @return array
     */
    public function getActivityIndexDealsWithUserNum($siteId)
    {
        $response = self::callByObject(array('\NCFGroup\Duotou\Services\Project', "getProjectEffect", array()));
        if ((!$response) || ($response['errCode'] != 0) || (empty($response['data']))) {
            return array();
        }
        $project = $response['data'];
        $vars = array(
            'projectId' => $response['data']['id'],
        );
        $investUserNumsResponse = \SiteApp::init()->dataCache->call(new DuotouService() , 'call', array(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars)), 180);  
        //$investUserNumsResponse = self::callByObject(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request));
        $investUserNums = array();
        if ($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
            $investUserNums = $investUserNumsResponse['data'];
        }

        $dtEntranceService = new DtEntranceService();
        $activityList = $dtEntranceService->getEntranceList($siteId);
        foreach ($activityList as & $activity) {
            $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
            if ($activity['lock_day'] == 1) {
                $activity['invest_user_num'] += intval($investUserNums['0']);
            }
        }

        return $activityList;
    }

    /**
     * 多投宝标的还款逻辑
     * @param int $p2pDealId p2p标的id
     * @param int $dealRepayId p2p 还款id
     * @param int $repayUserId p2p 还款用户ID
     * @return bool
     */
    public function repayDeal($p2pDealId, $dealRepayId, $repay_type, $isLast, $repayUserId=0)
    {
        if ($repay_type == self::REPAY_TYPE_NORMAL) {
            $deal_repay = DealRepayModel::instance()->find($dealRepayId);
            $principal = $deal_repay['principal'];
            $interest = bcadd($deal_repay['interest'], $deal_repay['impose_money'], 2);
        } elseif ($repay_type == self::REPAY_TYPE_PREPAY) {
            $deal_repay = DealPrepayModel::instance()->find($dealRepayId);
            $principal = $deal_repay['remain_principal'];
            $interest = bcadd($deal_repay['prepay_interest'], $deal_repay['prepay_compensation'], 2);
        } else {
            throw new \Exception("compound deal cannot load for duotou");
        }

        if (bccomp($principal, '0.00', 2) <= 0) {//只有本金大于0的时候才发起多投还款。只还息不处理
            return true;
        }

        $deal_service = new DealService();
        $deal = $deal_service->getDeal($p2pDealId, true, false);
        if (!$deal) {
            throw new \Exception("标的信息不存在");
        }

        $request = array(
            'p2pDealId' => $p2pDealId,
            'dealRepayId' => $dealRepayId,
            'principal' => $principal,
            'interest' => $interest,
            'dealStatus' => $deal['deal_status'],
            'isLast' => $isLast,
            'repayUserId' => $repayUserId,
        );

        $response = self::callByObject(array('\NCFGroup\Duotou\Services\DealRepay', "repayDeal", $request));
        return ($response && $response['data']) ? true : false;
    }

    /**
     * 多投宝标的流标逻辑
     * @param int $p2pDealId
     * @return bool
     */
    public function failDeal($p2pDealId)
    {
        $deal_service = new DealService();
        $deal = $deal_service->getDeal($p2pDealId, true, false);
        if (!$deal) {
            throw new \Exception("标的信息不存在");
        }

        $request = array(
            'p2pDealId' => $p2pDealId,
        );

        $response = self::callByObject(array('\NCFGroup\Duotou\Services\DealFail', "failDeal", $request));
        return ($response && $response['data']) ? true : false;
    }
    /**
     * p2p标的放款通知智多鑫
     * @param int $p2pDealId
     * @return bool
     */
    public function p2pDealHasLoansNotify($p2pDealId)
    {
        $deal_service = new DealService();
        $deal = $deal_service->getDeal($p2pDealId, true, false);
        if (!$deal) {
            throw new \Exception("标的信息不存在");
        }

        //非多投标
        if($deal['isDtb'] == 0){
            return true;
        }

        $request = array(
            'p2pDealId' => $p2pDealId,
        );

        $response = self::callByObject(array('\NCFGroup\Duotou\Services\P2pDeal', "p2pDealHasLoansNotify", $request));
        return ($response && $response['data']) ? true : false;
    }

    /**
     * 智多鑫赎回到账记录资金记录
     * @param $orderId
     * @param $userId 受让人（接盘人）
     * @param $redeemUserId 出让人(赎回人)
     * @param $money
     * @param $p2pDealId
     * @throws \Exception
     */
    public function dealRedeemMoneyLog($orderId, $userId, $redeemUserId, $money, $p2pDealId)
    {
        $user = UserService::getUserById($userId);
        $userRedeem = UserService::getUserById($redeemUserId);

        if (!$user || !$userRedeem) {
            throw new \Exception("受让人或出让人用户不存在 userId:{$userId},redeemUserId:{$redeemUserId}");
        }

        $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
        if (!$userIdAccountId) {
            throw new \Exception("未开通出借户[".$user['id']."]");
        }

        //outOrderId 为债转合同表（loan_mapping_contract）token字段，dealId投资或者受让底层资产标ID
        $bizToken = array('dealId'=>$p2pDealId,'orderId'=>$orderId,'outOrderId'=>$orderId);
        $res1 = AccountService::changeMoney($userIdAccountId,$money,'智多鑫-匹配债权',"编号 {$p2pDealId}", AccountEnum::MONEY_TYPE_LOCK_REDUCE,false,true,0,$bizToken);
        // 受让方
        if (!$res1) {
            throw new \Exception("购买债权转让扣款失败: orderId:{$orderId},user_id:{$userId},money:{$money}");
        }
        $moneyInfo = array(
            UserLoanRepayStatisticsEnum::DT_LOAD_MONEY => $money,
        );
        $res2 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], $moneyInfo);
        if (!$res2) {
            throw new \Exception("多投资产同步失败 uid:".$user['id']." orderId:".$orderId);
        }

        // 转让方
        $userRedeemdAccountId  = AccountService::getUserAccountId($redeemUserId,UserAccountEnum::ACCOUNT_INVESTMENT);
        if (!$userRedeemdAccountId) {
            throw new \Exception("未开通出借户[".$redeemUserId."]");
        }
        $res3 = AccountService::changeMoney($userRedeemdAccountId,$money,'智多鑫-债权出让',"编号 {$p2pDealId}", AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken);
        if (!$res3) {
            throw new \Exception("债权出让转入失败: orderId:{$orderId},user_id:{$user['id']},money:{$money}");
        }

        // 转让方余额冻结
        $res4 = AccountService::changeMoney($userRedeemdAccountId,$money,'智多鑫-债权出让本金回款并冻结',"编号 {$p2pDealId}", AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken);
        if (!$res4) {
            throw new \Exception("回款本金冻结失败 user:{$userRedeem['id']},orderId:{$orderId},money:{$money}");
        }
        $moneyInfo = array(
            UserLoanRepayStatisticsEnum::DT_LOAD_MONEY => -$money,
        );
        $res5 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($userRedeem['id'], $moneyInfo);
        if (!$res5) {
            throw new \Exception("多投资产同步失败 uid:".$userRedeem['id']." orderId:".$orderId);
        }
        return true;
    }

    public function dealRepayMoneyLog($user, $money, $token, $p2pDealId, $repayUserId, $moneyType, $projectInfo=array())
    {
        //根据p2p标的 获取借款人
        $p2pDeal = DealModel::instance()->find($p2pDealId);
        if (empty($p2pDeal)) {
            throw new \Exception("p2p标的信息不存在");
        }
        if (!in_array($moneyType, array('I','P','ZDXGLF'))) {
            throw new \Exception("还款的资金类型不正确 正确：P,I,ZDXGLF");
        }

        //outOrderId 还款记录表（duotou_p2p_deal_repay_detail）主键id加相应的业务类型前缀，比如 ZDX_PRINCIPAL_1111，ZDX_INTEREST_1112，ZDX_FEE1113
        //dealId 还款对应的底层标id
        $bizToken = array('dealId'=>$p2pDealId,'orderId'=>$token,'outOrderId'=>$token);
        if ($moneyType == 'I') { // 结息
            $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!$userIdAccountId) {
                throw new \Exception("未开通出借户[".$user['id']."]");
            }
            $res = AccountService::changeMoney($userIdAccountId,$money,'付息',"编号{$p2pDealId} {$p2pDeal['name']}", AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken);
        } elseif ($moneyType == 'ZDXGLF') {
            //收取管理费
            $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_MANAGEMENT);
            if (!$userIdAccountId) {
                throw new \Exception("未开通管理户[".$user['id']."]");
            }
            $res = AccountService::changeMoney($userIdAccountId,$money,'智多鑫-顾问服务费',"编号 {$projectInfo['projectId']},{$projectInfo['projectName']},收取顾问服务费", AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken);
            //支付管理费
            $chargePayAccountId  = AccountService::getUserAccountId($repayUserId,UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!$chargePayAccountId) {
                throw new \Exception("未开通管理户[".$repayUserId."]");
            }
            $res = AccountService::changeMoney($chargePayAccountId,$money,'智多鑫-顾问服务费',"编号 {$projectInfo['projectId']},{$projectInfo['projectName']},支付顾问服务费", AccountEnum::MONEY_TYPE_REDUCE);
        } else { // 还本
            // 借款人的减钱不在这里做，这里做投资人加钱、冻结、变更资产、转账
            $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!$userIdAccountId) {
                throw new \Exception("未开通借款户[".$user['id']."]");
            }
            $res = AccountService::changeMoney($userIdAccountId,$money,'还本',"编号{$p2pDealId} {$p2pDeal['name']}", AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken);
            if (!$res) {
                throw new \Exception("回款本金失败 user:{$user['id']},token:{$token},money:{$money}");
            }

            // 投资人的余额冻结
            $res2 = AccountService::changeMoney($userIdAccountId,$money,'智多鑫-本金回款并冻结',"编号{$p2pDealId}", AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken);
            if (!$res2) {
                throw new \Exception("回款本金冻结失败 user:{$user['id']},token:{$token},money:{$money}");
            }
            // 资产变更
            $res3 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], array('dt_load_money' => -$money));
            if (!$res3) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id".$p2pDealId);
            }
        }
        return true;
    }

    public function repayTransfer($orderId, $p2pDealId, $manageUserId, $repayType)
    {
        $request = array(
            'p2pDealId' => $p2pDealId,
            'orderId' => $orderId,
            'manageUserId' => $manageUserId,
        );
        $dealService = new DealService();
        $repayUserId = $dealService->getRepayUserAccount($p2pDealId, $repayType);
        if (!$repayUserId) {
            throw new \Exception('未设置代偿,代垫机构或代充值机构!');
        }

        $response = self::callByObject(array('\NCFGroup\Duotou\Services\DealRepayDetail', "getRepayDetail", $request));
        if (!$response || $response['data'] === false) {
            throw new \Exception("智多新还款数据拉取异常");
        }
        try {
            $GLOBALS['db']->startTrans();
            $repayOrderList = $response['data']['list']['repayOrderList']; // 智多新还款数据
            $chargeOrderList = $response['data']['list']['chargeOrderlist']; // 智多新收费数据
            foreach ($repayOrderList as $k=>$v) {
                $user = UserService::getUserById($v['receiveUserId']);
                $money = bcdiv($v['amount'], 100, 2);
                $moneyType = $v['type'];
                $this->dealRepayMoneyLog($user, $money, $v['subOrderId'], $p2pDealId, $repayUserId, $moneyType);
                if(isset($chargeOrderList[$k])) {
                    $chargeInfo = $chargeOrderList[$k];
                    $chargeReceiveUser = UserService::getUserById($chargeInfo['receiveUserId']);
                    $money = bcdiv($chargeInfo['chargeAmount'], 100, 2);
                    $this->dealRepayMoneyLog($chargeReceiveUser,$money,$chargeInfo['chargeOrderId'],$p2pDealId,$v['receiveUserId'],'ZDXGLF',$response['data']['projectInfo']);
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " fail". $ex->getMessage());
            throw new \Exception($ex->getMessage());
            return false;
        }
        return true;
    }

    /*
     * 智多鑫三期还款需求，变更标的的回款计划，并更换标的tag
     * @param int $deal_id
     */
    public function clearDealV3($deal_id)
    {
        $deal_service = new DealService();
        if ($deal_service->isDealDT($deal_id) === false) { // 如果不是智多鑫标的，返回true
            return true;
        }

        $user_id = app_conf('DT_YDT');

        // 下面这段属于脏代码，所以不想写的太好，不封装一次性的代码
        $dlr = new DealLoanRepayModel();
        $dlr->db->startTrans();

        $list = $dlr->findAll("`deal_id`='{$deal_id}' AND `status`='0'");
        if (!$list) {
            return true;
        }

        $arr = array();
        foreach ($list as $value) {
            $repay_id = $value['deal_repay_id'];
            $type = $value['type'];

            if (!isset($arr[$repay_id][$type])) {
                $arr[$repay_id][$type] = array(
                    'deal_id' => $deal_id,
                    'deal_repay_id' => $repay_id,
                    'deal_loan_id' => 0,
                    'loan_user_id' => $user_id,
                    'borrow_user_id' => $value['borrow_user_id'],
                    'type' => $type,
                    'time' => $value['time'],
                    'real_time' => $value['real_time'],
                    'status' => 0,
                    'deal_type' => $value['deal_type'],
                    'create_time' => get_gmtime(),
                    'update_time' => get_gmtime(),
                );
            }
            $arr[$repay_id][$type]['money'] += $value['money'];
        }

        try {
            $r = $dlr->db->query("UPDATE " . $dlr->tableName() . " SET `status`='2' WHERE `deal_id` = '{$deal_id}' AND `status`='0'");
            if ($r === false) {
                throw new \Exception('update loan repay fail');
            }

            foreach ($arr as $key => $val) {
                $deal_repay_id = $key;
                foreach ($val as $k => $v) {
                    $type = $k;
                    $dlr->setRow($v);
                    $r = $dlr->insert();
                    if ($r === false) {
                        throw new \Exception("insert new row fail");
                    }
                }
            }

            $tag_service = new \core\service\deal\DealTagService();
            $r = $tag_service->updateTag($deal_id, self::TAG_DT_V3);
            if ($r === false) {
                throw new \Exception("update tag fail");
            }

            $r = $dlr->db->commit();
            if ($r === false) {
                throw new \Exception("commit fail");
            }
        } catch (\Exception $e) {
            $dlr->db->rollback();
            return false;
        }

        return true;
    }
}

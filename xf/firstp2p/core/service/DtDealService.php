<?php
namespace core\service;

use core\dao\DealRepayModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealPrepayModel;
use core\dao\DealLoanRepayModel;
use core\service\DealService;
use core\service\DtEntranceService;
use \NCFGroup\Protos\Duotou\RequestCommon;
use libs\utils\Logger;
use libs\utils\Rpc;
use core\dao\UserLoanRepayStatisticsModel;

/**
 * 多投宝标的相关服务
 *
 * @author wangyiming@ucfgroup.com
 */
class DtDealService {

    const REPAY_TYPE_NORMAL = 1;
    const REPAY_TYPE_COMPOUND = 2;
    const REPAY_TYPE_PREPAY = 3;

    const TAG_DT = 'DEAL_DUOTOU';
    const TAG_DT_V3 = 'DEAL_DUOTOU_V3'; // 属于三期清盘的标的，变更回款计划，债权人为指定账户

    /**
     * 获取首页展示的多投宝标的
     * @return array
     */
    public function getIndexDeal() {
        $request = new RequestCommon();
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', "getProjectEffect", $request);
        if(!$response) {
            Logger::error(implode(" | ",array(__CLASS__,__FUNCTION__,"fail duotou rpc 调用失败")));
            return $response;
        }
        $user_id = $GLOBALS['user_info'] ? $GLOBALS['user_info']['id'] : 0;
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $userService = new UserService($user_id);
        $vars = array(
            'user_id' => $user_id,
            'isEnterprise' => $userService->isEnterpriseUser(),
        );
        if($response['data']) {
            $dealModel = new \core\dao\DealModel();
            $response['data']['rateYear'] = $dealModel->floorfix($response['data']['rateYear'],2);
            $response['data']['minLoanMoney'] = empty($response['data']['minLoanMoney']) ? '0.00' : $dealModel->floorfix($response['data']['minLoanMoney'],2);
        }
        $project =  $response['data'] ;
        if ($response['data'] && !empty($response['data'])) {

            if ($vars['isEnterprise'] == true) {
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
    public function getActivityIndexDeals($siteId) {
        $dtEntranceService = new DtEntranceService();
        $list = $dtEntranceService->getEntranceList($siteId);
        return $list;
    }
    /**
     * 获取多投活动页标的信息,同时返回分活动投资用户人数
     * @param $siteId
     * @return array
     */
    public function getActivityIndexDealsWithUserNum($siteId) {
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', "getProjectEffect", $request);
        if((!$response) || ($response['errCode'] != 0) || (empty($response['data']))) {
            return array();
        }
        $project = $response['data'];
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'projectId' => $response['data']['id'],
        );
        $request->setVars($vars);
        $rpc = new \libs\utils\Rpc('duotouRpc');
        //$investUserNumsResponse = $rpc->go('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request);
        $investUserNumsResponse = \SiteApp::init()->dataCache->call($rpc, 'go', array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request), 180);
        $investUserNums = array();
        if($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
            $investUserNums = $investUserNumsResponse['data'];
        }

        $dtEntranceService = new DtEntranceService();
        $activityList = $dtEntranceService->getEntranceList($siteId);
        foreach ($activityList as & $activity) {
            $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
            if($activity['lock_day'] == 1) {
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
    public function repayDeal($p2pDealId, $dealRepayId, $repay_type,$isLast,$repayUserId=0) {
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
        if(!$deal) {
            throw new \Exception("标的信息不存在");
        }
        $request = new RequestCommon();
        $vars = array(
            'p2pDealId' => $p2pDealId,
            'dealRepayId' => $dealRepayId,
            'principal' => $principal,
            'interest' => $interest,
            'dealStatus' => $deal['deal_status'],
            'isLast' => $isLast,
            'repayUserId' => $repayUserId,
        );
        $request->setVars($vars);

        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\DealRepay', "repayDeal", $request);
        return ($response && $response['data']) ? true : false;
    }

    /**
     * 多投宝标的流标逻辑
     * @param int $p2pDealId
     * @return bool
     */
    public function failDeal($p2pDealId) {
        $deal_service = new DealService();
        $deal = $deal_service->getDeal($p2pDealId, true, false);
        if(!$deal) {
            throw new \Exception("标的信息不存在");
        }

        $request = new RequestCommon();
        $vars = array(
            'p2pDealId' => $p2pDealId,
        );
        $request->setVars($vars);

        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\DealFail', "failDeal", $request);
        return ($response && $response['data']) ? true : false;
    }
    /**
     * p2p标的放款通知智多新
     * @param int $p2pDealId
     * @return bool
     */
    public function p2pDealHasLoansNotify($p2pDealId) {
        $deal_service = new DealService();
        $deal = $deal_service->getDeal($p2pDealId, true, false);
        if(!$deal) {
            throw new \Exception("标的信息不存在");
        }

        $request = new RequestCommon();
        $vars = array(
            'p2pDealId' => $p2pDealId,
        );
        $request->setVars($vars);

        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\P2pDeal', "p2pDealHasLoansNotify", $request);
        return ($response && $response['data']) ? true : false;
    }

    /**
     * 智多新赎回到账记录资金记录
     * @param $orderId
     * @param $userId 受让人（接盘人）
     * @param $redeemUserId 出让人(赎回人)
     * @param $money
     * @param $p2pDealId
     * @throws \Exception
     */
    public function dealRedeemMoneyLog($orderId,$userId,$redeemUserId,$money,$p2pDealId){
        $user = UserModel::instance()->find($userId);
        $userRedeem = UserModel::instance()->find($redeemUserId);

        if(!$user || !$userRedeem){
            throw new \Exception("受让人或出让人用户不存在 userId:{$userId},redeemUserId:{$redeemUserId}");
        }
        $bizToken = array('dealId'=>$p2pDealId);
        // 受让方
        $user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
        $res1 = $user->changeMoney($money, '智多新-匹配债权', "编号 {$p2pDealId}",0, 0,UserModel::TYPE_DEDUCT_LOCK_MONEY, 0, $bizToken);
        if(!$res1) {
            throw new \Exception("购买债权转让扣款失败: orderId:{$orderId},user_id:{$userId},money:{$money}");
        }
        $moneyInfo = array(
            UserLoanRepayStatisticsService::DT_LOAD_MONEY => $money,
        );
        $res2 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], $moneyInfo);
        if(!$res2) {
            throw new \Exception("多投资产同步失败 uid:".$user['id']." orderId:".$orderId);
        }

        // 转让方
        $userRedeem->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
        $res3 = $userRedeem->changeMoney($money, "智多新-债权出让", "编号 {$p2pDealId}", 0, 0, UserModel::TYPE_MONEY, 0, $bizToken);
        if(!$res3) {
            throw new \Exception("债权出让转入失败: orderId:{$orderId},user_id:{$user['id']},money:{$money}");
        }

        // 转让方余额冻结
        $userRedeem->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
        $res4 = $userRedeem->changeMoney($money, "智多新-债权出让本金回款并冻结", "编号 {$p2pDealId}", 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
        if(!$res4) {
            throw new \Exception("回款本金冻结失败 user:{$userRedeem['id']},orderId:{$orderId},money:{$money}");
        }
        $moneyInfo = array(
            UserLoanRepayStatisticsService::DT_LOAD_MONEY => -$money,
        );
        $res5 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($userRedeem['id'], $moneyInfo);
        if(!$res5) {
            throw new \Exception("多投资产同步失败 uid:".$userRedeem['id']." orderId:".$orderId);
        }
        return true;
    }

    public function dealRepayMoneyLog($user,$money,$token,$p2pDealId,$repayUserId,$moneyType){
        //根据p2p标的 获取借款人
        $p2pDeal = DealModel::instance()->find($p2pDealId);
        if(empty($p2pDeal)){
            throw new \Exception("p2p标的信息不存在");
        }
        if(!in_array($moneyType,array('I','P','ZDXGLF'))){
            throw new \Exception("还款的资金类型不正确 正确：P,I,ZDXGLF");
        }
        $bizToken = array('dealId'=>$p2pDealId);
        $user->changeMoneyDealType =  DealModel::DEAL_TYPE_SUPERVISION;

        if($moneyType == 'I'){ // 结息
            $res = $user->changeMoney($money, '付息', "编号{$p2pDealId} {$p2pDeal['name']}", 0, 0, 0, 0, $bizToken);
        }elseif($moneyType == 'ZDXGLF'){
            $res = $user->changeMoney($money, '智多新-顾问服务费', "编号{$p2pDealId} {$p2pDeal['name']}", 0, 0, 0, 0, $bizToken);
        }else{ // 还本
            // 借款人的减钱不在这里做，这里做投资人加钱、冻结、变更资产、转账
            $res = $user->changeMoney($money, '还本', "编号{$p2pDealId} {$p2pDeal['name']}", 0, 0, 0, 0, $bizToken);
            if (!$res) {
                throw new \Exception("回款本金失败 user:{$user['id']},token:{$token},money:{$money}");
            }

            // 投资人的余额冻结
            $res2 = $user->changeMoney($money, "智多新-本金回款并冻结", "编号{$p2pDealId}", 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            if(!$res2) {
                throw new \Exception("回款本金冻结失败 user:{$user['id']},token:{$token},money:{$money}");
            }
            // 资产变更
            $res3 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], array('dt_load_money' => -$money));
            if(!$res3) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id".$p2pDealId);
            }
        }
        return true;
    }

    public function repayTransfer($orderId,$p2pDealId,$manageUserId,$repayType){
        $vars = array(
            'p2pDealId' => $p2pDealId,
            'orderId' => $orderId,
            'manageUserId' => $manageUserId,
        );
        $dealService = new DealService();
        $repayUserId = $dealService->getRepayUserAccount($p2pDealId,$repayType);
        if(!$repayUserId){
            throw new \Exception('未设置代偿,代垫机构或代充值机构!');
        }
        $request = new RequestCommon();
        $rpc = new Rpc('duotouRpc');
        $request->setVars($vars);

        $response = $rpc->go('\NCFGroup\Duotou\Services\DealRepayDetail', "getRepayDetail", $request);
        if(!$response || $response['data'] === false){
            throw new \Exception("智多新还款数据拉取异常");
        }
        try{
            $GLOBALS['db']->startTrans();
            foreach($response['data']['list'] as $k=>$v){
                $user = UserModel::instance()->find($v['receiveUserId']);
                $money = bcdiv($v['amount'],100,2);
                $moneyType = $v['type'];
                $this->dealRepayMoneyLog($user,$money,$v['subOrderId'],$p2pDealId,$repayUserId,$moneyType);
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " fail". $ex->getMessage());
            return false;
        }
        return true;
    }

    /*
     * 智多新三期还款需求，变更标的的回款计划，并更换标的tag
     * @param int $deal_id
     */
    public function clearDealV3($deal_id) {
        $deal_service = new DealService();
        if ($deal_service->isDealDT($deal_id) === false) { // 如果不是智多新标的，返回true
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
                foreach($val as $k => $v) {
                    $type = $k;
                    $dlr->setRow($v);
                    $r = $dlr->insert();
                    if ($r === false) {
                        throw new \Exception("insert new row fail");
                    }
                }
            }

            $tag_service = new \core\service\DealTagService();
            $r = $tag_service->updateTag($deal_id, self::TAG_DT_V3);
            if ($r === false) {
                throw new \Exception("update tag fail");
            }

            $r = $dlr->db->commit();
            if ($r === false) {
                throw new \Exception("commit fail");
            }
        } catch(\Exception $e) {
            $dlr->db->rollback();
            return false;
        }

        return true;
    }


    /**
    * 根据id 获取智多鑫项目信息
    */
    function getProjectInfoById($dealId){
        $rpc = new Rpc('duotouRpc');
        $dealRequest = new RequestCommon();
        $dealRequest->setVars(array('project_id' => $dealId));
        $response = $rpc->go('NCFGroup\Duotou\Services\Project', 'getProjectInfoById', $dealRequest);
        if (!$response || $response['errCode']) {
            return array();
        }
        //智多鑫报备状态
        $response['data']['report_status'] = 1;
        return $response['data'];
    }
}

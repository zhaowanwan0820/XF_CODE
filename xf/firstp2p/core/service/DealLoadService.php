<?php
/**
 * DealLoadService.php
 *
 * @date 2014-03-20
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\DealLoadModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use core\dao\DealTransferLogModel;
use core\dao\EnterpriseModel;
use core\data\DealData;
use core\service\risk\RiskService;
use core\tmevent\bid\BankbidEvent;
use core\tmevent\bid\P2pbidEvent;
use core\tmevent\reserve\ProcEvent as ReserveProcEvent;
use libs\utils\Logger;
use libs\utils\DBC;
use libs\utils\Monitor;
use libs\utils\Rpc;
use core\service\DealTagService;
use core\dao\UserModel;
use core\dao\ThirdpartyOrderModel;
use core\service\DealService;
use core\service\DealGroupService;
use core\service\CouponService;
use core\service\UserService;
use core\service\DealLoanTypeService;
use core\service\DealProjectService;
use core\service\DealProjectRiskAssessmentService;
use core\service\AdunionDealService;
use core\service\UserCarryService;
use core\dao\JobsModel;
use core\dao\DealQueueModel;
use core\service\O2OService;
use core\service\UserTagService;
use core\service\UserProfileService;
use core\service\ReservationMatchService;
use libs\utils\Finance;
use libs\payment\supervision\Supervision;
use core\dao\DiscountModel;
use core\service\DiscountService;

use core\service\BonusService;
use core\service\P2pDealBidService;
use core\service\vip\VipService;
use core\service\candy\CandyActivityService;

use NCFGroup\Common\Library\Idworker;
use NCFGroup\Task\Services\TaskService AS GTaskService;


// for tianmai
use core\service\curlHook\ThirdPartyHookService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

// 红包币双写同步数据
use core\event\Bonus\ConsumeBonusEvent;


use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;

use core\dao\DealCustomUserModel;
use core\service\DealCustomUserService;
/**
 * 订单投标
 *
 * Class DealLoadService
 * @package core\service
 */
class DealLoadService extends BaseService {

    public static $fatal = 0;

    public static $bidTime = 0;

    //行为跟踪ID
    public $track_id = 0;
    //渠道标签
    public $euid = '';
    /**
     * 根据订单id获取投标列表
     *
     * @param $dealId
     * @return mixed
     */
    public function getDealLoanListByDealId($dealId) {
        return DealLoadModel::instance()->getDealLoanList($dealId);
    }

    public function getDealLoanListByDealIdPageable($dealId, $page = 1, $pageSize = 20)
    {
        return DealLoadModel::instance()->getDealLoanListPageable($dealId, $page, $pageSize);
    }

    /**
     * 根据用户id获取投资列表
     *
     * @param $user_id
     * @param $offset
     * @param $page_size
     * @param int $status
     * @param bool $date_start
     * @param bool $date_end
     * @return mixed
     */
    public function getUserLoadList($user_id, $offset=0, $page_size=10, $status = 0, $date_start = false, $date_end = false, $type = '', $exclude_loantype = 0, $deal_type_id = 0) {
        $result = DealLoadModel::instance()->getUserLoadList($user_id, $offset, $page_size, $status, $date_start, $date_end, $type, $exclude_loantype, $deal_type_id);

        $deal_service = new DealService();
        foreach ($result['list'] as $k => $deal) {
            $result['list'][$k]['isDealZX'] = $deal_service->isDealEx($deal['deal_type']);
        }

        return $result;
    }

    /**
     * 取得最后$num 条注册用户信息
     * @param $num
     */
    public function getLastLoadList($num){
        return DealLoadModel::instance()->getLastLoadList($num);
    }

    // 取得今日投资人数
    public function getLoadUsersNumByTime(){
        $startTime = empty($startTime) ? strtotime(date('Y-m-d')) : $startTime;
        return DealLoadModel::instance()->getLoadUsersNumByTime($startTime);
    }

    /**
     * 根据id获取投标信息
     *
     * @param $id
     * @return \libs\db\Model
     */
    public function getDealLoadDetail($id, $show_more = true, $slave = false) {
        $deal_load = DealLoadModel::instance()->find($id, '*', $slave);
        if (empty($deal_load)) {
            return false;
        }

        if($show_more === false){
            return $deal_load;
        }

        $deal_service = new DealService();
        $deal = $deal_service->getDeal($deal_load['deal_id'], true);
        $deal_load['deal'] = $deal;
        if ($deal['is_crowdfunding'] == 1) {
            $deal_load['income'] = 0;
        } else {
            $deal_load['income'] = DealModel::instance()->floorfix(\libs\utils\Finance::getExpectEarningByDealLoan($deal_load));
        }
        $deal_load['real_income'] = \app\models\dao\DealLoanRepay::instance()->getTotalIncomeMoney($id);
        $deal_load['total_income'] = $deal_load['money'] + $deal_load['income'];

        $deal_loan_type = DealLoanTypeModel::instance()->findViaSlave($deal['type_id']);
        $deal_load['deal_loan_type'] = $deal_loan_type;
        $deal_load['is_lease'] = $deal_loan_type['type_tag'] == DealLoanTypeModel::TYPE_ZCZR;

        $deal_load['isDealZX'] = $deal_service->isDealEx($deal['deal_type']);

        return $deal_load;
    }


    /**
     * 根据id获取投标信息,Open使用，简单的标的信息
     *
     * @param $id
     * @return \libs\db\Model
     */
    public function getDealLoadDetailForOpen($id, $slave = false) {
        $deal_load = DealLoadModel::instance()->find($id, '*', $slave);
        if (empty($deal_load)) {
            return false;
        }

        $deal_service = new DealService();
        $deal = $deal_service->getDeal($deal_load['deal_id'], true);
        $deal_load['deal'] = $deal;
        return $deal_load;
    }

    public function getDealLoadListByIds($arrIds) {
        if (empty($arrIds)) {
            return false;
        }

        $dealLoadList = DealLoadModel::instance()->getDealLoadByIds($arrIds);
        return empty($dealLoadList) ? false : $dealLoadList;
    }

    /**
     * 根据id数组获取投标信息,标的信息,Open使用
     *
     * @param idArray
     * @return \libs\db\Model
     */
    public function getDealLoadDetailByIDs($idArray = array()) {

        $dealLoads = DealLoadModel::instance()->getDealLoadByIds($idArray);
        if(empty($dealLoads)){
            return false;
        }
        //提取投资记录中的标的信息，查询标的信息
        $dealIds = array();
        foreach($dealLoads as $load){
            $dealIds[] = $load['deal_id'];
        }
        $dealIds = array_unique($dealIds);
        //查询标的信息
        $dealService = new DealService();
        $deal = $dealService->getDealInfoByIds($dealIds, array('id', 'name', 'repay_time', 'loantype'));
        //建立映射
        $dealsInfo = array();
        foreach($deal as $dealOne){
           $dealsInfo[$dealOne['id']] = $dealOne;
           unset($dealsInfo[$dealOne['id']]['id']);
        }
        foreach($dealLoads as &$loadOne){
            $loadOne['deal'] = $dealsInfo[$loadOne['deal_id']];
        }
        return $dealLoads;
    }

    /**
     * 已投资数目(包括流标等任意情况)
     *
     * @param $user_id int
     * @return integer
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function countByUserId($user_id, $is_slave = true)
    {
        return DealLoadModel::instance()->countByUserId($user_id, $is_slave);
    }
    /**
     * 已成功投资数目(流标、标被删除、无效均不算)
     *
     * @param $user_id int
     * @param $source_type array    来源 3:ios 4:android
     * @return integer
     */
    public function getCountByUserIdInSuccess($user_id, $source_type = array(),$source_type_allow=true) {
        return DealLoadModel::instance()->getCountByUserIdInSuccess($user_id, $source_type,$source_type_allow);
    }

    /**
     * 根据user_id获取投资人某段时间内累计投资总额  | 不知道这段是否应该加到 user_statics用户统计中去？ @todo
     * @param int $user_id int
     * @param bool $date_start string|false
     * @param bool $date_end string|false
     * @return float
     * @author zhanglei5@ucfgroup.com
     */
    public function getTotalLoanMoneyByUserId($user_id,$date_start=false, $date_end=false,$deal_status=array()) {
        return DealLoadModel::instance()->getTotalLoanMoneyByUserId($user_id,$date_start, $date_end,$deal_status);
    }

    /**
     * 获取用户投标的列表
     * @param $uid
     * @param $deal_id
     */
    public function getUserDealLoad($uid,$deal_id){
        $where = " deal_id = %d AND user_id = %d";
        $where = sprintf($where, $deal_id, $uid);
        return DealLoadModel::instance()->findByViaSlave($where);
    }

    /**
     * 获取交易次数
     * @param int $site_id
     */
    public function getDealTimesCache($site_id) {
        $deal_data = new DealData();
        $count = $deal_data->getDealTimesCache();
        if ($count === false) {
            $count = DealLoadModel::instance()->getDealTimes($site_id);
            $deal_data->setDealTimesCache($count);
        }
        return $count;
    }

    /**
     * dealSubsidyToUser 贴息给用户
     *
     * @param mixed $dealLoanId 投资记录id
     */
    public function dealSubsidyToUser($dealLoanId)
    {
        DBC::requireNotEmptyString($dealLoanId, 'dealLoadId不能为空');

        $GLOBALS['db']->startTrans();
        try {
            $dealLoan = DealLoadModel::instance()->find($dealLoanId);
            $dealLoan->subsidyToLoaner();
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::wLog(__FUNCTION__.print_r($e, true));
            return false;
        }
        return true;
    }

    /**
     * addDealTransferLog 添加dealtransferlog日志
     *
     * @param mixed $owner 此日志的所有者
     * @param mixed $fromUserId
     * @param mixed $toUserId
     * @param mixed $money 转了多少钱
     */
    public function addDealTransferLog($owner, $fromUserId, $toUserId, $money)
    {
        $dealTransferLog = DealTransferLogModel::instance()->findByOwner($owner);
        if (empty($dealTransferLog)) {
            $dealTransferLog = DealTransferLogModel::create($owner, $fromUserId, $toUserId, $money);
            $dealTransferLog->save();
        }
        return $dealTransferLog;
    }

    /**
     * 获取某个时间段，某个标的投资金额概况
     * @param unknown $deal_id
     * @param number $time_start
     * @param number $time_end
     * @return unknown
     */
    public function getLoadStatByDeal($deal_id, $time_start = 0, $time_end = 0){
        return DealLoadModel::instance()->getLoadStatByDeal($deal_id, $time_start, $time_end);
    }

    /**
     * 获取投资的通知贷状态
     * @param unknown $load_id
     * @return number
     */
    public function getDealLoadCompoundStatus($load_id){
        $load_id = intval($load_id);
        $deal_load = DealLoadModel::instance()->findViaSlave($load_id, '`deal_id`');
        $deal_load_compound_status = 0;//未投资
        if($deal_load){
            $deal_info = DealModel::instance()->findViaSlave($deal_load['deal_id'], '`deal_status`');
            if($deal_info['deal_status'] == 2){
                $deal_load_compound_status = 1;//已投
            }elseif(in_array($deal_info['deal_status'], array(4, 5))){
                $params[':deal_load_id'] = $load_id;
                $condition = "`deal_load_id` = ':deal_load_id'";
                $redemption = \core\dao\CompoundRedemptionApplyModel::instance()->findByViaSlave($condition, '`status`', $params);
                $deal_load_compound_status = 2;//待赎回
                if($redemption){
                    $deal_load_compound_status = 3;//还款中
                    if($redemption['status'] == 1){
                        $deal_load_compound_status = 4;//已还清
                    }
                }
            }
        }
        return $deal_load_compound_status;
    }

    public function errCatch($deal_id){
        $fatal = self::$fatal;
        if(!empty($deal_id) && !empty($fatal)){
            $deal_data = new DealData();
            $deal_data->leavePool($deal_id);
            $lastErr = error_get_last();
            Logger::info("bid err catch" ." lastErr: ". json_encode($lastErr) . " trace: ".json_encode(debug_backtrace()));
        }
    }

    /**
     * 根据用户id计算用户投资公益标的概览
     * @param int $user_id
     * @return array
     */
    public function getCrowdfundingByUser($user_id) {
        $res = DealLoadModel::instance()->getLoantypeSummaryByUser($user_id, $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']);
        if ($res['deal_id']) {
            $res['deal'] = DealModel::instance()->findViaSlave($res['deal_id']);
        }
        if ($res['loan_time']) {
            $res['loan_time'] = to_date($res['loan_time'], 'Y年m月d日');
        }

        return $res;
    }

    public function canUseDeal($deal, $user, $sourceType) {
        // 新手专享
        if($deal['deal_crowd']=='1' && $GLOBALS['db']->getOne("select count(id) from ".DB_PREFIX."deal_load where user_id = '{$user['id']}'"
                ." AND `deal_id` IN (SELECT `id` FROM ".DB_PREFIX."deal WHERE `deal_status` IN (1,2,4,5) AND is_delete = '0' AND is_effect = '1' AND `parent_id` != '0')")>0 ) {
            return false;
        }
        // 专享标
        if($deal['deal_crowd'] == '2'){
            $deal_group_service = new DealGroupService();
            $group_check = $deal_group_service->checkUserDealGroup($deal['id'], $user['id']);
            if(!$group_check){
                return false;
            }
        }

        // 手机专享
        // 手机新手专享
        $deal_service = new DealService();
        $allowdBid = $deal_service->allowedBidBySourceType($sourceType, $deal['deal_crowd'], $user);
        if($allowdBid['error'] == true) {
            return false;
        }

        // 指定用户可投
        if($deal['deal_crowd'] == '16' && $deal['deal_specify_uid'] != $user['id']) {
            return false;
        }

        // 老用户专享
        if ($deal['deal_crowd'] == DealModel::DEAL_CROWD_OLD_USER) {
            $rule = app_conf('RULE_OLD_USER');
            if (!empty($rule)) {
                $arr = explode(';', $rule);
                if (2 == count($arr)) {
                    if (to_date($user['create_time'], 'Ymd') >= $arr[0]) {
                        return false;
                    }
                }
            }
        }

        // VIP用户专享
        if($deal['deal_crowd'] == DealModel::DEAL_CROWD_VIP) {
            //指定vip专享
            $vipService = new VipService();
            $vipInfo = $vipService->getVipInfo($user['id']);
            if (empty($vipInfo) || ($deal['deal_specify_uid'] > $vipInfo['service_grade'])) {
                $vipBidMsg = $vipService->getVipBidErrMsg($deal['deal_specify_uid']);
                return false;
            }
        }

        $user_service = new UserService($user['id']);
        // 批量导入可投用户
        if($deal['deal_crowd'] == '34') {

            $deal_custom_user_model = new DealCustomUserModel();
            $ret = $deal_custom_user_model->getDealOneUser($deal['id'],$user['id']);
            if (empty($ret)) {
                $deal_custom_service = new DealCustomUserService();
                if ($deal_custom_service->isDealCuUser($deal['id']) == false){
                    // 必须是企业站且是企业用户的
                    if (is_qiye_site() && $user_service->isEnterprise() ){
                        $ret = $deal_custom_service->enterpriseCanLoanZx($user['id']);
                    }else{
                        $ret = $deal_custom_service->canLoanZx($user['id']);
                    }
                }

                if (empty($ret)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 检查用户身份可以进行投资该标的
     * @param object $deal
     * @param object $user
     * @param float $money
     * @param int $source_type
     * @param string $coupon_id
     * @param int $site_id
     * @return bool
     */
    public function checkCanBid($deal, $user, $money, $source_type, $coupon_id, $site_id) {
        if(!$user){
            throw new \Exception("用户不存在");
        }
        // 投资智多新的
        if(isset($deal['isDTB']) && $deal['isDTB'] === true){
            return true;
        }

        if(!$deal){
            throw new \Exception($GLOBALS['lang']['PLEASE_SPEC_DEAL']); // 未指定投标
        }

        // 限制投资
        $userCarryService = new UserCarryService();
        $isSupervision = ($deal['report_status'] == 1) ? true : false;
        $user_money_limit = $userCarryService->canWithdrawAmount($user['id'], $money, $isSupervision);
        if ($user_money_limit === false){
            throw new \Exception($GLOBALS['lang']['FORBID_BID']); // 账户无法投资
        }

        if($deal['user_id'] == $user['id']){
            throw new \Exception($GLOBALS['lang']['CANT_BID_BY_YOURSELF']);
        }

        if($deal['is_visible'] != 1){

            throw new \Exception($GLOBALS['lang']['DEAL_FAILD_OPEN']);
        }

        if(floatval($deal['progress_point']) >= 100){
            throw new \Exception($GLOBALS['lang']['DEAL_BID_FULL']);
        }

        if($deal['deal_status'] != 1 && ( ($deal['deal_status'] == 0 && $source_type != \app\models\dao\DealLoad::$SOURCE_TYPE['appointment']) || ($deal['deal_status'] == 6 && $source_type != \app\models\dao\DealLoad::$SOURCE_TYPE['reservation']) )){
            throw new \Exception($GLOBALS['lang']['DEAL_FAILD_OPEN']);
        }

        // 定时标
        if ($deal['start_loan_time'] && $deal['start_loan_time']>get_gmtime()) {
            throw new \Exception("该项目将于" . to_date($deal['start_loan_time'], "Y-m-d H点m分") . "开始，请稍后再试");
        }

        $deal_service = new DealService();

        $age_check = $deal_service->allowedBidByCheckAge($user);
        if($age_check['error'] == true){
            throw new \Exception($age_check['msg']);
        }

        // 手机专享标
        $allowdBid = $deal_service->allowedBidBySourceType($source_type, $deal['deal_crowd'], $user);
        if($allowdBid['error'] == true) {
            throw new \Exception($allowdBid['msg']);
        }

        // 老用户专享逻辑
        if ($deal['deal_crowd'] == DealModel::DEAL_CROWD_OLD_USER) {
            $rule = app_conf('RULE_OLD_USER');
            if (!empty($rule)) {
                $arr = explode(';', $rule);
                if (2 == count($arr)) {
                    if (to_date($user['create_time'], 'Ymd') >= $arr[0]) {
                        throw new \Exception($arr[1]);
                    }
                }
            }
        }

        //新手标
        if($deal['deal_crowd']=='1' && $GLOBALS['db']->getOne("select count(id) from ".DB_PREFIX."deal_load where user_id = '{$user['id']}'"
                ." AND `deal_id` IN (SELECT `id` FROM ".DB_PREFIX."deal WHERE `deal_status` IN (1,2,4,5) AND is_delete = '0' AND is_effect = '1' AND `parent_id` != '0')")>0 ){
            throw new \Exception($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? "该项目为新手专享项目，只有初次出借的新用户可以出借" : "该项目为新手专享项目，只有初次投资的新用户可以投资");
        }

        if($deal['deal_crowd'] == '16' && $deal['deal_specify_uid'] != $user['id']) {
            throw new \Exception($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '该项目为专享标，只有特定用户才可出借' :'该项目为专享标，只有特定用户才可投资');
        }
        // 个人/企业用户
        $user_service = new UserService($user['id']);
        // 标定制用户
        if($deal['deal_crowd'] == '34') {

            $deal_custom_user_model = new DealCustomUserModel();
            $ret = $deal_custom_user_model->getDealOneUser($deal['id'],$user['id']);
            if (empty($ret)) {
                $deal_custom_service = new DealCustomUserService();
                if ($deal_custom_service->isDealCuUser($deal['id']) == false){
                    // 必须是企业站且是企业用户的
                    if (is_qiye_site() && $user_service->isEnterprise() ){
                        $ret = $deal_custom_service->enterpriseCanLoanZx($user['id']);
                    }else{
                        $ret = $deal_custom_service->canLoanZx($user['id']);
                    }
                }

                if (empty($ret)) {
                    throw new \Exception($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '该项目为专享标，只有特定用户才可出借' : '该项目为专享标，只有特定用户才可投资');
                }
            }
        }

        //指定vip专享
        if($deal['deal_crowd'] == DealModel::DEAL_CROWD_VIP) {
            //指定vip专享
            $vipService = new VipService();
            $vipInfo = $vipService->getVipInfo($user['id']);
            if (empty($vipInfo) || ($deal['deal_specify_uid'] > $vipInfo['service_grade'])) {
                $vipBidMsg = $vipService->getVipBidErrMsg($deal['deal_specify_uid']);
                throw new \Exception($vipBidMsg);
            }
        }



        if($user_service->isEnterprise()){
            if($deal['bid_restrict'] == 1){
                throw new \Exception("本产品为个人会员专享");
            }
        }else{

            if($deal['bid_restrict'] == 2){
                throw new \Exception("本产品为企业会员专享");
            }
        }

        //特定用户组
        if($deal['deal_crowd'] == '2'){
            $deal_group_service = new DealGroupService();
            $group_check = $deal_group_service->checkUserDealGroup($deal['id'], $user['id']);
            if(!$group_check){
                throw new \Exception($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? "专享标为平台为特定用户推荐的优惠项目，只有特定用户才可以出借"  : "专享标为平台为特定用户推荐的优惠项目，只有特定用户才可以投资");
            }
        }

        //验证优惠码有效性
        $couponService = new CouponService();
        $coupon_id = ($coupon_id == CouponService::SHORT_ALIAS_DEFAULT) ? '' : $coupon_id;

        if($deal['must_coupon'] == 1 && empty($coupon_id)){
            throw new \Exception("该项目为专享标，请使用专享优惠码");
        }

        if ($coupon_id) {
            $coupon_id = str_replace(' ','',$coupon_id);
            $coupon = $couponService->queryCoupon($coupon_id, true);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal['id'], $user['id'], $money, $coupon_id, $site_id, "coupon result", json_encode($coupon))));
            if (!empty($coupon)) {
                if (!$coupon['is_effect']) {
                    throw new \Exception("您使用的优惠码不适应此项目，请输入有效的优惠码，谢谢");
                }
            } else {
                throw new \Exception("优惠码有误，请重新输入");
            }
        }


        $isDT = $deal_service->isDealDT($deal['id']);

        if($isDT && ($source_type != DealLoadModel::$SOURCE_TYPE['dtb'])) {//多投宝标的只允许多投宝服务投资
            throw new \Exception("此项目为智多新专享，当前不可进行投资");
        }

        //如果存在绑定优惠码，必须填绑定的优惠码，防止修改表单 20150303
        $coupon_latest = $couponService->getCouponLatest($user['id']);
        $is_fixed_coupon = !empty($coupon_latest) && $coupon_latest['is_fixed'];

        if (!$isDT && $is_fixed_coupon && $coupon_id != $coupon_latest['short_alias']) {
            throw new \Exception("您使用的优惠码不正确，请与客服联系，谢谢");
        }
        //优惠码结束

        // 金额相关开始
        if(bccomp($money, $deal['min_loan_money'], 2) == -1){
            throw new \Exception("最低投资金额为{$deal['min_loan_money']}元");
        }


        //最高投资限制 适用所用用户  只有最后一笔可以大于最大投资额度 其他情况都不能大于最大投资额度  $deal['need_money_decimal']-$money)>$deal['min_loan_money']（不是最后一笔投资）只有最后一笔可以大于 并且最后一笔必须是在最小加最大之间
        $deal_already_load_money = DealLoadModel::instance()->getUserLoadMoneyByDealid($user['id'], $deal['id']);
        if ($deal['max_loan_money']>0 && ($deal_already_load_money+$money)>$deal['max_loan_money'] && ($deal['need_money_decimal']-$money)>=$deal['min_loan_money'] || ($deal['max_loan_money']>0 && $money>=($deal['min_loan_money']+$deal['max_loan_money'])))
        {
            throw new \Exception("抱歉，当前标的最高累计投资{$deal['max_loan_money']}元");
        }

        //判断所投的钱是否超过了剩余投标额度
        $need = bcsub($deal['borrow_amount'], $deal['load_money'], 2);
        if(bccomp($money, $need, 2) == 1) {
            $message = $deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? "出借金额超过项目可出借金额。当前可出借额为：%s" : $GLOBALS['lang']['DEAL_LOAN_NOT_ENOUGHT'];
            throw new \Exception(sprintf($message,format_price($deal['borrow_amount'] - $deal['load_money'])));
        }

        $minLeft = bcsub($deal['need_money_decimal'], $money, 2);

        //当前标的最低投资额度
        $currentMinLoanMoney = $deal['min_loan_money'];
        if($deal['is_float_min_loan'] == DealModel::DEAL_FLOAT_MIN_LOAN_MONEY_YES && in_array($deal['deal_type'],array(DealModel::DEAL_TYPE_EXCLUSIVE,DealModel::DEAL_TYPE_EXCHANGE))){
            if($deal['buy_count'] == (DealModel::DEAL_MAX_LOAN_COUNT - 2)) {

                // 读取配置
                $jys_min_money = (new DealModel())->getJYSMinLoanMony($deal['jys_id']);

                $currentMinLoanMoney = empty($jys_min_money) ? DealModel::DEAL_MIN_LOAN_MONEY : $jys_min_money;

            } else if($deal['buy_count'] == (DealModel::DEAL_MAX_LOAN_COUNT - 1)) {
                $currentMinLoanMoney = $deal['need_money_decimal'];
            }
        }

        if ($minLeft > 0 && bccomp($minLeft, $currentMinLoanMoney, 2) == -1) {
            $message = $deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? "项目即将满标，您需要一次性出借%s" : $GLOBALS['lang']['LAST_BID_AT_ONCE_NOT_TRUE'];
            throw new \Exception(sprintf($message, $deal['need_money']));
         }
        // 金额相关结束

        // 达人专享标
        if ($deal['min_loan_total_count'] > 0 || $deal['min_loan_total_amount'] > 0) {
            $dealLoadModel = new DealLoadModel();

            $totalCount = $dealLoadModel->getCountByUserIdInSuccess($user['id']);
            $totalAmount = $dealLoadModel->getAmountByUserIdInSuccess($user['id']);

            $loanFlag = true;
            $res['msg'] = "达人专享标是平台为有经验的投资用户推荐的优惠项目，只有%s的用户才可以投标。";
            if( $deal['min_loan_total_count'] > 0 && $deal['min_loan_total_amount'] == 0 ){
                if($totalCount < $deal['min_loan_total_count']){
                    $loanFlag = false;
                    $res['msg'] = sprintf($res['msg'], '投资超过'. $deal['min_loan_total_count'] .'次');
                }

            }else if( $deal['min_loan_total_count'] == 0 && $deal['min_loan_total_amount'] > 0 ){

                if($totalAmount < $deal['min_loan_total_amount']){
                    $loanFlag = false;
                    $res['msg'] = sprintf($res['msg'], '累计投资超过'. $deal['min_loan_total_amount'] .'元');
                }

            }else if ( $deal['min_loan_total_count'] > 0 && $deal['min_loan_total_amount'] > 0 && $deal['min_loan_total_limit_relation'] == 0 ){

                if(($totalCount <= $deal['min_loan_total_count']) && ($totalAmount <= $deal['min_loan_total_amount'])){
                    $loanFlag = false;
                    $res['msg'] = sprintf($res['msg'], '投资超过'. $deal['min_loan_total_count'] .'次，或者累计投资超过'. $deal['min_loan_total_amount'] .'元');
                }

            }else if ( $deal['min_loan_total_count'] > 0 && $deal['min_loan_total_amount'] > 0 && $deal['min_loan_total_limit_relation'] == 1 ){

                if(!(($totalCount >= $deal['min_loan_total_count']) && ($totalAmount >= $deal['min_loan_total_amount']))){
                    $loanFlag = false;
                    $res['msg'] = sprintf($res['msg'], '投资超过'. $deal['min_loan_total_count'] .'次，并且累计投资超过'. $deal['min_loan_total_amount'] .'元');
                }

            }

            if(!$loanFlag){
                throw new \Exception($res['msg']);
            }
        }

        return true;
    }


    /**
     * 进行投资
     *
     * @userlock
     * $bankCallBackOrderId 验密投资时银行返回的订单号
     * @orderInfo 存管验密投资时根据订单ID获取到的订单信息数组
     * @return array
     **/
    public function bid($user_id, $deal, $money, $coupon_id, $source_type = 0, $site_id = 1, $jforder_id = false, $discount_id = '', $discount_type = 1,$optionParams=array()) {
        $deal_id = $deal['id'];
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "start")));
        $res = array(
            'error' => true,
            'msg' => '',
        );
        self::$fatal = 1;
        self::$bidTime = microtime(true);
        register_shutdown_function(array($this, "errCatch"),$deal_id);
        $deal_data = new DealData();
        \libs\utils\Monitor::add('DOBID_START');
        $lock = $deal_data->enterPool($deal_id);
        if ($lock === false) {
            // 正常退出
            self::$fatal = 0;
            $res['msg'] = "抢标人数过多，请稍后再试";
            \libs\utils\Monitor::add('DOBID_FAILED_LOCKED');
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "fail", $res['msg'])));
            return $res;
        }

        // 随鑫约的产品检查是实时的，pc app wap 是根据当时上标的项目评级检查
          if(!in_array($source_type,array(DealLoadModel::$SOURCE_TYPE['dtb'],DealLoadModel::$SOURCE_TYPE['reservation']))){
            $deal_project_riskAssessment_service = new DealProjectRiskAssessmentService();
            $deal_project_riskAssessment_ret = $deal_project_riskAssessment_service->checkRiskBid($deal['project_id'], $user_id, true);
            if ($deal_project_riskAssessment_ret['result'] == false) {
                $res['msg'] = '当前您的风险承受能力为"' . $deal_project_riskAssessment_ret['user_risk_assessment'] . '"';
                $res['remaining_assess_num'] = $deal_project_riskAssessment_ret['remaining_assess_num'];
                $deal_data->leavePool($deal_id);
                \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "fail", $res['msg'], "line:" . __LINE__)));
                return $res;
            }
        }
        $user = UserModel::instance()->find($user_id);
        $deal_service = new DealService();
        $user_service = new UserService();

        //P2P只允许投资户投资
        if($deal_service->isP2pPath($deal)){
            if(!$user_service->allowAccountLoan($user['user_purpose'])){
                self::$fatal = 0;
                $res['msg'] = $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID'];
            }
        }

        $deal_model = DealModel::instance()->find($deal_id);

        // BID_MORE 判断
        $tagService = new UserTagService();
        $bidMore = $tagService->getTagByConstNameUserId('BID_MORE', $user_id);
        $siteId = \libs\utils\Site::getId();

        /****** 新的投资逻辑开始  ************************/

        if(\es_cookie::is_set('euid')){
            $this->euid = \es_cookie::get('euid');
        }
        if(\es_session::get('track_id')){
            $this->track_id = \es_session::get('track_id');
        }

        //app接口调用，使用optionParams传递euid
        $euid = isset($optionParams['euid']) ? $optionParams['euid'] : "";
        if (!empty($euid)){
            $this->euid = $euid;
        }

        try {
            // 1、判断是否符合投资条件 不能包含余额
            $this->checkCanBid($deal, $user, $money, $source_type, $coupon_id, $site_id);

            // 是否走存管
            $isP2pBid = $deal_service->isP2pPath($deal_model) ? true :false;
            $deal['isDTB'] = $deal_service->isDealDT($deal_id);
            $dealBidService = new P2pDealBidService();

            // 2、是否免密的投资判断 非免密投资银行已经完成投资处理不需要在进行资金划转
            $orderInfo = isset($optionParams['orderInfo']) ? $optionParams['orderInfo'] : array();
            $orderParams = (isset($orderInfo['params']) && !empty($orderInfo['params'])) ? json_decode($orderInfo['params'],true) : array();

            //基于TM 的投资逻辑 先声明因为要用到事务ID
            $gtm = new GlobalTransactionManager();
            $gtm->setName('bid');

            if(!empty($orderInfo)){
                $transferMoney = false;
                $globalOrderId = $orderInfo['order_id'];
                $orderParams = json_decode($orderInfo['params'],true);
                $bonusInfo = isset($orderParams['bonusInfo']) ? $orderParams['bonusInfo'] : array();
            }else{
                $globalOrderId = $gtm->getTid();
                //$transferMoney  = $dealBidService->needTransferMoney($user,$deal_model,$money);

                // 投资不在有余额划转--直接屏蔽
                $transferMoney  = false;
            }

            // 3、资金划转
            if($transferMoney && bccomp($transferMoney,'0.00',2) ==1){
                $transferOrderId = Idworker::instance()->getId();
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP," dealId:{$deal_id},userId:{$user_id} 开始资金划转 金额：{$transferMoney}")));
                $transferRes = $dealBidService->moneyTransfer($transferOrderId,$user['id'],$transferMoney,$isP2pBid);
                if(!$transferRes){
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "资金划转失败 orderId:".$globalOrderId.", transFerOrderId:".$transferOrderId)));
                    throw new \Exception("投资失败");
                }
                // 此处需要重新获取下用户信息,因为在资金划转后用户资金已经发生变化了
                $user = UserModel::instance()->find($user_id);
            }

            // 4、余额验证 (余额验证不能放在前面,因为存管行有可能已经完成投资，这时候判断余额是不严谨的 所以需要单独摘出来) 银行验密投资不需要再次验证余额
            if(empty($orderInfo)){
                $moneyInfo = (new UserService())->getMoneyInfo($user,$money,$globalOrderId);
                //多投投资时候不使用红包
                if ($deal['isDTB'] === true){
                    $moneyInfo['bonus'] = 0;
                    $moneyInfo['bonusInfo'] = array();
                    $moneyInfo['bonusInfo']['accountInfo'] = array();
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "多投投资不能使用红包 orderId:".$globalOrderId)));
                }
                $totalCanBidMoney = $isP2pBid ? bcadd($moneyInfo['bank'],$moneyInfo['bonus'],2) : bcadd($moneyInfo['lc'],$moneyInfo['bonus'],2);
                if((bccomp($money,$totalCanBidMoney,2) == 1)){
                    throw new \Exception('余额不足，请先进行充值');
                }
                $bonusInfo = $moneyInfo['bonusInfo'];
            }


            // 5、红包查询
            if(!isset($bonusInfo['accountInfo'])){
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "未查询到红包信息 orderId:".$globalOrderId)));
                $bonusMoney = 0;
            }else{
                $bonusMoney = $bonusInfo['money'];
            }

            if ($bonusMoney > 0) {
                $limitBonus = app_conf('BONUS_USE_MAX_VALUES');
                if ($limitBonus > 0 && $bonusMoney > $limitBonus) {
                    $limitBonus = $limitBonus / 10000;
                    throw new \Exception("单笔投资使用红包金额最多{$limitBonus}万~");
                }
            }

        } catch (\Exception $e) {
            $res['msg'] = $e->getMessage();
            $deal_data->leavePool($deal_id);
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,"fail", $e->getMessage())));
            return $res;
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,"GTM start")));
        // 6、添加event
        try {

            //预约处理
            if (!empty($optionParams['reserveInfo'])) {
                $reserveInfo = $optionParams['reserveInfo'];
                $gtm->addEvent(new ReserveProcEvent($reserveInfo['id'], $reserveInfo['user_id'], $money, $deal_id, $globalOrderId));
            }

            // 银行免密投资
            if(empty($orderInfo) && $isP2pBid && $deal['isDTB'] == false){
                $gtm->addEvent(new BankbidEvent($globalOrderId,$deal_id,$user['id'],$money,$bonusInfo));
            }

            // 红包消费
            if(bccomp($bonusMoney,'0.00',2) == 1){
                $gtm->addEvent(new \core\tmevent\bid\BonusConsumeEvent($user['id'],$bonusInfo,$globalOrderId,$deal_model['name'], 10 + $deal_model['deal_type']));
            }

            $bidParams = array(
                'couponId' => $coupon_id,
                'sourceType' => $source_type,
                'siteId' => $site_id,
                'jforderId' => $jforder_id,
                'discountId' => $discount_id,
                'discountType' => $discount_type,
                'bidMore' => $bidMore,
                'bonusInfo' => $bonusInfo,
                'euid' => $this->euid,
                'trackId' => $this->track_id,
            );
            $bidParams = array_merge($bidParams,$orderParams);

            $gtm->addEvent(new P2pbidEvent(
                $globalOrderId,
                $deal_id,
                $user['id'],
                $money,
               $bidParams
            ));  // p2p投资

            $bidRes = $gtm->execute(); // 同步执行
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "GTM Error" . $e->getMessage())));
            return $this->getBidResult($deal_data,$deal_id,$discount_id,$globalOrderId);
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,"GTM end")));
        $loadId = P2pIdempotentService::getLoadIdByOrderId($globalOrderId);
        if($bidRes === true && $loadId !== false){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,$loadId,$globalOrderId, "succ")));
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,$loadId,$globalOrderId, "fali")));
        }
        return $this->getBidResult($deal_data,$deal_id,$discount_id,$globalOrderId,$loadId,$bidRes,$money,$bonusInfo);
    }

    /**
     * 银行验密投资 回跳时调用此方法
     * @param $orderId
     * @param $uid
     * @param $amount
     * @param $status 回调状态 'S' or 'F'
     */
    public function bidForBankSecret($orderId,$uid,$status){
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        $res['error'] = true;
        $res['msg'] = "出借失败，请稍后再试";
        $logParams = "uid:{$uid},orderId:{$orderId},status:{$status}";

        if(!in_array($status,array(SupervisionBaseService::RESPONSE_FAILURE,SupervisionBaseService::RESPONSE_SUCCESS))){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "fail","存管行返回的回调状态值错误 params:".$logParams)));
            return $res;
        }

        if(!$orderInfo){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "fail","存管行返回的订单不存在 params:".$logParams)));
            return $res;
        }

        $userId = $orderInfo['loan_user_id'];
        if($userId != $uid){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "fail","投资用户与实际用户不符 orderId:".$orderId)));
            return $res;
        }

        if($status == SupervisionBaseService::RESPONSE_FAILURE){
            return $res;
        }

        $dealService = new DealService();

        $dealId = $orderInfo['deal_id'];
        $deal = $dealService->getDeal($dealId);
        if(!$deal){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "fail","投资标的不存在 orderId:".$orderId." dealId:".$dealId)));
            return $res;
        }


        $money = $orderInfo['money'];
        $orderParams = json_decode($orderInfo['params'],true);
        $couponId = isset($orderParams['couponId']) ? $orderParams['couponId'] : false;
        $sourceType = isset($orderParams['sourceType']) ? $orderParams['sourceType'] : false;
        $siteId = isset($orderParams['siteId']) ? $orderParams['siteId'] : false;
        $jfOrderId = isset($orderParams['jforderId']) ? $orderParams['jforderId'] : false;
        $discountId = isset($orderParams['discountId']) ? $orderParams['discountId']:false;
        $discountType = isset($orderParams['discountType']) ? $orderParams['discountType'] : false;

        if($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK && $orderInfo['result'] == P2pIdempotentService::RESULT_FAIL) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $orderId, "免密投资订单已通过回调处理失败 orderId:" . $orderId)));
            return $res;
        }elseif($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK && $orderInfo['result'] == P2pIdempotentService::RESULT_SUCC) {
            // 投资成功
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $orderId, "免密投资订单已通过回调处理成功 orderId:" . $orderId)));

            $loadId = $orderInfo['load_id'];
            $res['error'] = false;
            $res['msg'] = "投资成功";
            $res['load_id'] = $loadId;
            $res['deal_status'] = $deal['deal_status'];
            $res['deal_id'] = $deal['id'];
            $res['money']   = $orderInfo['money'];
            $res['discountId'] = $discountId;
            $res['discountType'] = $discountType;
            $res['discountGoodsPrice'] = isset($orderParams['discountGoodsPrice']) ? $orderParams['discountGoodsPrice']: '';
            return $res;
        }
        UserCarryService::$checkWithdrawLimit = false; //验密投资回调绕过限制提现
        $bidRes = $this->bid($userId,$deal,$money,$couponId,$sourceType,$siteId,$jfOrderId,$discountId,$discountType,array('orderInfo'=>$orderInfo));


        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        // 再次更新deal信息
        $deal = DealModel::instance()->find($dealId);

        if($bidRes['error'] === true){
            if($orderInfo['result'] == P2pIdempotentService::RESULT_SUCC){
                $res['error']   = false;
                $res['msg']     = "投资成功";
                $res['load_id'] = $orderInfo['load_id'];
                $res['deal_status'] = $deal['deal_status'];
                $res['deal_id'] = $deal['id'];
                $res['money']   = $orderInfo['money'];
                $res['discountId'] = $discountId;
                $res['discountType'] = $discountType;
                $res['discountGoodsPrice'] = isset($orderParams['discountGoodsPrice']) ? $orderParams['discountGoodsPrice']: '';
            }
        }else{
            $res['error']   = false;
            $res['msg']     = "投资成功";
            $res['load_id'] = $orderInfo['load_id'];
            $res['deal_status'] = $deal['deal_status'];
            $res['deal_id'] = $deal['id'];
            $res['money']   = $orderInfo['money'];
            $res['discountId'] = $discountId;
            $res['discountType'] = $discountType;
            $res['discountGoodsPrice'] = isset($orderParams['discountGoodsPrice']) ? $orderParams['discountGoodsPrice']: '';
        }
        return $res;
    }

    /**
     * 投资成功返回信息
     * @param DealData $dealData
     * @param $dealId
     * @param $discountId
     * @param $globalOrderId
     * @param $loadId
     * @param bool|false $bidRes 投资结果
     * @param bool|false $bidMoney 投资金额
     * @param bool|false $bonusInfo 投资使用红包信息
     * @return mixed
     */
    public function getBidResult(DealData $dealData,$dealId,$discountId,$globalOrderId,$loadId=false,$bidRes=false,$bidMoney=false,$bonusInfo=false){
        $dealModel = DealModel::instance()->find($dealId);
        if(!$loadId){
            $dealService = new DealService();
            $isDTB = $dealService->isDealDT($dealId);
            // 出现异常 实际理财没有投资成功而存管投资成功 智多新逻辑特殊不走正常投资取消
            if($bidRes === true && $dealModel['report_status'] == DealModel::DEAL_REPORT_STATUS_YES && $isDTB === false){
                $cnacService = new P2pDealBidService();
                $cancRes = $cnacService->dealBidCancelRequest($globalOrderId);
                // 保证一定要通知到
                if(!$cancRes){
                    $function = '\core\service\P2pDealBidService::dealBidCancelRequest';
                    $param = array($globalOrderId);
                    $job_model = new \core\dao\JobsModel();
                    $job_model->priority = 99;
                    $add_job = $job_model->addJob($function, $param,false,10);
                    if (!$add_job) {
                        \libs\utils\Alarm::push(self::ALARM_BANK_CALLBAK,'投资取消通知银行jobs添加失败'," dealId:{$dealId}, orderId:".$globalOrderId);
                    }
                }
            }
            $res['error'] = true;
            $res['msg'] = "投资失败，请稍后再试";
            $dealData->leavePool($dealId);
        }else{
            \libs\utils\Monitor::add('DOBID_SUCCESS');
            // 投资成功，此时可以释放资源
            $dealData->leavePool($dealId);
            self::$fatal = 0;

            \SiteApp::init()->cache->set(DiscountService::CACHE_CONSUME_PREFIX.$discountId, 1, 3600);//投资劵消费缓存
            $res['error'] = false;
            $res['msg'] = "投资成功";
            $res['order_id'] = $globalOrderId;
            $res['load_id'] = $loadId;
            $res['deal_status'] = $dealModel['deal_status'];
            $res['deal_id'] = $dealModel['id'];
            $res['money'] = $bidMoney;
            $res['use_bonus_money'] = $bonusInfo['money'] ?: 0;
        }
        $cost = round(microtime(true) - self::$bidTime, 3);
        Logger::info('WX_DealLoadService::bid succ cost:'.$cost);
        if($cost > 1){
            Monitor::add('WX_BID_TIME');
        }
        return $res;
    }


    function getJumpDataAfterBid($user,$loadId,$dealId,$money,$otherParams=array()){
        \core\service\risk\RiskServiceFactory::instance(\libs\utils\Risk::BC_BID)->notify();
        $discountId = isset($otherParams['discountId']) ? $otherParams['discountId'] :'';
        $discountGoodsPrice = isset($otherParams['discountGoodsPrice']) ? $otherParams['discountGoodsPrice'] :'';
        $discountGoodsType = isset($otherParams['discountGoodsType']) ? $otherParams['discountGoodsType'] :'';
        $siteId = isset($otherParams['siteId']) ? $otherParams['siteId'] :'';

        $deal = DealModel::instance()->find($dealId);
        // 读取O2O列表
        $prizeList = array();
        // 通知贷标的投资,不生成O2O礼物
        if ($deal['deal_type'] != 1) {
            $event = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
            $loadId = $loadId;
            $digObject = new \core\service\DigService('makeLoan', array(
                'id' => $user['id'],
                'loadid' => $loadId,
                'cn' => '',
            ));
            $prizeList = $digObject->getResult();
        }
        $showGiftInfo = 0;
        //企业用户和通知贷标的投资不生成红包 --- TODO 这里以后要单独出来一个event 用GTM处理
        if (empty($prizeList) && $deal['deal_type'] != 1 && $user['user_type'] != \core\dao\UserModel::USER_TYPE_ENTERPRISE) {
            $make_bonus = (new DealService())->makeBonus($dealId, $loadId, $user['id'], $money, $siteId);
        }

        // 如果包含O2O礼券，则显示礼券领取图片
        if (!empty($prizeList)){
            $showGiftInfo = 1;
        }
        $this->template = "";
        //注册成功，消除SESSION中的token
        bid_check_token(true);
        $jumpData = ['id' => $loadId, 'gS' => $showGiftInfo, 'action' => $event];

        if ($discountId > 0) {
            $jumpData['dP'] = str_replace(',', '', $discountGoodsPrice);
            $jumpData['dT'] = intval($discountGoodsType);
        }
        return $jumpData;
    }

    /**
     * 投标成功回调
     */
    public function bidSuccessCallback($param)
    {
        // 打tag之外的首投复投事件逻辑
        $dealService = new DealService();
        $dealService->dealEvent($param['user_id'], $param['money'], $param['coupon_id'], $param['load_id'], false, $param['site_id']);
        $isDealDT = $dealService->isDealDT($param['deal_id']);
        //信力埋点
        $userService = new UserService();
        $isEnterprise = $userService->checkEnterpriseUser($param['user_id']);
        if (!$isDealDT && !$isEnterprise) {
            $candyActivity = new CandyActivityService();
            $sourceType = ($param['deal_type'] == DealModel::DEAL_TYPE_GENERAL) ? CandyActivityService::SOURCE_TYPE_P2P : CandyActivityService::SOURCE_TYPE_ZHUANXIANG;
            $activityToken = $sourceType.'_'.$param['user_id'].'_'.$param['load_id'];
            try {
                $ret = $candyActivity->activityCreateByType($sourceType, $activityToken, $param['user_id'], $param['annualized_amount'], $param['money']);
            } catch (\Exception $e) {
                Logger::info("bidSuccessCallback. add candy activity exception. token:{$param['token']}, annualizedMoney:{$param['annualized_amount']}, money:{$param['money']}, msg:" . $e->getMessage());
            }
        }
        //用户投资次数相关，打tag，（重要，必须实时，否则返利计算错误）, 必须在consume之前
        //邀请码使用，邀请码可为空
        //智多新标的不再二次计算
        if (!$isDealDT) {
            $coupon = new CouponService();
            $coupon_consume_result = $coupon->consume($param['load_id']);
        }

        //if ($param['bonus'] > 0) {
            //$taskId = (new GTaskService())->doBackground((new ConsumeBonusEvent($param['load_id'])), 20);
            //Logger::info("BonusDataToNewService:BonusService::consume:dealLoadId={$param['load_id']}:taskId=$taskId");
        //}

        // 定投嘉年华活动 20151121
        $deal = $dealService->getDeal($param['deal_id'], true, false);
        if ($deal['type_id'] == DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT)) {
            $dealTagService = new DealTagService();
            $arrDealTag = $dealTagService->getTagByDealId($param['deal_id']);
            if (in_array(app_conf('ROULETTE_BXT'), $arrDealTag)) {
                $dealLoad = DealLoadModel::instance()->find($param['load_id']);

                $finance = new Finance();
                $moneyYear = $finance->getMoneyYearPeriod($param['money'], $deal['loantype'], $deal['repay_time']);
                $moneyYear = DealModel::instance()->floorfix($moneyYear);

                $rouletteRankService = new UserRouletteRankService();
                $rouletteRankService->updateUserMoney($param['user_id'], $dealLoad['user_name'], $dealLoad['user_deal_name'], $moneyYear);
            }
        }
        // 满标操作
        if ($param['is_deal_full'] == true) {
            $arr_deal = $deal->getRow();
            $arr_deal['deal_status'] = 2;
            $state_manager = new \core\service\deal\StateManager();
            $state_manager->setDeal($arr_deal);
            $state_manager->work();

            $QueueModel = DealQueueModel::instance()->getDealQueueByFirstDealId($param['deal_id']);
            if (!empty($QueueModel) && $QueueModel->startDealAutoByQueue() === false) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "满标触发自动上标失败")));
            }
        }

        //投标完成 发送邮件和短信等通知  caolong 2013-12-25
        send_tender_deal_message(array('id' => $param['deal_id'], 'name' => $param['deal_name']),'tender',number_format($param['money'], 2), $param['load_id'], $param['site_id']);

        //广告联盟
        //if ($param['deal_type'] != 1) {
        //    $adunionDealService = new AdunionDealService();
        //    $adunionDealService->triggerAdRecord($param['user_id'], 4, $param['deal_id'], $param['load_id'], $param['money'], 0, $param['coupon_id'], $param['euid'], $param['track_id']);
        //}

        $dealData = new \core\data\DealData();
        //2014年为中国信贷大屏幕准备的数据源，后面jk采用白泽的数据
        //$dealData->pushCreditLoad(array('deal_id'=>$param['deal_id'], 'load_id'=>$param['load_id'], 'userid'=>$param['user_id'], 'money'=>$param['money'], 'ip' => $param['ip'], 'time' => $param['time']));

        //投资劵消费同步
        $discountService = new DiscountService();
        $discountService->consumeEvent($param['user_id'], $param['discount_id'], $param['load_id'], $param['deal_name']);

        //天脉埋点，投资
        if($param['coupon_id'] == $GLOBALS['sys_config']['CURL_HOOK_CONF']['TianMaiCoupon']){
            // 天脉合作，异步通知
            $channel = 'TianMaiInvest';
            $loantypeName = $deal->getLoantypeName();
            $inputParams = array(
                'mobile'=> md5($param['phone']),
                'orderId' => $param['load_id'],
                'timeStamp'=> $param['time'],
                'userId' => $param['user_id'],
                'productID'=>$param['deal_id'],
                'productName'=>$deal['name'],
                'repayTime' => $deal['loantype']==5?$deal['repay_time'].'天':$deal['repay_time'].'个月',
                'incomeRate' => number_format($deal['rate'],2),
                'money' => $param['money']*100,
                'loantypeName'=>$loantypeName,
                'inviteCode'=>$param['coupon_id'],
            );

            $url = $GLOBALS['sys_config']['CURL_HOOK_CONF'][$channel];
            $tphs = new ThirdPartyHookService();
            $ret = $tphs->asyncCall($url, $inputParams, $channel);
            if(!empty($ret)){
                \libs\utils\Monitor::add('TIANMAI_INVEST_PUSH');
            }else{
                \libs\utils\Alarm::push('thirdparty_push', '天脉投资推送失败', var_export($inputParams,true));
            }
            // 天脉done
        }

        //用户统计埋点(由于普通标的投资肯定会出发邀请码返利的用户分析埋点,所以这里暂时去掉,消息总线后恢复)
        $userProfileService = new UserProfileService();
        $userProfileService->bidProfile($param['user_id'],$param['deal_id'],$param['money']);

        if($dealService->isDealYtsh($param['deal_id'])){
            $XHService = new \core\service\XHService();
            $XHService->bidSuccessNotify($param['load_id']);
        }
        return true;
    }

    /**
     * 根据loantype获取标的列表
     * add by longbo
     */
    public function getDealLoadByLoantype($userId = null, $loantype = 0, $offset = 0, $count = 10, $isTotal = false) {
        return DealLoadModel::instance()->getDealLoadByLoantype($userId, $loantype, $offset, $count, $isTotal);
    }

    /**
     *  投标生成合同
     */
    public function sendContract($param){
        $send_contract_service = new SendContractService();
        if($send_contract_service->send($param['deal_id'], $param['load_id'], $param['is_full'],$param['create_time'])){
            return true;
        }else{
            Logger::info('sendContract. sendContractFailed. msg:执行失败, param:'.json_encode($param));
            return false;
        }
    }

    /**
     *  满标合同检测
     */
    public function fullCheck($param){
        $send_contract_service = new SendContractService();
        if($send_contract_service->fullCheck($param['deal_id'])){
            return true;
        }else{
            Logger::info('fullCheck执行失败, param:'.json_encode($param));
            return false;
        }
    }

//    /**
//     * 后台预约多投宝资产后，在多投宝增加一条关联记录
//     * @param array $param
//     * @return bool
//     */
//    public function dtBidSuccess($param) {
//        $deal_id = $param['deal_id'];
//        $money = $param['money'];
//        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
//        $vars = array(
//            'id' => $deal_id,
//            'money' => $money,
//        );
//        $request->setVars($vars);
//
//        $rpc = new \libs\utils\Rpc('duotouRpc');
//        $response = $rpc->go("\NCFGroup\Duotou\Services\P2pDeal", "bidP2pDealSuccess", $request);
//
//        if(!$response) {
//            throw new \Exception("rpc请求超时或网络错误");
//        }
//        if($response['errCode'] != 0) {
//            throw new \Exception($response['errMsg']);
//        }
//        return $response['data'] ? true : false;
//    }

    /*
     * 根据siteid获取用户投资记录
     * @param int $site_id
     * @return model
     */
    public function getLoadBySiteId($site_id = 1, $offset = 0, $count = 10, $updateTime = 0, $sortType = 0)
    {
        return DealLoadModel::instance()->getLoadBySiteId($site_id, $offset, $count, $updateTime, $sortType);
    }

    /**
     * [getP2pUserDealData 从指定id开始，获取一定数量的交易信息]
     * @author <fanjingwen@ucfgroup.com>
     * @param  [int] $begin_id          [开始的id]
     * @param  [int] $once_catch_counts [获取多少条记录]
     * @return [string]                 [格式化的数据，[[姓名，身份证号，手机号](md5)，投资金额，投资时间]]
     */
    public function getP2pUserDealDataFormat($begin_id, $once_catch_counts)
    {
        $data_arr = DealLoadModel::instance()->getP2pUserDealData($begin_id, $once_catch_counts);
        $salt = 'The 2016 Rio de Janeiro Olympic Games';
        $data_str = '';
        foreach ($data_arr as $data) {
            // 1、解密敏感字段；2、时间+8
            $data_str .= $data['id'] . ',';
            $data_str .= strtoupper(md5($data['real_name'] . $salt))  . ',';
            $data_str .= strtoupper(md5($data['idno'] . $salt))  . ',';
            $data_str .= strtoupper(md5($data['mobile'] . $salt))  . ',';
            $data_str .= $data['money'] . ',';
            $data_str .= $data['create_time'] + 28800 . "\n";
        }

        return $data_str;
    }

    /**
     * @获取单条投资记录(第三方合作使用)
     * @param int $loadId
     * @return array
     */
    public function findLoadInfo($loadId)
    {
        $dealLoad = DealLoadModel::instance()->find($loadId, '*', $slave);
        return !empty($dealLoad) ? $dealLoad : false;
    }

    /**
     * 获取最新投资动态
     * @param int $deal_type
     * @param int $money
     * @param int $limit
     * @return array
     */
    public function getNewLoads($deal_type, $money, $limit=30, $limit_time = 86400)
    {
        $dealLoad = DealLoadModel::instance()->getNewLoads($deal_type, $money, $limit, $limit_time);
        return !empty($dealLoad) ? $dealLoad : false;
    }

    /**
     * 获取用户是否首投(p2p+多投)
     * @param int $userId
     * @return bool
     */

    public function isFirstInvest($userId){
        $userId = intval($userId);

        $p2pUserInvest = DealLoadModel::instance()->countByUserId($userId);

        if($p2pUserInvest > 1){
            return false;
        }else{
            $rpc = new Rpc('duotouRpc');
            $request = new \NCFGroup\Protos\Duotou\RequestCommon();
            $vars = array(
                'userId' => $userId,
            );
            $request->setVars($vars);
            $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getInvestNumByUserId',$request);
            if(!$response) {
                return;
            }

            if(($p2pUserInvest + $response['data']) > 1){
                return false;
            }
        }

        return true;



    }

    /**
     * 获取用户投资次数与总金额
     */
    public function getTotalMoneyAndCount($userId)
    {
        $sql = "SELECT SUM(`money`) AS `sum`, count(id) as cnt FROM firstp2p_deal_load WHERE user_id = '$userId' GROUP BY user_id";
        $result = DealLoadModel::instance()->findBySql($sql, [], true);
        return $result;
    }

    public function isInvestByUserId($userId)
    {
        $sql = "select user_id from firstp2p_deal_load
        where user_id = $userId and source_type in (0,1,2,3,4,6,7,8) limit 1";
        $result = DealLoadModel::instance()->findAllBySql($sql);
        if (empty($result)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 判断用户今天是否投过资
     */
    public function hasLoanToday($userId)
    {
        $startTime = strtotime(date('Ymd')) - date('Z');
        $sql = sprintf("SELECT `id` FROM firstp2p_deal_load WHERE user_id = '%d' AND create_time = '%d'", $userId, $startTime);
        $result = DealLoadModel::instance()->findBySqlViaSlave($sql);
        // 检查网信
        if (!empty($result)) {
            return true;
        }

        // 检查普惠
        if (\core\service\ncfph\DealLoadService::isTodayLoadByUserId($userId)) {
            return true;
        }
        return false;
    }

    /**
     * 根据时间判断用户是否投过资
     */
    public function hasLoanByTime($userId, $time)
    {
        if (empty($userId)) {
            return false;
        }
        $startTime = $time - date('Z');
        $sql = sprintf("SELECT `id` FROM firstp2p_deal_load WHERE user_id = '%d' AND create_time > '%d'", $userId, $startTime);
        $result = DealLoadModel::instance()->findBySqlViaSlave($sql);
        // 检查网信
        if (!empty($result)) {
            return true;
        }

        // 检查普惠
        $phLoads = \core\service\ncfph\DealLoadService::getUserLoadMoneyStat($userId, $time);
        if ($phLoads['money'] > 0) {
            return true;
        }
        return false;
    }

}

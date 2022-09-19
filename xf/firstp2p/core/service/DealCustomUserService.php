<?php

/**
 * 标的定制用户 service
 *
 *
 **/

namespace core\service;

use core\dao\DealCustomUserModel;
use core\dao\DealLoadModel;
use libs\utils\Logger;
use libs\utils\Finance;
use core\service\BonusService;
use core\service\UserThirdBalanceService;
use core\dao\DealModel;
use core\dao\UserModel;
use core\service\BwlistService;
\FP::import("libs.common.dict");

class DealCustomUserService extends BaseService {

    const TYPE_USER_ID = 1;
    const TYPE_GROUP_ID = 2;

    const GROUP_TYPE_SERVICE = 0;
    const GROUP_TYPE_INVITE = 1;
    const GROUP_TYPE_OWN = 2;

    private $customUsermodel = '';


    /**
     * 初始化model
     */
    public function __construct(){

        $model = new DealCustomUserModel();

        $this->customUsermodel = $model;

    }
    /**
     * 获取定制列表
     */
    public function getList(){

        return $this->customUsermodel->getEffectiveStateList();
    }

    /**
     * 获取带有deal 条件的列表
     * @param bool $is_all_site
     * @param bool $is_display
     * @param int $site_id
     * @param array $option
     * @param bool $is_real_site
     */
    public function getDealCustomUserList($user_id,$is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false,$is_show_p2p = false){

        // 黑名单
        //$deal_cu_backlist = app_conf("DEAL_CU_BLACKLIST");
        $isBacklist = $this->checkBlackList($user_id);
        if ($isBacklist){
            return array();
        }

        $list = $this->getCommonList($is_all_site, $is_display, $site_id, $option,$is_real_site,$is_show_p2p);
        return $this->processDisplayList($list,$user_id);
    }

    /**
     * 获取企业专项列表
     * @param $user_id
     * @param bool $is_all_site
     * @param bool $is_display
     * @param array $option
     * @param bool $is_real_site
     * @param bool $is_show_p2p
     */
    public function getEnterpriseDealCustomUserList ($user_id,$is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false,$is_show_p2p = false){


         if (!is_qiye_site()){
             return false;
         }
        $list = $this->getCommonList($is_all_site, $is_display, $site_id, $option,$is_real_site,$is_show_p2p);

        return $this->processDisplayList($list,$user_id,1);

    }

    /**
     * 获取单独的尊享列表
     * @param bool $is_all_site
     * @param bool $is_display
     * @param int $site_id
     * @param array $option
     * @param bool $is_real_site
     * @param bool $is_show_p2p
     * @return array|bool
     */
    public function getCommonList($is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false,$is_show_p2p = false){

        $dealModel = new DealModel();
        $dealCuStr = $dealModel->getListBatchImportByDealCrowd(2);
        if (empty($dealCuStr)){
            return array();
        }
        $option['isHitSupervision'] = $is_show_p2p;
        $option['is_read_deal_custom_user'] = true;
        // 获取所有定制列表
        $list = $dealModel->getDealCustomUserList($is_all_site, $is_display, $site_id, $option,$is_real_site);
        return $list;
    }
    /**
     * 检查标的是否用户专项
     * @param $deal_id
     * @return boole true 是 | false 否
     */
    public function isDealCuUser($deal_id){

        if (empty($deal_id)){
            return false;
        }
        // 获取特定用户的标列表
        $dealUserCu = $this->customUsermodel->getCommaSeparatedDealId();

        $dealUserCuIds = array();
        if (!empty($dealUserCu)) {
            $dealUserCuIds = explode(',', $dealUserCu);
        }

        if (in_array($deal_id,$dealUserCuIds)){
            return true;
        }

        return false;
    }


    /**
     * 处理需要展示标的列表
     * @param array $list
     * @param $is_have_cu_user 是否定制
     * @param int $is_enterprise
     */

    public function processDisplayList($list = array(),$user_id,$is_enterprise_site = 0){

        if (empty($list)){
            return array();
        }
        if ($user_id <= 0) {
            return [];
        }
        $canloanZxRes = $this->canLoanZx($user_id);
        if (!$canloanZxRes) {
            return [];
        }

        $result = array();
        // 排序从小到大按照deal_status ,repaly_time ,income_fee_rate,id
        $sort_deal_status = array();
        $sort_repay_time = array();
        $sort_income_fee_rate = array();
        $sort_id = array();

        //获取定制标列表
        $dealCuList = $this->getDealCuList();
        foreach($list as $key => $deal){

            $ret = true;
            if(isset($dealCuList[$deal['id']])){
                $ret = $this->isHaveCuUserCache($user_id,$deal['id']);
            }

            if (!empty($ret)) {
                // 保存tag原始值
                $dealTagName = $deal['deal_tag_name'];
                $result[$key] = DealModel::instance()->handleDealNew($deal, 1);
                $result[$key]['deal_tag_name_origin'] = $dealTagName;
                $sort_deal_status[] = $deal['deal_status'];
                if ($deal['loantype'] != $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']){
                    $day = $deal['repay_time']*DealModel::DAY_OF_MONTH;
                }else{
                    $day = $deal['repay_time'];
                }
                $sort_repay_time[] = $day;
                $sort_income_fee_rate[] = $deal['income_fee_rate'];
                $sort_id[] = $deal['id'];
            }

        }
        if (!empty($result)){
            array_multisort($sort_deal_status,SORT_ASC,$sort_repay_time,SORT_ASC,$sort_income_fee_rate,SORT_ASC,$sort_id,SORT_DESC,$result);
        }
        return empty($result)? array():array_values($result) ;
    }
    /**
     *
     * 获取缓存有效的标所有用户
     * @param $refresh
     */
    public function getCacheDealUserIds($refresh = 0){
        $refresh = empty($refresh) ? false: true;
        // 缓存10分钟
        $cache_time = 600;
        return  \SiteApp::init()->dataCache->call(new DealCustomUserService(), 'getDealUserIds', array(), $cache_time,$refresh);
    }

    /**
     * 清空数据
     * @param $dealId
     */
    public function delAll($dealId){

        $dealId = intval($dealId);

        if (empty($dealId)){
            return false;
        }
        $ret = DealCustomUserModel::instance()->deleteByDealIdType($dealId);
        if (empty($ret)){
            return false;
        }
        // 刷新缓存
        $this->getCacheDealUserIds(1);

        return true;
    }
    /**
     * 获取有效的标用户
     */
    public function getDealUserIds(){

        $list =  $this->customUsermodel->getEffectiveStateUserIdsList();
        $ret = array();
        if(!empty($list)){
            foreach($list as $key => $v){
                if ($v['user_id'] > 0) {
                    $ret[$v['user_id']] = $v['user_id'];
                }
            }
        }
        return $ret;
    }

     /**
     *
     * 获取缓存有效的标所有用户
     * @param $refresh
     */
    public function getCacheDealUserGroupIds($refresh = 0){
        $refresh = empty($refresh) ? false: true;
        // 缓存10分钟
        $cache_time = 600;
        return  \SiteApp::init()->dataCache->call(new DealCustomUserService(), 'getDealUserGroupIds', array(), $cache_time,$refresh);
    }

    /**
     * 获取有效的标用户
     */
    public function getDealUserGroupIds(){

        $list =  $this->customUsermodel->getEffectiveStateUserGroupIdsList();
        $ret = array();
        if(!empty($list)){
            foreach($list as $key => $v){
                if ($v['group_id'] > 0) {
                    $ret[$v['group_id']] = $v['group_id'];
                }
            }
        }
        return $ret;
    }
    /**
     * 检查用户是否可见
     * @param $user_id
     * @param bool $is_all_site
     * @param bool $is_display
     * @param int $site_id
     * @param array $option
     * @param bool $is_real_site
     * @param bool $is_show_p2p
     */
    public function checkIsShowUser($user_id,$is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false,$is_show_p2p = false){

        //黑名单
        $isBacklist = $this->checkBlackList($user_id);
        if ($isBacklist){
            return false;
        }
        if ($user_id <= 0) {
            return false;
        }
        $canloanZxRes = $this->canLoanZx($user_id);
        if (!$canloanZxRes) {
            return false;
        }

        return true;

        // 下面的代码判断意义不大了，而且还存在慢sql问题
        // $option['isHitSupervision'] = $is_show_p2p;
        // $option['is_read_deal_custom_user'] = true;

        // $dealModel = new DealModel();
        // $list = $dealModel->getDealCustomUserList($is_all_site, $is_display, $site_id, $option,$is_real_site);
        // if (!empty($list)){
        //     return true;
        // }

        // return false;
    }
    /**
     * 获取定制标的可投用户
     */
    public function getDealUserList($deal_id){

        if (empty($deal_id) || !is_numeric($deal_id)){
            return false;
        }

        return $this->customUsermodel->getDealUserList($deal_id);
    }

    public function insertInfo($params=array(),$deal_id,$is_delete){
        if(empty($deal_id)){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__,'deal_id:'.$deal_id)));
            return false;
        }
        $GLOBALS['db']->startTrans();
        try{
            if($is_delete==0){
                $deleteResult=$this->deleteInfo($deal_id);
                if(!$deleteResult){
                    throw new \Exception("delete 数据失败");
                }
            }
            foreach($params as $value){
                $result=DealCustomUserModel::instance()->changeUserId($value);
            }
            $GLOBALS['db']->commit();
        }catch(\Exception $e){
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__,$e->getMessage(), "line:" . __LINE__)));
            return false;
        }

        return true;
    }

    public function deleteInfo($deal_id){
        if(empty($deal_id)){
            return false;
        }
        return DealCustomUserModel::instance()->deleteByDealId($deal_id);
    }

    /**
     * 过滤专项的标
     * @param $deal_type_str 逗号分隔的
     */
    public function filterZx($deal_type_str){
        if (empty($deal_type_str)){
            return $deal_type_str;
        }
        $deal_type_arr = explode(',',$deal_type_str);
        $deal_type_str = '';
        foreach($deal_type_arr as $v){
            if ($v != DealModel::DEAL_TYPE_EXCLUSIVE){
                $deal_type_str .= $v.',';
            }
        }

        return trim($deal_type_str,',');
    }

    /**
     * 是否可以投资专享(含交易所)
     * @param $userId
     */
    public function canLoanZx($userId,$is_enterprise_site = 0) {

        $userId = intval($userId);
        if (empty($userId)) {
            return false;
        }

        // 企业站
        if ($is_enterprise_site){
            return $this->enterpriseCanLoanZx($userId);
        }
        //条件1：有专享在途
        //条件2：没有专享在途但投过专享，且目前网信余额+存管余额>=2万



        //白名单, 小名单列表，后台单个保存用
        /*$deal_cu_whitelist = (array)\dict::get('DEAL_CU_WHITELIST_SUB');
        if (!empty($deal_cu_whitelist) && in_array($userId,$deal_cu_whitelist)){
            return true;
        }*/

        //白名单
        $bwlistService = new BwlistService();
        $isWhiteList = $bwlistService -> inList('DEAL_CU_WHITE',$userId);
        if($isWhiteList){
            return true;
        }

        //白名单, 批量导入用
        /*$deal_cu_whitelist = (array)\dict::get('DEAL_CU_WHITELIST');
        if (!empty($deal_cu_whitelist) && in_array($userId,$deal_cu_whitelist)){
            return true;
        }*/

        $dealTable = DealModel::instance()->tableName();
        $dealLoadTable = DealLoadModel::instance()->tableName();
        $sql1 = "SELECT id FROM {$dealLoadTable} WHERE user_id = {$userId} AND deal_id IN (SELECT id FROM {$dealTable} WHERE deal_status IN (1,2,4) AND deal_type IN (2,3) ) LIMIT 1";

        $loadInfo  = DealLoadModel::instance()->findBySql($sql1, null, true);
        if(!empty($loadInfo)) {
            return true;
        }

        $sql1 = "SELECT id FROM {$dealLoadTable} WHERE user_id = {$userId} AND deal_type IN (2,3) LIMIT 1";

        $loadInfo  = DealLoadModel::instance()->findBySql($sql1, null, true);
        //投资过专享
        if(!empty($loadInfo)) {
            $limitMoney = 10000;
            if(!empty($GLOBALS['user_info'])) {
                $user_info = $GLOBALS['user_info'];
            }else {
                $user_info = UserModel::instance()->find($userId);
            }

            $bonusService = new BonusService();
            $bonus = $bonusService->get_useable_money($userId);
            $money_available = Finance::addition(array($user_info['money'], $bonus['money']), 2);
            $userThirdBalanceService = new UserThirdBalanceService();
            //资产中心余额
            $balanceResult = $userThirdBalanceService->getUserSupervisionMoney($userId);
            //加上存管金额
            $money_available = Finance::addition(array($money_available, $balanceResult['supervisionBalance']), 2);
            //网信余额+存管余额>=2万符合条件
            if(bccomp($money_available,$limitMoney,2) > -1) {
                return true;
            }
        }
        return false;
    }

    /**
     * 企业用户是否可以投资专享(含交易所)
     * @param int $user_id
     */
    public function enterpriseCanLoanZx($userId){

        //白名单, 小名单列表，后台单个保存用
        $deal_cu_whitelist = (array)\dict::get('DEAL_CU_WHITELIST_SUB');
        if (!empty($deal_cu_whitelist) && in_array($userId,$deal_cu_whitelist)){
            return true;
        }
        //白名单
        $bwlistService = new BwlistService();
        $isWhiteList = $bwlistService -> inList('DEAL_CU_WHITE',$userId);
        if($isWhiteList){
            return true;
        }

        $dealTable = DealModel::instance()->tableName();
        $dealLoadTable = DealLoadModel::instance()->tableName();
        $sql1 = "SELECT id FROM {$dealLoadTable} WHERE user_id = {$userId} AND deal_id IN (SELECT id FROM {$dealTable} WHERE deal_status IN (1,2,4) AND deal_type IN (2,3) ) LIMIT 1";
        $loadInfo  = DealLoadModel::instance()->findBySql($sql1, null, true);
        if(!empty($loadInfo)) {
            return true;
        }
        $sql1 = "SELECT id FROM {$dealLoadTable} WHERE user_id = {$userId} AND deal_type IN (2,3) LIMIT 1";
        $loadInfo  = DealLoadModel::instance()->findBySql($sql1, null, true);
        if (!empty($loadInfo)){
            return true;
        }

        return false;

    }


    /** 是否在黑名单里
     * @param $userId
     * @return bool
     */
    public function checkBlackList($user_id){

        $deal_cu_backlist = (array)\dict::get('DEAL_CU_BLACKLIST');
        if (!empty($deal_cu_backlist) && in_array($user_id,$deal_cu_backlist)){
            return true;
        }

        //黑名单
        $bwlistService = new BwlistService();
        $isBlackList = $bwlistService -> inList('DEAL_CU_BLACK',$user_id);
        if($isBlackList){
            return true;
        }

        return false;
    }

    /*
     * 获取有专享在途的用户
     * 有专享在途的用户，插入标的定制数据，20180502
     * @param $deal_id_list 标的id数组
     * @param $deal_type 0值取专享和交易所，0取交易所且不在专享里的用户
     * @param $is_execute 是否执行数据库操作
     */
    public function inserDealUserZX($deal_id_list, $deal_type=false, $is_execute=false){
        $log_info = array(__CLASS__, __FUNCTION__, json_encode($deal_id_list), $deal_type, $is_execute);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        if (empty($deal_id_list)) {
            Logger::info(implode(" | ", array_merge($log_info, array('empty deal list'))));
            return false;
        }
        $dealCustomUserModel = new \core\dao\DealCustomUserModel();
        $uids = $dealCustomUserModel->getUserIdsZhuanXiangZaiTu($deal_type);
        if (empty($uids)) {
            Logger::info(implode(" | ", array_merge($log_info, array('empty user list'))));
            return false;
        }
        Logger::info(implode(" | ", array_merge($log_info, array('count', count($uids)))));
        if (empty($is_execute)) {
            Logger::info(implode(" | ", array_merge($log_info, array('execute pass'))));
            return true;
        }
        $i = 1;
        foreach ($uids as $user) {
            foreach ($deal_id_list as $deal_id) {
                $deal_user = new \core\dao\DealCustomUserModel();
                $csv_data['user_id'] = $user['user_id'];
                $userModel = new \core\dao\UserModel ();
                $userInfo = $userModel->find(intval($csv_data['user_id']));
                $csv_data['user_name'] = $userInfo['user_name'];
                //$csv_data['admin_id'] = $adm_session ["adm_id"];
                $csv_data['deal_id'] = $deal_id;
                try {
                    $deal_user->changeUserId($csv_data);
                } catch (\Exception $e) {
                    Logger::info(implode(" | ", array_merge($log_info, array($i, $deal_id, $csv_data['user_id'], 'error', $e->getMessage()))));
                }
                Logger::info(implode(" | ", array_merge($log_info, array($i, $deal_id, $csv_data['user_id'], 'done'))));
            }
            $i++;
        }
        Logger::info(implode(" | ", array_merge($log_info, array(json_encode($deal_id_list), 'done'))));
    }

    /**
     * 投资限定条件1 - 用户组
     * @param  [type]  $dealId   [description]
     * @param  array   $groupIds [description]
     * @param  integer $adminId  [description]
     * @return [type]            [description]
     */
    public function saveGroupIds($dealId, $groupIds = [], $adminId = 0, $groupType = 0) {

        $type = self::TYPE_GROUP_ID;
        $cnt = DealCustomUserModel::instance()->count("deal_id='$dealId' AND type=$type");
        $cnt = intval($cnt);
        $dealId = intval($dealId);
        if ($cnt > 0) {
            $result = dealCustomUserModel::instance()->updateRows("delete from firstp2p_deal_custom_user where deal_id='$dealId' and type=$type LIMIT $cnt");
        }

        if (empty($groupIds)) {
            return true;
        }

        $valuesArr = [];
        $sql = 'INSERT INTO firstp2p_deal_custom_user (deal_id, group_id,user_name, admin_id, type, create_time, update_time) VALUES';
        $value = '("%s", "%s", "%s", "%s", "%s", "%s", "%s")';
        $createTime = $updateTime = time();
        foreach ($groupIds as $groupId) {
            if ($groupId > 0) {
                //不想在表里面增加字段
                $valuesArr[] = sprintf($value, $dealId, $groupId,$groupType,$adminId, $type, $createTime, $updateTime);
            }
        }
        if (empty($valuesArr)) {
            return true;
        }
        $valuesStr = implode(',', $valuesArr);
        $result =  dealCustomUserModel::instance()->updateRows($sql.$valuesStr);
        return $result;
    }

    private function getUserInfo($userId)
    {
        $cacheKey = 'cache_deal_custom_user_service_user_info_'.$userId;
        $userInfo = \SiteApp::init()->cache->get($cacheKey);
        if (empty($userInfo)) {
            $userInfo = UserModel::instance()->findViaSlave($userId, 'group_id');
            \SiteApp::init()->cache->set($cacheKey, $userInfo, 600);
        }
        return $userInfo;
    }

    private function getInviteUserInfo($userId)
    {
        $cacheKey = 'cache_deal_custom_user_service_invite_user_info_'.$userId;
        $inviteUserInfo = \SiteApp::init()->cache->get($cacheKey);
        if (empty($inviteUserInfo)) {
            $inviteUserInfo = \core\dao\CouponBindModel::instance()->findByViaSlave("user_id='{$userId}'", 'refer_user_id,invite_user_id');
            \SiteApp::init()->cache->set($cacheKey, $inviteUserInfo, 600);
        }
        return $inviteUserInfo;
    }

    /**
     * 是否能投资专享标
     */
    public function isHaveCuUser($userId,$dealId){
        if(empty($userId) || empty($dealId)){
            return false;
        }

        //指定用户可以投,缓存了所有用户id
        $userIds = $this->getCacheDealUserIds();
        if(isset($userIds[$userId])){
            $result = $this->isCuUserCache($userId,$dealId);
            if(!empty($result)){
                return true;
            }
        }

        //指定用户组可投
        $result = $this->isCuGroup($userId,$dealId);
        if(!empty($result)){
            return true;
        }

        return false;

    }

    /** 
     * 是否定制用户
     */
    public function isCuUser($userId,$dealId){
        // 是否有定制用户
        $result = DealCustomUserModel::instance()->findByViaSlave('deal_id ='. $dealId .' and user_id ='. $userId . ' and type = '.self::TYPE_USER_ID , 'id');
        if(empty($result)){
            return false;
        }
        return true;
    }

    private function isCuUserCache($userId,$dealId){
         return  \SiteApp::init()->dataCache->call(new DealCustomUserService(), 'isCuUser', array($userId,$dealId),600);
   } 

    /**
     * 是否制定用户组
     */
    public function isCuGroup($userId,$dealId){

        $userGroupId = $referUserGroupId = $inviteGroupId = 0;
        //是否指定用户组可投
        $userInfo = $this->getUserInfo($userId);
        if(empty($userInfo)){
            return false;
        }
        $userGroupId = $userInfo['group_id'];

        $couponInfo = $this->getInviteUserInfo($userId);
        $referUserId = $couponInfo['refer_user_id'];
        if($referUserId){
            $referUserInfo = $this->getUserInfo($referUserId);
            $referUserGroupId = empty($referUserInfo)? '0' : $referUserInfo['group_id'];
        }

        $inviteUserId = $couponInfo['invite_user_id'];
        if($inviteUserId){
            $inviteUserInfo = $this->getUserInfo($inviteUserId);
            $inviteGroupId = empty($inviteUserInfo)? '0' : $inviteUserInfo['group_id'];
        }

        Logger::info(implode(" | ",array(__CLASS__,__FUNCTION__,$dealId,$userId.'-'.$userGroupId,$referUserId.'-'.$referUserGroupId,$inviteUserId.'-'.$inviteGroupId)));

        return $this->isCuTypeGroupCache($dealId,$userGroupId,self::GROUP_TYPE_OWN) || $this->isCuTypeGroupCache($dealId,$referUserGroupId,self::GROUP_TYPE_SERVICE) || $this->isCuTypeGroupCache($dealId,$inviteGroupId,self::GROUP_TYPE_INVITE);
    }

   public function isCuTypeGroupCache($dealId,$groupId,$groupType = 0){
         return  \SiteApp::init()->dataCache->call(new DealCustomUserService(), 'isCuTypeGroup', array($dealId,$groupId,$groupType),600);
   } 
 
   public function isCuTypeGroup($dealId,$groupId,$groupType = 0){
         if(empty($dealId) || empty($groupId)){
            return false;
         }

        //提前缓存用户组
        $groupIds = $this->getCacheDealUserGroupIds();
        if(!isset($groupIds[$groupId])){
            return false;
        }
        switch ($groupType) {
            case self::GROUP_TYPE_SERVICE:
                $result = $this->isServiceGroup($dealId,$groupId);
                break;
            case self::GROUP_TYPE_INVITE:
                $result = $this->isInviteGroup($dealId,$groupId);
                break;
            case self::GROUP_TYPE_OWN:
                $result = $this->isOwnGroup($dealId,$groupId);
                break;
            default:
                $result = false;
                break;
        }
        return $result;
   }

   private function isOwnGroup($dealId,$groupId){
        $result = DealCustomUserModel::instance()->findByViaSlave('deal_id ='. $dealId . ' and type = '.self::TYPE_GROUP_ID . ' and group_id = '.$groupId , 'id');
        if(empty($result)){
            return false;
        }

        return true;
   }

    private function isInviteGroup($dealId,$groupId){
        $result = DealCustomUserModel::instance()->findByViaSlave('deal_id ='. $dealId . ' and type = '.self::TYPE_GROUP_ID . " and user_name  in('1','2')  and group_id = " .$groupId , 'id');
        if(empty($result)){
            return false;
        }

        return true;
    }

    private function isServiceGroup($dealId,$groupId){
        $result = DealCustomUserModel::instance()->findByViaSlave('deal_id ='. $dealId . ' and type = '.self::TYPE_GROUP_ID . " and user_name  in('0','2','')  and group_id = ".$groupId , 'id');
        if(empty($result)){
            return false;
        }

        return true;
    }

    /**
     * 获取定制标
     */
    public function getDealCuList(){
        $list = $this->customUsermodel->getEffectiveStateList();
        if (empty($list)){
            return false;
        }

        $dealIds = array();
        foreach($list as $v){
            $dealIds[$v['deal_id']] = $v['deal_id'];
        }

        return $dealIds;
    }

    public function isHaveCuUserCache($userId,$dealId){
        return  \SiteApp::init()->dataCache->call(new DealCustomUserService(), 'isHaveCuUser', array($userId,$dealId),600);
    }


} // END class

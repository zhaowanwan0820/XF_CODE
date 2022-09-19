<?php

/**
 * 标的定制用户 service
 *
 *
 **/

namespace core\service\deal;

use core\dao\deal\DealCustomUserModel;
use core\dao\deal\DealLoadModel;
use libs\utils\Logger;
use libs\utils\Finance;
use core\service\bonus\BonusService;
use core\service\UserThirdBalanceService;
use core\dao\deal\DealModel;
use core\dao\user\UserModel;
use core\service\BaseService;
use core\service\user\UserService;
\FP::import("libs.common.dict");

class DealCustomUserService extends BaseService {

    private $customUsermodel = '';


    /**
     * 初始化model
     */
    public function __construct(){

        $model = new DealCustomUserModel();

        $this->customUsermodel = $model;

    }

    /**
     * 获取带有deal 条件的列表
     * @param bool $is_all_site
     * @param bool $is_display
     * @param int $site_id
     * @param array $option
     * @param bool $is_real_site
     */
    public function getDealCustomUserList($user_id,$is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false){

        // 黑名单
        //$deal_cu_backlist = app_conf("DEAL_CU_BLACKLIST");
        $isBacklist = UserService::checkBlackList($user_id);
        if ($isBacklist){
            return array();
        }

        $list = $this->getCommonList($is_all_site, $is_display, $site_id, $option,$is_real_site);
        return $this->processDisplayList($list,$user_id);
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
    public function getCommonList($is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false){

        $dealModel = new DealModel();
        $dealCuStr = $dealModel->getListBatchImportByDealCrowd(2);
        if (empty($dealCuStr)){
            return array();
        }
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
        // 是否有定制用户
        $is_have_cu_user = true;
        $userIds = $this->getCacheDealUserIds();

        if (empty($user_id) || empty($userIds) || !isset($userIds[$user_id])){
            $is_have_cu_user = false;
        }

        $result = array();
        $is_hava_zx = false;
        // 排序从小到大按照deal_status ,repaly_time ,income_fee_rate,id
        $sort_deal_status = array();
        $sort_repay_time = array();
        $sort_income_fee_rate = array();
        $sort_id = array();
        foreach($list as $key => $deal){
            $isDealCu = $this->isDealCuUser($deal['id']);
            if ($is_have_cu_user) {
                $ret = $this->customUsermodel->getDealOneUser($deal['id'], $user_id);

            }else{
                $ret = false;
                if ($is_hava_zx && $isDealCu == false){
                    $ret = true;
                }
            }
            if (empty($ret) && $isDealCu == false){
                $ret = $this->canLoanZx($user_id,$is_enterprise_site);

                if (!empty($ret)){
                    $is_hava_zx = true;
                }
            }
            if (!empty($ret)) {
                $result[$key] = DealModel::instance()->handleDealNew($deal, 1);
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
     * 获取有效的标用户
     */
    public function getDealUserIds(){

        $list =  $this->customUsermodel->getEffectiveStateUserIdsList();
        $ret = array();
        if(!empty($list)){
            foreach($list as $key => $v){
                $ret[$v['user_id']] = $v['user_id'];
            }
        }
        return $ret;
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
        $deal_cu_whitelist = (array)\dict::get('DEAL_CU_WHITELIST_SUB');
        if (!empty($deal_cu_whitelist) && in_array($userId,$deal_cu_whitelist)){
            return true;
        }

        //白名单
        $isWhiteList = UserService::checkBwList('DEAL_CU_WHITE',$userId);
        if($isWhiteList){
            return true;
        }

        //白名单, 批量导入用
        $deal_cu_whitelist = (array)\dict::get('DEAL_CU_WHITELIST');
        if (!empty($deal_cu_whitelist) && in_array($userId,$deal_cu_whitelist)){
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
        //投资过专享
        if(!empty($loadInfo)) {
            $limitMoney = 10000;
            if(!empty($GLOBALS['user_info'])) {
                $user_info = $GLOBALS['user_info'];
            }else {
                $user_info = UserModel::instance()->find($userId);
            }

            $bonus = BonusService::getUsableBonus($userId, false, 0, false, $GLOBALS['user_info']['is_enterprise']);
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

        //$deal_cu_backlist = app_conf("DEAL_CU_BLACKLIST");
        //黑名单
        $isBacklist = UserService::checkBlackList($user_id);
        if ($isBacklist){
            return false;
        }
        // 是否有定制用户
        $is_have_cu_user = true;
        $userIds = $this->getCacheDealUserIds();
        if (empty($user_id) || empty($userIds) || !isset($userIds[$user_id])){
            $is_have_cu_user = false;
        }
        $dealModel = new DealModel();
        $dealCuStr = $dealModel->getListBatchImportByDealCrowd(2);
        if (empty($dealCuStr)){
            return array();
        }
        $option['isHitSupervision'] = $is_show_p2p;
        $option['is_read_deal_custom_user'] = true;
        $list = $dealModel->getDealCustomUserList($is_all_site, $is_display, $site_id, $option,$is_real_site);
        $isShow = false;
        if (!empty($list)){
            foreach($list as $key => $deal){
                if ($is_have_cu_user) {
                    $ret = $this->customUsermodel->getDealOneUser($deal['id'], $user_id);
                }else{
                    if ($this->isDealCuUser($deal['id']) == false) {
                        $ret = $this->canLoanZx($user_id);
                    }
                }
                if (!empty($ret)) {
                    $isShow = true;
                    break;
                }
            }
        }

        return $isShow;
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
        $isBlackList = UserService::checkBwList('DEAL_CU_BLACK',$user_id);
        if($isBlackList){
            return true;
        }

        return false;
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
    public function getEnterpriseDealCustomUserList ($user_id,$is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false){
        if (!is_qiye_site()){
            return false;
        }
        $list = $this->getCommonList($is_all_site, $is_display, $site_id, $option,$is_real_site);

        return $this->processDisplayList($list,$user_id,1);

    }
} // END class

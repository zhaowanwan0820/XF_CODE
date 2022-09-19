<?php

/**
 * UserProfileService.php
 * 
 * Filename: UserProfileService.php
 * Descrition: 
 * Author: yutao@ucfgroup.com
 * Date: 16-6-16 下午3:33
 */

namespace core\service;

use libs\utils\Logger;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Services\TaskService as GTaskService;
use libs\utils\Monitor;
use core\event\UserProfile\BidProfileEvent;
use core\event\UserProfile\CouponProfileEvent;
use core\service\userProfile\UserProfile;
use core\service\userProfile\InvestIndex;
use core\service\userProfile\CommissionIndex;
use core\event\UserProfile\CouponLogAddProfileEvent;
use core\event\UserProfile\CouponPayProfileEvent;
use core\dao\UserProfileModel;
use core\dao\UserImageModel;
use core\service\BonusService;
use NCFGroup\Common\Library\Date\XDateTime;

class UserProfileService extends BaseService {


    const USER_AVATOR_NO_EXIST = "user_avator_no_exist";

    /**
     * 用户统计相关log
     * @param type $params 参数
     * @param type $functionName 函数名
     * @param type $className 类名
     * @param type $flag 日志记录标识
     * @param type $logLevel 日志记录级别
     */
    public function userProfileLog($params, $functionName, $className, $flag = null, $logLevel = Logger::INFO) {
        Logger::wLog(implode(" | ", array($className, $functionName, json_encode($params), $flag)), $logLevel, Logger::FILE, APP_ROOT_PATH . 'log/logger/user_profile_' . date('Ymd') . '.log');
        return;
    }

    /**
     * 投资埋点出调用的分析策略
     * @param type $userId  用户ID
     * @param type $dealId  标ID
     * @param type $money  投资金额
     * @return boolean
     */
    public function bidProfile($userId, $dealId, $money) {
        return true;
        /*  vipslave 慢sql 业务统计功能迁移至理财师中，数据总线统计
        if ($userId <= 0 || $dealId <= 0 || $money <= 0) {
            return false;
        }
        $obj = new GTaskService();
        $event = new BidProfileEvent($userId, $dealId, $money);
        for ($i = 0; $i < 3; $i++) { //失败重试次数
            $result = $obj->doBackground($event, 10, TASK::PRIORITY_NORMAL);
            if ($result) {
                //Monitor::add(self::DISCOUNT_CONSUME_TASK);
                $this->userProfileLog(func_get_args(), __FUNCTION__, __CLASS__, 'bidProfileSuccess');
                return true;
            }
        }
        $this->userProfileLog(func_get_args(), __FUNCTION__, __CLASS__, 'bidProfileFailed');

        return false;
        */
    }

    /**
     * 更改邀请码的GM调用函数
     * @param type $userId 用户ID
     * @return boolean
     */
    public function updateCouponProfile($userId) {
        return true;

        /*  vipslave 慢sql 业务统计功能迁移至理财师中，数据总线统计
        if (empty($userId)) {
            return false;
        }
        $obj = new GTaskService();
        $event = new CouponProfileEvent($userId);
        for ($i = 0; $i < 3; $i++) { //失败重试次数
            $result = $obj->doBackground($event, 10, TASK::PRIORITY_NORMAL);
            if ($result) {
                $this->userProfileLog(func_get_args(), __FUNCTION__, __CLASS__, 'updateCouponProfileSuccess');
                //Monitor::add(self::DISCOUNT_CONSUME_TASK);
                return true;
            }
        }
        $this->userProfileLog(func_get_args(), __FUNCTION__, __CLASS__, 'updateCouponProfileFailed');
        return false;
        */
    }

    /**
     * couponLog 增加记录埋点触发用户统计
     * @param type $userId 用户ID
     * @param type $money  金额
     * @return boolean
     */
    public function addCouponLogProfile($userId, $money, $isTzd = false) {
        return true;

        /*  vipslave 慢sql 业务统计功能迁移至理财师中，数据总线统计
        if (empty($userId)) {
            return false;
        }
        $obj = new GTaskService();
        $event = new CouponLogAddProfileEvent($userId, $money, $isTzd);
        for ($i = 0; $i < 3; $i++) { //失败重试次数
            $result = $obj->doBackground($event, 10, TASK::PRIORITY_NORMAL, XDateTime::now()->addSecond(5));
            if ($result) {
                $this->userProfileLog(func_get_args(), __FUNCTION__, __CLASS__, 'addCouponLogProfileSuccess');
                return true;
            }
        }

        $this->userProfileLog(func_get_args(), __FUNCTION__, __CLASS__, 'addCouponLogProfileFailed');
        return false;
        */
    }

    /**
     * 返利触发用户统计
     * @param type $userId 用户ID
     * @param type $money  金额
     * @return boolean
     */
    public function payCouponProfile($dealId, $isTzd = false) {
        return true;

        /*  vipslave 慢sql 业务统计功能迁移至理财师中，数据总线统计
        if (empty($dealId)) {
            return true;
        }
        $obj = new GTaskService();
        $dealLoadModel = new \core\dao\DealLoadModel();
        $userList = $dealLoadModel->getDealLoanUserList($dealId);

        $err = array();
        foreach ($userList as $one) {
            $event = new CouponPayProfileEvent($one['user_id'], $one['m'], $isTzd);
            for ($i = 0; $i < 3; $i++) { //失败重试次数
                $result = $obj->doBackground($event, 10, TASK::PRIORITY_NORMAL, XDateTime::now()->addSecond(10));
                if ($result) {
                    $this->userProfileLog(array('dealId' => $dealId, 'userId' => $one['user_id']), __FUNCTION__, __CLASS__, 'payCouponProfileSuccess');
                } else {
                    $err[] = $one['user_id'];
                }
            }
        }
        if (!empty($err)) {
            $this->userProfileLog(array('dealId' => $dealId, 'errUserIds' => $err), __FUNCTION__, __CLASS__, 'payCouponProfileFailed');
            return false;
        }
        return true;
        */
    }

    /**
     * 批量刷数据，离线
     */
    public function flushData($startUserId, $toUserId, $key = 'all') {

        $userProfile = new UserProfile();
        $userProfile->fullDataFlush($startUserId, $toUserId, $key);
    }

    /**
     * 针对单一用户刷数据，可在线
     */
    public function flushSingleUserData($userId, $key = 'all') {
        $userProfile = new UserProfile();
        $ret = $userProfile->sigleDataFlush($userId, $key);
        return $ret;
    }

    /**
     * 新投资进来，接口，通过 gm 增量更新
     */
    public function updateInvest($userId, $dealId, $money) {
        $ret = $this->flushSingleUserData($userId, 'invest');
        return $ret;
        /*
          $investIndex = new InvestIndex();
          $ret = $investIndex->newInvest(array('user_id' => $userId, 'deal_id' => $dealId, 'money' => $money));
          return $ret;
         */
    }

    /**
     * 新的返利记录，gm 增量更新 新的返利记录
     */
    public function updateCouponLog($userId, $money, $isTzd = false) {
        $ret = $this->flushSingleUserData($userId, 'commission');
        return $ret;
        /*
          $commissionIndex = new CommissionIndex();
          $ret = $commissionIndex->newProfit($userId, $money, $isTzd);
          return $ret;
         */
    }

    /**
     * 发放返利，通过 gm 增量更新
     */
    public function payCouponLog($userId, $money, $isTzd = false) {
        $ret = $this->flushSingleUserData($userId, 'commission');
        return $ret;
        /*
          $commissionIndex = new CommissionIndex();
          $ret = $commissionIndex->payCommission($userId, $money, $isTzd);
          return $ret;
         */
    }

    /**
     * 修改邀请码会重新跑这个用户的所有返利情况，通过 gm 增量更新
     */
    public function changeCoupon($userId) {
        $userProfile = new UserProfile();
        $ret = $userProfile->sigleDataFlush($userId /* , 'commission' */);
        return $ret;
    }

    /**
     * 根据在投金额排序的客户列表(如过在投为0，就是空仓/未invest)
     */
    public function getInvestCustomers($referUserId, $type = 0) {
        $upm = new UserProfileModel();
        $ret = $upm->getInvestCustomers($referUserId, $type);
        $investList = array();
        $emptyList = array();
        foreach ($ret as $one) {
            $norepay_principal = floatval($one['norepay_principal']);
            if (!empty($norepay_principal)) {
                $investList[] = $one['user_id'];
            } else {
                $emptyList[] = $one['user_id'];
            }
        }
        return array('empty' => $emptyList, 'investList' => $investList);
    }

    public function getUnRealNameCustomers($referUserId,$offset,$count) {
        $upm = new UserProfileModel();
        $ret = $upm->getUnRealNameCustomers($referUserId,$offset,$count);
        return $ret;
    }


    public function getUserHeadImg($mobile) {
        $bbs = new BonusService();
        $info = $bbs->getWeixinInfoByMobile($mobile);
        return $info['user_info'];
    }

    /**
     * 输出的时候刷新头像以及时间的数据
     */
    public function flushCfpData($data) {

        if(!is_array($data) || empty($data)){
            return array();
        }

        foreach ($data as &$one) {
            $userId = $one['id'];
            $mobile = $one['mobile'];

            $avator = '';
            // 从img库获取头像
            $avator = $this->getUserHeadImgFromUserImg($userId);
            if($avator == self::USER_AVATOR_NO_EXIST){
                // 没有的话从微信里面拿一下
                $avator = $this->getLcsUserHeadImg($mobile);
                if($avator == self::USER_AVATOR_NO_EXIST){
                    // 还没有就算了
                    $avator = '';
                }
            }else{
                if( strpos($avator,'http') !== 0 ){
                    // 从img库拿的头像需要拼一个静态地址
                    $avator = 'http:'.(isset($GLOBALS['sys_config']['STATIC_HOST']) ? $GLOBALS['sys_config']['STATIC_HOST'] : '//static.firstp2p.com').'/' .$avator;
                }
            }


            $one['avator'] = $avator;
            $one['create_time_str'] = date('Y-m-d H:i:s', $one['create_time'] + 28800);
        }
        return $data;
    }

    private function getLcsUserHeadImg($mobile) {
        if(empty($mobile)){
            return self::USER_AVATOR_NO_EXIST;
        }
        $bbs = new BonusService();
        $info = $bbs->getWeixinInfoByMobile($mobile);
        if(!empty($info['user_info']) && !empty($info['user_info']['headimgurl']) ){
            return $info['user_info']['headimgurl'];
        }else{
            return self::USER_AVATOR_NO_EXIST;
        }
    }

    /**
    * 根据userId查询用户在头像服务器中的地址
    */
    private function getUserHeadImgFromUserImg($userId){
        $userImgModel = new UserImageModel();
        $avatorInfo = $userImgModel->getUserImageInfo($userId);
        if(empty($avatorInfo)){
            return self::USER_AVATOR_NO_EXIST;
        }else{
            return $avatorInfo['attachment'];
        }
    }


    public function getUserProfileByIds($ids, $orderBy = 'all_invest', $offset = 0, $count = 10) {
        $upm = new UserProfileModel();
        $profileList = $upm->getListByUserIds($ids, $orderBy, $offset, $count);
        $userId = array();
        foreach ($profileList as &$one) {
            $userBase = $upm->getBaseInfoByUserIds($one['user_id']);
            $one = array_merge($userBase, $one);
            //$userId[] = $one['id'];
        }
        /* 不补数据了。送的功能出了个bug。尴尬
          $diff = array_diff($ids,$userId);
          foreach($diff as $oneUid){
          $userBase = $upm->getBaseInfoByUserIds($oneUid);
          $profileList[] = array_merge($userBase,array('user_id'=>$oneUid,'show_str'=>0));
          }
         */
        return $profileList;
    }

    public function getListByRefererUserId($referUserId, $orderBy = 'all_invest', $offset = 0, $count = 10) {
        $upm = new UserProfileModel();
        $profileList = $upm->getListByRefererUserId($referUserId, $orderBy, $offset, $count);
        foreach ($profileList as &$one) {
            $userBase = $upm->getBaseInfoByUserIds($one['user_id']);
            $one = array_merge($userBase, $one);
        }
        $total = $upm->getAllCustomersByReferUserId($referUserId);
        return array('data'=>$profileList,'total'=>$total);
    }

    public function getUserProfileByUserIdReferUserId($userId, $referUserId) {
        $upm = new UserProfileModel();
        $ret = $upm->getUserProfileByUserIdReferUserId($userId, $referUserId);
        return $ret;
    }

    public function getUsersByMoney($referUserId, $offset = 0, $count = 10) {
        $upm = new UserProfileModel();
        $ret = $upm->getUsersByMoney($referUserId, $offset, $count);
        return $ret;
    }

    public function getUsersByMoneyByUids($uids,$offset,$count) {
        $upm = new UserProfileModel();
        $ret = $upm->getUsersByMoneyByUids($uids,$offset,$count);
        return $ret;
    }

    public function getUsersByRepayTime($referUserId, $offset = 0, $count = 10) {
        $upm = new UserProfileModel();
        $profileList = $upm->getUsersByRepayTime($referUserId, $offset, $count);
        foreach ($profileList as &$one) {
            $userBase = $upm->getBaseInfoByUserIds($one['user_id']);
            $one = array_merge($userBase, $one);
        }
        $total = $upm->getAllCustomersByReferUserId($referUserId);
        return array('data'=>$profileList,'total'=>$total);
    }

    public function getUsersByRepayTimeByUids($uids, $offset = 0, $count = 10) {
        $upm = new UserProfileModel();
        $profileList = $upm->getUsersByRepayTimeByUids($uids, $offset, $count);
        foreach ($profileList['data'] as &$one) {
            $userBase = $upm->getBaseInfoByUserIds($one['user_id']);
            $one = array_merge($userBase, $one);
        }
        return $profileList;
    }

    public function flushCustomersByReferUserId($referUserId) {
        $upm = new UserProfileModel();
        $userIds = $upm->getCustomersByReferUserId($referUserId);
        foreach ($userIds as $one) {
            $ret = $this->updateCouponProfile($one['user_id']);
            if (empty($ret)) {
                $err[] = $ret;
            }
        }
        return $err;
    }

}

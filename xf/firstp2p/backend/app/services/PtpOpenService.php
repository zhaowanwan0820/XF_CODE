<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoUser;
use core\service\UserTagService;
use core\service\UserService;
use core\service\DealLoadService;
use core\service\AdunionDealService;
use core\service\O2OService;
use core\dao\CouponBindModel;
use core\dao\UserModel;
use core\dao\OtoAllowanceLogModel;
use core\dao\OtoAcquireLogModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\CouponService;
use NCFGroup\Protos\Ptp\RequestSmsg;
use NCFGroup\Protos\Open\ProtoSendSms;
use NCFGroup\Protos\Ptp\RequestUser;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\RequestDealLoadDetail;
use NCFGroup\Ptp\daos\AdunionDealDAO;
use NCFGroup\Protos\Ptp\RequestUserListInfo;
use NCFGroup\Protos\Ptp\ResponseUserListInfo;
use NCFGroup\Protos\Ptp\RequestCoupon;
use libs\utils\Logger;

use NCFGroup\Protos\Ptp\RequestUserList;
use NCFGroup\Protos\Ptp\ResponseUserList;
use NCFGroup\Ptp\daos\UserDAO;
use core\service\UserBankcardService;
use core\service\BankService;
use NCFGroup\Ptp\daos\UserTagDAO;
use NCFGroup\Ptp\daos\UserTagRelationDAO;

/**
 * PtpOpenService
 * 开放平台相关service
 * @uses ServiceBase
 * @package default
 */
set_time_limit(0);

class PtpOpenService extends ServiceBase {

    public function sendSmsgByOpen(RequestSmsg $msg){
        //获取用户的UserID，根据用户的ID，获取用户的手机号信息
    }

    // 核销系统增加红包发放记录数据同步
    public function getBonusListFromAllowanceLog(SimpleRequestBase $request) {
        $userId = $request->getParam('userId');
        $response = new ResponseBase();
        if (empty($userId)) {
            $response->data = array();
            return $response;
        }

        $columns = 'to_user_id,acquire_log_id,deal_load_id,create_time,allowance_money,allowance_coupon,allowance_id';
        $res = array();
        if (!is_array($userId)) {
            $ids = explode(',', $userId);
        } else {
            $ids = $userId;
        }

        foreach ($ids as $id) {
            // 取最新的100条数据
            $cond = '`to_user_id`='.intval($id)
                .' AND `allowance_type`='.CouponGroupEnum::ALLOWANCE_TYPE_BONUS
                .' AND `acquire_log_id` > 0 ORDER BY id DESC LIMIT 30';

            $res[$id] = array();
            $list = OtoAllowanceLogModel::instance()->getAllowanceLogByCond($cond, $columns);
            foreach ($list as $item) {
                $record = array();
                $record['deal_load_id'] = $item['deal_load_id'];
                $record['create_time'] = $item['create_time'];
                $record['allowance_money'] = $item['allowance_money'];
                $record['bonus_id'] = $item['allowance_id'];
    
                $acquireLog = OtoAcquireLogModel::instance()->findByViaSlave('id='.$item['acquire_log_id']);
                $record['trigger_mode'] = $acquireLog['trigger_mode'];
    
                $res[$id][] = $record;
            }
        }

        $response->data = $res;
        return $response;
    }

    //Cpa数据的实时列表展示，直接从p2p数据库分页进行拉取,无时间范围，目前没有使用
    public function getCpaAllowanceLog(SimpleRequestBase $req)
    {
        $data = $req->getParamArray();
        $siteId = $data['siteId'];
        $toUserId = $data['toUserId'];
        $actionType = $data['actionType'];
        $allowanceType = $data['allowanceType'];
        $allowanceCoupon = $data['allowanceCoupon'];
        $pageNo = $data['pageNo'];
        $pageSize = $data['pageSize'];
        $sum = intval($data['sum']);
        $o2o = new O2OService();
        $data = $o2o->getAllowanceLog($siteId, $toUserId, $actionType, $allowanceType, $allowanceCoupon, $pageNo, $pageSize, $sum);
        if(!empty($data)){
            //根据log中的deal_load_id,查询用户的投资记录
            //分页查询，逐条查询，后期因为性能问题，修改为批量查询
            foreach($data['list'] as &$val){
                $dealId = intval($val['deal_load_id']);
                //触发人的id
                $userId = intval($val['from_user_id']);
                if(!empty($userId)){
                     //根据userId,查询出用户的手机号
                    $userS = new PtpUserService();
                    $pUser = new ProtoUser();
                    $pUser->UserId = intval($userId);
                    $userInfo = $userS->getUserInfoById($pUser);
                    $val['userMobile'] = $userInfo->getMobile();
                    $val['realName'] = $userInfo->getRealName();
                    $val['sex'] = $userInfo->getSex();
                }
                //根据dealId,查询投资信息
                if(!empty($dealId)){
                    $dealLoad = new DealLoadService();
                    $dealInfo = $dealLoad->getDealLoadDetailForOpen($dealId, true);
                    if(!empty($dealInfo)){
                        //Cap 标的详情
                        $val['dealName'] = $dealInfo['deal']['name'];
                    }
                }
            }
        }

        return $data;
    }


    //Cpa任务脚本数据采集
    //频率是每天一次，采集相应站点，昨天的数据（分页进行数据拉取）
    public function getCpaAllowanceLogByTime(SimpleRequestBase $req)
    {
        $data = $req->getParamArray();
        $siteId = $data['siteId'];
        $fromUserId = $data['fromUserId'];
        $actionType = $data['actionType'];
        $allowanceType = $data['allowanceType'];
        $allowanceCoupon = $data['allowanceCoupon'];
        $pageNo = $data['pageNo'];
        $pageSize = $data['pageSize'];
        $beginTime = $data['beginTime'];
        $endTime = $data['endTime'];
        $back = $data['back'];
        $mobile = $data['mobile'];

        if (!empty($fromUserId) || !empty($mobile)) {
            $userService = new UserService();
            $userInfo = $userService->getUserByUidOrMobile($fromUserId, $mobile);
            if (empty($userInfo)) {
                return array('count' => 0, 'list' => false);
            }
            $fromUserId = $userInfo['id'];
        }
        $o2o = new O2OService();
        $data = $o2o->getAllowanceLogByTime($siteId, $fromUserId, $actionType, $allowanceType, $allowanceCoupon, $beginTime, $endTime, $pageNo, $pageSize, $back);
        if(!empty($data['list'])){
           //查询采用IN查询
            $userIdArray = array();
            $dealLoadIdArray = array();
            foreach($data['list'] as &$val){
                $val['euid'] = '';
                $dealLoadIdArray[]= intval($val['deal_load_id']);
                //触发人的id
                $userIdArray[] = intval($val['from_user_id']);
            }
            $userIdArray = array_unique($userIdArray);
            $dealLoadIdArray = array_unique($dealLoadIdArray);
            //查询投标信息和标的信息
            $dealLoad = new DealLoadService();
            $dealLoadsInfo = $dealLoad->getDealLoadDetailByIDs($dealLoadIdArray);
            //建立映射
            $idToDealLoadsInfo = array();
            foreach($dealLoadsInfo as $dealsInfo){
                $idToDealLoadsInfo[$dealsInfo['id']] = $dealsInfo;
            }
            //根据用户数组去查询用户的信息
            $userService = new UserService();
            $userInfoArray = $userService->getUserInfoByIds($userIdArray, 'id, mobile, real_name, sex');
            //根据deal_load_id 查询出euid
            //从firstp2p_adunion_deal表中，拉取euid信息
            $orders = AdunionDealDAO::getOrderInfoByUids($userIdArray);
            if(!empty($orders)){
               foreach($orders as $valOrder){
                   $euids[$valOrder['uid']] = $valOrder['euid'];
               }
            }
            foreach($data['list'] as &$valList){
                $userId = intval($valList['from_user_id']);
                if(!empty($userId)){
                    $valList['userMobile'] = $userInfoArray[$userId]['mobile'];
                    $valList['realName'] = $userInfoArray[$userId]['real_name'];
                    $valList['sex'] = $userInfoArray[$userId]['sex'];
                }
                $valList['euid'] = empty($euids[$userId]) ? '' : $euids[$userId];
                //deal and dealLoad Info
                $dealLoadId = intval($valList['deal_load_id']);
                $valList['siteId'] = $idToDealLoadsInfo[$dealLoadId]['site_id'];
                $valList['money'] = $idToDealLoadsInfo[$dealLoadId]['money'];
                $valList['dealName'] = $idToDealLoadsInfo[$dealLoadId]['deal']['name'];
                $valList['repayTime'] = $idToDealLoadsInfo[$dealLoadId]['deal']['repay_time'];
                $valList['loanType'] = $idToDealLoadsInfo[$dealLoadId]['deal']['loantype'];
            }
        }
        return $data;
    }

    /**
     * @通过手机号直接发送短信
     * @param object $req
     * @return void
     */
    public function openSendSms(ProtoSendSms $req)
    {
        $message    = $req->getMessages();
        $mobileData = $req->getMobiles();
        $logsTitle  = $req->getLogstitle();

        if(strpos($mobileData, ',') == false){
            $res = \SiteApp::init()->sms->send($mobileData,$message);
            \libs\utils\Logger::debug(sprintf($logsTitle, serialize($res), $mobileData, $message));
            return;
        }

        $mobiles = explode(',', $mobileData);
        foreach($mobiles as $val){
            $sendRes[$val] = \SiteApp::init()->sms->send($val,$message);
        }

        \libs\utils\Logger::debug(sprintf($logsTitle, serialize($sendRes), '', $message));
    }

    /*
     * open的红包券发券接口
    */
    public function acquireCoupons(SimpleRequestBase $request){
        $param = $request->getParamArray();
        $users = $param['users'];
        $couponGroupId = $param['couponGroupId'];
        $o2oService = new O2OService();
        $res = $o2oService->acquireCouponsForBatchUsers($users, $couponGroupId);
        return $res;
    }

    /**
     * open的获取红包券红包使用状态接口
     */
    public function getCouponBonusStatus(SimpleRequestBase $request){
        $param = $request->getParamArray();
        $coupons = $param['coupons'];
        $o2oService = new O2OService();
        $res = $o2oService->getCouponBonusStatus($coupons);
        return $res;
    }

    /**
     * 通过euid获取响应的用户信息，此接口被爱耳目等活动
     * 理论上，euid 和 uid 一一对应
     */
    public function getUidsByEuids(SimpleRequestBase $request){
        $euidArr = $request->getParamArray();
        $orders = AdunionDealDAO::getOrderInfoByEuids($euidArr);
        //建立映射，进行返回
        $retArr = array();
        if(!empty($orders)){
            foreach($orders as $val){
                $retArr[$val['euid']] = array($val['uid'], strtotime($val['createdAt']));
            }
        }
        return $retArr;
    }

    public function getUserList(RequestUserList $request){
        $params = $request->getParams();
        $couponService = new \core\service\CouponService();
        $couponInfo = $couponService->checkCoupon($params['invite_code']);
        if (empty($couponInfo)) {
            throw new \Exception('邀请码错误');
        }

        $params['refer_user_id'] = $couponInfo['refer_user_id'];
        $userList = UserDAO::getUserList($params, $request->getPageable());

        $list = array();
        if ($userList['list']) {
            //提取用户ID，查询用户的渠道信息
            $userIdArr = array();
            $euids = array();
            foreach ($userList['list'] as $val){
                $userIdArr[] = $val['id'];
            }

            //从firstp2p_adunion_deal表中，拉取euid信息
            $userIdArr = array_unique($userIdArr);
            $orders = AdunionDealDAO::getOrderInfoByUids($userIdArr);
            if(!empty($orders)){
               foreach($orders as $valOrder){
                   $euids[$valOrder['uid']] = $valOrder['euid'];
               }
            }
            $bankcardList = (new UserBankcardService())->getBankcardByUserIdArr($userIdArr);

            $couponService = new \core\service\CouponService();
            foreach ($userList['list'] as $userInfo) {
                $tmp = array(
                    'userId' => $userInfo['id'],
                    'realName' => $userInfo['realName'],
                    'mobile' => $userInfo['mobile'],
                    'idno' => $userInfo['idno'],
                    'registerTime' => $userInfo['createTime'],
                    'euid' => empty($euids[$userInfo['id']]) ? '' : $euids[$userInfo['id']],
                    'bankNo' => isset($bankcardList[$userInfo['id']]) ? $bankcardList[$userInfo['id']]['bankcard'] : '',
                );
                $list[] = $tmp;
            }
        }

        $response = new ResponseUserList();
        $response->resCode = RPCErrorCode::SUCCESS;
        $response->setList($list);
        $response->setTotal($userList['total']);
        $response->setPageNo($userList['pageNo']);
        $response->setPageSize($userList['pageSize']);
        return $response;
    }

    /**
     * 根据条件获取用户信息列表
     */
    public function getUserListByParams ($request) {
        $params = $request->getParamArray();
        $userList = UserDAO::getUserListByParams($params);

        $list = array();
        if ($userList['list']) {
            //提取用户ID，查询用户的渠道信息
            $userIdArr = array();
            $euids = array();
            foreach ($userList['list'] as $val){
                $userIdArr[] = $val['id'];
            }

            // 从firstp2p_adunion_deal表中，拉取euid信息
            $userIdArr = array_unique($userIdArr);
            $orders = AdunionDealDAO::getOrderInfoByUids($userIdArr);
            if(!empty($orders)){
               foreach($orders as $valOrder){
                   $euids[$valOrder['uid']] = $valOrder['euid'];
               }
            }
            $bankcardList = (new UserBankcardService())->getBankcardByUserIdArr($userIdArr);

            $couponService = new \core\service\CouponService();
            foreach ($userList['list'] as $userInfo) {
                $tmp = array(
                    'userId' => $userInfo['id'],
                    'userName' => $userInfo['realName'],
                    'mobile' => $userInfo['mobile'],
                    'inviteCode' => $userInfo['inviteCode'],
                    'idno' => $userInfo['idno'],
                    'registerTime' => $userInfo['createTime'],
                    'euid' => empty($euids[$userInfo['id']]) ? '' : $euids[$userInfo['id']],
                    'bankNo' => isset($bankcardList[$userInfo['id']]) ? $bankcardList[$userInfo['id']]['bankcard'] : '',
                );
                $list[] = $tmp;
            }
        }

        $response = new ResponseUserList();
        $response->resCode = RPCErrorCode::SUCCESS;
        $response->setList($list);
        $response->setTotal($userList['total']);
        $response->setPageNo($userList['pageNo']);
        $response->setPageSize($userList['pageSize']);
        return $response;
    }

    /**
     * @根据邀请码获取邀请人Id,并根据邀请人id获取被邀请人的手机号
     * @param SimpleRequestBase req
     * @return response
     */
    public function getCodeInviteMobile(RequestCoupon $req)
    {
        $result = (new CouponService())->checkCoupon($req->getCoupon());
        if(!$refUserId = $result['refer_user_id']){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '没有找到对应的邀请人';
            return $response;
        }

        $bindRes = (new CouponBindModel())->getByReferUserId($refUserId, 0, 60000);
        if(empty($bindRes)){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '没有找到被邀请人';
            return $response;
        }

        //通过被邀请人的userId到用户表查找对应的手机号
        $userIdList = $mobileList = array();
        foreach($bindRes as $val) $userIdList[] = $val['user_id'];

        $userInfoList = (new UserModel())->getUserInfoByIDs($userIdList);
        foreach($userInfoList as $val) $mobileList[] = $val['mobile'];

        $response['resCode'] =  RPCErrorCode::SUCCESS;
        $response['resMsg']  = '返回手机号';
        $response['data']    = $mobileList;

        return $response;
    }

     /**
     * 根据用户手机号mobile获取uid,再根据uid获取tag
     * 最后通过tag_name查询tag_id,并判断tag_id是否在tag里
     * @param SimpleRequestBase req
     * @return response
     */
    public function detectUserByMobile(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        $result = array(
            'is_regist'   => 0, //是否注册 0 否 1 是
            'is_bid'      => 0, //是否投资 0 否 1 是
            'bind_coupon' => 0, //绑码信息 0 未绑 1 绑指定码 2 绑其它码
        );

        //是否注册
        $mobile = intval($params['mobile']);
        $response = new ResponseBase();
        $uid = UserDAO::getUidByMobile($mobile);
        if (empty($uid)) {
            $response->result = $result;
            return $response;
        }

        //是否投资
        $tagName = $params['tag_name'];
        $tagId = UserTagDAO::getTagIDByTagName($tagName);
        $tagIds = UserTagRelationDAO::getTagByUid($uid);
        foreach($tagId as $key => $tagItem){
            if (isset($tagIds[$key])) {
                $result['is_bid'] = 1;
            }
        }

        //是否绑码
        $couponBindModel = new \core\dao\CouponBindModel();
        $bindInfo = current($couponBindModel->getByUserIds(array($uid)));
        if (!empty($bindInfo)) {
            $result['bind_coupon'] = strtoupper($bindInfo['short_alias']) == strtoupper($params['short_alias']) ? 1 : 2;
        }

        $result['is_regist'] = 1;
        $response->result = $result;
        return $response;
    }

    public function getDealLoadById($request) {
        $params = $request->getParamArray();
        $service = new DealLoadService();
        $dealLoadInfo = $service->findLoadInfo($params['id']);
        return empty($dealLoadInfo) ? array() : $dealLoadInfo->_row;
    }

    public function getDealLoadListByIds($request) {
        $params = $request->getParamArray();
        $service = new DealLoadService();
        $dealLoadList = $service->getDealLoadListByIds($params['dealLoadIds']);
        $response = new ResponseBase();
        $response->data = empty($dealLoadList) ? array() : $dealLoadList;
        return $response;
    }

    /**
     * @根据userId获取用户信息
     * @param ProtoUser $req
     * @return array
     */
    public function getUserIdMobile(SimpleRequestBase $req)
    {
        $data = $req->getParamArray();
        $userInfo = (new UserService())->getByFieldUser($data['userId'], '*');

        $res = $userInfo->_row;
        if (!empty($res)) {
            $bankcardInfo = (new UserBankcardService())->getBankcard($data['userId']);
            $res['bankinfo'] = $bankcardInfo->_row;
        }

        return $res;
    }

    /**
     * @根据用户Id和邀请码获取用户的euid
     * @param SimpleRequestBase $req
     * @param return array
     */
    public function getUserAdUnionInfo(SimpleRequestBase $req)
    {
        $data = $req->getParamArray();
        $adUnionInfo = (new AdunionDealService())->getUserAdUnionInfo($data['userId'], $data['invtionCode']);
        return $adUnionInfo->_row;
    }

    public function getEuidInfoByUids($request) {
        $euidList = array();
        $params = $request->getParamArray();
        $result = AdunionDealDAO::getEuidInfoByIds($params['uids'], $params['coupon']);
        foreach ((array)$result as $item) {
            $euidList[$item['uid']] = $item;
        }

        $response = new ResponseBase();
        $response->euidList = $euidList;

        return $response;
    }

    public function getUserAccountInfo(SimpleRequestBase $req)
    {
        $data = $req->getParamArray();

        $userInfo = (new UserService())->getUserAccountInfo($data['userId']);

        $response = new ResponseBase();
        $response->userInfo = $userInfo;

        return $response;
    }

    /**
     * 从广告联盟表中获取注册用户
     */
    public function getRegistListForWdzj($request) {
        $params = $request->getParamArray();
        $response = new ResponseBase();

        // 没有邀请码
        $response->errno = -1;
        if (empty($params['cn'])) {
            return $response;
        }

        // 查广告联盟表
        $response->errno = 0;
        $regList = AdunionDealDAO::getRegistList($params);
        if (empty($regList['userList'])) {
            $response->data  = $regList;
            return $response;
        }

        // 查用户表
        $uids = array_keys($regList['userList']);
        $userList = UserDAO::getUserInfoByIds($uids);

        // 赋值
        $return = [];
        foreach ($regList['userList'] as $uid => $item) {
            $return[] = [
                'uuid' => $item['euid'],
                'platUserId' => numTo32($uid),
                'platUserPhone' => $userList[$uid]['mobile'],
                'regTime' => date("Y-m-d H:i:s", $userList[$uid]['create_time'])
            ];
        }

        $regList['userList'] = $return;
        $response->data = $regList;

        return $response;
    }

}

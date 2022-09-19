<?php

namespace core\service\oto;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\CouponService;
use core\service\oto\O2ORpcService;
use core\service\RemoteTagService;
use core\service\UserTagService;
use core\service\oto\O2OUtils;
use NCFGroup\Protos\O2O\RequestGetCouponGroup;
use NCFGroup\Protos\O2O\RequestGetCouponGroupListByTrigger;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\dao\UserModel;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\DealTagModel;
use core\dao\OtoAcquireLogModel;
use core\dao\CompoundRedemptionApplyModel;
use core\exception\O2OException;
use core\service\UserService;
use NCFGroup\Common\Library\Idworker;
use core\event\O2ORetryEvent;
use NCFGroup\Common\Library\Date\XDateTime;
use core\service\CouponBindService;
use NCFGroup\Common\Library\Msgbus;

class O2OCouponGroupService extends O2ORpcService {
    //还款方式
    const LOAN_TYPE_5 = 5;//按天一次性还款
    const LOAN_TYPE_BY_CROWDFUNDING = 7; // 公益标

    static $pushMsgActions = array(
        CouponGroupEnum::TRIGGER_REGISTER,
        CouponGroupEnum::TRIGGER_FIRST_BINDCARD
    );

    /**
     * 券组上下线操作
     */
    public function updateCouponGroupStatus($couponGroupId, $status) {

        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(
                array(
                    'ids' => array($couponGroupId),
                    'status' => $status,
                    'unavailableTime' => false,
                    'availableTime' => false
                )
            );

            $response = $this->requestO2O('NCFGroup\O2O\Services\CouponGroup','batchUpdateGroupStatus', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return true;
    }


    /**
     * 生成的o2o触发记录
     * @param $userId int 用户id
     * @param $action int 触发动作
     * @param $token string 订单号，触发唯一凭证
     * @param $dealType int 交易类型
     * @param $dealLoadId int 交易id
     * @param $triggerType int 触发类型
     * @param $extra array 额外信息
     * @return int|false
     */
    public function addTriggerLog(
        $userId,
        $action,
        $token,
        $dealType = CouponGroupEnum::CONSUME_TYPE_P2P,
        $dealLoadId = 0,
        $triggerType = CouponGroupEnum::TRIGGER_TYPE_P2P,
        $extra = array()
    ) {
        try {
            // 参数校验
            if (empty($userId) || !is_numeric($userId) || $userId < 0) {
                throw new O2OException('用户id不正确');
            }

            // O2O触发列表，异步执行，不需要同步得到结果
            $params = array($userId, $action, $dealLoadId, $dealType);
            $event = new O2ORetryEvent('getCouponGroupList', $params);
            $taskObj = new GTaskService();

            // 执行时间，放在五分钟之后，这里是为了留一个补偿机会
            $executeTime = XDateTime::now();
            $executeTime = $executeTime->addMinute(5);
            $taskId = $taskObj->doBackground($event, 3, 'NORMAL', $executeTime);
            if (!$taskId) {
                // 记录错误日志
                PaymentApi::log('O2OService::getCouponGroupList add task failed, data:'.json_encode($params), Logger::ERR);
            }

            $message = array(
                'userId'=>$userId,
                'createTime'=>$extra['createTime']
            );

            $topic = false;
            // 绑定银行卡
            if ($action == CouponGroupEnum::TRIGGER_FIRST_BINDCARD) {
                $topic = 'bind_card';
            } else if ($action == CouponGroupEnum::TRIGGER_REGISTER) {
                $topic = 'user_register';
                $message['cn'] = isset($extra['invite_code']) ? $extra['invite_code'] : '';
                $message['referUserId'] = isset($extra['refer_user_id']) ? $extra['refer_user_id'] : 0;
            } else if (in_array($action, CouponGroupEnum::$CHARGE_TRIGGER))  {
                $topic = 'user_recharge';
                $message['channel'] = $action==CouponGroupEnum::TRIGGER_ONLINE_CHARGE? 1 : 2;
                $message['amount'] = $extra['deal_money'];
                $message['orderId'] = $dealLoadId;
            } else if (in_array($action, CouponGroupEnum::$TRIGGER_DEAL_MODES)) {
                $topicMap = array(
                    CouponGroupEnum::CONSUME_TYPE_ZHUANXIANG => 'deal_zx',
                    CouponGroupEnum::CONSUME_TYPE_P2P => 'deal_p2p'
                );

                $topic = $topicMap[$dealType];
                $message['dealBidDays'] = $extra['dealBidDays'];
                $message['amount'] = $extra['deal_money'];
                $message['annualAmount'] = $extra['deal_annual_amount'];
                $message['dealTag'] = $extra['dealTag'];
                $message['discountId'] = $extra['discountId'];
                $message['bonusAmount'] = $extra['bonusAmount'];
                $message['inviter'] = $extra['inviter'];
                $message['orderId'] = $extra['orderId'];
                $message['dealType'] = $extra['deal_type']; // 这里是标的deal_type字段
                $message['sourceType'] = $extra['sourceType']; // 投资来源
                $hasFirstBid = OtoAcquireLogModel::instance()->hasFirstBid($userId, CouponGroupEnum::TRIGGER_FIRST_DOBID);
                $message['isFirstBid'] = $hasFirstBid ? false : true;

                // 服务码升级后，邀请人和服务人都通过统一方法获取
                $couponBindService = new CouponBindService();
                $bindInfo = $couponBindService->getByUserId($userId);
                $inviteUserId = 0;
                if ($bindInfo) {
                    $inviteUserId = $bindInfo['invite_user_id'];
                }

                $message['referUserId'] = $inviteUserId;
            }
            // 发送相关消息到队列
            if ($topic) {
                Msgbus::instance()->produce($topic, $message);
            }

            $params = array();
            $params['userId'] = intval($userId);
            $params['triggerMode'] = intval($action);
            $params['triggerType'] = intval($triggerType);
            $params['dealType'] = intval($dealType);
            $params['dealLoadId'] = $dealLoadId;
            $params['expireTime'] = time() + 7 * 3600; // 默认是7天的有效期
            $params['extraInfo'] = $extra;
            $params['giftToken'] = trim($token);

            $request = new SimpleRequestBase();
            $request->setParamArray($params);
            $response = $this->requestO2O('NCFGroup\O2O\Services\CouponAcquireLog', 'triggerOrder', $request);
            return intval($response['data']);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * getCouponGroupList
     * 根据用户动作从o2o侧获取优惠券组
     *
     * @param $userId int 用户id
     * @param $action int 触发动作
     * @param $dealLoadId int 交易id
     * @param $dealType int 交易类型
     * @access public
     * @return array
     */
    public function getCouponTriggerList($userId, $action, $dealLoadId = 0, $dealType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        // 记录请求参数
        $params = array(
            'userId'=>$userId,
            'action'=>$action,
            'dealLoadId'=>$dealLoadId,
            'dealType'=>$dealType
        );
        PaymentApi::log("O2OService.getCouponTriggerList, params: ".json_encode($params), Logger::INFO);

        // popup弹出礼券列表，event弹出活动引导
        $res = array('popup'=>array(), 'event'=>array());
        try {
            // 获取用户券信息
            $giftInfo = OtoAcquireLogModel::instance()->getGiftInfo($userId, $action, $dealLoadId, $dealType);
            if (empty($giftInfo)) {
                // 没有落单记录，表示没有触发机会
                return $res;
            }

            // 已经存在结果了，且为空，就不用再请求了, 或领取礼券了
            if ($giftInfo['request_status'] == OtoAcquireLogModel::REQUEST_STATUS_EMPTY
                || $giftInfo['gift_id'] != 0) {
                return $res;
            }

            // 先判断是否有值
            // 注册走主库，不然从库可能查不到用户
            $userId = $giftInfo['user_id'];
            $action = $giftInfo['o2o_trigger_mode'];
            $dealLoadId = $giftInfo['deal_load_id'];
            // siteId改为从extra_info的落单信息中获取，避免多个入口更新siteId
            $siteId = isset($giftInfo['extra_info']['site_id']) ? $giftInfo['extra_info']['site_id'] : \libs\utils\Site::getId();
            if (!empty($giftInfo['coupon_group_ids'])) {
                // 已经触发了，或者为直推礼券
                $couponGroupIds = explode(',', $giftInfo['coupon_group_ids']);
                if (empty($couponGroupIds)) {
                    throw new O2OException('coupon_group_ids值异常', O2OException::CODE_P2P_ERROR);
                }

                // 这里只用更新popup
                $res['popup'] = $this->getCouponGroupListByTriggerAndCached($giftInfo, $couponGroupIds);
                return $res;
            }

            $slave = ($action == CouponGroupEnum::TRIGGER_REGISTER) ? false : true;
            $userInfo = UserModel::instance()->find($userId, 'id, create_time, refer_user_id, group_id', $slave);
            if (empty($userInfo)) {
                throw new O2OException($userId.'用户不存在', O2OException::CODE_P2P_ERROR);
            }

            // 触发类型
            $triggerType = isset($giftInfo['extra_info']['trigger_type']) ? $giftInfo['extra_info']['trigger_type']
                : CouponGroupEnum::TRIGGER_TYPE_P2P;

            // 交易类型
            $consumeType = isset($giftInfo['extra_info']['consume_type']) ? $giftInfo['extra_info']['consume_type']
                : CouponGroupEnum::CONSUME_TYPE_P2P;

            // 投资金额和年化
            $money = $giftInfo['extra_info']['deal_money'];
            $annualizedAmount = $giftInfo['extra_info']['deal_annual_amount'];

            // 邀请人id
            $inviteUserId = 0;
            // 服务人id
            $serviceUserId = 0;
            // 服务人标识
            $serviceIdentify = false;

            // 服务码升级后，邀请人和服务人都通过统一方法获取
            $couponBindService = new CouponBindService();
            $bindInfo = $couponBindService->getByUserId($userId);
            if ($bindInfo) {
                $inviteUserId = $bindInfo['invite_user_id'];
                $serviceUserId = $bindInfo['refer_user_id'];
                if ($serviceUserId) {
                    $couponService = new CouponService();
                    $serviceIdentify = $couponService->hasServiceAbility($serviceUserId);//服务标识
                }
            }

            $withdrawTime = 0;
            if (in_array($action, CouponGroupEnum::$CHARGE_TRIGGER)) {
                // 如果是充值触发，需要从acquireLog获取充值金额+提现成功时间
                $withdrawTime = $giftInfo['extra_info']['withdraw_time'];
            }

            $actionTime = $giftInfo['create_time'];
            // 获取投资人的旧tag
            $userTagService = new UserTagService();
            $tags = $userTagService->getTagsViaSlave($userId);
            $userTags = array();
            foreach ($tags as $tag) {
                $userTags[] = $tag['const_name'];
            }

            // 获取投资人的远程tag
            $remoteTagService = new RemoteTagService();
            $remoteUserTags = $remoteTagService->getUserAllTag($userId);

            $inviteUserTags = array();
            $remoteInviteUserTags = array();
            $inviteUserGroupId = '';
            if (!empty($inviteUserId)) {
                // 获取邀请人的旧tag
                $refTags = $userTagService->getTagsViaSlave($inviteUserId);
                foreach ($refTags as $rTag) {
                    $inviteUserTags[] = $rTag['const_name'];
                }

                // 获取邀请人的远程tag
                $remoteInviteUserTags = $remoteTagService->getUserAllTag($inviteUserId);
                $referUserInfo = UserModel::instance()->findViaSlave($inviteUserId, 'group_id');
                $inviteUserGroupId = $referUserInfo['group_id'];
            }

            $serviceUserTags = array();
            $remoteServiceUserTags = array();
            $serviceUserGroupId = '';
            if (!empty($serviceUserId)) {
                // 获取服务人的旧tag
                $serviceTags = $userTagService->getTagsViaSlave($serviceUserId);
                foreach ($serviceTags as $sTag) {
                    $serviceUserTags[] = $sTag['const_name'];
                }

                // 获取服务人的远程tag
                $remoteServiceUserTags = $remoteTagService->getUserAllTag($serviceUserId);
                $serviceUserInfo = UserModel::instance()->findViaSlave($serviceUserId, 'group_id');
                $serviceUserGroupId = $serviceUserInfo['group_id'];
            }

            $tags = array();
            $dealBidDays = 0;
            //兼容旧的标的Tag的处理逻辑，优先使用extra_info的值
            $tags = (isset($giftInfo['extra_info']['dealTag']) && $giftInfo['extra_info']['dealTag']) ? $giftInfo['extra_info']['dealTag'] : $tags;
            // 标的投资天数
            $dealBidDays = isset($giftInfo['extra_info']['dealBidDays']) ? $giftInfo['extra_info']['dealBidDays'] : $dealBidDays;

            // tag+组id的过滤条件放到单独参数中传递给o2o端过滤
            $filter = array();
            $filter['userTags'] = $userTags;
            $filter['referUserTags'] = $inviteUserTags;
            $filter['serviceUserTags'] = $serviceUserTags;
            $filter['remoteUserTags'] = $this->filterRemoteTag($remoteUserTags);
            $filter['remoteReferUserTags'] = $this->filterRemoteTag($remoteInviteUserTags);
            $filter['remoteServiceUserTags'] = $this->filterRemoteTag($remoteServiceUserTags);
            $filter['dealTags'] = $tags;
            $filter['userGroupId'] = $userInfo['group_id'];
            $filter['referUserGroupId'] = $inviteUserGroupId;
            $filter['serviceUserGroupId'] = $serviceUserGroupId;
            $filter['userId'] = $userId;
            $filter['referUserId'] = $inviteUserId;
            $filter['serviceUserId'] = $serviceUserId;
            $filter['serviceIdentify'] = $serviceIdentify; // 服务人服务标识
            // 增加提现成功时间字段
            $filter['withdrawTime'] = $withdrawTime;
            // 增加用户的注册时间
            $filter['userRegisterTime'] = $userInfo['create_time'];
            // 标的投资天数
            $filter['dealBidDays'] = $dealBidDays;

            $request = new RequestGetCouponGroupListByTrigger();
            // 处理action的mapping问题
            $request->setTriggerMode(intval($action));
            $request->setTriggerTime(intval($actionTime));
            $request->setAmount($money);
            $request->setAnnualizedAmount($annualizedAmount);
            $request->setSiteId($siteId);
            $request->setUserId($userId);
            $request->setDealLoadId($dealLoadId);
            $request->setFilter($filter);
            $request->setTriggerType(intval($triggerType));
            $request->setDealType(intval($consumeType));

            $userService = new UserService();
            $userType = $userService->checkEnterpriseUser($userId) ? CouponGroupEnum::USER_TYPE_ENTERPRISE : CouponGroupEnum::USER_TYPE_PERSON;
            $request->setUserType($userType);

            // 请求O2O获取数据
            $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroupListByTrigger', $request);

            // 处理触发返利
            $GLOBALS['db']->startTrans();
            try {
                // 需要实时触发的券组
                $groups = array();
                if (!empty($response['list']['popup'])) {
                    $groups = $response['list']['popup'];
                }

                $couponGroupIds = implode(',', array_keys($groups));
                $data = array('coupon_group_ids' => $couponGroupIds);
                if (empty($groups)) {
                    $data['request_status'] = OtoAcquireLogModel::REQUEST_STATUS_EMPTY;
                } else {
                    $data['request_status'] = OtoAcquireLogModel::REQUEST_STATUS_SUC;
                    $data['expire_time'] = time() + $response['expireTime'];
                }

                // 请求成功，更新相应的值，如果请求O2O成功了，只能更新一次
                $updateRes = OtoAcquireLogModel::instance()->updateById($data, $giftInfo['id']);

                // 处理触发返利的情况
                if (!empty($response['list']['trigger']) && $updateRes == 1) {
                    // 这里不能同步阻塞
                    $this->rebateTriggerAllowance(
                        $userId,
                        $inviteUserId,
                        $dealLoadId,
                        $giftInfo['id'],
                        $response['list']['trigger'],
                        $siteId,
                        $serviceUserId,
                        $triggerType,
                        $consumeType
                    );
                }

                if (!empty($response['list']['event'])) {
                    $res['event'] = $response['list']['event'];
                }

                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                throw $e;
            }

            // 记录返回结果
            PaymentApi::log("O2OService.getCouponTriggerList, couponGroupIds: ".implode(',', array_keys($groups)), Logger::INFO);
            $res['popup'] = $groups;

            return $res;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * getCouponGroupList
     * 根据用户动作从o2o侧获取优惠券组
     *
     * @param $userId int 用户id
     * @param $action int 触发动作
     * @param $dealLoadId int 交易id
     * @param $dealType int 交易类型
     * @access public
     * @return array
     */
    public function getCouponGroupList($userId, $action, $dealLoadId = 0, $dealType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        // 记录请求参数
        $params = array(
            'userId'=>$userId,
            'action'=>$action,
            'dealLoadId'=>$dealLoadId,
            'dealType'=>$dealType
        );
        PaymentApi::log("O2OService.getCouponGroupList, params: ".json_encode($params), Logger::INFO);

        try {
            // 获取用户券信息
            $giftInfo = OtoAcquireLogModel::instance()->getGiftInfo($userId, $action, $dealLoadId, $dealType);
            if (empty($giftInfo)) {
                // 没有落单记录，表示没有触发机会
                return array();
            }

            // 已经存在结果了，且为空，就不用再请求了, 或领取礼券了
            if ($giftInfo['request_status'] == OtoAcquireLogModel::REQUEST_STATUS_EMPTY
                || $giftInfo['gift_id'] != 0) {
                return array();
            }

            // 先判断是否有值
            $groups = array();
            if ($dealType == CouponGroupEnum::CONSUME_TYPE_GOLD) {
                $groups = $this->getCouponGroupListByGoldTrigger($giftInfo);
            } else {
                $groups = $this->getCouponGroupListByTrigger($giftInfo);
            }

            // 记录返回结果
            PaymentApi::log("O2OService.getCouponGroupList, couponGroupIds: ".implode(',', array_keys($groups)), Logger::INFO);
            return $groups;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取缓存的触发列表，直接传递$couponGroupIds进行获取
     */
    private function getCouponGroupListByTriggerAndCached($giftInfo, array $couponGroupIds) {
        $userId = $giftInfo['user_id'];
        $money = $giftInfo['extra_info']['deal_money'];
        $annualizedAmount =  $giftInfo['extra_info']['deal_annual_amount'];
        $dealLoadId = $giftInfo['deal_load_id'];
        // siteId改为从extra_info的落单信息中获取，避免多个入口更新siteId
        $siteId = isset($giftInfo['extra_info']['site_id']) ? $giftInfo['extra_info']['site_id'] : \libs\utils\Site::getId();

        $triggerMode = $giftInfo['o2o_trigger_mode'];
        $request = new RequestGetCouponGroupListByTrigger();
        $request->setTriggerMode(intval($triggerMode));
        $request->setUserId(intval($userId));
        $request->setDealLoadId(intval($dealLoadId));
        $request->setSiteId(intval($siteId));
        $request->setCouponGroupId($couponGroupIds);
        $request->setTriggerTime(time());
        $request->setAmount($money);
        $request->setAnnualizedAmount($annualizedAmount);

        // 从giftInfo里面的extra_info里面获取信息
        // 触发类型
        $triggerType = isset($giftInfo['extra_info']['trigger_type']) ? $giftInfo['extra_info']['trigger_type']
            : CouponGroupEnum::TRIGGER_TYPE_P2P;

        $request->setTriggerType($triggerType);

        // 交易类型
        $consumeType = isset($giftInfo['extra_info']['consume_type']) ? $giftInfo['extra_info']['consume_type']
            : CouponGroupEnum::CONSUME_TYPE_P2P;

        $request->setDealType($consumeType);

        // 请求O2O获取数据
        $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroupListByTrigger', $request);
        // 返回触发后弹出的礼券
        return $response['list']['popup'];
    }

    /**
     * 黄金触发
     * 获取缓存的触发列表，直接传递$couponGroupIds进行获取
     */
    private function getCouponGroupListByGoldTriggerAndCached($giftInfo, array $couponGroupIds) {
        $userId = $giftInfo['user_id'];
        $money = $giftInfo['extra_info']['deal_money'];
        $annualizedAmount =  $giftInfo['extra_info']['deal_annual_amount'];
        $dealLoadId = $giftInfo['deal_load_id'];

        $triggerMode = $giftInfo['o2o_trigger_mode'];
        $request = new RequestGetCouponGroupListByTrigger();
        $request->setTriggerMode(intval($triggerMode));
        $request->setUserId(intval($userId));
        $request->setDealLoadId(intval($dealLoadId));
        $request->setCouponGroupId($couponGroupIds);
        $request->setTriggerTime(time());
        $request->setAmount($money);
        $request->setAnnualizedAmount($annualizedAmount);

        // 从giftInfo里面的extra_info里面获取信息
        // 触发类型
        $triggerType = isset($giftInfo['extra_info']['trigger_type']) ? $giftInfo['extra_info']['trigger_type']
            : CouponGroupEnum::TRIGGER_TYPE_GOLD;

        $request->setTriggerType($triggerType);

        // 交易类型
        $consumeType = isset($giftInfo['extra_info']['consume_type']) ? $giftInfo['extra_info']['consume_type']
            : CouponGroupEnum::CONSUME_TYPE_GOLD;

        $request->setDealType($consumeType);

        // 请求O2O获取数据
        $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroupListByGoldTrigger', $request);
        // 返回触发后弹出的礼券
        return $response['list']['popup'];
    }

    /**
     * 获取触发的券组列表
     */
    private function getCouponGroupListByTrigger($giftInfo) {
        // 注册走主库，不然从库可能查不到用户
        $userId = $giftInfo['user_id'];
        $action = $giftInfo['o2o_trigger_mode'];
        $dealLoadId = $giftInfo['deal_load_id'];
        // siteId改为从extra_info的落单信息中获取，避免多个入口更新siteId
        $siteId = isset($giftInfo['extra_info']['site_id']) ? $giftInfo['extra_info']['site_id'] : \libs\utils\Site::getId();
        $bidSource = isset($giftInfo['extra_info']['bid_source']) ? $giftInfo['extra_info']['bid_source'] : '';

        if ($giftInfo['request_status'] == OtoAcquireLogModel::REQUEST_STATUS_EMPTY) {
            return array();
        }

        if (!empty($giftInfo['coupon_group_ids'])) {
            // 已经触发了，或者为直推礼券
            $couponGroupIds = explode(',', $giftInfo['coupon_group_ids']);
            if (empty($couponGroupIds)) {
                throw new O2OException('coupon_group_ids值异常', O2OException::CODE_P2P_ERROR);
            }

            return $this->getCouponGroupListByTriggerAndCached($giftInfo, $couponGroupIds);
        }

        $slave = ($action == CouponGroupEnum::TRIGGER_REGISTER) ? false : true;
        $userInfo = UserModel::instance()->find($userId, 'id, create_time, refer_user_id, group_id', $slave);
        if (empty($userInfo)) {
            throw new O2OException($userId.'用户不存在', O2OException::CODE_P2P_ERROR);
        }

        // 触发类型
        $triggerType = isset($giftInfo['extra_info']['trigger_type']) ? $giftInfo['extra_info']['trigger_type']
            : CouponGroupEnum::TRIGGER_TYPE_P2P;

        // 交易类型
        $consumeType = isset($giftInfo['extra_info']['consume_type']) ? $giftInfo['extra_info']['consume_type']
            : CouponGroupEnum::CONSUME_TYPE_P2P;

        // 投资金额和年化
        $money = $giftInfo['extra_info']['deal_money'];
        $annualizedAmount = $giftInfo['extra_info']['deal_annual_amount'];

        // 邀请人id
        $inviteUserId = 0;
        // 服务人id
        $serviceUserId = 0;//服务人id和服务标识需要服务中心提供接口
        // 服务人标识
        $serviceIdentify = 0;

        // 服务码升级后，邀请人和服务人都通过统一方法获取
        $couponBindService = new CouponBindService();
        $bindInfo = $couponBindService->getByUserId($userId);
        if ($bindInfo) {
            $inviteUserId = $bindInfo['invite_user_id'];
            $serviceUserId = $bindInfo['refer_user_id'];
            if ($serviceUserId) {
                $couponService = new CouponService();
                $serviceIdentify = $couponService->hasServiceAbility($serviceUserId);//服务标识
            }
        }

        $withdrawTime = 0;
        if (in_array($action, CouponGroupEnum::$CHARGE_TRIGGER)) {
            // 如果是充值触发，需要从acquireLog获取充值金额+提现成功时间
            $withdrawTime = $giftInfo['extra_info']['withdraw_time'];
        }

        $actionTime = $giftInfo['create_time'];
        // 获取投资人的旧tag
        $userTagService = new UserTagService();
        $tags = $userTagService->getTagsViaSlave($userId);
        $userTags = array();
        foreach ($tags as $tag) {
            $userTags[] = $tag['const_name'];
        }

        // 获取投资人的远程tag
        $remoteTagService = new RemoteTagService();
        $remoteUserTags = $remoteTagService->getUserAllTag($userId);

        $inviteUserTags = array();
        $remoteInviteUserTags = array();
        $inviteUserGroupId = '';
        if (!empty($inviteUserId)) {
            // 获取邀请人的旧tag
            $refTags = $userTagService->getTagsViaSlave($inviteUserId);
            foreach ($refTags as $rTag) {
                $inviteUserTags[] = $rTag['const_name'];
            }

            // 获取邀请人的远程tag
            $remoteInviteUserTags = $remoteTagService->getUserAllTag($inviteUserId);
            $inviteUserInfo = UserModel::instance()->findViaSlave($inviteUserId, 'group_id');
            $inviteUserGroupId = $inviteUserInfo['group_id'];
        }

        $serviceUserTags = array();
        $remoteServiceUserTags = array();
        $serviceUserGroupId = '';
        if (!empty($serviceUserId)) {
            // 获取服务人的旧tag
            $serviceTags = $userTagService->getTagsViaSlave($serviceUserId);
            foreach ($serviceTags as $sTag) {
                $serviceUserTags[] = $sTag['const_name'];
            }

            // 获取服务人的远程tag
            $remoteServiceUserTags = $remoteTagService->getUserAllTag($serviceUserId);
            $serviceUserInfo = UserModel::instance()->findViaSlave($serviceUserId, 'group_id');
            $serviceUserGroupId = $serviceUserInfo['group_id'];
        }

        $servicerUserTags = array();
        $remoteServicerUserTags = array();
        $servicerUserGroupId = '';
        if (!empty($servicerUserId)) {
            // 获取服务人的旧tag
            $servicerTags = $userTagService->getTagsViaSlave($servicerUserId);
            foreach ($servicerTags as $sTag) {
                $servicerUserTags[] = $sTag['const_name'];
            }

            // 获取服务人的远程tag
            $remoteServicerUserTags = $remoteTagService->getUserAllTag($servicerUserId);
            $servicerUserInfo = UserModel::instance()->findViaSlave($servicerUserId, 'group_id');
            $servicerUserGroupId = $servicerUserInfo['group_id'];
        }

        $tags = array();
        $dealBidDays = 0;
        //兼容旧的标的Tag的处理逻辑，优先使用extra_info的值
        $tags = (isset($giftInfo['extra_info']['dealTag']) && $giftInfo['extra_info']['dealTag']) ? $giftInfo['extra_info']['dealTag'] : $tags;
        // 用户投资天数
        $dealBidDays = isset($giftInfo['extra_info']['dealBidDays']) ? $giftInfo['extra_info']['dealBidDays'] : $dealBidDays;

        // tag+组id的过滤条件放到单独参数中传递给o2o端过滤
        $filter = array();
        $filter['userTags'] = $userTags;
        $filter['referUserTags'] = $inviteUserTags;
        $filter['serviceUserTags'] = $serviceUserTags;
        $filter['remoteUserTags'] = $this->filterRemoteTag($remoteUserTags);
        $filter['remoteReferUserTags'] = $this->filterRemoteTag($remoteInviteUserTags);
        $filter['remoteServicerUserTags'] = $this->filterRemoteTag($remoteServiceUserTags);
        $filter['dealTags'] = $tags;
        $filter['userGroupId'] = $userInfo['group_id'];
        $filter['referUserGroupId'] = $inviteUserGroupId;
        $filter['serviceUserGroupId'] = $serviceUserGroupId;
        $filter['userId'] = $userId;
        $filter['referUserId'] = $inviteUserId;
        $filter['serviceUserId'] = $serviceUserId;
        $filter['serviceIdentify'] = $serviceIdentify; // 服务人服务标识
        // 增加提现成功时间字段
        $filter['withdrawTime'] = $withdrawTime;
        // 增加用户的注册时间
        $filter['userRegisterTime'] = $userInfo['create_time'];
        // 标的投资天数
        $filter['dealBidDays'] = $dealBidDays;
        // 投资渠道来源
        $filter['bidSource'] = $bidSource;

        $request = new RequestGetCouponGroupListByTrigger();
        // 处理action的mapping问题
        $request->setTriggerMode(intval($action));
        $request->setTriggerTime(intval($actionTime));
        $request->setAmount($money);
        $request->setAnnualizedAmount($annualizedAmount);
        $request->setSiteId($siteId);
        $request->setUserId($userId);
        $request->setDealLoadId($dealLoadId);
        $request->setFilter($filter);
        $request->setTriggerType(intval($triggerType));
        $request->setDealType(intval($consumeType));

        $userService = new UserService();
        $userType = $userService->checkEnterpriseUser($userId)
            ? CouponGroupEnum::USER_TYPE_ENTERPRISE
            : CouponGroupEnum::USER_TYPE_PERSON;

        $request->setUserType($userType);

        // 请求O2O获取数据
        $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroupListByTrigger', $request);
        // 记录这次的触发结果的关键信息
        PaymentApi::log("O2OService.getCouponGroupList, popup: "
            .implode(',', array_keys($response['list']['popup'])).', trigger: '
            .json_encode($response['list']['trigger']), Logger::INFO);

        // 处理触发返利
        $GLOBALS['db']->startTrans();
        try {
            // 需要实时触发的券组
            $groups = array();
            if (!empty($response['list']['popup'])) {
                $groups = $response['list']['popup'];
            }

            $couponGroupIds = implode(',', array_keys($groups));
            $data = array('coupon_group_ids' => $couponGroupIds);
            if (empty($groups)) {
                $data['request_status'] = OtoAcquireLogModel::REQUEST_STATUS_EMPTY;
            } else {
                $data['request_status'] = OtoAcquireLogModel::REQUEST_STATUS_SUC;
                $data['expire_time'] = time() + $response['expireTime'];
            }

            // 请求成功，更新相应的值，如果请求O2O成功了，只能更新一次
            $updateRes = OtoAcquireLogModel::instance()->updateById($data, $giftInfo['id']);

            // 处理触发返利的情况
            if (!empty($response['list']['trigger']) && $updateRes == 1) {
                // 这里不能同步阻塞
                $this->rebateTriggerAllowance($userId, $inviteUserId, $dealLoadId, $giftInfo['id'],
                $response['list']['trigger'], $siteId, $serviceUserId, $triggerType, $consumeType);
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return $groups;
    }

    /**
     * 获取黄金触发的券组列表
     */
    private function getCouponGroupListByGoldTrigger($giftInfo) {
        // 注册走主库，不然从库可能查不到用户
        $userId = $giftInfo['user_id'];
        $action = $giftInfo['o2o_trigger_mode'];
        $dealLoadId = $giftInfo['deal_load_id'];
        // siteId改为从extra_info的落单信息中获取，避免多个入口更新siteId
        $siteId = isset($giftInfo['extra_info']['site_id']) ? $giftInfo['extra_info']['site_id'] : \libs\utils\Site::getId();

        if ($giftInfo['request_status'] == OtoAcquireLogModel::REQUEST_STATUS_EMPTY) {
            return array();
        }

        if (!empty($giftInfo['coupon_group_ids'])) {
            // 已经触发了，或者为直推礼券
            $couponGroupIds = explode(',', $giftInfo['coupon_group_ids']);
            if (empty($couponGroupIds)) {
                throw new O2OException('coupon_group_ids值异常', O2OException::CODE_P2P_ERROR);
            }

            return $this->getCouponGroupListByGoldTriggerAndCached($giftInfo, $couponGroupIds);
        }

        $userInfo = UserModel::instance()->find($userId, 'id, create_time, refer_user_id, group_id', true);
        if (empty($userInfo)) {
            throw new O2OException($userId.'用户不存在', O2OException::CODE_P2P_ERROR);
        }

        // 触发类型
        $triggerType = $giftInfo['extra_info']['trigger_type'];

        // 交易类型
        $consumeType = $giftInfo['extra_info']['consume_type'];

        // 投资金额和年化
        $money = $giftInfo['extra_info']['deal_money'];
        $annualizedAmount = $giftInfo['extra_info']['deal_annual_amount'];

        // 邀请人id
        $referUserId = O2OUtils::getReferId($userId, $action, $dealLoadId, $consumeType);

        $actionTime = $giftInfo['create_time'];

        // tag+组id的过滤条件放到单独参数中传递给o2o端过滤
        $filter = array();
        $filter['dealTags'] = $giftInfo['extra_info']['dealTags'];
        $filter['userGroupId'] = $userInfo['group_id'];
        if (!empty($giftInfo['extra_info']['discountId'])) {
            $filter['discountId'] = $giftInfo['extra_info']['discountId'];      // 券id
        }
        if (!empty($giftInfo['extra_info']['dealBidDays'])) {
            $filter['dealBidDays'] = $giftInfo['extra_info']['dealBidDays'];    // 标的期限
        }

        $referUserInfo = UserModel::instance()->findViaSlave($referUserId, 'group_id');
        $filter['inviteUserGroupId'] = $referUserInfo ? $referUserInfo['group_id'] : 0;

        $request = new RequestGetCouponGroupListByTrigger();
        // 处理action的mapping问题
        $request->setTriggerMode(intval($action));
        $request->setTriggerTime(intval($actionTime));
        $request->setAmount($money);
        $request->setAnnualizedAmount($annualizedAmount);
        $request->setUserId($userId);
        $request->setDealLoadId($dealLoadId);
        $request->setFilter($filter);
        $request->setTriggerType(intval($triggerType));
        $request->setDealType(intval($consumeType));

        // 请求O2O获取数据
        $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroupListByGoldTrigger', $request);
        // 记录这次的触发结果的关键信息
        PaymentApi::log("O2OService.getCouponGroupList, popup: "
            .implode(',', array_keys($response['list']['popup'])).', gold trigger: '
            .json_encode($response['list']['trigger']), Logger::INFO);

        // 处理触发返利
        $GLOBALS['db']->startTrans();
        try {
            // 需要实时触发的券组
            $groups = array();
            if (!empty($response['list']['popup'])) {
                $groups = $response['list']['popup'];
            }

            $couponGroupIds = implode(',', array_keys($groups));
            $data = array('coupon_group_ids' => $couponGroupIds);
            if (empty($groups)) {
                $data['request_status'] = OtoAcquireLogModel::REQUEST_STATUS_EMPTY;
            } else {
                $data['request_status'] = OtoAcquireLogModel::REQUEST_STATUS_SUC;
                $data['expire_time'] = time() + $response['expireTime'];
            }

            // 请求成功，更新相应的值，如果请求O2O成功了，只能更新一次
            $updateRes = OtoAcquireLogModel::instance()->updateById($data, $giftInfo['id']);

            // 处理触发返利的情况
            if (!empty($response['list']['trigger']) && $updateRes == 1) {
                // 这里不能同步阻塞
                $this->rebateTriggerAllowance(
                    $userId,
                    $referUserId,
                    $dealLoadId,
                    $giftInfo['id'],
                    $response['list']['trigger'],
                    $siteId,
                    0,
                    0,
                    CouponGroupEnum::CONSUME_TYPE_GOLD,
                    true
                );
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return $groups;
    }

    /**
     * 触发返利
     * @param $userId int 用户id
     * @param $referUserId int 邀请人id
     * @param $dealLoadId int 交易id
     * @param $acquireLogId int 触发记录id
     * @param $trigger array 触发返利配置
     * @param $siteId int 分站id
     * @param $isGold bool 是否黄金触发业务，默认是false
     * @return int 任务id
     */
    private function rebateTriggerAllowance($userId, $referUserId, $dealLoadId, $acquireLogId, $trigger, $siteId, $serviceUserId = 0, $triggerType = 0, $consumeType = CouponGroupEnum::CONSUME_TYPE_ZHUANXIANG, $isGold=false) {
        if (empty($trigger)) {
            return;
        }

        $orderId = 0;
        if ($isGold) {
            $orderId = Idworker::instance()->getId();
            if (empty($orderId)) {
                throw new \Exception('赠金订单id生成失败');
            }
        }

        //先生成触发返利记录，返利gearman可能执行失败，不太方便追溯问题，这里必须记录taskId
        $event = new \core\event\O2ORebateTriggerEvent(
            $userId,
            $referUserId,
            $acquireLogId,
            $dealLoadId,
            $trigger,
            $siteId,
            $orderId,
            $serviceUserId,
            $triggerType,
            $consumeType
        );
        $taskObj = new GTaskService();
        $taskId = $taskObj->doBackground($event, 10);
        PaymentApi::log("O2OService.O2ORebateTriggerEvent, userId: ".$userId.', referUserId: '.$referUserId
            .', dealLoadId: '.$dealLoadId.', acquireLogId: ' .$acquireLogId.', taskId:'.$taskId. ',serviceUserId:'.$serviceUserId, Logger::INFO);

        return $taskId;
    }

    /**
     * filterRemoteTag传递远程tag时过滤掉不参与筛选的tag键
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2016-06-06
     * @param mixed $remoteUserTags
     * @access private
     * @return void
     */
    private function filterRemoteTag($remoteUserTags) {
        foreach($remoteUserTags as $k => $v) {
            if(in_array($k, CouponGroupEnum::$BLACK_TAG)) {
                unset($remoteUserTags[$k]);
            }
        }
        return $remoteUserTags;
    }

    /**
     * getRebateGold
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-01-14
     * @param mixed $userId
     * @param mixed $amount
     * @param mixed $annualizedAmount
     * @param mixed $discountId
     * @param mixed $dealBidDays
     * @param mixed $triggerTime
     * @access public
     * @return void
     */
    public function getRebateGold($userId, $amount, $annualizedAmount, $discountId, $dealBidDays, $triggerTime) {
        $params = array('userId' => $userId, 'amount' => $amount, 'annualizedAmount' => $annualizedAmount, 'discountId' => $discountId, 'dealBidDays' => $dealBidDays, 'triggerTime' => $triggerTime);
        $request = new SimpleRequestBase();
        $request->setParamArray($params);
        // 请求O2O获取数据
        $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getRebateGold', $request);
        return $response['data'];
    }

    /**
     * getRebateGoldRule获取满赠规则
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-01-14
     * @access public
     * @return void
     */
    public function getRebateGoldRule($dealBidDays) {
        $params = array('dealBidDays' => $dealBidDays);
        $request = new SimpleRequestBase();
        $request->setParamArray($params);
        // 请求O2O获取数据
        $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getRebateGoldRule', $request);
        return $response['data'];
    }
}

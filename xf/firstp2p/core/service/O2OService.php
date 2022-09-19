<?php
/**
 * O2OService class file.
 *
 * @author 芦正帅<luzhengshuai@ucfgroup.com>
 **/

namespace core\service;

use libs\utils\Aes;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Monitor;
use core\dao\UserModel;
use core\dao\DealLoadModel;
use core\dao\OtoAcquireLogModel;
use core\dao\OtoAllowanceLogModel;
use core\dao\PaymentNoticeModel;
use core\dao\JobsModel;
use NCFGroup\Protos\O2O\Enum\CouponEnum;
use NCFGroup\Protos\O2O\RequestAcquireCoupon;
use NCFGroup\Protos\O2O\RequestGetConfirmedCouponList;
use NCFGroup\Protos\O2O\RequestGetConfirmedCouponListForWeb;
use NCFGroup\Protos\O2O\RequestGetConfirmedCouponCount;
use NCFGroup\Protos\O2O\RequestSetCouponWaitStatus;
use NCFGroup\Protos\O2O\RequestSetCouponConfirm;
use NCFGroup\Protos\O2O\RequestGetCouponInfo;
use NCFGroup\Protos\O2O\RequestGetUserCouponList;
use NCFGroup\Protos\O2O\RequestCouponExchange;
use NCFGroup\Protos\O2O\RequestExchangeReuletteCoupon;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Gearman\WxGearManWorker;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\exception\O2OException;
use core\service\oto\O2OUtils;
use core\service\oto\O2ODiscountService;
use core\service\RemoteTagService;
use core\service\CouponService;
use core\service\UserTagService;
use core\service\oto\O2OCouponService;
use core\service\oto\O2OCouponGroupService;
use core\event\O2ORetryEvent;
use core\service\UserService;
use core\dao\BonusModel;
use core\dao\BonusUsedModel;
use core\service\WXBonusService;
use core\dao\DealModel;
use core\service\DtEntranceService;
use NCFGroup\Protos\O2O\Enum\GameEnum;
use core\service\SparowService;
use NCFGroup\Common\Library\ApiService;
use core\service\BwlistService;

/**
 * O2OService
 */
class O2OService extends BaseService {
    //还款方式
    const LOAN_TYPE_5 = 5;//按天一次性还款
    const LOAN_TYPE_BY_CROWDFUNDING = 7; // 公益标
    //标的类型
    const DEAL_TYPE_COMPOUND = 1;//通知贷
    const SIGN_FAILD   = 'DISCOUNT_SIGN_FAILD';

    // aes 加密密匙
    const SIGN_KEY = 'dVlhTXBEbWNNUnE4cUJOSnAyYnY';

    public function __construct() {
        if(!isset($GLOBALS['o2oRpc']) || !($GLOBALS['o2oRpc'] instanceof \NCFGroup\Common\Extensions\RPC\RpcClientAdapter)) {
            $o2oRpcConfig = $GLOBALS['components_config']['components']['rpc']['o2o'];
            $GLOBALS['o2oRpc'] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($o2oRpcConfig['rpcServerUri'],
                $o2oRpcConfig['rpcClientId'], $o2oRpcConfig['rpcSecretKey']);
        }
    }

    static $rulesRequireForm = array(
        CouponGroupEnum::ONLINE_GOODS_REPORT,
        CouponGroupEnum::ONLINE_GOODS_REALTIME,
        CouponGroupEnum::ONLINE_COUPON_REPORT,
        CouponGroupEnum::ONLINE_COUPON_REALTIME
    );

    // 领取兑换接口不触发转账的情况
    static $acquireExchangeTriggerConfirm = array(
        CouponGroupEnum::OFFLINE_LIMIT_USE,
        CouponGroupEnum::OFFLINE_UNLIMIT_USE
    );

    // 充值触发相关
    static $chargeActions = array(
        CouponGroupEnum::TRIGGER_DAY_FIRST_CHARGE,  // 每日首次充值
        CouponGroupEnum::TRIGGER_CHARGE,            // 充值
    );

    public static $error = false;
    public static $errorMsg = '';
    public static $errorCode = 0;
    public static $staticTags = array();

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
        $couponGroupService = new O2OCouponGroupService();
        $res = $couponGroupService->getCouponGroupList($userId, $action, $dealLoadId, $dealType);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $couponGroupService->getErrorMsg();
            self::$errorCode = $couponGroupService->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取优惠券组详情
     */
    public function getCouponGroupInfo($couponGroupId, $userId, $action, $dealLoadId = 0,
                                       $dealType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        try {
            if (empty($couponGroupId)) {
                throw new \Exception('券组id不能为空');
            }

            $annualizedAmount = 0;
            // 对于有交易的记录特殊处理
            if (in_array($action, CouponGroupEnum::$TRIGGER_DEAL_MODES)) {
                $acquireLogInfo = OtoAcquireLogModel::instance()->getGiftInfo($userId, $action, $dealLoadId, $dealType);
                if (empty($acquireLogInfo)) {
                    throw new \Exception('没有落单记录');
                }

                if (!empty($acquireLogInfo['extra_info']['deal_annual_amount'])) {
                    $annualizedAmount = $acquireLogInfo['extra_info']['deal_annual_amount'];
                }
            }

            $request = new SimpleRequestBase();
            $params = array();
            $params['id'] = intval($couponGroupId);
            $params['annualizedAmount'] = floatval($annualizedAmount);
            $request->setParamArray($params);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroup', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        if (empty($response['data'])) {
            return array();
        }

        $groupInfo = $response['data'];
        // 前端针对券触发时间进行验证，有些券是有单独期限的
        $groupInfo['p2pTriggerTime'] = isset($acquireLogInfo['create_time']) ? $acquireLogInfo['create_time'] : 0;
        $groupInfo['action'] = $acquireLogInfo['trigger_mode'];
        $groupInfo['gift_id'] = isset($acquireLogInfo['gift_id']) ? $acquireLogInfo['gift_id'] : 0;
        $groupInfo['storeId'] = $groupInfo['useFormId'];
        return $groupInfo;
    }

    /**
     * 获取优惠券组详情[后台直推查询名称]
     */
    public function getCouponGroupInfoById($couponGroupId, $annualizedAmount = 0) {
        try {
            if (empty($couponGroupId)) {
                throw new \Exception('券组id不能为空');
            }

            // cache设置
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            // 缓存30分钟
            $cacheExpireTime = 1800;
            $redisKey = md5('REDIS_KEY_O2O_COUPONGROUP'.$couponGroupId.'_'.$annualizedAmount);
            $couponGroup = $redis->get($redisKey);
            $res = false;
            if ($couponGroup) {
                $res = unserialize($couponGroup);
            }

            if ($res === false) {
                $request = new SimpleRequestBase();
                $params = array();
                $params['id'] = intval($couponGroupId);
                $params['annualizedAmount'] = floatval($annualizedAmount);
                $request->setParamArray($params);
                $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroup', $request);

                // 设置缓存
                $res = $response['data'];
                $redis->setex($redisKey, $cacheExpireTime, serialize($res));
            }

            return $res;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * exchangeCoupon
     * 兑换券
     *
     * @param string $couponId 优惠券编号
     * @param integer $userId 用户编号
     * @param integer $storeId 商户编号
     * @param array $receiverParam 收货信息[收货类型的券]
     * @param array $extraParam 信息[收券类型的券]
     * @param array $msgConf 短信配置
     * @return array
     * @author liguizhi@ucfgroup.com
     * @date 2015-7-6
     */
    public function exchangeCoupon($couponId, $userId, $storeId, $receiverParam = array(), $extraParam = array(), $msgConf = array()) {
        try {
            $params = array($couponId, $userId, $storeId, $receiverParam, $extraParam, $msgConf);
            return $this->doExchangeCoupon($couponId, $userId, $storeId, $receiverParam, $extraParam, $msgConf);
        } catch (\Exception $e) {
            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doExchangeCoupon', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * acquireCoupon
     * 领取券
     *
     * @param string $couponGroupId 礼券券组id
     * @param int $userId 用户id
     * @param int $action 触发动作
     * @param int $dealLoadId 交易id
     * @param string $mobile 手机号
     * @access public
     * @return array
     */
    public function acquireCoupon($couponGroupId, $userId, $action, $dealLoadId = 0, $mobile = '',
                                  $dealType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        try {
            return $this->doAcquireCoupon($couponGroupId, $userId, $action, $dealLoadId, $mobile, $dealType);
        } catch (\Exception $e) {
            $params = array($couponGroupId, $userId, $action, $dealLoadId, $mobile, $dealType);
            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireCoupon', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 领取AR红包
     * @param int $userId 用户id
     * @param string $trackId 图片id
     * @param string $trackName 图片名称
     * @return mixed 如果有错误，返回false
     */
    public function acquireArCoupon($userId, $trackId, $trackName) {
        try {
            return $this->doAcquireArCoupon($userId, $trackId, $trackName);
        } catch (\Exception $e) {
            $params = array($userId, $trackId, $trackName);
            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireArCoupon', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 获取投资券信息
     * @param $discountId int 投资券id
     * @return array
     */
    public function getDiscount($discountId) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getDiscount($discountId);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取投资券组使用规则
     * @param $groupId int 投资券组id
     * @return array
     */
    public function getDiscountGroup($groupId) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getDiscountGroup($groupId);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 检查投资券是否适用
     * @param $groupId int 投资券组id
     * @param $dealId int 标的id
     * @param $money float 金额
     * @return bool
     */
    public function checkDiscountUseRules($groupId, $dealId, $money, &$errorInfo = array(), $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        $o2oDiscount = new O2ODiscountService();
        return $o2oDiscount->checkDiscountUseRules($groupId, $dealId, $money, $errorInfo, $consumeType);
    }

    /**
     * 同时获取多张投资券，有异步重试机制
     * 如果同步调用成功，会立刻返回结果
     *
     * @param $userId int 用户id
     * @param $discountGroupId string 投资券组id，多个用逗号分隔
     * @param $token string 唯一token
     * @param $dealLoadId int 交易id
     * @param $remark string 备注
     * @param $isSyncResult bool 是否返回同步结果,true为返回，false不返回（会异步执行）
     * @param $rebateAmount float 返利金额，覆盖o2o的券组返利金额
     * @param $rebateLimit int 返利期限，覆盖o2o的券组的返利期限
     * @return array | false
     */
    public function acquireDiscounts($userId, $discountGroupIds, $token, $dealLoadId = 0, $remark = '', $isSyncResult = false,
                                    $rebateAmount = 0, $rebateLimit = 0) {
        try {
            $params = array($userId, $discountGroupIds, $token, $dealLoadId, $remark, $rebateAmount, $rebateLimit);

            // 返回同步结果
            if ($isSyncResult) {
                return $this->doAcquireDiscounts($userId, $discountGroupIds, $token, $dealLoadId, $remark, $rebateAmount, $rebateLimit);
            } else {
                // 异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireDiscounts', $params);
                $taskId = $taskObj->doBackground($event, 10);
                return true;
            }
        } catch (\Exception $e) {
            if ($isSyncResult && $e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireDiscounts', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    // 由于支持幂等，暂时不用事务，如果有失败，直接重试
    public function doAcquireDiscounts($userId, $discountGroupIds, $token, $dealLoadId = 0, $remark = '',
                                       $rebateAmount = 0, $rebateLimit = 0) {
        $o2oDiscount = new O2ODiscountService();
        $groupIds = explode(',', $discountGroupIds);
        $res = array();
        foreach ($groupIds as $key=>$groupId) {
            if (empty($groupId) || !is_numeric($groupId)) {
                continue;
            }

            $discountToken = $key == 0 ? $token : $token.'_'.$key;
            $discount = $o2oDiscount->acquireDiscount($userId, $groupId, $discountToken, $dealLoadId, $remark,
                $rebateAmount, $rebateLimit);

            if ($discount === false) {
                throw new O2OException($o2oDiscount->getErrorMsg(), $o2oDiscount->getErrorCode());
            } else {
                // 防止groupId重复问题
                if (isset($res[$groupId])) {
                    $res[$groupId.'_'.$key] = $discount;
                } else {
                    $res[$groupId] = $discount;
                }
            }
        }
        return $res;
    }

    /**
     * 领取指定投资券规则的投资券
     * @param $userId int 用户id
     * @param $discountRuleId int 投资规则id
     * @param $token string 唯一token
     * @param $bidAmount float 起投金额
     * @param $bidDayLimit int 起投期限
     * @param $dealLoadId int 交易id
     * @param $remark string 备注
     * @param $rebateAmount float 返利金额
     * @param $rebateLimit int 返利期限
     * @return array | false
     */
    public function acquireRuleDiscount($userId, $discountRuleId, $token, $bidAmount = 0, $bidDayLimit = 0,
                                    $dealLoadId = 0, $remark = '', $rebateAmount = 0, $rebateLimit = 0) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->acquireRuleDiscount($userId, $discountRuleId, $token, $bidAmount, $bidDayLimit,
            $dealLoadId, $remark, $rebateAmount, $rebateLimit);

        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }
    /**
     * 领取投资券
     * @param $userId int 用户id
     * @param $discountGroupId int 投资券组id
     * @param $token string 唯一token
     * @param $dealLoadId int 交易id
     * @param $remark string 备注
     * @param $rebateAmount float 返利金额
     * @param $rebateLimit int 返利期限
     * @return array | false
     */
    public function acquireDiscount($userId, $discountGroupId, $token, $dealLoadId = 0,
                                    $remark = '', $rebateAmount = 0, $rebateLimit = 0) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->acquireDiscount($userId, $discountGroupId, $token, $dealLoadId, $remark, $rebateAmount, $rebateLimit);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 批量领取投资券
     * @param $userIds string 用户列表，多个用逗号','分割，建议用户列表每次在2000个以内
     * @param $groupIds string 投资券组列表，多个用逗号','分割
     * @param $taskId int 任务id
     * @param $seriaNo int 批次号
     * @param $tokenPre string token前缀
     * @param $siteId int 分站id，默认为1，表示主站
     * @return int|false 成功领取的投资券个数
     */
    public function batchAcquireDiscount($userIds, $groupIds, $taskId, $serialNo, $tokenPre, $siteId = 1) {
        $userService = new UserService();
        $userIdArray = explode(',', $userIds);
        $newUserIds = array();
        if ($userIdArray) {
            foreach ($userIdArray as $userId) {
                $newUserIds[] = $userId;
            }
        }
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->batchAcquireDiscount($newUserIds, $groupIds, $taskId, $serialNo, $tokenPre, $siteId);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 兑换投资券
     * @param $userId int 用户id
     * @param $discountId int 投资券id
     * @param $dealLoadId int 交易id
     * @param $triggerTime int 触发时间
     * @return array | false
     */
    public function exchangeDiscount($userId, $discountId, $dealLoadId, $triggerTime = false) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->exchangeDiscount($userId, $discountId, $dealLoadId, $triggerTime);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * freezeDiscount冻结投资券
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-10-29
     * @param mixed $userId
     * @param mixed $discountId
     * @param mixed $dealLoadId
     * @param mixed $discountType
     * @param mixed $triggerTime
     * @access public
     * @return void
     */
    public function freezeDiscount($userId, $discountId, $triggerTime, $consumeId, $consumeType, $discountType) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->freezeDiscount($userId, $discountId, $triggerTime, $consumeId, $consumeType, $discountType);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * unfreezeDiscount解冻投资券
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-10-29
     * @param mixed $userId
     * @param mixed $discountId
     * @param mixed $dealLoadId
     * @param mixed $discountType
     * @param mixed $triggerTime
     * @access public
     * @return void
     */
    public function unfreezeDiscount($userId, $discountId, $triggerTime, $consumeId, $consumeType, $discountType) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->unfreezeDiscount($userId, $discountId, $triggerTime, $consumeId, $consumeType, $discountType);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 投资券锁定赠送，用户赠送的用户还未注册的情况
     * @param $fromUserId int 赠送者
     * @param $discountId string 投资券id，多个用逗号分隔
     * @param $toMobile string 接收者手机号
     * @return array | false
     */
    public function lockAndGiveDiscount($fromUserId, $discountId, $toMobile = '') {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->lockAndGiveDiscount($fromUserId, $discountId, $toMobile);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 投资券赠送
     * @param $fromUserId int 赠送者
     * @param $toUserId int 接受者
     * @param $discountId string 投资券id，多个用逗号分隔
     * @param $toMobile string 接受手机号
     * @return array | false
     */
    public function giveDiscount($fromUserId, $toUserId, $discountId, $toMobile = '') {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->giveDiscount($fromUserId, $toUserId, $discountId, $toMobile);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取用户好友个数
     * @param $userId int 用户id
     * @return int
     */
    public function getUserFriendCount($userId) {
        $o2oDiscount = new O2ODiscountService();
        return $o2oDiscount->getUserFriendCount($userId);
    }

    /**
     * 获取用户好友列表
     * @param $userId int 用户id
     * @param $page int 页码
     * @param $pageSize int 每页个数
     * @return array
     */
    public function getUserFriendList($userId, $page = 1, $pageSize = 10) {
        $o2oDiscount = new O2ODiscountService();
        return $o2oDiscount->getUserFriendList($userId, $page, $pageSize);
    }

    /**
     * 获取用户可赠送列表
     * @param $userId int 用户id
     * @param $page int 页码
     * @param $pageSize int 每页个数
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @return array | false
     */
    public function getUserGivenDiscountList($userId, $page = 1, $pageSize = 10, $type = 0,
                                             $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getUserGivenDiscountList($userId, $page, $pageSize, $type, $consumeType);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取用户未使用的投资券个数
     * @param $userId int 用户id
     * @return array 对应个数
     */
    public function getMineUnusedDiscountCount($userId) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getMineUnusedDiscountCount($userId);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取用户未使用的投资券个数
     * @param $userId int 用户id
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示返现券和加息券
     * @return int 对应个数
     */
    public function getUserUnusedDiscountCount($userId, $type = 0, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getUserUnusedDiscountCount($userId, $type, $consumeType);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取用户未来将要过期的投资券个数
     * @param $userId int 用户id
     * @param $elapsedTime int 逝去的时间
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示返现券和加息券
     * @return int | false
     */
    public function getUserWillExpireDiscountCount($userId, $elapsedTime = 86400, $type = 0,
                                                   $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getUserWillExpireDiscountCount($userId, $elapsedTime, $type, $consumeType);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取用户可用的投资券个数
     * @param $userId int 用户id
     * @param $dealId int 交易id
     * @param $money float 金额
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示返现券和加息券
     * @return mixed
     */
    public function getAvailableDiscountCount($userId, $dealId, $money = false, $type = 0,
                                              $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $bidDayLimit = 0) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getAvailableDiscountCount($userId, $dealId, $money, $type, $consumeType, $bidDayLimit);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取用户可用的投资券列表
     * @param $userId int 用户id
     * @param $dealId int 标id
     * @param $money float 金额
     * @param $page int 页码
     * @param $pageSize int 每页显示数量
     * @param $type int 投资券类型，1为返现券，2为加息券，0表示不区分类型
     * @return mixed
     */
    public function getAvailableDiscountList($userId, $dealId, $money = false, $page = 1, $pageSize = 10, $type = 0,
                                             $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $bidDayLimit = 0) {
        $o2oDiscount = new O2ODiscountService();

        if ($type == 0 || $type == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES || $type == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK) {
            if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
                $entranceInfo = (new DtEntranceService())->getEntranceInfo($dealId);
                $lockDay = isset($entranceInfo['lock_day']) ? $entranceInfo['lock_day'] : 0;
                $annualizedAmount = DealModel::instance()->floorfix($money * $lockDay / DealModel::DAY_OF_YEAR, 2);
            } else if ($consumeType == CouponGroupEnum::CONSUME_TYPE_RESERVE) {
                $annualizedAmount = DealModel::instance()->floorfix($money * $bidDayLimit / DealModel::DAY_OF_YEAR, 2);
            } else {
                $annualizedAmount = O2OUtils::getAnnualizedAmountByDealIdAndAmount($dealId, $money);
                $annualizedAmount = ($annualizedAmount > 0) ? $annualizedAmount : 0;
            }
        } else {
            $annualizedAmount = 0;
        }

        $res = $o2oDiscount->getAvailableDiscountList($userId, $dealId, $money, $page, $pageSize, $type,
            $annualizedAmount, $consumeType, $bidDayLimit);

        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取加息券的预期收益的展示
     */
    public function getExpectedEarningInfo($userId, $dealId, $money, $discountId, $appversion, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getExpectedEarningInfo($userId, $dealId, $money, $discountId, $appversion, $consumeType);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取用户投资券列表
     * @param $userId int 用户id
     * @param $status int 投资券状态
     * @param $page int 页码编号
     * @param $pageSize int 每页显示数
     * @param $type int 投资券类型，1为返现券，2为加息券，0表示不区分
     */
    public function getUserDiscountList($userId, $status = 0, $page = 1, $pageSize = 10, $type = 0,
                                        $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $useStatus = 0) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->getUserDiscountList($userId, $status, $page, $pageSize, $type, $consumeType, $useStatus);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 判断用户投资券动态
     * @param $userId int 用户id
     * @return int 1为有，0没有
     */
    public function checkUserMoments($userId) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->checkUserMoments($userId);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * 退回优惠券
     * @param $userId int 用户id
     * @param $discountId int 券id
     * @param $token string token唯一码
     * @return false|int
     */
    public function refundDiscount($userId, $discountId = 0, $token = '') {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->refundDiscount($userId, $discountId, $token);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }

        return $res;
    }

    /**
     * 清理投资券动态
     * @param $userId int 用户id
     * @return int 1为成功，0失败
     */
    public function clearUserMoments($userId) {
        $o2oDiscount = new O2ODiscountService();
        $res = $o2oDiscount->clearUserMoments($userId);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oDiscount->getErrorMsg();
            self::$errorCode = $o2oDiscount->getErrorCode();
        }
        return $res;
    }

    /**
     * getUserCouponList
     * 获取用户券列表
     *
     * @param int $userId 用户id
     * @param int $status 券码状态，为0表示对状态不进行过滤
     * @param int $page 页码
     * @param int $pageSize 每页个数
     * @param int $hasTotalCount 是否包含对应的总个数
     * @return array
     */
    public function getUserCouponList($userId, $status = 0, $page = 1, $pageSize = 10, $hasTotalCount = 0) {

        try {
            $request = new RequestGetUserCouponList();
            $request->setUserId(intval($userId));
            $request->setStatus(intval($status));
            $request->setPage(intval($page));
            $request->setPageSize(intval($pageSize));
            $request->setHasTotalCount($hasTotalCount);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getUserCouponList', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        if (!is_array($response) || empty($response['dataPage']['data'])) {
            return array();
        }

        //TODO O2O接口 $list = array();
        foreach ($response['dataPage']['data'] as $data ) {
            $item = $data['coupon'];
            $item['productName'] = $data['product']['productName'];
            $item['pic'] = $data['product']['pic'];
            $item['couponGroupId'] = $data['couponGroup']['id'];
            $item['useRules'] = $data['couponGroup']['useRules'];
            $item['goodPrice'] = $data['couponGroup']['goodPrice'];
            $item['isShowCouponNumber'] = $data['couponGroup']['isShowCouponNumber'];
            $item['couponExchangedDesc'] = $data['couponGroup']['couponExchangedDesc'];
            $item['pcPic'] = $data['product']['pcPic'];
            $item['couponExchangedPcDesc'] = $data['couponGroup']['couponExchangedPcDesc'];
            if ($item['useEndTime'] <= time() && $item['status'] != CouponEnum::STATUS_USED) {
                $item['status'] = CouponEnum::STATUS_EXPIRED;
            }
            $item['isNew'] = ($item['createTime'] >= strtotime(date('Ymd'))) ? 1 : 0;
            $list[] = $item;
        }
        return $list;
    }

    /**
     * getUserCouponCount获取用户已领取列表总数
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2016-02-03
     * @param $userId int 用户id
     * @param $status int 状态
     * @access public
     * @return int
     */
    public function getUserCouponCount($userId, $status = 0) {
        try {
            $request = new SimpleRequestBase();
            $params = array('ownerUserId'=>$userId, 'status'=>$status);
            $request->setParamArray($params);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getUserCouponCount', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $response['data'];
    }

    /**
     * 获取用户未来将要过期的礼券个数
     * @param $userId int 用户id
     * @param $elapsedTime int 逝去的时间
     * @return int | false
     */
    public function getUserWillExpireCouponCount($userId, $elapsedTime = 86400) {
        $o2oCoupon = new O2OCouponService();
        $res = $o2oCoupon->getUserWillExpireCouponCount($userId, $elapsedTime);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $o2oCoupon->getErrorMsg();
            self::$errorCode = $o2oCoupon->getErrorCode();
        }
        return $res;
    }

    /**
     * 获取用户领到的投资券详情
     */
    public function getCouponInfo($couponId, $userId = false) {

        try {
            $request = new RequestGetCouponInfo();
            $request->setCouponId(intval($couponId));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getCouponInfo', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        if (!is_array($response)) {
            return array();
        }

        $couponInfo = $response['coupon'];
        if ($userId !== false) {
            if (empty($userId) || $userId != $couponInfo['ownerUserId']) {
                $this->setErrorMsg('该券不属于您，无法查看！');
                return false;
            }
        }

        // 这里的goodPrice还是取$couponInfo里面的值，o2o接口会保证该值的正确
        //$couponInfo['goodPrice'] = $response['couponGroup']['goodPrice'];
        $couponInfo['useFormId'] = $response['couponGroup']['useFormId'];
        $couponInfo['couponDesc'] = $response['couponGroup']['couponDesc'];
        $couponInfo['couponSource'] = $response['couponGroup']['couponSource'];
        $couponInfo['useTimeType'] = $response['couponGroup']['useTimeType'];
        $couponInfo['useRules'] = $response['couponGroup']['useRules'];
        $couponInfo['productName'] = $response['product']['productName'];
        $couponInfo['storeId'] = $response['couponGroup']['useFormId'];
        $couponInfo['pic'] = $response['product']['pic'];
        $couponInfo['pcPic'] = $response['product']['pcPic'];
        $couponInfo['couponPcDesc'] = $response['couponGroup']['couponPcDesc'];
        $couponInfo['couponExchangedPcDesc'] = $response['couponGroup']['couponExchangedPcDesc'];
        //$couponInfo['storeList'] = $response['storeList'];
        //$couponInfo['storeUsed'] = $response['storeUsed'];
        $couponInfo['isShowCouponNumber'] = $response['couponGroup']['isShowCouponNumber'];
        //TODO 获取券的兑换地址
        if ($couponInfo['status'] == CouponEnum::STATUS_UNUSED && in_array($response['couponGroup']['useRules'], self::$rulesRequireForm)) {
            $couponInfo['p2pExchangeUrl'] = "/gift/exchangeForm?couponId={$couponInfo['id']}&storeId={$response['couponGroup']['useFormId']}&useRules={$response['couponGroup']['useRules']}";
            $couponInfo['openExchangeUrl'] = "/coupon/acquireForm?couponId={$couponInfo['id']}&storeId={$response['couponGroup']['useFormId']}&useRules={$response['couponGroup']['useRules']}&couponGroupId={$response['couponGroup']['id']}";
        } else {
            $couponInfo['p2pExchangeUrl'] = '';
            $couponInfo['openExchangeUrl'] = '';
        }

        // 获取券的来源
        $couponInfo['fromSourceName'] = empty($couponInfo['couponFrom'])? $response['couponGroup']['fromSiteDesc'] : $couponInfo['couponFrom'];
        // couponToken => userId_action_dealLoadId
        $params = explode('_', $couponInfo['couponToken']);
        // 通过triggerMode优化券来源的处理
        $isSendByPartner = $response['couponGroup']['isSendByPartner'];
        if (empty($couponInfo['fromSourceName']) && is_numeric($params[0]) && $isSendByPartner != CouponGroupEnum::SEND_BY_PARTNER) {
            $triggerMode = $params[1];
            $dealLoadId = $params[2];
            $dealType = empty($params[3]) ? CouponGroupEnum::CONSUME_TYPE_P2P : $params[3];
            if (in_array($triggerMode, CouponGroupEnum::$TRIGGER_DEAL_MODES) && $dealType == CouponGroupEnum::CONSUME_TYPE_P2P) {
                $dealInfo = DealLoadModel::instance()->getDealInfoByLoadId($dealLoadId);
                $couponInfo['fromSourceName'] = '投资"' .$dealInfo['name'].'"获得';
            }

            if ($triggerMode == CouponGroupEnum::TRIGGER_REGISTER) {
                $couponInfo['fromSourceName'] = '注册获得';
            }

            if ($triggerMode == CouponGroupEnum::TRIGGER_FIRST_BINDCARD) {
                $couponInfo['fromSourceName'] = '首次绑卡获得';
            }

            if ($triggerMode == CouponGroupEnum::TRIGGER_ADMIN_PUSH || $triggerMode > 100) {
                $couponInfo['fromSourceName'] = '平台奖励优惠券';
            }

            if ($triggerMode == CouponGroupEnum::TRIGGER_MEDAL) {
                $couponInfo['fromSourceName'] = '勋章奖励';
            }

            if ($triggerMode == CouponGroupEnum::TRIGGER_RESEND_COUPON) {
                $couponInfo['fromSourceName'] = '直推任务';
            }

            if (in_array($triggerMode, CouponGroupEnum::$CHARGE_TRIGGER)) {
                $couponInfo['fromSourceName'] = '充值奖励';
            }
        }

        if ($couponInfo['useEndTime'] <= time() && $couponInfo['status'] != CouponEnum::STATUS_USED) {
            $couponInfo['status'] = CouponEnum::STATUS_EXPIRED;
        }

        //TODO 显示券的使用信息
        switch ($response['couponGroup']['useRules']) {
        case CouponGroupEnum::ONLINE_GOODS_REALTIME:
        case CouponGroupEnum::ONLINE_GOODS_REPORT:
            $couponInfo['p2pUsedDesc']['title'] = '收货信息';
            if ($couponInfo['status'] == CouponEnum::STATUS_USED) {
                unset($response['couponOrder']['receiverExtra']);
                $couponInfo['p2pUsedDesc']['detail'] = array_values($response['couponOrder']);
            }
            break;
        case CouponGroupEnum::ONLINE_COUPON_REPORT:
        case CouponGroupEnum::ONLINE_COUPON_REALTIME:
            $couponInfo['p2pUsedDesc']['title'] = '收券信息';
            if ($couponInfo['status'] == CouponEnum::STATUS_USED) {
                $orderInfo = array();
                foreach ($response['couponOrder'] as $key => $value) {
                    if ($value) {
                        $orderInfo[$key] = $value;
                    }
                }
                $couponInfo['p2pUsedDesc']['detail'] = array_values($orderInfo);
                $couponInfo['p2pUsedDesc']['orderInfo'] = $orderInfo;
            }
            break;
        case CouponGroupEnum::OFFLINE_UNLIMIT_USE:
        case CouponGroupEnum::OFFLINE_LIMIT_USE:
            break;
        default:
            $couponInfo['p2pUsedDesc']['title'] = '';
            $couponInfo['p2pUsedDesc']['detail'] = '';
            break;
        }

        return $couponInfo;
    }

    /**
     * 根据券码获取券详情
     */
    public function getCouponInfoByCouponCode($couponCode, $storeId) {
        try {
            $request = new RequestGetCouponInfo();
            $request->setCouponNumber($couponCode);
            $request->setStoreId($storeId);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getCouponInfo', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        if (!is_array($response)) {
            return array();
        }

        if ($response) {
            $request = new SimpleRequestBase();
            $request->setParamArray(array('couponGroupId'=>$response['coupon']['couponGroupId'], 'storeId' => $storeId));
            $checkStoreFlag = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'checkCouponStore', $request);
            if(empty($checkStoreFlag) || ($checkStoreFlag['isStore'] == 0)){
                return array();
            }
        }
        $couponInfo = $response['coupon'];
        $couponInfo['goodPrice'] = $response['couponGroup']['goodPrice'];
        $couponInfo['couponDesc'] = $response['couponGroup']['couponDesc'];
        $couponInfo['couponSource'] = $response['couponGroup']['couponSource'];
        $couponInfo['useRules'] = $response['couponGroup']['useRules'];
        $couponInfo['productName'] = $response['product']['productName'];
        $couponInfo['pic'] = $response['product']['pic'];
        //$couponInfo['storeList'] = $response['storeList'];
        //$couponInfo['storeUsed'] = $response['storeUsed'];
        $couponInfo['isShowCouponNumber'] = $response['couponGroup']['isShowCouponNumber'];
        if ($couponInfo['useEndTime'] <= time()) {
            $couponInfo['status'] = CouponEnum::STATUS_EXPIRED;
        }

        $userInfo = UserModel::instance()->find($couponInfo['ownerUserId']);
        return array('couponInfo' => $couponInfo, 'userInfo' => $userInfo);
    }

    /**
     * 获取商铺兑换记录总数
     */
    public function getConfirmedCouponCount($storeId, $beginTime=0, $endTime=0, $couponNumber='') {
        try {
            $request = new RequestGetConfirmedCouponCount();
            $request->setStoreId(intval($storeId));
            $request->setBeginTime(intval($beginTime));
            $request->setEndTime(intval($endTime));
            $request->setCouponNumber($couponNumber);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getConfirmedCouponCount', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
        return $response;
    }
    /**
     * 获取商铺兑换记录表[web商家服务调用]
     */
    public function getConfirmedCouponListForWeb($storeId, $page = 1, $pageSize = 10, $beginTime=0, $endTime=0, $couponNumber='') {

        try {
            $request = new RequestGetConfirmedCouponListForWeb();
            $request->setStoreId(intval($storeId));
            $request->setPage(intval($page));
            $request->setPageSize(intval($pageSize));
            $request->setBeginTime($beginTime);
            $request->setEndTime($endTime);
            $request->setCouponNumber($couponNumber);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getConfirmedCouponListForWeb', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
        if (empty($response['couponList'])) {
            return array();
        }
        $list = array();
        foreach ($response['couponList'] as $couponInfo) {
            $item = array(
                'productName' => $couponInfo['product']['productName'],
                'couponStatus' => $couponInfo['coupon']['status'],
                'couponNumber' => $couponInfo['coupon']['couponNumber'],
                'updateTime' => $couponInfo['coupon']['updateTime'],
                'price' => $couponInfo['group']['goodPrice']
            );
            $list[] = $item;
        }
        return $list;
    }
    /**
     * 获取商铺兑换记录表
     */
    public function getConfirmedCouponList($storeId, $page = 1, $pageSize = 10) {


        try {
            $request = new RequestGetConfirmedCouponList();
            $request->setStoreId(intval($storeId));
            $request->setPage(intval($page));
            $request->setPageSize(intval($pageSize));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getConfirmedCouponList', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
        if (empty($response['couponList'])) {
            return array();
        }
        $list = array();
        foreach ($response['couponList'] as $couponInfo) {
            $item = array(
                'productName' => $couponInfo['product']['productName'],
                'couponStatus' => $couponInfo['coupon']['status'],
                'couponNumber' => $couponInfo['coupon']['couponNumber'],
                'updateTime' => $couponInfo['coupon']['updateTime'],
                'price' => $couponInfo['group']['goodPrice']
            );
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 商户发起兑换请求
     */
    public function setCouponWaitStatus($couponId, $storeId, $userId) {

        try {
            $request = new RequestSetCouponWaitStatus();
            $request->setCouponId(intval($couponId));
            $request->setStoreId(intval($storeId));
            $request->setOwnerUserId(intval($userId));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'setCouponWaitStatus', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
        return $response;
    }

    /**
     * 用户确认兑换
     */
    public function setCouponConfirm($couponId, $storeId) {
        try {
            $params = array($couponId, $storeId);
            return $this->doSetCouponConfirm($couponId, $storeId);
        } catch (\Exception $e) {
            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doSetCouponConfirm', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 兑换完成转账逻辑
     * @param $storeId int 门店id
     * @param $data array 返利配置
     * @param $dealLoadId int 交易id
     * @return int task任务id
     */
    public function transferMoneyOnConfirm($storeId, $data, $dealLoadId, $siteId = false) {
        // 检查是否有相关的返利
        if (empty($data['coupon']['allowance'])) {
            // 没有想过返利，直接返回true
            return true;
        }

        if ($siteId === false) {
            $siteId = \libs\utils\Site::getId();
        }

        $event = new \core\event\O2ORebateExchangeEvent($storeId, $data, $dealLoadId, $siteId);
        $taskObj = new GTaskService();
        $taskId = $taskObj->doBackground($event, 10);
        // 记录关键信息
        $params = array();
        $params['storeId'] = $storeId;
        $params['userId'] = $data['coupon']['ownerUserId'];
        $params['couponId'] = $data['coupon']['id'];
        $params['couponGroupId'] = $data['coupon']['couponGroupId'];
        PaymentApi::log("O2OService.O2ORebateExchangeEvent, data:".json_encode($params).', taskId:'.$taskId, Logger::INFO);
        return $taskId;
    }

    /**
     * 给邀请人返红包或者现金
     * @param $data array 券相关数据
     * @param $dealLoadId int 交易id
     * @param $siteId int 分站id，默认是false
     * @return int task任务id
     */
    public function rebateInviteUser($data, $dealLoadId, $siteId = false) {
        // 检查是否有相关的返利
        if (empty($data['coupon']['allowance'])) {
            // 没有相关返利，直接返回true
            return true;
        }

        // 如果没有，则取默认值
        if ($siteId === false) {
            $siteId = \libs\utils\Site::getId();
        }

        $event = new \core\event\O2ORebateAcquireEvent($data, $dealLoadId, $siteId);
        $taskObj = new GTaskService();
        $taskId = $taskObj->doBackground($event, 10);
        // 记录关键信息
        $params = array();
        $params['userId'] = $data['coupon']['ownerUserId'];
        $params['couponId'] = $data['coupon']['id'];
        $params['couponGroupId'] = $data['coupon']['couponGroupId'];
        PaymentApi::log("O2OService.O2ORebateAcquireEvent, data:".json_encode($params).', taskId:'.$taskId, Logger::INFO);
        return $taskId;
    }

    /**
     * 获取用户未领取的列表
     */
    public function getUnpickList($userId, $page = 1, $pageSize = 10, $expireStatus = OtoAcquireLogModel::UNPICK_ALL) {
        $userId = intval($userId);
        $giftList = OtoAcquireLogModel::instance()->getUnpickList($userId, $page, $pageSize, $expireStatus);
        if (empty($giftList)) {
            return array();
        }

        $unPickList = array();
        $currentTime = time();
        foreach ($giftList as $key => $giftInfo) {
            $giftInfo = $giftInfo->getRow();
            $extraInfo = json_decode($giftInfo['extra_info'], true);
            $giftInfo['money'] = $extraInfo['deal_money'];
            $dealType = isset($extraInfo['consume_type']) ? $extraInfo['consume_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
            if (in_array($giftInfo['trigger_mode'], CouponGroupEnum::$TRIGGER_DEAL_MODES)) {
                if ($dealType == CouponGroupEnum::CONSUME_TYPE_P2P) {
                    // 交易的标的名称
                    $dealName = '';
                    if (isset($extraInfo['dealName'])) {
                        //普惠的dealName注入到extraInfo中
                        $dealName = $extraInfo['dealName'];
                    } else {
                        $deal = \core\service\ncfph\DealLoadService::getO2ODealLoadInfo($giftInfo['deal_load_id']);
                        if (!isset($deal['name'])) {
                            $deal = DealLoadModel::instance()->getDealInfoByLoadId($giftInfo['deal_load_id']);
                        }

                        $dealName = $deal ? $deal['name'] : '';
                    }
                    $giftInfo['deal_name'] = $dealName ? '通过'.$dealName.'获得' : '交易获得';
                } else if ($dealType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
                    $giftInfo['deal_name'] = '通过智多新获得';
                } else if ($dealType == CouponGroupEnum::CONSUME_TYPE_GOLD) {
                    $giftInfo['deal_name'] = '购买'.(empty($extraInfo['dealName']) ? '优长金' : $extraInfo['dealName']).'获得';
                } else if ($dealType == CouponGroupEnum::CONSUME_TYPE_GOLD_CURRENT) {
                    $giftInfo['deal_name'] = '购买优金宝获得';
                } else if ($dealType == CouponGroupEnum::CONSUME_TYPE_ZHUANXIANG) {
                    $deal = DealLoadModel::instance()->getDealInfoByLoadId($giftInfo['deal_load_id']);
                    $giftInfo['deal_name'] = $deal ? '通过'.$deal['name'].'获得' : '交易获得';
                } else {
                    $giftInfo['deal_name'] = '交易获得';
                }
            } else if ($giftInfo['trigger_mode'] == CouponGroupEnum::TRIGGER_REGISTER) {
                $giftInfo['deal_name'] = '注册获得';
            } else if ($giftInfo['trigger_mode'] == CouponGroupEnum::TRIGGER_FIRST_BINDCARD) {
                $giftInfo['deal_name'] = '首次绑卡获得';
            } else if ($giftInfo['trigger_mode'] == CouponGroupEnum::TRIGGER_ADMIN_PUSH) {
                $giftInfo['deal_name'] = '平台奖励';
            } else if (in_array($giftInfo['trigger_mode'], CouponGroupEnum::$CHARGE_TRIGGER)) {
                $giftInfo['deal_name'] = '充值奖励';
            } else {
                $giftInfo['deal_name'] = '交易获得';
            }

            $giftInfo['expired'] = $giftInfo['expire_time'] > $currentTime ? 0 : 1;
            $unPickList[] = $giftInfo;
        }

        return $unPickList;
    }

    private function _handleException($e, $functionName, $data = array()) {
        PaymentApi::log("O2OService.$functionName:".json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE).json_encode($data, JSON_UNESCAPED_UNICODE), Logger::ERR);
        // 需要报的错误信息
        $this->setErrorMsg($e->getMessage());
        self::$errorCode = $e->getCode();
//         \libs\utils\Alarm::push('o2o_exception', $functionName, 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE).'msg: '.$e->getMessage());
        return false;
    }

    public function hasError() {
        return self::$error;
    }

    public function setErrorMsg($msg) {
        self::$error = true;
        self::$errorMsg = $msg;
        return true;
    }

    public function getErrorCode() {
        return self::$errorCode;
    }

    public function getErrorMsg() {
        return self::$errorMsg ? self::$errorMsg : '系统异常';
    }

    // 请求o2o方法
    public function requestO2O($service, $method, $request, $timeOut = 3, $retry = true) {
        if (app_conf('O2O_SERVICE_ENABLE') == 0) {
            throw new \Exception('O2O Service is down');
        }

        $beginTime = microtime(true);
        // 考虑到统一处理的便捷，后期可以考虑集成到phalcon-common框架中
        if ($request instanceof \NCFGroup\Common\Extensions\Base\ProtoBufferBase) {
            // 在底层请求里面统一传递，o2o对分站的支持
            $request->_site_id_ = \libs\utils\Site::getId();
            // 跨系统日志id的统一
            $request->_log_id_ = Logger::getLogId();
        }

        $namespaces = explode('\\', $service);
        $className = array_pop($namespaces);
        $logFunc = 'O2OService.'.$className.'.'.$method;
        PaymentApi::log("[req]{$logFunc}:".json_encode($request, JSON_UNESCAPED_UNICODE), Logger::RPC);

        // 增加重试
        $maxTryTimes = 3;
        $retryTimes = 0;
        do {
            try {
                if ($maxTryTimes != 3) {
                    ++$retryTimes;
                    PaymentApi::log("{$logFunc} retry {$retryTimes}", Logger::WARN);
                }
                $GLOBALS['o2oRpc']->setTimeout($timeOut);
                $response = $GLOBALS['o2oRpc']->callByObject(array(
                    'service' => $service,
                    'method' => $method,
                    'args' => $request
                ));
                if (!empty($response) || !$retry) {
                    break;
                }
            } catch (\Exception $e) {
                \libs\utils\Alarm::push('o2o_exception', $logFunc,
                    'request: '.json_encode($request, JSON_UNESCAPED_UNICODE)
                    .', msg: '.$e->getMessage().', code: '.$e->getCode());

                // 超时，重试
                if ($e->getCode() == \NCFGroup\Protos\O2O\RPCErrorCode::RPC_RETRY_AGAIN_LATER) {
                    if ($maxTryTimes == 1 || !$retry) {
                        PaymentApi::log("{$logFunc}:".$e->getMessage(), Logger::WARN);
                        // 优化显示结果
                        throw new \core\exception\O2OTimeoutException('系统繁忙,请稍后再试', O2OException::CODE_RPC_TIMEOUT, $e);
                    }
                } else {
                    PaymentApi::log("{$logFunc}:".$e->getMessage(), Logger::ERR);
                    throw $e;
                }
            }
        } while(--$maxTryTimes > 0);

        if (gettype($response) == 'object') {
            $response = $response->toArray();
        }

        $res = json_encode($response, JSON_UNESCAPED_UNICODE);
        $res = ($res === false) ? 'invalid response: '.var_export($response, true) : mb_substr($res, 0, 1000);
        $endTime = microtime(true);
        $elapsedTime = round($endTime - $beginTime, 3);
        PaymentApi::log("[resp][cost:{$elapsedTime}]{$logFunc}:".$res, Logger::RPC);
        return $response;
    }

    /**
     * O2O补发
     */
    public function resend($couponGroupId, $userId, $triggerMode, $dealLoadId, $actionTime = 0) {
        $annualizedAmount = 0;
        if ($dealLoadId > 0 && in_array($triggerMode, CouponGroupEnum::$TRIGGER_DEAL_MODES)) {
            $annualizedAmount = O2OUtils::getAnnualizedAmountByDealLoadId($dealLoadId);
        }

        //TODO 获取券组信息
        if (!$this->resendGroup[$couponGroupId]) {
            $request = new SimpleRequestBase();
            $params = array();
            $params['id'] = intval($couponGroupId);
            $params['annualizedAmount'] = floatval($annualizedAmount);
            $request->setParamArray($params);
            $couponGroupInfo = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroup', $request);
            $this->resendGroup[$couponGroupId] = $couponGroupInfo['data'];
        }
        $couponGroupInfo = $this->resendGroup[$couponGroupId];

        $action = $triggerMode;
        if ($action == CouponGroupEnum::TRIGGER_FIRST_DOBID) {
            $firstDealLoad = DealLoadModel::instance()->getFirstDealByUser($userId);
            $dealLoadId = $firstDealLoad['id'];
        } else if ($action == CouponGroupEnum::TRIGGER_ADMIN_PUSH) {
            $maxId = $this->getMaxGiftSendId();
            $dealLoadId = ++$maxId;
        } else if (in_array($action, CouponGroupEnum::$CHARGE_TRIGGER)) {
            $annualizedAmount = 0;
            $chargeInfo = PaymentNoticeModel::instance()->getInfoById($dealLoadId);
            if (empty($chargeInfo)) {
                PaymentApi::log('补发失败，充值订单号不存在'.$dealLoadId.'|triggerMode:'.$action);
                return false;
            } else {
                $money = $chargeInfo['money'];
            }

        }
        $userInfo = UserModel::instance()->findViaSlave($userId, 'id, create_time, refer_user_id, mobile');
        $giftInfo = OtoAcquireLogModel::instance()->getGiftInfo($userId, $action, $dealLoadId);
        $acquireLogId = $giftInfo['id'];
        $actionTime = empty($actionTime) ? time() : $actionTime;
        $GLOBALS['db']->startTrans();
        try {
            if (empty($giftInfo)) {
                $userGiftModel = new OtoAcquireLogModel();
                $userGiftModel->user_id = $userId;
                $userGiftModel->trigger_mode = $action;
                $userGiftModel->deal_load_id = $dealLoadId;
                $userGiftModel->expire_time = $actionTime + 24*3600;
                $userGiftModel->create_time = $actionTime;
                if (in_array($action, CouponGroupEnum::$CHARGE_TRIGGER)) {
                    //充值类型的扩展信息包含充值金额
                    $userGiftModel->extra_info = json_encode(array('deal_money' => $money));
                } else {
                    //其他类型的扩展信息包含年化
                    $userGiftModel->extra_info = json_encode(array('deal_annual_amount' => $annualizedAmount));
                }
                $userGiftModel->save();
                $acquireLogId = $userGiftModel->id;
            }

            $request = new RequestAcquireCoupon();
            $request->setCouponGroupId(intval($couponGroupId));
            $request->setUserId(intval($userId));
            $request->setCouponToken($userId . '_' .$action . '_' . $dealLoadId);
            $request->setReceiverExtra(array('phone' => $userInfo['mobile']));
            $request->setTriggerTime($actionTime);
            $request->setAnnualizedAmount($annualizedAmount);
            $request->setTriggerMode($action);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'acquireCoupon', $request);
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        if (!is_array($response)) {
            return array();
        }

        try {
            $couponInfo = $response['coupon'];
            $couponInfo['productName'] = $response['product']['productName'];
            // 更新用户的领取记录
            $updateData = array();
            $updateData['gift_id'] = $couponInfo['id'];
            $updateData['gift_code'] = $couponInfo['couponNumber'];
            $updateData['gift_group_id'] = $couponInfo['couponGroupId'];
            $updateData['request_status'] = OtoAcquireLogModel::REQUEST_STATUS_SUC;
            $updateData['expire_time'] = $actionTime + 24*3600;
            OtoAcquireLogModel::instance()->updateById($updateData, $acquireLogId);

            // 给邀请人返利
            $res = $this->rebateInviteUser($response, $dealLoadId);
            if (!$res) {
                throw new O2OException('邀请人返利失败', O2OException::CODE_P2P_ERROR);
            }
            // TODO 线上领取即兑换券实时兑换,这块的一致性和完整性还有待保证
            if (in_array($couponGroupInfo['useRules'], CouponGroupEnum::$ONLINE_ATONCE_USE_RULES)) {
                $res = $this->p2pConfirmCoupon($couponInfo['id'], 0, false, $response, $dealLoadId);
                if (!$res) {
                    throw new O2OException('转账返利失败', O2OException::CODE_P2P_ERROR);
                }
            }

            $res = true;
            $tagService = new UserTagService();
            if ($action == CouponGroupEnum::TRIGGER_FIRST_DOBID) {
                $res = $tagService->addUserTagsByConstName($userId, 'O2O_STLQ');
            } else if ($action == CouponGroupEnum::TRIGGER_FIRST_BINDCARD){
                $res = $tagService->addUserTagsByConstName($userId, 'O2O_BKLQ');
            }

            if ($res === false) {
                throw new O2OException("用户[{$userId}]添加领券标签失败");
            }
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
        return $couponInfo;
    }

    /**
     * getExchangeForm
     * 根据券供应商和券类型获取表单
     *
     * @param integer $storeId
     * @param integer $rule
     * @access public
     * @return array()
     */
    public function getExchangeForm($storeId, $rule = CouponGroupEnum::ONLINE_GOODS_REALTIME) {
        // 收货地址静态配置
        $staticFormRules = array(
            CouponGroupEnum::ONLINE_GOODS_REALTIME,
            CouponGroupEnum::ONLINE_GOODS_REPORT,
        );

        if (in_array($rule, $staticFormRules)) {
            return array(
                'storeName' => '',
                'form' => array(
                    'receiverName' => array(
                        'required' => true,
                        'type' => 'string',
                        'name' => 'receiverName',
                        'displayName' => '姓名',
                    ),
                    'receiverPhone' => array(
                        'required' => true,
                        'type' => 'string',
                        'name' => 'receiverPhone',
                        'displayName' => '手机号',
                    ),
                    'receiverCode' => array(
                        'required' => true,
                        'type' => 'string',
                        'name' => 'receiverCode',
                        'displayName' => '邮政编码',
                    ),
                    'receiverAddress' => array(
                        'required' => true,
                        'type' => 'string',
                        'name' => 'receiverAddress',
                        'displayName' => '地址',
                    ),
                ),
                'msgConf' => array(
                    'needMsg' => 0,
                    'msgTpl' => '',
                )
            );
        }

        try {
            if (empty($storeId)) {
                throw new O2OException('门店id为空', O2OException::CODE_P2P_ERROR);
            }

            $request = new SimpleRequestBase();
            $request->setParamArray(array('storeId' => intval($storeId)));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Supplier', 'getFormConfByStoreId', $request);
        } catch (\Exception $e) {
            //TODO 异常处理
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
        return $response['storeFormConf'];
    }

    /**
     * P2P兑换券之后的逻辑
     * @param $couponId int 礼券id
     * @param $storeId int 兑换门店id
     * @param $isExchange bool 是否是兑换请求
     * @param $rebateConfig array 返利配置
     * @param $dealLoadId int 交易id
     * @param $siteId int 分站id，默认是false
     * @return array
     */
    public function p2pConfirmCoupon($couponId, $storeId = 0, $isExchange = false,
                                     $rebateConfig = array(), $dealLoadId = 0, $siteId = false) {
        //TODO 现在couponGroup这个判断主要是为了兼容新逻辑切换,因为之前rebateConfig存的数据不完整
        if (empty($rebateConfig) || !isset($rebateConfig['couponGroup'])) {
            $request = new RequestGetCouponInfo();
            $request->setCouponId(intval($couponId));
            $couponInfo = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getCouponInfo', $request);
        } else {
            $couponInfo = $rebateConfig;
        }

        $res = $this->transferMoneyOnConfirm($storeId, $couponInfo, $dealLoadId, $siteId);
        if (!$res) {
            return false;
        }

        // TODO 因为couponInfo取的快照，兑换时间直接取当前时间
        $couponInfo['coupon']['updateTime'] = time();
        if ($isExchange) {
            return $couponInfo;
        } else {
            return array(
                'productName' => $couponInfo['product']['productName'],
                'goodPrice' => $couponInfo['couponGroup']['goodPrice'],
                'storeAddr' => '',
                'updateTime' => $couponInfo['coupon']['updateTime'],
                'ownerUserId' => $couponInfo['coupon']['ownerUserId']
            );
        }
    }

    /**
     * 读取TRIGGER_ADMIN_PUSH最大的deal_load_id
     * @return integer
     */
    public function getMaxGiftSendId() {
        $sql = "SELECT id FROM firstp2p_oto_acquire_log order by id desc";
        $id = $GLOBALS['db']->getOne($sql, true);
        return $id > 0 ? intval($id) : 0;
    }

    /**
     * 直接发送领取机会
     * @param $chance array 领取机会信息
     * @return boolean
     */
    public function doSendGift($chance) {
        $res = OtoAcquireLogModel::instance()->addLog($chance, OtoAcquireLogModel::REQUEST_STATUS_SUC);
        return $res;
    }

    public function getAnnualizedAmountByDealLoadId($dealLoadId, $slave = true) {
        return O2OUtils::getAnnualizedAmountByDealLoadId($dealLoadId, $slave);
    }

    /**
     * 获取用户未领取的列表
     */
    public function getUnpickCount($userId, $status = OtoAcquireLogModel::UNPICK_UNEXPIRED) {

        $userId = intval($userId);
        $currentTime = time();
        if($status == OtoAcquireLogModel::UNPICK_UNEXPIRED) {
            $condition = "user_id = $userId AND request_status = " .OtoAcquireLogModel::REQUEST_STATUS_SUC. " AND gift_id = 0 AND expire_time > $currentTime";
        } elseif ($status == OtoAcquireLogModel::UNPICK_EXPIRED) {
            $condition = "user_id = $userId AND request_status = " .OtoAcquireLogModel::REQUEST_STATUS_SUC. " AND gift_id = 0 AND expire_time <= $currentTime";
        } else {
            $condition = "user_id = $userId AND request_status = " .OtoAcquireLogModel::REQUEST_STATUS_SUC. " AND gift_id = 0";
        }
        return OtoAcquireLogModel::instance()->countViaSlave($condition);
    }

    /**
     * 领取AR券
     */
    public function doAcquireArCoupon($userId, $trackId, $trackName) {
        $couponService = new O2OCouponService();
        $response = $couponService->acquireArCoupon($userId, $trackId, $trackName);

        $couponInfo = $response['coupon'];
        $couponInfo['productName'] = $response['product']['productName'];
        $couponInfo['isShowCouponNumber'] = $response['couponGroup']['isShowCouponNumber'];
        $couponInfo['couponDesc'] = $response['couponGroup']['couponDesc'];
        $couponInfo['useRules'] = $response['couponGroup']['useRules'];
        $couponInfo['storeId'] = $response['couponGroup']['useFormId'];
        $couponInfo['goodPrice'] = $response['couponGroup']['goodPrice'];

        // 领取返利
        $res = $this->rebateInviteUser($response, 0);
        if (!$res) {
            // 失败尝试重试
            throw new O2OException('邀请人返利失败', O2OException::CODE_RPC_TIMEOUT);
        }

        // 兑换返利
        if (in_array($couponInfo['useRules'], CouponGroupEnum::$ONLINE_ATONCE_USE_RULES)) {
            $res = $this->p2pConfirmCoupon($couponInfo['id'], 0, false, $response);
            if (!$res) {
                // 失败尝试重试
                throw new O2OException('兑换转账返利失败', O2OException::CODE_RPC_TIMEOUT);
            }
        }

        return $couponInfo;
    }

    /**
     * doAcquireCoupon
     * 领取券
     *
     * @param string $couponGroupId
     * @param integer $userId
     * @param integer $action
     * @access public
     * @return array
     */
    public function doAcquireCoupon($couponGroupId, $userId, $action, $dealLoadId, $mobile, $dealType) {
        $acquireLogInfo = OtoAcquireLogModel::instance()->getGiftInfo($userId, $action, $dealLoadId, $dealType);
        if (empty($acquireLogInfo)) {
            throw new \Exception('没有落单记录');
        }

        if (empty($mobile)) {
            $userInfo = UserModel::instance()->findViaSlave($userId, 'mobile');
            $mobile = $userInfo['mobile'];
        }

        $bidAmount = 0;
        $annualizedAmount = 0;
        $action = $acquireLogInfo['o2o_trigger_mode'];
        if (in_array($action, CouponGroupEnum::$TRIGGER_DEAL_MODES) && !empty($acquireLogInfo['extra_info'])) {
            $annualizedAmount = $acquireLogInfo['extra_info']['deal_annual_amount'];
            $bidAmount = $acquireLogInfo['extra_info']['deal_money'];
        }

        $couponToken = $userId . '_' .$action . '_' . $dealLoadId . '_' . $dealType;
        $request = new RequestAcquireCoupon();
        $request->setCouponGroupId(intval($couponGroupId));
        $request->setUserId(intval($userId));
        $request->setCouponToken($couponToken);
        //TODO 临时传用户Phone
        $request->setReceiverExtra(array('phone' => $mobile));
        $request->setTriggerTime($acquireLogInfo['create_time']);
        $request->setBidAmount($bidAmount);
        $request->setAnnualizedAmount($annualizedAmount);
        $request->setTriggerMode($acquireLogInfo['trigger_mode']);
        $request->setDealLoadId($dealLoadId);
        $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'acquireCoupon', $request, 4);

        $couponInfo = $response['coupon'];
        $couponInfo['productName'] = $response['product']['productName'];
        $couponInfo['isShowCouponNumber'] = $response['couponGroup']['isShowCouponNumber'];
        $couponInfo['couponDesc'] = $response['couponGroup']['couponDesc'];
        $couponInfo['useRules'] = $response['couponGroup']['useRules'];
        $couponInfo['storeId'] = $response['couponGroup']['useFormId'];
        //处理兑换url
        if (in_array($response['couponGroup']['useRules'], self::$rulesRequireForm)) {
            $couponInfo['p2pExchangeUrl'] = "/gift/exchangeForm?couponId={$couponInfo['id']}&storeId={$response['couponGroup']['useFormId']}&useRules={$response['couponGroup']['useRules']}";
            $couponInfo['openExchangeUrl'] = "/coupon/acquireForm?couponId={$couponInfo['id']}&storeId={$response['couponGroup']['useFormId']}&useRules={$response['couponGroup']['useRules']}&couponGroupId={$response['couponGroup']['id']}";
        }

        //显示兑换标题
        switch ($response['couponGroup']['useRules']) {
        case CouponGroupEnum::ONLINE_GOODS_REALTIME:
        case CouponGroupEnum::ONLINE_GOODS_REPORT:
            $couponInfo['p2pUsedDesc']['title'] = '收货信息';
            break;
        case CouponGroupEnum::ONLINE_COUPON_REPORT:
        case CouponGroupEnum::ONLINE_COUPON_REALTIME:
            $couponInfo['p2pUsedDesc']['title'] = '收券信息';
            break;
        default:
            $couponInfo['p2pUsedDesc']['title'] = '';
            break;
        }

        // 下面的业务放在一个事务里面
        $GLOBALS['db']->startTrans();
        try {
            $updateData = array();
            $updateData['gift_group_id'] = $couponGroupId;
            $updateData['gift_id'] = $couponInfo['id'];
            $updateData['gift_code'] = $couponInfo['couponNumber'];
            OtoAcquireLogModel::instance()->updateById($updateData, $acquireLogInfo['id']);

            // 给邀请人返利
            $siteId = isset($acquireLogInfo['extra_info']['site_id'])
                ? $acquireLogInfo['extra_info']['site_id']
                : \libs\utils\Site::getId();

            $res = $this->rebateInviteUser($response, $dealLoadId, $siteId);
            if (!$res) {
                throw new O2OException('邀请人返利失败', O2OException::CODE_P2P_ERROR);
            }

            // TODO 线上领取即兑换券实时兑换,这块的一致性和完整性还有待保证
            if (in_array($couponInfo['useRules'], CouponGroupEnum::$ONLINE_ATONCE_USE_RULES)) {
                $res = $this->p2pConfirmCoupon($couponInfo['id'], 0, false, $response, $dealLoadId, $siteId);
                if (!$res) {
                    throw new O2OException('兑换转账返利失败', O2OException::CODE_P2P_ERROR);
                }
            }

            $tagService = new UserTagService();
            $res = true;
            if ($action == CouponGroupEnum::TRIGGER_FIRST_DOBID) {
                $res = $tagService->addUserTagsByConstName($userId, 'O2O_STLQ');
            } else if ($action == CouponGroupEnum::TRIGGER_FIRST_BINDCARD){
                $res = $tagService->addUserTagsByConstName($userId, 'O2O_BKLQ');
            }

            if (!$res) {
                throw new O2OException('用户添加tag失败', O2OException::CODE_P2P_ERROR);
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            PaymentApi::log("O2OService.doAcquireCoupon: ".$e->getMessage(), Logger::ERR);
            throw $e;
        }

        try {
            $remoteTagService = new RemoteTagService();
            $remoteTagService->addUserTag($userId, 'O2O_ACQUIRE_COUPON_GROUP', $couponGroupId);
        } catch (\Exception $e) {
            //TODO
        }

        return $couponInfo;
    }

    /**
     * 领取多个礼券，支持同步返回结果和异步执行
     *
     * @param $userId int 用户id
     * @param $couponGroupId string 礼券券组id，多个用逗号分隔
     * @param $token string token码
     * @param string $mobile 返利手机号
     * @param int $dealLoadId 交易id
     * @param $isSyncResult bool 是否返回同步结果,true为返回，false不返回（会异步执行）
     * @param $rebateAmount float 返利金额，覆盖o2o的券组返利金额
     * @param $rebateLimit int 返利期限，覆盖o2o的券组的返利期限
     * @access public
     * @return array | bool
     */
    public function acquireCoupons($userId, $couponGroupIds, $token, $mobile = '', $dealLoadId = 0,
                                   $isSyncResult = false, $rebateAmount = 0, $rebateLimit = 0) {
        try {
            $params = array($userId, $couponGroupIds, $token, $mobile, $dealLoadId, $rebateAmount, $rebateLimit);
            PaymentApi::log('acquireCoupons-params:'.json_encode($params, JSON_UNESCAPED_UNICODE));

            if (empty($userId) || !is_numeric($userId) || $userId < 0) {
                throw new O2OException('用户id不正确');
            }

            if (empty($couponGroupIds)) {
                throw new O2OException('礼券券组id不能为空');
            }

            if (empty($token)) {
                throw new O2OException('token不能为空');
            }

            if ($isSyncResult) {
                return $this->doAcquireCoupons($userId, $couponGroupIds, $token, $mobile, $dealLoadId, $rebateAmount, $rebateLimit);
            } else {
                // 异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireCoupons', $params);
                $taskId = $taskObj->doBackground($event, 10);
                return true;
            }
        } catch (\Exception $e) {
            if ($isSyncResult && $e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireCoupons', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    public function doAcquireCoupons($userId, $couponGroupIds, $token, $mobile = '',
                                     $dealLoadId = 0, $rebateAmount = 0, $rebateLimit = 0) {
        $res = array();
        $groupIds = explode(',', $couponGroupIds);
        foreach ($groupIds as $key=>$groupId) {
            if (empty($groupId) || !is_numeric($groupId)) {
                continue;
            }

            $couponToken = $key == 0 ? $token : $token.'_'.$key;
            $coupon = $this->acquireAllowanceCoupon($groupId, $userId, $couponToken, $mobile, $dealLoadId,
                $rebateAmount, $rebateLimit);

            $res[$groupId] = $coupon;
        }

        return $res;
    }

    /**
     * 领取多个礼券，支持同步返回结果和异步执行
     *
     * @param $userInfo array 二维数组用户信息：id或mobile array(array('userId'=>123,'token'=>'chunyu001', 'money'=>12),array('mobile'=>13813813138,'token'=>'chunyu002', 'money'=>23))
     * 支持给多个用户传不同金额的红包券组
     * @param $couponGroupId string 礼券券组id
     * @param $isSyncResult boolean 是否同步返回数据，默认同步
     * @return array
     */
    public function acquireCouponsForBatchUsers($userInfo, $couponGroupId, $isSyncResult=true) {
        try {
            $params = array($userInfo, $couponGroupId, $isSyncResult);
            PaymentApi::log('acquireCouponsForBatchUsers-params:'.json_encode($params, JSON_UNESCAPED_UNICODE));

            if ($isSyncResult) {
                return $this->doAcquireCouponsForBatchUsers($userInfo, $couponGroupId, $isSyncResult);
            } else {
                // 异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireCouponsForBatchUsers', $params);
                $taskId = $taskObj->doBackground($event, 10);
                return true;
            }
        } catch (\Exception $e) {
            if ($isSyncResult && $e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireCouponsForBatchUsers', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    public function doAcquireCouponsForBatchUsers($userInfo, $couponGroupId, $isSyncResult) {
        $res = array();
        $userService = new UserService();
        foreach ($userInfo as $key=>$item) {
            $userId = 0;
            $mobile = '';
            if (isset($item['userId'])) {
                //如果是用户id
                $user = $userService->getUser($item['userId']);
                if (empty($user)) {
                    $res[$item['token']]['errMsg'] = $item['userId'].'用户不存在';
                    continue;
                }
                $userId = $item['userId'];
                $mobile = $user['mobile'];
            } else {
                //指定手机号发送,先查询用户id
                $userId = $userService->getUserIdByMobile($item['mobile']);
                if (empty($userId)) {
                    $res[$item['token']]['errMsg'] = $item['mobile'].'手机号错误或用户不存在';
                    continue;
                }
                $mobile = $item['mobile'];

            }

            $coupon = $this->acquireAllowanceCoupon($couponGroupId, $userId, $item['token'], $mobile, '', $item['money']);
            if (isset($coupon['coupon'])) {
                $res[$item['token']] = array('couponId' => $coupon['coupon']['id'], 'userId' => $userId, 'mobile' => $mobile, 'money' => $item['money']);
            } else {
                $res[$item['token']]['errMsg'] = '礼券发送失败';
            }
        }
        return $res;
    }

    /**
     * 查询红包礼券对应的红包状态
     * @param array $list 礼券数组 (couponId=>token,couponId2=>token2)
     */
    public function getCouponBonusStatus($list) {
        $res = array();
        $bonusService = new WXBonusService();
        $couponIds = array_keys($list);
        PaymentApi::log('getCouponBonusStatus couponIds:,'.json_encode($couponIds));
        try{
            if (empty($couponIds)) {
                return false;
            }
            $cond = 'gift_id in('. implode(",", $couponIds).') AND allowance_type ='.CouponGroupEnum::ALLOWANCE_TYPE_BONUS.' AND action_type='.OtoAllowanceLogModel::ACTION_TYPE_ACQUIRE;//领取返利
            $allowanceLogs = OtoAllowanceLogModel::instance()->getAllowanceLogByCond($cond, 'allowance_id, gift_id');
            $coupons = array();
            if($allowanceLogs) {
                foreach($allowanceLogs as $allowance) {
                    if (isset($allowance['allowance_id'])) {
                        $coupons[$allowance['allowance_id']] = $allowance['gift_id'];
                    } else {
                        continue;
                    }
                }
            }
            $allowanceIds = array_keys($coupons);
            if (empty($allowanceIds)) {
                return false;
            }

            $bonusLogs = $bonusService->getConsumeInfo($allowanceIds);
            if ($bonusLogs) {
                foreach($bonusLogs as $item) {
                    $openToken = $list[$coupons[$item['bonusId']]];
                    $res[$openToken] = $item;
                }
            }
            return $res;
        } catch (\Exception $e) {
            PaymentApi::log('getCouponBonusStatus failed,'.$e->getMessage());
            return false;
        }
    }

    /**
     * 领取返利礼券
     *
     * @param $couponGroupId int 礼券券组id
     * @param $userId int 用户id
     * @param $token string token码
     * @param $mobile string 返利手机号
     * @param $dealLoadId int 凭证id
     * @param $rebateAmount float 返利金额,透传会覆盖券组的金额（一般用于红包）
     * @param $rebateLimit int 返利期限,透传会覆盖券组的期限（一般用于红包）
     * @param $logId int 行为记录id，一般为acquire log的主键id
     * @param $annualizedAmount float 年化金额，用于红包公式，默认为0
     * @access public
     * @return array
     */
    public function acquireAllowanceCoupon(
        $couponGroupId,
        $userId,
        $token,
        $mobile = '',
        $dealLoadId = 0,
        $rebateAmount = 0,
        $rebateLimit = 0,
        $logId = 0,
        $annualizedAmount = 0
    ) {
        // 对于部分领取及兑换券，需要手机号
        if (empty($mobile)) {
            $userInfo = UserModel::instance()->findViaSlave($userId, 'mobile');
            $mobile = $userInfo['mobile'];
        }

        $params = array(
            'couponGroupId' => $couponGroupId,
            'userId' => $userId,
            'token' => $token,
            'mobile' => $mobile,
            'dealLoadId' => $dealLoadId,
            'rebateAmount' => $rebateAmount,
            'rebateLimit' => $rebateLimit,
            'acquireLogId' => $logId
        );
        PaymentApi::log('acquireAllowanceCoupon param: '.json_encode($params));

        // 年化金额，为了支持年化红包的计算
        // 这里考虑到方便，没有通过参数透传，直接通过logId从acquireLog里面获取
        if ($annualizedAmount == 0 && $logId > 0 && $dealLoadId > 0) {
            $annualizedAmount = OtoAcquireLogModel::instance()->getAnnuAmountById($logId, true);
        }

        $request = new RequestAcquireCoupon();
        $request->setCouponGroupId(intval($couponGroupId));
        $request->setUserId(intval($userId));
        $request->setCouponToken($token);
        //TODO 临时传用户Phone
        $request->setReceiverExtra(array('phone' => $mobile));
        $request->setDealLoadId($dealLoadId);
        $request->setRebateAmount($rebateAmount);
        $request->setRebateLimit($rebateLimit);
        $request->setAnnualizedAmount($annualizedAmount);
        $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'acquireCoupon', $request, 3);
        if ($response) {
            $response['logId'] = $logId;
        }

        // 给邀请人返利
        $res = $this->rebateInviteUser($response, $dealLoadId);
        if (!$res) {
            throw new O2OException('邀请人返利失败', O2OException::CODE_P2P_ERROR);
        }

        // TODO 线上领取即兑换券实时兑换,这块的一致性和完整性还有待保证
        $couponInfo = $response['coupon'];
        if (in_array($couponInfo['useRules'], CouponGroupEnum::$ONLINE_ATONCE_USE_RULES)) {
            $res = $this->p2pConfirmCoupon($couponInfo['id'], 0, false, $response, $dealLoadId);
            if (!$res) {
                throw new O2OException('转账返利失败', O2OException::CODE_P2P_ERROR);
            }
        }
        PaymentApi::log('acquireAllowanceCoupon success, couponId: '.$couponInfo['id']);
        return $response;
    }

    /**
     * acquireMedalCoupon
     * medal领取券
     *
     * @param string $couponGroupIds
     * @param integer $userId
     * @param integer $medalId
     * @access public
     * @return bool
     */
    public function acquireMedalCoupon($couponGroupIds, $userId, $medalId = 0, $mobile = '') {
        try {
            $params = array($couponGroupIds, $userId, $medalId, $mobile);
            $this->doAcquireMedalCoupon($couponGroupIds, $userId, $medalId, $mobile);
            return true;
        } catch (\Exception $e) {
            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireMedalCoupon', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * doAcquireMedalCoupon
     * medal领取券
     *
     * @param string $couponGroupId
     * @param integer $userId
     * @param integer $action
     * @access public
     * @return array
     */
    public function doAcquireMedalCoupon($couponGroupIds, $userId, $dealLoadId, $mobile) {
        foreach($couponGroupIds as $index =>$groupId) {
            $token = 'medal_'.$userId.'_'.$groupId.'_'.$dealLoadId.'_'.$index;
            $this->acquireAllowanceCoupon($groupId, $userId, $token, $mobile, $dealLoadId);
        }
    }

    /**
     * 用户确认兑换
     */
    public function doSetCouponConfirm($couponId, $storeId) {
        $acquireLogInfo = OtoAcquireLogModel::instance()->getByGiftId($couponId);
        $userId = $acquireLogInfo ? $acquireLogInfo['user_id'] : 0;
        //TODO 存储兑换记录
        $request = new RequestSetCouponConfirm();
        $request->setCouponId(intval($couponId));
        $request->setStoreId(intval($storeId));
        $request->setOwnerUserId(intval($userId));
        $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'setCouponConfirm', $request);
        // 兑换成功，进行兑换返利
        return $this->p2pConfirmCoupon($couponId, $storeId, false);
    }

    /**
     * doExchangeCoupon
     * 兑换券
     *
     * @param string $couponId 优惠券编号
     * @param integer $userId 用户编号
     * @param integer $storeId 商户编号
     * @param array $receiverParam 收货信息[收货类型的券]
     * @param array $extraParam 信息[收券类型的券]
     * @param array $msgConf 短信配置
     * @return array
     * @author liguizhi@ucfgroup.com
     * @date 2015-7-6
     */
    public function doExchangeCoupon($couponId, $userId, $storeId, $receiverParam = array(), $extraParam = array(), $msgConf = array()) {
        $request = new RequestCouponExchange();
        $request->setCouponId(intval($couponId));
        $request->setStoreId(intval($storeId));
        $request->setOwnerUserId(intval($userId));
        if (!empty($receiverParam)) {
            //receiverParam是收货信息数组，包含字段receiverName,receiverPhone,receiverCode,receiverArea,receiverAddress
            foreach($receiverParam as $k => $v) {
                $$k = $v;//解析receiverParam
            }
        }
        if (isset($receiverName)) {
            $request->setReceiverName($receiverName);
        }
        if (isset($receiverPhone)) {
            $request->setReceiverPhone($receiverPhone);
        }
        if (isset($receiverCode)) {
            $request->setReceiverCode($receiverCode);
        }
        if (isset($receiverArea)) {
            $request->setReceiverArea($receiverArea);
        }
        if (isset($receiverAddress)) {
            $request->setReceiverAddress($receiverAddress);
        }
        if (!empty($extraParam)) {
            //extraParam是收券信息数组，包含字段username,phone,idno,email
            foreach($extraParam as $key => $val) {
                $$key = $val;//解析extraParam
            }
        }
        $extra = array();
        if (isset($userName)) {
            $extra['userName'] = $userName;
        }
        if (isset($phone)) {
            $extra['phone'] = $phone;
        }
        if (isset($idno)) {
            $extra['idno'] = $idno;
        }
        if (isset($email)) {
            $extra['email'] = $email;
        }
        $request->setReceiverExtra($extra);
        $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'exchangeCoupon', $request);
        $couponInfo = $this->p2pConfirmCoupon($couponId, $storeId, true);
        if (empty($couponInfo)) {
            throw new O2OException('转账返利失败', O2OException::CODE_P2P_ERROR);
        }

        // 如果配置需要发短信，则调用短信系统发送短信
        if (isset($phone) && !empty($phone) && ($msgConf['needMsg'] == 1)) {
            $msgData = array($msgConf['storeName'], $couponInfo['coupon']['couponNumber'], $msgConf['storeTel']);
            $res = \SiteApp::init()->sms->send($phone, json_encode($msgData), $msgConf['tplId']);
            if(!empty($res['status']) && $res['status'] == 1) {
                PaymentApi::log("O2OService.".__FUNCTION__.' phone:'.$phone .json_encode($msgData)." success", Logger::INFO);
            } else {
                PaymentApi::log("O2OService.".__FUNCTION__.' phone:'.$phone .json_encode($msgData)." fail", Logger::ERR);
            }
        }
        return $couponInfo;
    }

    /**
     * acquireExchange
     * 领取券+兑换(新版优化)
     *
     * @param string $couponGroupId 礼券券组id
     * @param int $userId 用户id
     * @param int $action 触发动作
     * @param int $dealLoadId int 交易id
     * @param string $mobile 手机号
     * @param array $receiverExtra 收获地址相关信息
     * @param array $extraParam 额外信息
     * @param bool $isNeedExchange 是否兑换
     * @access public
     * @return array
     */
    public function acquireExchange($couponGroupId, $userId, $action, $dealLoadId = 0, $mobile = '',
                                    $receiverExtra = array(), $extraParam = array(), $isNeedExchange = 1,
                                    $dealType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        try {
            return $this->doAcquireExchange($couponGroupId, $userId, $action, $dealLoadId, $mobile,
                $receiverExtra, $extraParam, $isNeedExchange, $dealType);
        } catch (\Exception $e) {
            $params = array($couponGroupId, $userId, $action, $dealLoadId, $mobile,
                $receiverExtra, $extraParam, $isNeedExchange, $dealType);

            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doAcquireExchange', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * doAcquireExchange
     * 领取券+兑换券
     *
     * @param string $couponGroupId 礼券券组id
     * @param int $userId 用户id
     * @param int $action 触发动作
     * @param int $dealLoadId int 交易id
     * @param string $mobile 手机号
     * @param array $receiverExtra 收获地址相关信息
     * @param array $extraParam 额外信息
     * @param bool $isNeedExchange 是否兑换
     * @access public
     * @return array
     */
    public function doAcquireExchange($couponGroupId, $userId, $action, $dealLoadId, $mobile,
                                      $receiverExtra, $extraParam, $isNeedExchange, $dealType) {
        $receiverAddress = $receiverName = $receiverPhone = $receiverCode = $receiverArea =  '';
        //增加receiverArea 字段默认值，否则下面extract方法不会解析reveiverExtra里的值
        extract($receiverExtra, EXTR_IF_EXISTS);

        $acquireLogInfo = OtoAcquireLogModel::instance()->getGiftInfo($userId, $action, $dealLoadId, $dealType);
        if (empty($acquireLogInfo)) {
            throw new \Exception('未查询到领取机会');
        }

        if (empty($mobile)) {
            $userInfo = UserModel::instance()->findViaSlave($userId, 'mobile');
            $mobile = $userInfo['mobile'];
        }

        $triggerMode = $acquireLogInfo['o2o_trigger_mode'];
        $annualizedAmount = $acquireLogInfo['extra_info']['deal_annual_amount'];
        $bidAmount = $acquireLogInfo['extra_info']['deal_money'];
        $couponToken = $userId . '_' .$triggerMode . '_' . $dealLoadId . '_' . $dealType;
        $request = new RequestAcquireCoupon();
        $request->setCouponGroupId(intval($couponGroupId));
        $request->setUserId(intval($userId));
        $request->setCouponToken($couponToken);
        $request->setReceiverExtra($extraParam);
        $request->setReceiverName($receiverName);
        $request->setReceiverPhone($receiverPhone);
        $request->setReceiverCode($receiverCode);
        $request->setReceiverAddress($receiverAddress);
        $request->setReceiverArea($receiverArea);
        $request->setTriggerTime($acquireLogInfo['create_time']);
        $request->setBidAmount($bidAmount);
        $request->setAnnualizedAmount(floatval($annualizedAmount));
        $request->setTriggerMode($triggerMode);
        $request->setDealLoadId($dealLoadId);
        $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'generateCoupon', $request, 4);

        $couponInfo = $response['coupon'];
        $couponInfo['productName'] = $response['product']['productName'];
        $couponInfo['isShowCouponNumber'] = $response['couponGroup']['isShowCouponNumber'];
        $couponInfo['couponDesc'] = $response['couponGroup']['couponDesc'];
        $couponInfo['useRules'] = $response['couponGroup']['useRules'];
        $couponInfo['storeId'] = $response['couponGroup']['useFormId'];
        $couponInfo['goodPrice'] = $response['couponGroup']['goodPrice'];
        // 处理兑换url
        if (in_array($response['couponGroup']['useRules'], self::$rulesRequireForm)) {
            $couponInfo['p2pExchangeUrl'] = "/gift/exchangeForm?couponId={$couponInfo['id']}&storeId={$response['couponGroup']['useFormId']}&useRules={$response['couponGroup']['useRules']}";
            $couponInfo['openExchangeUrl'] = "/coupon/acquireForm?couponId={$couponInfo['id']}&storeId={$response['couponGroup']['useFormId']}&useRules={$response['couponGroup']['useRules']}&couponGroupId={$response['couponGroup']['id']}";
        }

        //显示兑换标题
        switch ($response['couponGroup']['useRules']) {
        case CouponGroupEnum::ONLINE_GOODS_REALTIME:
        case CouponGroupEnum::ONLINE_GOODS_REPORT:
            $couponInfo['p2pUsedDesc']['title'] = '收货信息';
            break;
        case CouponGroupEnum::ONLINE_COUPON_REPORT:
        case CouponGroupEnum::ONLINE_COUPON_REALTIME:
            $couponInfo['p2pUsedDesc']['title'] = '收券信息';
            break;
        default:
            $couponInfo['p2pUsedDesc']['title'] = '';
            break;
        }

        // 下面的业务放在一个事务里面
        $GLOBALS['db']->startTrans();
        try {
            $updateData = array();
            $updateData['gift_group_id'] = $couponGroupId;
            $updateData['gift_id'] = $couponInfo['id'];
            $updateData['gift_code'] = $couponInfo['couponNumber'];
            OtoAcquireLogModel::instance()->updateById($updateData, $acquireLogInfo['id']);
            $siteId = $acquireLogInfo['extra_info']['site_id'];

            // 给邀请人返利
            $res = $this->rebateInviteUser($response, $dealLoadId, $siteId);
            if (!$res) {
                throw new O2OException('邀请人返利失败', O2OException::CODE_P2P_ERROR);
            }

            // TODO 线上领取即兑换券实时兑换,这块的一致性和完整性还有待保证
            if (!in_array($couponInfo['useRules'], self::$acquireExchangeTriggerConfirm)) {
                $res = $this->p2pConfirmCoupon($couponInfo['id'], 0, false, $response, $dealLoadId, $siteId);
                if (!$res) {
                    throw new O2OException('转账返利失败', O2OException::CODE_P2P_ERROR);
                }
            }

            $tagService = new UserTagService();
            $res = true;
            if ($action == CouponGroupEnum::TRIGGER_FIRST_DOBID) {
                $res = $tagService->addUserTagsByConstName($userId, 'O2O_STLQ');
            } else if ($action == CouponGroupEnum::TRIGGER_FIRST_BINDCARD){
                $res = $tagService->addUserTagsByConstName($userId, 'O2O_BKLQ');
            }
            if (!$res) {
                throw new O2OException('用户添加tag失败', O2OException::CODE_P2P_ERROR);
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            PaymentApi::log("O2OService.doAcquireCoupon: ".$e->getMessage(), Logger::ERR);
            throw $e;
        }

        try {
            $remoteTagService = new RemoteTagService();
            $remoteTagService->addUserTag($userId, 'O2O_ACQUIRE_COUPON_GROUP', $couponGroupId);
        } catch (\Exception $e) {
            //TODO
        }
        return $couponInfo;
    }

    /**
     * o2o凭证记录，历史原因，定义为静态方法
     * 双写数据，p2p写完acquireLog后，往o2o同步记录
     *
     * @param $userId int 用户id
     * @param $action int 触发动作
     * @param $dealLoadId int 交易id
     * @param $siteId int 分站id
     * @param $money float 金额
     * @param $annualizedAmount float 年化额
     * @param $consumeType int 业务类型:p2p,duotou,要依据业务类型在p2p修正trigger_mode
     * @param $triggerType int 触发类型
     * @param $extra array 额外信息
     * @access public
     * @return bool 是否落单成功
     */
    public static function triggerO2OOrder($userId, $action, $dealLoadId = 0, $siteId = 0,
                                           $money = 0, $annualizedAmount = 0,
                                           $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P,
                                           $triggerType = CouponGroupEnum::TRIGGER_TYPE_P2P,
                                           $extra = array()) {
        // 落单记录
        $logParams = array(
            'userId'=>$userId,
            'action'=>$action,
            'dealLoadId'=>$dealLoadId,
            'siteId'=>$siteId,
            'money'=>$money,
            'annualizedAmount'=>$annualizedAmount,
            'consumeType'=>$consumeType,
            'triggerType'=>$triggerType,
            'extra'=>$extra
        );
        PaymentApi::log("[req]triggerO2OOrder:".json_encode($logParams, JSON_UNESCAPED_UNICODE), Logger::INFO);

        // 如果siteId为0或空，则取当前的siteId值
        if ($siteId == 0) {
            $siteId = \libs\utils\Site::getId();
        }

        $extraInfo = array(
            'deal_money' => $money,
            'deal_annual_amount' => $annualizedAmount,
            'site_id' => $siteId,
            'consume_type' => $consumeType,
            'trigger_type' => $triggerType
        );

        if (is_array($extra)) {
            $extraInfo = array_merge($extra, $extraInfo);
        }

        $acquireLogModel = OtoAcquireLogModel::instance();
        // 获取用户券信息
        $giftInfo = $acquireLogModel->getGiftInfo($userId, $action, $dealLoadId, $consumeType);
        // 已经落过单了
        if ($giftInfo) {
            return true;
        }

        $GLOBALS['db']->startTrans();
        try{
            // 进行落单处理
            $action = $acquireLogModel->fixOtoAcquireLogAction($userId, $action);
            $data = array(
                'user_id' => $userId,
                'deal_load_id' => $dealLoadId,
                'extra_info' => json_encode($extraInfo, JSON_UNESCAPED_UNICODE),
                'trigger_mode' => $action
            );
            $res = $acquireLogModel->addLog($data);
            if (!$res) {
                throw new \Exception('O2O触发落单失败');
            }

            // 往o2o同步acquireLog数据
            $jobs_model = new JobsModel();
            $triggerMode = $action;
            // 对于新的o2o系统，需要修正action的值
            if ($action == CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID
                || $action == CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID) {
                $triggerMode = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
            }

            // 领取记录的凭证
            $giftToken = $consumeType.'_'.$userId.'_'.$triggerMode.'_'.$dealLoadId;
            $extraInfo['createTime'] = time();
            $triggerParam = array(
                'userId' => $userId,
                'action' => $triggerMode,
                'token' => $giftToken,
                'dealType' => $consumeType,
                'dealLoadId' => $dealLoadId,
                'triggerType' => $triggerType,
                'extra' => $extraInfo,
            );

            $jobs_model->priority = JobsModel::PRIORITY_O2O_TRIGGER;
            // 删除停止报警 --20191023
            //$r = $jobs_model->addJob('\core\service\oto\O2OCouponGroupService::addTriggerLog', $triggerParam);
            //if ($r === false) {
            //    throw new \Exception("添加O2O触发记录失败");
            //}
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return true;
    }

    public static function getSiteO2OStatus() {
        // 验证分站开关
        $siteId = \libs\utils\Site::getId();
        $showO2O = intval(get_config_db('IS_O2O_OPEN', $siteId));
        if (!$showO2O) {
            return false;
        }

        return true;
    }

    public function logInfo($msg, $functionName, $data = array()) {
        PaymentApi::log("O2OService.$functionName:".json_encode($msg, JSON_UNESCAPED_UNICODE).json_encode($data, JSON_UNESCAPED_UNICODE), Logger::INFO);
    }

    /**
     * sendAward
     * 第三方直接发券
     *
     * @access public
     * @param $couponGroupId int 礼券券组id
     * @param $userId int 用户id
     * @param $dealLoadId int 订单id
     * @param $siteId int 来源id，分站id
     * @return array
     */
    public function sendAward($couponGroupId, $userId, $dealLoadId, $siteId) {
        PaymentApi::log('sendAward param|couponGroupId|'.($couponGroupId). '|userId:'.$userId.'|dealLoadId|'.$dealLoadId);
        //TODO 获取券组信息,先检测是否允许发放该券组
        $request = new SimpleRequestBase();
        $params = array();
        $params['id'] = intval($couponGroupId);
        $params['annualizedAmount'] = 0;
        $request->setParamArray($params);
        $response = $this->requestO2O('\NCFGroup\O2O\Services\CouponGroupInfo', 'getCouponGroup', $request);
        $couponGroupInfo = $response['data'];
        if(empty($couponGroupInfo) || ($couponGroupInfo['isSendByPartner'] != 1) || ($couponGroupInfo['fromSiteId'] != $siteId)) {
            PaymentApi::log('sendAward checkSite error: siteId|'.$siteId. "|fromSiteId:". $couponGroupInfo['fromSiteId']. "|isSendByPartner:".$couponGroupInfo['isSendByPartner']);
            return false;
        }

        try {
            $token = 'award_'.$userId.'_'.$siteId.'_'.$dealLoadId;
            $params = array($couponGroupId, $userId, $token, '', $dealLoadId);
            return $this->acquireAllowanceCoupon($couponGroupId, $userId, $token, '', $dealLoadId);
        } catch (\Exception $e) {
            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('acquireAllowanceCoupon', $params);
                $taskId = $taskObj->doBackground($event, 10);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * chargeTriggerO2O充值触发o2o
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2016-06-15
     * @param mixed $userId
     * @param mixed $action
     * @param mixed $orderIdj
     * @param mixed $money
     * @param int $siteId
     * @param int $withdrawTime
     * @access public
     * @return bool
     */
    public function chargeTriggerO2O($userId, $action, $orderId, $money, $siteId, $withdrawTime) {
        $params = array(
            'userId'=>$userId,
            'action'=>$action,
            'orderId'=>$orderId,
            'money'=>$money,
            'siteId'=>$siteId,
            'withdrawTime'=>$withdrawTime
        );
        PaymentApi::log('chargeTriggerO2O params: '.json_encode($params, JSON_UNESCAPED_UNICODE));

        try {
            $extra = array('withdraw_time'=>$withdrawTime);
            // 生成触发订单
            self::triggerO2OOrder(
                $userId,
                $action,
                $orderId,
                $siteId,
                $money,
                0,
                CouponGroupEnum::CONSUME_TYPE_RECHARGE,
                CouponGroupEnum::TRIGGER_TYPE_P2P,
                $extra
            );

            // 获取触发列表
            $couponGroupList = $this->getCouponGroupList($userId, $action, $orderId, CouponGroupEnum::CONSUME_TYPE_RECHARGE);
            if ($couponGroupList) {
                if (count($couponGroupList) == 1) {
                    // 只有一个奖品时，如果是领取兑换类型，直接兑换
                    $groupInfo = $couponGroupList[0];
                    $groupId = $groupInfo['id'];
                    $useRules = $groupInfo['useRules'];
                    if (!in_array($useRules, self::$rulesRequireForm)) {
                        $userInfo = UserModel::instance()->findViaSlave($userId, 'mobile');
                        $mobile = $userInfo['mobile'];
                        $extraParam['phone'] = $mobile;
                        // 直接兑换
                        $this->doAcquireExchange($groupId, $userId, $action, $orderId, $mobile, array(),
                            $extraParam, 1, CouponGroupEnum::CONSUME_TYPE_RECHARGE);
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            PaymentApi::log('chargeTriggerO2O error'.$e->getMessage());
            return false;
        }
    }

    public static function addResendTask($resendTaskId) {
        $event = new \core\event\O2OResendCouponEvent($resendTaskId);
        $taskObj = new GTaskService();
        $taskId = $taskObj->doBackground($event, 10);
        PaymentApi::log("O2OService.O2OResendCouponEvent, resendTaskId:$resendTaskId | taskId:$taskId" , Logger::INFO);
        return $taskId;
    }

    //open开放平台实时分页获取CPA凭证接口
    public static function getAllowanceLog($siteId, $toUserId, $actionType, $allowanceType, $allowanceCoupon, $pageNo = 1, $pageSize = 10, $sum = 0) {
        // 先查询是否已有记录
        try{
            $log = OtoAllowanceLogModel::instance();
            $data =  $log->getList($siteId, $toUserId, $actionType, $allowanceType, $allowanceCoupon, $pageNo, $pageSize, $sum);
        } catch (\Exception $e) {
            PaymentApi::log("开发平台O2OService.OtoAllowanceLog调用异常", Logger::INFO);
        }
        return $data;
    }

    //open开放平台，根拒时间段，分页获取CPA凭证接口
     public static function getAllowanceLogByTime($siteId, $fromUserId, $actionType, $allowanceType, $allowanceCoupon,  $beginTime, $endTime, $pageNo = 1, $pageSize = 10, $back = 0) {
        try{
            $log = OtoAllowanceLogModel::instance();
            $data =  $log->getListByTime($siteId, $fromUserId, $actionType, $allowanceType, $allowanceCoupon,$beginTime, $endTime, $pageNo, $pageSize, $back);
        }
        catch (\Exception $e) {
            PaymentApi::log("开发平台O2OService.OtoAllowanceLog调用异常", Logger::INFO);
        }
        return $data;
    }

    /**
     * 批量赠送投资券，理财师app用户
     * @param giveList 格式array(array('toUserId'=>123, 'discountId'=>345),array('toUserId'=>234, 'discountId'=>456))
     */
    public function batchGiveDiscount($userId, $giveList, $isSyncResult = false) {
        $isSyncResult = true;
        try {
            $params = array($userId, $giveList);
            PaymentApi::log("批量赠送投资券. userId:$userId  ,list:".json_encode($giveList));
            if ($isSyncResult) {
                // 返回同步结果
                $res = $this->doBatchGiveDiscount($userId, $giveList);
                return $res;
            } else {
                // 异步丢task执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doBatchGiveDiscount', $params);
                $taskId = $taskObj->doBackground($event, 3);
                return true;
            }
        } catch (\Exception $e) {
            if ($isSyncResult && $e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doBatchGiveDiscount', $params);
                $taskId = $taskObj->doBackground($event, 3);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    public function doBatchGiveDiscount($userId, $giveList) {
        $o2oDiscount = new O2ODiscountService();
        $res = array();
        foreach ($giveList as $item) {
            if (empty($item['toUserId']) || empty($item['discountId'])) {
                continue;
            }
            $discount = $o2oDiscount->giveDiscount($userId, $item['toUserId'], $item['discountId'], $item['mobile']);
            if ($discount === false) {
                PaymentApi::log("转赠异常".$o2oDiscount->getErrorMsg()."fromUserId:$userId; toUserId:{$item['toUserId']};discountId:{$item['discountId']}",Logger::ERR);
            } else {
                $res[$item['discountId']] = $discount;
            }
        }
        return $res;
    }

    /**
     * 校验用户投资券id是否合法
     * @param $userId int 用户id
     * @param $dealId int 标id或者活动id
     * @param $discountId int 券id
     * @param $discountGroupId int 券组id
     * @param $discountSign string 签名值
     * @param $money int 投资额
     * @return bool
     */
    public function checkDiscountSignature($userId, $dealId, $discountId, $discountGroupId, $discountSign, $money, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P)
    {
        try {
            $userId = intval($userId);
            $dealId = intval($dealId);
            $discountId = intval($discountId);
            $discountGroupId = intval($discountGroupId);
            $money = intval($money);
            Logger::debug($userId.' | '.$dealId.' | '.$discountId.' | '.$discountGroupId.' | '.$money);
            if (!$userId || !$dealId || !$discountId || !$discountGroupId || !$money) {
                throw new \Exception('检验参数不合法');
            }

            // 投资券discount_sign验证
            $signParams = array(
                'user_id' => $userId,
                'deal_id' => $dealId,
                'discount_id' => $discountId,
                'discount_group_id' => $discountGroupId
            );
            $signStr = $this->getSignature($signParams);
            if ($discountSign != $signStr) {
                Monitor::add(Monitor::O2O_DISCOUNT_SIGN_FAILD);
                throw new \Exception('优惠券签名不匹配');
            }
            $errorInfo = array();
            $this->checkDiscountUseRules($discountGroupId, $dealId, $money, $errorInfo, $consumeType);
            if ($errorInfo) {
                switch ($errorInfo['errorCode']) {
                    case 1:
                        $msg = '优惠劵使用期限需大于' . $errorInfo['discountDayLimit'] . '天';
                        break;
                    case 2:
                        $msg = '最低投资金额为' . $errorInfo['discountGoodsPrice'] . '元';
                        break;
                    default:
                        break;
                }
                throw new \Exception($msg);
            }
            return true;
        } catch (\Exception $ex) {
            return $this->_handleException($ex, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取参数加密字符串.
     *
     * @param array $data
     * @access public
     *
     * @return string
     */
    public function getSignature($data)
    {
        return Aes::signature($data, self::SIGN_KEY);
    }

    /**
     * getRebateGold获取买金赠金值
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
        $couponGroupService = new O2OCouponGroupService();
        $res = $couponGroupService->getRebateGold($userId, $amount, $annualizedAmount, $discountId, $dealBidDays, $triggerTime);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $couponGroupService->getErrorMsg();
            self::$errorCode = $couponGroupService->getErrorCode();
        }
        return $res;
    }

    /**
     * getRebateGoldRule
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-01-14
     * @param mixed $dealBidDays
     * @access public
     * @return void
     */
    public function getRebateGoldRule($dealBidDays) {
        $couponGroupService = new O2OCouponGroupService();
        $res = $couponGroupService->getRebateGoldRule($dealBidDays);
        if ($res === false) {
            self::$error = true;
            self::$errorMsg = $couponGroupService->getErrorMsg();
            self::$errorCode = $couponGroupService->getErrorCode();
        }
        return $res;
    }

    /**
     * changeUserGamePoints
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-05-16
     * @param mixed $userId
     * @param mixed $token
     * @param mixed $points
     * @param mixed $sourceType
     * @param mixed $sourceValue
     * @param mixed $note
     * @access public
     * @return void
     */
    public function changeUserGamePoints($userId, $token, $points, $sourceType, $sourceValue, $note) {
        $userService = new UserService();
        if ($userService->checkEnterpriseUser($userId)) {
            PaymentApi::log("O2OService.changeUserGamePoints, 企业用户". $userId ."不参与世界杯积分:");
            return true;
        }
        $userInfo = $userService->getUser($userId);
        if (in_array($userInfo['group_id'], GameEnum::$WORLDCUP_BLACKLIST_USERGROUP) ) {
            PaymentApi::log("O2OService.changeUserGamePoints userId: ".$userId.', groupId: '.$userInfo['group_id'].'会员组不参与世界杯积分');
            return true;
        }
        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(array(
                'userId'=>intval($userId),
                'token'=>$token,
                'points'=>intval($points),
                'sourceType'=>$sourceType,
                'sourceValue'=>$sourceValue,
                'note'=>$note
            ));

            $response = $this->requestO2O('NCFGroup\O2O\Services\GameMatch', 'changeUserGamePoints', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * batchIncrGameTimes批量更新用户游戏次数
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-06-29
     * @param mixed $users
     * @param mixed $eventId
     * @access public
     * @return void
     */
    public function batchIncrGameTimes($users, $eventId, $sourceType, $note='', $sourceValue='') {
        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(array(
                'users'=>$users,
                'eventId'=>$eventId,
                'sourceType' => $sourceType,
                'note' => $note,
                'sourceValue' => $sourceValue,
            ));
            $response = $this->requestO2O('NCFGroup\O2O\Services\GameUser', 'batchIncrGameTimes', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    public function batchSendCoupons($users, $couponGroupId) {
        try {
            $params = array($users, $couponGroupId);
            PaymentApi::log('batchSendCoupons-params:'.json_encode($params, JSON_UNESCAPED_UNICODE));

            // 异步gearman执行
            $taskObj = new GTaskService();
            $event = new O2ORetryEvent('doBatchSendCoupons', $params);
            $taskId = $taskObj->doBackground($event, 3);
            return true;
        } catch (\Exception $e) {
            if ($e->getCode() == O2OException::CODE_RPC_TIMEOUT) {
                // 对于超时失败，异步gearman执行
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('doBatchSendCoupons', $params);
                $taskId = $taskObj->doBackground($event, 3);
            }

            if (!empty($taskId)) $params['taskId'] = $taskId;
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    public function doBatchSendCoupons($users, $couponGroupId) {
        $res = array();
        foreach ($users as $item) {
            $coupon = $this->acquireAllowanceCoupon($couponGroupId, $item['userId'], $item['token']);
            if (isset($coupon['coupon'])) {
                $res[$item['userId']] = array('couponId' => $coupon['coupon']['id'], 'userId' => $item['userId']);
            } else {
                $res[$item['userId']]['errMsg'] = '礼券发送失败';
            }
        }
        PaymentApi::log('batchSendCoupons-result:'.json_encode($res, JSON_UNESCAPED_UNICODE));
        return $res;
    }

    /**
     * getGameLinkUrl 游戏礼券获取游戏链接
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-10-15
     * @param mixed $couponGroupInfo
     * @access public
     * @return void
     */
    public function getGameLinkUrl($couponGroupInfo, $token='') {
        $gameUrl = '';
        foreach ($couponGroupInfo['allowance'] as $item) {
            if ($item['mode'] == CouponGroupEnum::ALLOWANCE_TYPE_GAME_CENTER) {
                $gameCode = $item['gameId'];
                $sparowService = new SparowService($gameCode);
                $res = $sparowService->getGameLink();
                if (isset($res['link']) && $res['link']) {
                    $gameUrl = $res['link'] . ($token ? '&token='.$token : '');
                }
                break;
            }
        }
        return $gameUrl;
    }

    public static function triggerUniqueCode($userId, $consumeType, $dealLoadId, $money, $action) {
        if (!in_array($action, CouponGroupEnum::$TRIGGER_DEAL_MODES)) {
            PaymentApi::log("O2OService.triggerUniqueCode, $dealLoadId | $action not deal action");
            return true;
        }
        if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
            PaymentApi::log("O2OService.triggerUniqueCode, $dealLoadId | $consumeType not trigger");
            return true;
        }
        $startTime = app_conf('WX2000_START_TIME');
        $endTime = app_conf('WX2000_END_TIME');
        if (empty($startTime) || empty($endTime)) {
            PaymentApi::log("O2OService.triggerUniqueCode, 活动时间没配置");
            return true;
        }
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        if (time() < $startTime || time() > $endTime) {
            PaymentApi::log("O2OService.triggerUniqueCode, 活动未开始或已结束");
            return true;
        }

        if (BwlistService::inList('O2O_UNIQUECODE_BLACK', $userId)) {
            PaymentApi::log("O2OService.triggerUniqueCode, 用户". $userId ."不参与抽奖码活动");
            return true;
        }
        try {
            $params = array(
                'userId' => $userId,
                'consumeType' => $consumeType,
                'dealLoadId' => $dealLoadId,
                'money' => $money,
                'gameId' => app_conf('O2O_UNIQUECODE_GAMEID')
            );
            return ApiService::rpc('o2o', 'game/triggerUniqueCode', $params);
        } catch (\Exception $e) {
            return false;
        }
    }
}

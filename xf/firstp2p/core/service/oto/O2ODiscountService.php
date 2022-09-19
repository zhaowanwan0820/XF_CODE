<?php

namespace core\service\oto;

use core\service\DtEntranceService;
use core\service\oto\O2ORpcService;
use core\service\DealTagService;
use core\service\MsgBoxService;
use core\exception\O2OException;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\CouponEnum;
use NCFGroup\Protos\O2O\RequestAcquireDiscount;
use NCFGroup\Protos\O2O\RequestExchangeDiscount;
use NCFGroup\Protos\O2O\RequestAvailableDiscountList;
use NCFGroup\Protos\O2O\RequestAvailableDiscountCount;
use NCFGroup\Protos\O2O\RequestGetUserDiscountList;
use NCFGroup\Protos\O2O\RequestGetUserGivenDiscountList;
use NCFGroup\Protos\O2O\RequestGiveDiscount;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Finance;
use core\dao\DealProjectModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use NCFGroup\Protos\O2O\RequestGetTotalCount;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use core\service\UserService;
use core\dao\DealLoadModel;
use core\dao\DiscountModel;
use core\service\DiscountService;

// 投资券相关服务接口
class O2ODiscountService extends O2ORpcService {
    //还款方式
    const LOAN_TYPE_5 = 5;//按天一次性还款
    const LOAN_TYPE_BY_CROWDFUNDING = 7; // 公益标
    //标的类型
    const DEAL_TYPE_COMPOUND = 1;//通知贷

    /**
     * 根据站ID确实是否启用投资劵.
     *
     * @param int $siteId
     * @access public
     */
    public function siteSwitch($siteId = false) {
        // 取默认的siteId
        if ($siteId === false) {
            $siteId = \libs\utils\Site::getId();
        }

        return intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
    }

    /**
     * 获取投资券信息
     * @param $discountId int 投资券id
     * @return array
     */
    public function getDiscount($discountId) {
        try {
            if (empty($discountId) || !is_numeric($discountId)) {
                throw new O2OException('投资券id不能为空或非数字');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray(array('id'=>$discountId));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getDiscount', $request);
            $discount = $response['data'];
            $discount['useInfo'] = $this->getUseInfo($discount);
            return $discount;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取投资券组使用规则
     * @param $groupId int 投资券组id
     * @return array
     */
    public function getDiscountGroup($groupId) {
        try {
            $groupId = trim($groupId);
            if (empty($groupId) || !is_numeric($groupId)) {
                throw new O2OException('投资券组id不能为空或非数字');
            }

            // cache设置
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            // 缓存5分钟
            $cacheExpireTime = 300;
            $redisKey = md5('REDIS_KEY_O2O_DISCOUNTGROUP'.$groupId);
            $discountGroup = $redis->get($redisKey);
            $res = false;
            if ($discountGroup) {
                $res = unserialize($discountGroup);
            }

            if ($res == false) {
                $request = new SimpleRequestBase();
                $request->setParamArray(array('id'=>$groupId));
                $response = $this->requestO2O('\NCFGroup\O2O\Services\DiscountGroup', 'getDiscountGroup', $request, 1);
                $res = $response['data'];
                $redis->setex($redisKey, $cacheExpireTime, serialize($res));
            }
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $res;
    }

    public function validateProduct($productInfo, $type, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $bidDayLimit = 0) {

        $res = array();
        //随鑫约校验标的信息逻辑
        if ($consumeType == CouponGroupEnum::CONSUME_TYPE_RESERVE) {
            $res['bidDayLimit'] = $bidDayLimit;
            $res['category'] = '';
            $res['projectId'] = '';
            $res['dealTag'] = '';
            return $res;
        }
        if (empty($productInfo)) {
            return false;
        }
        // 智多鑫特殊处理 智多鑫的dealId可能为1，与活期黄金冲突，所以把智多鑫放在活期黄金前面
        if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
            // 加息券不能用在活期智多鑫
            if ($type == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES && $productInfo['lock_day'] == 1) {
                return false;
            }

            $res['bidDayLimit'] = $productInfo['lock_day'];
            $res['projectId'] = '';
            $res['dealTag'] = '';
            $res['category'] = '';
            return $res;
        }

        // 活期黄金标特殊处理
        if ($productInfo['id'] == CommonEnum::GOLD_CURRENT_DEALID) {
            // 这里做默认值处理
            $res['bidDayLimit'] = 1;
            $res['projectId'] = 0;
            $res['dealTag'] = 'YJB_COUPON';
            $res['category'] = 0;
            return $res;
        }

        if ($type == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {

            $res['projectId'] = $productInfo['projectId'];
            $res['dealTag'] = $productInfo['tags'];
            $res['category'] = $productInfo['type_tag'];
            $res['bidDayLimit'] = intval($productInfo['repayTime']);

            return $res;
        } else {
            // 返现券和加息券

            $res = array();
            $res['projectId'] = $productInfo['project_id'];
            $res['category'] = $productInfo['category'];
            $res['dealTag'] = $productInfo['deal_tag_name'];
            $res['bidDayLimit'] = intval($productInfo['repayTime']);

            return $res;
        }
    }

    /**
     * 验证标的信息
     * @param $dealId int 标id
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵价券，0为返现券和加息券
     */
    public function validateDealInfo($dealId, $type, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $bidDayLimit = 0) {
        $res = array();
        //随鑫约校验标的信息逻辑
        if ($consumeType == CouponGroupEnum::CONSUME_TYPE_RESERVE) {
            $res['bidDayLimit'] = $bidDayLimit;
            $res['category'] = '';
            $res['projectId'] = '';
            $res['dealTag'] = '';
            return $res;
        }
        if (empty($dealId)) {
            return false;
        }
        // 智多鑫特殊处理 智多鑫的dealId可能为1，与活期黄金冲突，所以把智多鑫放在活期黄金前面
        if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU){
            // 根据id查找智多鑫活动信息
            $entranceInfo = (new DtEntranceService())->getEntranceInfo($dealId);
            // 加息券不能用在活期智多鑫
            if ($type == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES && $entranceInfo['lock_day'] == 1) {
                return false;
            }

            $res['bidDayLimit'] = $entranceInfo['lock_day'];
            $res['projectId'] = '';
            $res['dealTag'] = '';
            $res['category'] = '';
            return $res;
        }

        // 活期黄金标特殊处理
        if ($dealId == CommonEnum::GOLD_CURRENT_DEALID) {
            // 这里做默认值处理
            $res['bidDayLimit'] = 1;
            $res['projectId'] = 0;
            $res['dealTag'] = 'YJB_COUPON';
            $res['category'] = 0;
            return $res;
        }

        if ($type == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
            // 黄金券
            $dealResp = (new \core\service\GoldService())->getDealById($dealId);
            if ($dealResp['errCode'] != 0) {
                return false;
            }

            $res['projectId'] = $dealResp['data']['projectId'];
            $res['dealTag'] = $dealResp['data']['tags'];

            $res['category'] = DealLoanTypeModel::instance()->getLoanNameByTypeId($dealResp['data']['typeId']);
            // 变现通不能使用投资劵
            if ($res['category'] == DealLoanTypeModel::TYPE_BXT) {
                return false;
            }

            $bidDayLimit = intval($dealResp['data']['repayTime']);
            if ($dealResp['data']['loantype'] != self::LOAN_TYPE_5) {
                $bidDayLimit = $bidDayLimit * 30;
            }

            $res['bidDayLimit'] = $bidDayLimit;

            return $res;
        } else {
            // 返现券和加息券
            $columns = 'advisory_id, project_id, type_id, loantype, deal_type, deal_crowd, deal_tag_name, repay_time';
            $dealInfo = DealModel::instance()->find($dealId, $columns);
            if (empty($dealInfo)) {
                return false;
            }

            // 网贷理财中通知贷、公益标、不能投
            if ($dealInfo['deal_type'] == self::DEAL_TYPE_COMPOUND
                || $dealInfo['loantype'] == self::LOAN_TYPE_BY_CROWDFUNDING
            ) {
                return false;
            }

            // 投资券资产端黑名单过滤
            $siteId = \libs\utils\Site::getId();
            $blackListStr = get_config_db('DISCOUNT_ADVISORY_BLACKLIST', $siteId);
            if (!empty($blackListStr)) {
                // 多个咨询服务id用逗号进行分割
                $blackList = explode(',', $blackListStr);
                if (!empty($dealInfo['advisory_id']) && in_array($dealInfo['advisory_id'], $blackList)) {
                    PaymentApi::log("Discount advisory blacklist hit, dealId: {$dealId}, advisory id: "
                        .$dealInfo['advisory_id'].", blacklist: ".$blackListStr, Logger::INFO);

                    return false;
                }
            }

            // 特定标的下（标的带有tag：TZQ_NOCOUPON）不能使用投资券
            $dealTagService = new DealTagService();
            $tagNames = $dealTagService->getTagByDealId($dealId, false);
            if (in_array('TZQ_NOCOUPON', $tagNames)) {
                return false;
            }

            $res = array();
            $res['projectId'] = $dealInfo['project_id'];
            $res['category'] = DealLoanTypeModel::instance()->getLoanNameByTypeId($dealInfo['type_id']);
            $res['dealTag'] = $dealInfo['deal_tag_name'];
            $bidDayLimit = intval($dealInfo['repay_time']);
            if ($dealInfo['loantype'] != self::LOAN_TYPE_5) {
                $bidDayLimit = $bidDayLimit * 30;
            }

            // 变现通不能使用投资劵
            if ($res['category'] == DealLoanTypeModel::TYPE_BXT) {
                return false;
            }

            $res['bidDayLimit'] = $bidDayLimit;
            return $res;
        }
    }

    public function validateDiscount($groupId, $productInfo, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        $productInfoJson = json_encode($productInfo, JSON_UNESCAPED_UNICODE);
        PaymentApi::log("O2ODiscountService validateDiscount groupId: {$groupId}, productInfo: {$productInfoJson}", Logger::INFO);
        $discountGroup = $this->getDiscountGroup($groupId);
        if ($discountGroup == false) {
            PaymentApi::log("O2ODiscountService validateDiscount, empty discount group: ", Logger::ERR);
            return false;
        }

        // 验证标的信息
        $options = $this->validateProduct($productInfo, $discountGroup['type'], $consumeType);
        if ($options == false) {
            PaymentApi::log("O2ODiscountService validateDiscount, empty options", Logger::WARN);
            return false;
        }

        $bidDayLimit = $options['bidDayLimit'];
        $category = $options['category'];
        $projectId = $options['projectId'];
        $dealTag = $options['dealTag'];

        // 标的期限
        if ($bidDayLimit < $discountGroup['bidDayLimit']) {
            PaymentApi::log("O2ODiscountService validateDiscount, bidDayLimit({$bidDayLimit}, {$discountGroup['bidDayLimit']})", Logger::WARN);
            return false;
        }

        $useData = explode(',', $discountGroup['useData']);
        $useDataValue = false;
        $useRules = $discountGroup['useRules'];
        if ($useRules == CouponGroupEnum::DEAL_USE_RULES_CATEGORY) {
            // 获取标的类别
            $useDataValue = $category;
        } else if ($useRules == CouponGroupEnum::DEAL_USE_RULES_PROJECTS) {
            // 获取项目id
            $useDataValue = $projectId;
        } else if ($useRules == CouponGroupEnum::DEAL_USE_RULES_TAGS) {
            // 获取标的tag
            $useDataValue = $dealTag;
            if ($useDataValue) {
                // 标的tag可能存在多个，用逗号“,”进行分割
                $dealTag = explode(',', $useDataValue);
                if (count($dealTag) >= 2) {
                    $useDataValue = $dealTag;
                }
            }
        }

        PaymentApi::log("O2ODiscountService validateDiscount, useRules: {$useRules}({$useDataValue}, {$discountGroup['useData']})", Logger::INFO);
        if ($useDataValue !== false && !empty($useData)) {
            $useType = $discountGroup['useType'];
            if ($useType == CouponGroupEnum::RESTRICT_TYPE_DEAL) {
                // 包含
                if (is_array($useDataValue)) {
                    $isFound = false;
                    foreach ($useDataValue as $value) {
                        if (in_array($value, $useData)) {
                            $isFound = true;
                            break;
                        }
                    }

                    if (!$isFound) return false;
                } else {
                    if (!in_array($useDataValue, $useData)) return false;
                }
            }
            if ($useType == CouponGroupEnum::RESTRICT_TYPE_NO_DEAL) {
                // 不包含
                if (is_array($useDataValue)) {
                    $isFound = false;
                    foreach ($useDataValue as $value) {
                        if (in_array($value, $useData)) {
                            $isFound = true;
                            break;
                        }
                    }

                    if ($isFound) return false;
                } else {
                    if (in_array($useDataValue, $useData)) return false;
                }
            }
        }

        return true;

    }

    /**
     * 判断投资券在指定标的是否可用
     * @param $groupId int 投资券组id
     * @param $dealId int 标的id
     * @return bool
     */
    public function validateDiscountUseRules($groupId, $dealId, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        PaymentApi::log("O2ODiscountService validateDiscountUseRules groupId: {$groupId}, dealId: {$dealId}", Logger::INFO);
        $discountGroup = $this->getDiscountGroup($groupId);
        if ($discountGroup == false) {
            PaymentApi::log("O2ODiscountService validateDiscountUseRules, empty discount group: ", Logger::ERR);
            return false;
        }

        // 验证标的信息
        $options = $this->validateDealInfo($dealId, $discountGroup['type'], $consumeType);
        if ($options == false) {
            PaymentApi::log("O2ODiscountService validateDiscountUseRules, empty options", Logger::WARN);
            return false;
        }

        $bidDayLimit = $options['bidDayLimit'];
        $category = $options['category'];
        $projectId = $options['projectId'];
        $dealTag = $options['dealTag'];

        // 标的期限
        if ($bidDayLimit < $discountGroup['bidDayLimit']) {
            PaymentApi::log("O2ODiscountService validateDiscountUseRules, bidDayLimit({$bidDayLimit}, {$discountGroup['bidDayLimit']})", Logger::WARN);
            return false;
        }

        $useData = explode(',', $discountGroup['useData']);
        $useDataValue = false;
        $useRules = $discountGroup['useRules'];
        if ($useRules == CouponGroupEnum::DEAL_USE_RULES_CATEGORY) {
            // 获取标的类别
            $useDataValue = $category;
        } else if ($useRules == CouponGroupEnum::DEAL_USE_RULES_PROJECTS) {
            // 获取项目id
            $useDataValue = $projectId;
        } else if ($useRules == CouponGroupEnum::DEAL_USE_RULES_TAGS) {
            // 获取标的tag
            $useDataValue = $dealTag;
        }

        PaymentApi::log("O2ODiscountService validateDiscountUseRules, useRules: {$useRules}({$useDataValue}, {$discountGroup['useData']})", Logger::INFO);
        if ($useDataValue !== false && !empty($useData)) {
            $useType = $discountGroup['useType'];
            if ($useType == CouponGroupEnum::RESTRICT_TYPE_DEAL && !in_array($useDataValue, $useData)) {
                return false;
            }
            if ($useType == CouponGroupEnum::RESTRICT_TYPE_NO_DEAL && in_array($useDataValue, $useData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查投资券是否适用
     * @param $groupId int 投资券组id
     * @param $dealId int 标的id
     * @param $money float 金额
     * @return bool
     */
    public function checkDiscountUseRules($groupId, $dealId, $money, &$errorInfo, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        PaymentApi::log("O2ODiscountService checkDiscountUseRules groupId: {$groupId}, dealId: {$dealId}, money: {$money}", Logger::INFO);
        $discountGroup = $this->getDiscountGroup($groupId);
        if ($discountGroup == false) {
            PaymentApi::log("O2ODiscountService checkDiscountUseRules, empty discount group: ", Logger::ERR);
            return false;
        }

        // 验证标的信息
        $options = $this->validateDealInfo($dealId, $discountGroup['type'], $consumeType);
        if ($options == false) {
            PaymentApi::log("O2ODiscountService checkDiscountUseRules, empty options", Logger::WARN);
            return false;
        }

        $bidDayLimit = $options['bidDayLimit'];
        $category = $options['category'];
        $projectId = $options['projectId'];
        $dealTag = $options['dealTag'];

        // 标的期限
        if ($bidDayLimit < $discountGroup['bidDayLimit']) {
            PaymentApi::log("O2ODiscountService checkDiscountUseRules, bidDayLimit({$bidDayLimit}, {$discountGroup['bidDayLimit']})", Logger::WARN);
            $errorInfo = array('errorCode' => 1, 'discountDayLimit' => $discountGroup['bidDayLimit']);
            return false;
        }

        // 投标金额
        if ($money < $discountGroup['bidAmount']) {
            PaymentApi::log("O2ODiscountService checkDiscountUseRules, money({$money}, {$discountGroup['bidAmount']})", Logger::WARN);
            $errorInfo = array('errorCode' => 2, 'discountGoodsPrice' => $discountGroup['bidAmount']);
            return false;
        }

        $useData = explode(',', $discountGroup['useData']);
        $useDataValue = false;
        $useRules = $discountGroup['useRules'];
        if ($useRules == CouponGroupEnum::DEAL_USE_RULES_CATEGORY) {
            // 获取标的类别
            $useDataValue = $category;
        } else if ($useRules == CouponGroupEnum::DEAL_USE_RULES_PROJECTS) {
            // 获取项目id
            $useDataValue = $projectId;
        } else if ($useRules == CouponGroupEnum::DEAL_USE_RULES_TAGS) {
            // 获取标的tag
            $useDataValue = $dealTag;
            if ($useDataValue) {
                // 标的tag可能存在多个，用逗号“,”进行分割
                $tags = explode(',', $useDataValue);
                if (count($tags) >= 2) {
                    $useDataValue = $tags;
                }
            }
        }

        PaymentApi::log("O2ODiscountService checkDiscountUseRules, useRules:
            {$useRules}({$category}, {$projectId}, {$dealTag}, {$discountGroup['useData']})", Logger::INFO);

        if ($useDataValue !== false && !empty($useData)) {
            $useType = $discountGroup['useType'];
            // 对于值为数组的情况
            if (is_array($useDataValue)) {
                // 1为包含，2为不包含
                $checkFailed = ($useType == 1) ? true : false;
                foreach ($useDataValue as $value) {
                    if (in_array($value, $useData)) {
                        // 1已经包含，则设置为false，2不包含却包含了，设置为true
                        $checkFailed = ($useType == 1) ? false : true;
                        break;
                    }
                }

                return $checkFailed ? false : true;
            }

            if ($useType == CouponGroupEnum::RESTRICT_TYPE_DEAL && !in_array($useDataValue, $useData)) {
                return false;
            }
            if ($useType == CouponGroupEnum::RESTRICT_TYPE_NO_DEAL && in_array($useDataValue, $useData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 领取指定投资券规则的投资券
     * @param $userId int 用户id
     * @param $discountRuleId int 投资规则id
     * @param $token string 唯一token
     * @param $bidAmount float 起投资金额或购买克数
     * @param $bidDayLimit int 起投期限
     * @param $dealLoadId int 交易id
     * @param $remark string 备注
     * @param $rebateAmount float 返利金额
     * @param $rebateLimit int 返利期限
     * @return array | false
     */
    public function acquireRuleDiscount($userId, $discountRuleId, $token, $bidAmount = 0, $bidDayLimit = 0,
                                    $dealLoadId = 0, $remark = '', $rebateAmount = 0, $rebateLimit = 0) {
        try {
            if (empty($userId) || !is_numeric($userId)) {
                throw new O2OException('用户id不能为空或非数字');
            }

            $discountRuleId = trim($discountRuleId);
            if (empty($discountRuleId) || !is_numeric($discountRuleId)) {
                throw new O2OException('投资券规则id不能为空或非数字');
            }

            if (empty($token)) {
                throw new O2OException('token不能为空');
            }

            $request = new SimpleRequestBase();
            $params = array(
                'userId'=>$userId,
                'discountRuleId'=>$discountRuleId,
                'token'=>$token,
                'bidAmount'=>$bidAmount,
                'bidDayLimit'=>$bidDayLimit,
                'dealLoadId'=>$dealLoadId,
                'remark'=>$remark,
                'rebateAmount'=>$rebateAmount,
                'rebateLimit'=>$rebateLimit
            );
            $request->setParamArray($params);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\DiscountRule', 'acquire', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $response['data'];
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
        try {
            if (empty($userId) || !is_numeric($userId)) {
                throw new O2OException('用户id不能为空或非数字');
            }

            $discountGroupId = trim($discountGroupId);
            if (empty($discountGroupId) || !is_numeric($discountGroupId)) {
                throw new O2OException('投资券组id不能为空或非数字');
            }

            if (empty($token)) {
                throw new O2OException('token不能为空');
            }

            $request = new RequestAcquireDiscount();
            $request->setDiscountGroupId(intval($discountGroupId));
            $request->setUserId(intval($userId));
            $request->setCouponToken($token);
            $request->setDealLoadId($dealLoadId);
            $request->setRemark($remark);
            $request->setRebateAmount($rebateAmount);
            $request->setRebateLimit($rebateLimit);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'acquireDiscount', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $response['data'];
    }

    /**
     * 批量领取投资券
     * @param $userIds string 用户列表，多个用逗号','分割，建议用户列表每次在2000个以内
     * @param $groupIds string 投资券组列表，多个用逗号','分割
     * @param $taskId int 任务id
     * @param $seriaNo int 批次号
     * @param $tokenPre string token前缀
     * @param $siteId int 分站id
     * @return int|false 成功领取的投资券个数
     */
    public function batchAcquireDiscount($userIds, $groupIds, $taskId, $serialNo, $tokenPre, $siteId = 1) {
        try {
            if (empty($userIds)) {
                throw new O2OException('用户id列表不能为空');
            }

            if (empty($groupIds)) {
                throw new O2OException('投资券组id列表不能为空');
            }

            if (empty($taskId)) {
                throw new O2OException('任务id不能为空');
            }

            if (is_array($userIds)) {
                $userIds = implode(',', $userIds);
            }

            if (is_array($groupIds)) {
                $groupIds = implode(',', $groupIds);
            }

            $request = new SimpleRequestBase();
            $params = array(
                'userIds'=>$userIds,
                'groupIds'=>$groupIds,
                'taskId'=>$taskId,
                'serialNo'=>$serialNo,
                'tokenPrefix'=>$tokenPre,
                'siteId'=>$siteId
            );
            $request->setParamArray($params);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'batchAcquireDiscount', $request, 10);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $response['data'];
    }

    /**
     * 兑换投资券
     */
    public function exchangeDiscount($userId, $discountId, $dealLoadId, $triggerTime = false) {
        try {
            if (!$this->siteSwitch()) {
                throw new O2OException('该站点暂未开通投资券功能');
            }

            if (empty($userId) || !is_numeric($userId)) {
                throw new O2OException('用户id不能为空或非数字');
            }

            if (empty($discountId) || !is_numeric($discountId)) {
                throw new O2OException('投资券id不能为空或非数字');
            }

            if (empty($dealLoadId)) {
                throw new O2OException('交易id不能为空');
            }

            $request = new RequestExchangeDiscount();
            $request->setOwnerUserId(intval($userId));
            $request->setDiscountId(intval($discountId));
            $request->setDealLoadId($dealLoadId);
            if ($triggerTime !== false) {
                $request->setTriggerTime(intval($triggerTime));
            }
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'exchangeDiscount', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $response;
    }

    /**
     * 获取用户未使用的投资券个数
     * @param $userId int 用户id
     * @return array 对应个数
     */
    public function getMineUnusedDiscountCount($userId) {
        // 参数校验
        if (!$this->siteSwitch() || empty($userId) || !is_numeric($userId) || $userId < 0) {
            return false;
        }

        try {
            $request = new SimpleRequestBase();
            $params = array('ownerUserId' => $userId);
            $request->setParamArray($params);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getMineUnusedDiscountCount', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取用户未使用的投资券个数
     * @param $userId int 用户id
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @return int 对应个数
     */
    public function getUserUnusedDiscountCount($userId, $type = 0, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        return 0;
        // 参数校验
        if (!$this->siteSwitch() || empty($userId) || !is_numeric($userId) || $userId < 0) {
            return 0;
        }

        try {
            $request = new SimpleRequestBase();
            $params = array('ownerUserId' => $userId, 'type'=>$type, 'consumeType'=>$consumeType);
            $request->setParamArray($params);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getUserUnusedDiscountCount', $request, 1, false);
            return intval($response['data']);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 批量获取用户未使用的投资券个数
     * @param $userIds string 用户id列表，逗号分隔
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @return array
     */
    public function getUsersUnusedDiscountCountForAdmin($userIds, $type = null,
                                                        $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        try {
            // 参数校验
            if (empty($userIds)) {
                throw new O2OException('用户id列表不能为空');
            }

            if (is_array($userIds)) {
                $userIds = implode(',', $userIds);
            }

            $request = new SimpleRequestBase();
            $params = array('userIds' => $userIds);
            if ($type) {
                $params['type'] = $type;
            }
            $params['consumeType'] = $consumeType;
            $request->setParamArray($params);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getUsersUnusedDiscountCountForAdmin', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取用户可用的投资券列表
     * @param $userId int 用户id
     * @param $dealId int 标的id
     * @param $money float 金额
     * @param $page int 页码
     * @param $pageSize int 每页的个数
     * @param $type 类型，1为返现券，2为加息券，3为黄金券，为0表示不区分类型
     * @param $annualizedAmount 年化额
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @return array
     */
    public function getAvailableDiscountList($userId, $dealId, $money = false, $page = 1, $pageSize = 10, $type = 0,
                                             $annualizedAmount = 0, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $bidDayLimit = 0) {
        $result = array('total' => 0, 'totalPage' => 0, 'list' => array());
        $options = $this->validateDealInfo($dealId, $type, $consumeType, $bidDayLimit);

        if (!$this->siteSwitch() || $options == false || empty($userId) || !is_numeric($userId)) {
            $result['list'] = array();
            return $result;
        }

        if ($money !== false && $money > 0) {
            $options['bidAmount'] = $money;
        }

        try {
            $request = new RequestAvailableDiscountList();
            if (!empty($options['bidAmount'])) {
                $request->setBidAmount($options['bidAmount']);
            }
            if (!empty($options['bidDayLimit'])) {
                $request->setBidDayLimit($options['bidDayLimit']);
            }
            if (!empty($options['category'])) {
                $request->setCategory($options['category']);
            }
            if (!empty($options['dealTag'])) {
                $request->setDealTag($options['dealTag']);
            }
            if (!empty($options['projectId'])) {
                $request->setProjectId(intval($options['projectId']));
            }
            $request->setOwnerUserId(intval($userId));
            $request->setPage(intval($page));
            $request->setPageSize(intval($pageSize));
            $request->setHasTotalCount(1);
            $request->setType($type);
            $request->setAnnualizedAmount($annualizedAmount);
            $request->setConsumeType($consumeType);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getAvailableDiscountList', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        if (!is_array($response) || empty($response['dataPage'])) {
            $result['list'] = array();
            return $result;
        }

        $result['total'] = $response['dataPage']['total'];
        $result['totalPage'] = $response['dataPage']['totalPage'];
        $list = array();
        $hongbaoText = app_conf('NEW_BONUS_TITLE');
        $hongbaoUnit = app_conf('NEW_BONUS_UNIT');
        foreach ($response['dataPage']['data'] as $item ) {
            // 用于在投资确认页展现投资券信息
            $allowanceInfo = '';
            $goodPriceInfo = '';
            if ($item['type'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES) {
                $allowanceInfo .= '可获'.$item['goodsPrice'].'%加息，满'.$item['bidAmount'].'元可用';
                $goodPriceInfo .= '获得加息'.$item['goodsPrice'].'%';
                if ($item['goodsMaxPrice'] > 0) {
                    $goodPriceInfo .= '，最高返利'.$item['goodsMaxPrice'].'元';
                }

                if ($item['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY) {
                    $goodPriceInfo .= '现金';
                } else if ($item['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
                    $goodPriceInfo .= $hongbaoText;
                }

                if ($item['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_WITH_INTEREST) {
                    $goodPriceInfo .= "\n".'随息发放';
                } else if ($item['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_ONE_TIME) {
                    $goodPriceInfo .= "\n".'预计24小时内到帐';
                }
            } else if ($item['type'] == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK) {
                $allowanceInfo = '可获';
                $goodPriceInfo = '获得';
                if ($item['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY) {
                    $allowanceInfo .= $item['goodsPrice'].'元现金';
                    $goodPriceInfo .= $item['goodsPrice'].'元现金';
                } else if ($item['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
                    $allowanceInfo .= $item['goodsPrice'].$hongbaoUnit.$hongbaoText;
                    $goodPriceInfo .= $item['goodsPrice'].$hongbaoUnit.$hongbaoText;
                }
                $goodPriceInfo .= "\n".'预计24小时内到帐';
                $allowanceInfo .= '，满'.$item['bidAmount'].'元可用';
            } else if ($item['type'] == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
                $goodPriceInfo = '已获'.$item['goodsPrice'].'克黄金'."\n".'请到优金宝账户中查看';
                $allowanceInfo = '可获'.$item['goodsPrice'].'克黄金，需购买满'.$item['bidAmount'].'克黄金';
            }

            // 为了在前端正常展示，后端做urlencode处理，用于参数透传到confirm.html
            $item['youhuiquan'] = $allowanceInfo;
            // 这里的goodPriceInfo用于透传
            $item['goodPriceInfo'] = $goodPriceInfo;

            // 券的使用规则
            $item['useInfo'] = $this->getUseInfo($item);
            $list[] = $item;
        }

        $result['list'] = $list;
        return $result;
    }

    /**
     * 获取加息券的预期收益的展示
     */
    public function getExpectedEarningInfo($userId, $dealId, $money, $discountId, $appversion = '', $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        if (!$this->siteSwitch() || empty($discountId) || !is_numeric($discountId) || empty($userId) || !is_numeric($userId)) {
            return false;
        }

        $discount = $this->getDiscount($discountId);
        if ($discount === false) {
            return false;
        }

        // 权限验证，只有券的属主才能查看
        if ($discount['ownerUserId'] != $userId) {
            $this->setErrorMsg('该券不属于您，无权查看');
            return false;
        }

        // 用于在投资确认页展现投资券信息
        $res = array();
        $allowanceInfo = '';
        $goodPriceInfo = '';
        $discountAmount = 0;
        if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES) {
            // 如果金额为空，或者没有达到最小投资额，则显示初始文案
            if (empty($money) || $money < $discount['bidAmount']) {
                $allowanceInfo .= '可获'.$discount['goodsPrice'].'%加息';
                $goodPriceInfo .= '加息'.$discount['goodsPrice'].'%';
                if ($discount['goodsMaxPrice'] > 0) {
                    $goodPriceInfo .= '，最高返利'.$discount['goodsMaxPrice'].'元';
                }

                if ($discount['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY) {
                    $goodPriceInfo .= '现金';
                } else if ($discount['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
                    $goodPriceInfo .= app_conf('NEW_BONUS_TITLE');
                }
                $discountAmount = $discount['goodsPrice'];
            } else {
                if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
                    $entranceInfo = (new DtEntranceService())->getEntranceInfo($dealId);
                    $lockDay = isset($entranceInfo['lock_day']) ? $entranceInfo['lock_day'] : 0;
                    $moneyYear = $money * $lockDay / DealModel::DAY_OF_YEAR;
                    $goodPrice = bcmul($moneyYear, $discount['goodsPrice'] * 0.01, 5);
                } else {
                    $dealModel = DealModel::instance()->findViaSlave($dealId);
                    $finance = new Finance();
                    // 计算年化额
                    $moneyYear = $finance->getMoneyYearPeriod($money, $dealModel->loantype, $dealModel->repay_time);
                    $rebateRate = $dealModel->getRebateRate($dealModel->loantype);
                    $goodPrice = bcmul($moneyYear, $discount['goodsPrice'] * 0.01 * $rebateRate, 5);
                }
                if ($discount['goodsMaxPrice'] > 0 && bccomp($goodPrice, $discount['goodsMaxPrice'], 5) == 1) {
                    $goodPrice = $discount['goodsMaxPrice'];
                }
                // 金额保留2位小数
                $goodPrice = DealModel::instance()->floorfix($goodPrice);

                $allowanceInfo .= '预期可获'.$goodPrice;
                if ($discount['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_WITH_INTEREST) {
                    $goodPriceInfo .= '加息'.$discount['goodsPrice'].'%，预期可获'.$goodPrice.'元';
                } else if ($discount['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_ONE_TIME) {
                    $goodPriceInfo .= '获得'.$goodPrice.'元';
                }

                if ($discount['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY) {
                    $allowanceInfo .= '元现金';
                    $goodPriceInfo .= '现金';
                } else if ($discount['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
                    $allowanceInfo .= app_conf('NEW_BONUS_UNIT') . app_conf('NEW_BONUS_TITLE');
                    $goodPriceInfo .= app_conf('NEW_BONUS_TITLE');
                }
            }

            if ($discount['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_WITH_INTEREST) {
                $goodPriceInfo .= "\n".'随息发放';
            } else if ($discount['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_ONE_TIME) {
                $goodPriceInfo .= "\n".'预计24小时内到帐';
            }

            $allowanceInfo .= '，满'.$discount['bidAmount'].'元可用';
            $discountAmount = isset($goodPrice) ? $goodPrice : 0;
        } else if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK) {
            $allowanceInfo .= '可获';
            $goodPriceInfo .= '获得';
            if ($discount['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY) {
                $allowanceInfo .= $discount['goodsPrice'].'元现金';
                $goodPriceInfo .= $discount['goodsPrice'].'元现金';
            } else if ($discount['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
                $allowanceInfo .= $discount['goodsPrice'] . app_conf('NEW_BONUS_UNIT') . app_conf('NEW_BONUS_TITLE');
                $goodPriceInfo .= $discount['goodsPrice'] . app_conf('NEW_BONUS_UNIT') . app_conf('NEW_BONUS_TITLE');
            }
            $goodPriceInfo .= "\n".'预计24小时内到帐';
            $allowanceInfo .= '，满'.$discount['bidAmount'].'元可用';
        } else if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
            $goodPriceInfo = '已获'.$discount['goodsPrice'].'克黄金';
            if (!empty($appversion) && $appversion > 470) {
                $goodPriceInfo .= "\n".'请到优金宝账户中查看';
            } else {
                $goodPriceInfo .= '请到优金宝账户中查看';
            }

            $allowanceInfo = '可获'.$discount['goodsPrice'].'克黄金，需购买满'.$discount['bidAmount'].'克黄金';
            $discountAmount = $discount['goodsPrice'];
        }

        // 这里的discountDetail直接用于展示
        $res['discountDetail'] = $allowanceInfo;
        // 这里的discountGoodPrice用于透传
        $res['discountGoodPrice'] = $goodPriceInfo;
        $res['discountAmount'] = $discountAmount;
        return $res;
    }

    /**
     * 获取用户可用的投资券个数
     * @param $userId int 用户id
     * @param $dealId int 交易id
     * @param $money float 金额
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @param $bidDayLimit int 投资期限
     * @return mixed
     */
    public function getAvailableDiscountCount($userId, $dealId, $money = false, $type = 0,
                                              $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $bidDayLimit = 0) {

        $options = $this->validateDealInfo($dealId, $type, $consumeType, $bidDayLimit);
        if (!$this->siteSwitch() || $options == false || empty($userId) || !is_numeric($userId)) {
            return 0;
        }

        if ($money !== false && $money > 0) {
            $options['bidAmount'] = $money;
        }

        try {
            $request = new RequestAvailableDiscountCount();
            if (!empty($options['bidAmount'])) {
                $request->setBidAmount($options['bidAmount']);
            }
            if (!empty($options['bidDayLimit'])) {
                $request->setBidDayLimit($options['bidDayLimit']);
            }
            if (!empty($options['category'])) {
                $request->setCategory($options['category']);
            }
            if (!empty($options['dealTag'])) {
                $request->setDealTag($options['dealTag']);
            }
            if (!empty($options['projectId'])) {
                $request->setProjectId(intval($options['projectId']));
            }

            $request->setOwnerUserId(intval($userId));
            $request->setType($type);
            $request->setConsumeType($consumeType);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getAvailableDiscountCount', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
        return $response['data'];
    }

    /**
     * 获取用户投资券列表
     * @param $userId int 用户id
     * @param $status int 投资券状态
     * @param $page int 页码编号
     * @param $pageSize int 每页显示数
     * @param $type int 投资券类型，1为返现券，2为加息券，0表示不区分类型
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @return array
     */
    public function getUserDiscountList($userId, $status = 0, $page = 1, $pageSize = 10, $type = 0,
                                        $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $useStatus = 0) {
        $result = array('total' => 0, 'totalPage' => 0, 'list' => array());
        if (!$this->siteSwitch() || empty($userId) || !is_numeric($userId)) {
            return $result;
        }

        try {
            $request = new RequestGetUserDiscountList();
            $request->setUserId(intval($userId));
            $request->setStatus(intval($status));
            $request->setPage(intval($page));
            $request->setPageSize(intval($pageSize));
            $request->setHasTotalCount(1);
            $request->setType($type);
            $request->setConsumeType($consumeType);
            $request->setUseStatus($useStatus);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getUserDiscountList', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        if (!is_array($response) || empty($response['dataPage'])) {
            $result['list'] = array();
            return $result;
        }

        $result['total'] = $response['dataPage']['total'];
        $result['totalPage'] = $response['dataPage']['totalPage'];
        $list = array();
        $expiredList = array();
        foreach ($response['dataPage']['data'] as $item ) {
            // 过期判断，可能存在没有及时更新过期券状态的情况
            if ($item['useEndTime'] <= time() && $item['status'] != CouponEnum::STATUS_USED) {
                if ($item['status'] != CouponEnum::STATUS_WAIT_CONFIRM) {
                    $item['status'] = CouponEnum::STATUS_EXPIRED;
                }
                $item['useInfo'] = $this->getUseInfo($item);
                $expiredList[] = $item;
            }else {
                $item['useInfo'] = $this->getUseInfo($item);
                $list[] = $item;
            }
        }
        $list = array_merge($list,$expiredList);
        $result['list'] = $list;
        return $result;
    }

    /**
     * 投资券锁定赠送
     * @param $fromUserId int 转出方
     * @param $discountId int 投资券id
     * @param $toMobile string 接收者手机号
     * @return mixed
     */
    public function lockAndGiveDiscount($fromUserId, $discountId, $toMobile = '') {
        if (!$this->siteSwitch()) {
            $this->setErrorMsg('该站点暂未开通投资券功能');
            return false;
        }

        try {
            if (empty($discountId)) {
                throw new O2OException('投资券id不能为空');
            }

            if (empty($fromUserId) || !is_numeric($fromUserId)) {
                throw new O2OException('赠送者不能为空或非数字');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray(array(
                'userId'=>$fromUserId,
                'discountId'=>$discountId,
                'toMobile'=>$toMobile
            ));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'lockAndGiveDiscount', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $response['data'];

    }

    /**
     * 投资券赠送
     * @param $fromUserId int 转出方
     * @param $toUserId int 接受者
     * @param $discountId int 投资券id
     * @param $toMobile string 接收者手机号
     * @return mixed
     */
    public function giveDiscount($fromUserId, $toUserId, $discountId, $toMobile = '') {
        if (!$this->siteSwitch()) {
            $this->setErrorMsg('该站点暂未开通投资券功能');
            return false;
        }

        try {
            if (empty($discountId)) {
                throw new O2OException('投资券id不能为空');
            }

            if (empty($fromUserId) || !is_numeric($fromUserId)) {
                throw new O2OException('赠送者不能为空或非数字');
            }

            if (empty($toUserId) || !is_numeric($toUserId)) {
                throw new O2OException('接受者不能为空或非数字');
            }

            // 通过凭证表 判断券是否已经被使用
            if ($this->getDiscountRecord(intval($discountId))) {
                throw new O2OException('此券已经被使用');
            }

            // 判断$fromUserId和$toUserId的邀请人关系
//            $couponBindService = new \core\service\CouponBindService();
//            if ($couponBindService->checkComparedUserId($toUserId, $fromUserId) == false) {
//                throw new O2OException('只能赠送给好友');
//            }

            $request = new RequestGiveDiscount();
            $request->setDiscountId(trim($discountId));
            $request->setFromUserId(intval($fromUserId));
            $request->setToUserId(intval($toUserId));
            $request->setToMobile($toMobile);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'giveDiscount', $request);
            // 赠送成功之后，推送相关消息
            $msgBoxService = new MsgBoxService();
            $userService = new \core\service\UserService();
            $user = $userService->getUserByUserId($fromUserId);
            $userName = isset($user['real_name']) ? $user['real_name'] : '';
            // 这里现在只支持赠送一张投资券，o2o接口支持送多张
            $msg = '您的好友'.$userName.'送您1张';
            $discount = $response['data'][$discountId];
            if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK) {
                $msg .= $discount['goodsPrice'].'元返现券';
            } else if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES) {
                $msg .= '加息券，最高加息'.$discount['goodsPrice'].'%';
            } else if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
                $msg .= $discount['goodsPrice'].'克黄金抵扣券，购买满'.$discount['bidAmount']
                    .'可用，'.date('Y-m-d H:i', $discount['useStartTime']).'至'
                    .date('Y-m-d H:i', $discount['useEndTime']).'有效，请尽快使用。';
            }

            $msg .= '，'.date('m-d H:i', $discount['useStartTime'])
                .'至'.date('m-d H:i', $discount['useEndTime']).'有效，请尽快使用。';
            $msgBoxService->create($toUserId, MsgBoxEnum::TYPE_DISCOUNT, '获得投资券', $msg);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $response['data'];
    }

    /**
     * 获取用户可赠送的投资券列表
     * @param $userId int 用户id
     * @param $page int 页码
     * @param $pageSize int 每页个数
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @return mixed
     */
    public function getUserGivenDiscountList($userId, $page = 1, $pageSize = 10, $type = 0,
                                             $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        $result = array('total' => 0, 'totalPage' => 0, 'list' => array());
        if (!$this->siteSwitch() || empty($userId) || !is_numeric($userId)) {
            return $result;
        }

        try {
            $request = new RequestGetUserGivenDiscountList();
            $request->setUserId(intval($userId));
            $request->setPage(intval($page));
            $request->setPageSize(intval($pageSize));
            $request->setHasTotalCount(1);
            $request->setType($type);
            $request->setConsumeType($consumeType);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getUserGivenDiscountList', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        if (!is_array($response) || empty($response['dataPage'])) {
            $result['list'] = array();
            return $result;
        }

        $result['total'] = $response['dataPage']['total'];
        $result['totalPage'] = $response['dataPage']['totalPage'];
        $list = array();
        foreach ($response['dataPage']['data'] as $item ) {
            $item['useInfo'] = $this->getUseInfo($item);
            $list[] = $item;
        }

        $result['list'] = $list;
        return $result;
    }

    /**
     * 获取用户好友个数
     * @param $userId int 用户id
     * @return int
     */
    public function getUserFriendCount($userId) {
        $beginTime = microtime(true);
        PaymentApi::log("[req]O2OService.Discount.getUserFriendCount:".' userId: '.$userId, Logger::INFO);

        $couponBindService = new \core\service\CouponBindService();
        $count = $couponBindService->getUserFriendCount($userId);

        $endTime = microtime(true);
        $elapsedTime = round($endTime - $beginTime, 3);
        PaymentApi::log("[resp][cost:{$elapsedTime}]O2OService.Discount.getUserFriendCount:".' total: '.$count, Logger::INFO);
        return $count;
    }

    /**
     * 获取用户好友列表
     * @param $userId int 用户id
     * @param $page int 页码
     * @param $pageSize int 每页个数
     * @return array | false
     */
    public function getUserFriendList($userId, $page = 1, $pageSize = 10) {
        $beginTime = microtime(true);
        PaymentApi::log("[req]O2OService.Discount.getUserFriendList:".' userId: '.$userId, Logger::INFO);

        $result = array('total' => 0, 'totalPage' => 0, 'list' => array());
        $couponBindService = new \core\service\CouponBindService();
        $items = $couponBindService->getAllReferUserId($userId, $page, $pageSize);
        $result['total'] = isset($items['total']) ? $items['total'] : 0;

        $list = array();
        // 反序处理
        foreach ($items['data'] as $item) {
            // 手机号脱敏
            if (strlen($item['mobile']) > 4) {
                $item['mobile'] = substr_replace($item['mobile'], str_repeat('*', 4), 3, 4);
            }

            // 用户注册时间
            $item['create_time'] = date('Y-m-d', strtotime($item['create_time']));
            $list[] = $item;
        }

        $result['list'] = $list;

        $endTime = microtime(true);
        $elapsedTime = round($endTime - $beginTime, 3);
        PaymentApi::log("[resp][cost:{$elapsedTime}]O2OService.Discount.getUserFriendList:".' total: '.$result['total'], Logger::INFO);
        return $result;
    }

    /**
     * 新手券显示开关
     */
    private function isOpenUsePurpose() {
        //判断开关 0是关闭 不让用户看到新手券使用限制
        if ((int)app_conf('USE_PURPOSE_SWITCH') === 0 ) {
            return false;
        } elseif((int)app_conf('USE_PURPOSE_SWITCH') === 1 ) {
            return true;
        }
    }

    /**
     * 获取投资券的使用限制文案
     */
    private function getUseInfo(array $discount) {
        $useInfo = '';
        $discountId = $discount['id'];
        $rewards = '';
        if (!empty($discount['rewards'])) {
            //黄金券转赠判断
            $rewards = json_decode($discount['rewards'], true);
        }

        if ($rewards) {
            $givenLog = $this->getGivenLogByDiscountId($discountId);
            if (empty($givenLog) || empty($givenLog['toUserId'])) {
                foreach ($rewards as $key => $rewardItem) {
                    $rewardItem['type'] = $rewardItem['type'] ? intval($rewardItem['type']) : '';
                    if ($rewardItem['type'] == CouponGroupEnum::SPECIAL_REWARD_RULE_REWARD_SENDER) {
                        if (empty($rewardItem['value'])) {
                            continue;
                        }
                        $discountGroupIds = explode(",", $rewardItem['value']);
                        foreach ($discountGroupIds as $key => $groupId) {
                            if (empty($groupId)) {
                                unset($discountGroupIds[$key]);
                            }
                        }
                        $groupCountArr = array_count_values($discountGroupIds);
                        $useInfo .= "被赠送的好友使用后你会获得";
                        foreach ($groupCountArr as $rewardGroupId => $count) {
                            $res = $this->getDiscountGroup($rewardGroupId);
                            $useInfo .= $count . "张" . $res['goodsPrice'] . "克黄金券,购买满" . $res['bidAmount'] . "克黄金,产品期限满" . $res['bidDayLimit'] . "天可用;";
                        }
                        $useInfo = rtrim($useInfo, ";");
                        $useInfo .= "。";
                    }
                }
            }
        }

        $switch = $this->isOpenUsePurpose();
        if ($switch) {
            if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_GOLD && $discount['usePurpose'] == CouponGroupEnum::USE_PURPOSE_FIRST) {
                $useInfo .= '使用黄金新手券不能获得满额赠金奖励。';
            }
        }

        $useData = explode(',', $discount['useData']);
        if ($discount['useRules'] == CouponGroupEnum::DEAL_USE_RULES_PROJECTS) {
            foreach ($useData as $key=>$projectId) {
                $projectInfo = DealProjectModel::instance()->findViaSlave($projectId, 'name');
                if ($projectInfo) {
                    $useData[$key] = $projectInfo['name'];
                }
            }
        }

        // 赠送判断
        if ($discount['givenStatus'] == CouponGroupEnum::GIVEN_TYPE_ONLY_SEND) {
            $useInfo .= '仅可用于赠送好友。';
        }
        // 加息券判断
        if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES && $discount['goodsMaxPrice'] > 0) {
            $useInfo .= '最高返利'.$discount['goodsMaxPrice'].'元。';
        }

        if ($discount['useType'] == CouponGroupEnum::RESTRICT_TYPE_DEAL) {
            $useInfo .= '仅限投资'.implode('、', $useData).'时使用。';
        } else if ($discount['useType'] == CouponGroupEnum::RESTRICT_TYPE_NO_DEAL) {
            $useInfo .= '不可用于投资'.implode('、', $useData).'。';
        }
        return $useInfo;
    }

    /*
     * 获取券的赠送记录
     * $param $discountId int 券id
     * $return 券的赠送记录
     */
    public function getGivenLogByDiscountId($discountId) {
        try {
            if (empty($discountId)) {
                throw new \Exception('券ID不能为空');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray(array(
                'discountId'=> $discountId,
            ));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getGivenLogByDiscountId', $request);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }
    /**
     * 获取用户未来将要过期的投资券个数
     * @param $userId int 用户id
     * @param $elapsedTime int 逝去的时间
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @return int | false
     */
    public function getUserWillExpireDiscountCount($userId, $elapsedTime = 86400, $type = 0,
                                                   $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        // 参数校验
        if (!$this->siteSwitch() || empty($userId) || !is_numeric($userId) || $userId < 0) {
            return 0;
        }

        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(array(
                'ownerUserId'=>$userId,
                'timeElapsed'=>$elapsedTime,
                'type'=>$type,
                'consumeType'=>$consumeType
            ));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getUserWillExpireDiscountCount', $request, 1, false);
            return intval($response['data']);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取用户投资券总数
     * @param $userId int 用户id
     * @param $type int 类型，1为返现券，2为加息券，3为黄金券，默认为0，表示不区分
     * @param $status int 投资券状态
     * @param $consumeType int 交易类型，1为p2p，2为duotuo，3为gold
     * @return int
     */
    public function getUserDiscountCount($userId, $type = 0, $status = 1,
                                         $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(array(
                'ownerUserId'=>$userId,
                'type'=>$type,
                'status'=>$status,
                'consumeType'=>$consumeType
            ));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'getUserDiscountCount', $request, 1, false);
            return intval($response['data']);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }


    /*
     * 判断用户投资券动态
     * @param $userId int 用户id
     * @return int 1为有，0没有
     */
    public function checkUserMoments($userId) {
        return 0;
        // 参数校验
        if (!$this->siteSwitch() || empty($userId) || !is_numeric($userId) || $userId < 0) {
            return 0;
        }

        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(array('userId'=>$userId));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'checkUserMoments', $request, 1, false);
            return intval($response['data']);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 清理投资券动态
     * @param $userId int 用户id
     * @return int 1为成功，0失败
     */
    public function clearUserMoments($userId) {
        return 0;
        // 参数校验
        if (!$this->siteSwitch() || empty($userId) || !is_numeric($userId) || $userId < 0) {
            return 0;
        }

        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(array('userId'=>$userId));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'clearUserMoments', $request, 1, false);
            return intval($response['data']);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 退回优惠券
     * @param $userId int 用户id
     * @param $discountId int 券id
     * @param $token string token唯一码
     * @return mixed
     */
    public function refundDiscount($userId, $discountId = 0, $token = '') {
        try {
            if (empty($userId) || !is_numeric($userId)) {
                throw new O2OException('用户id不能为空或非数字');
            }

            if ($discountId <= 0 && empty($token)) {
                throw new O2OException('discountId和token不能都为空');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray(array(
                'userId'=>$userId,
                'discountId'=>$discountId,
                'token'=>$token
            ));

            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'refundDiscount', $request, 1, false);
            return $response['data'];
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * getDiscountRecord 获取投资券使用凭证
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-08-20
     * @param mixed $discountId
     * @access public
     * @return void
     */
    public function getDiscountRecord($discountId) {
        return DiscountModel::instance()->findby('discount_id = '.intval($discountId));
    }

    /**
     * GTM锁定投资券
     * @param $userId int 用户id
     * @param $discountId int 投资券id
     * @param $dealLoadId int 交易id
     * @param $discountType int 投资券类型
     * @param $triggerTime int 触发时间
     * @return bool
     */
    public function consumeDiscount($userId, $discountId, $dealLoadId, $discountType, $triggerTime,
                                    $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $extraInfo = array()) {
        if (!$this->siteSwitch()) {
            throw new O2OException('该站点暂未开通投资券功能');
        }

        if (empty($userId) || !is_numeric($userId)) {
            throw new O2OException('用户id不能为空或非数字');
        }

        if (empty($discountId) || !is_numeric($discountId)) {
            throw new O2OException('投资券id不能为空或非数字');
        }

        // 对于非黄金，这里需要合法的交易id
        if ($discountType != CouponGroupEnum::DISCOUNT_TYPE_GOLD && empty($dealLoadId)) {
            throw new O2OException('交易id不能为空');
        }

        // 只增加discount凭证记录
//        $consumeType = $discountType == CouponGroupEnum::DISCOUNT_TYPE_GOLD
//            ? CouponGroupEnum::CONSUME_TYPE_GOLD_ORDER
//            : $consumeType;

        // 先查询记录是否存在
        $record = DiscountModel::instance()->findBy('discount_id='.intval($discountId));
        if (!empty($record)) {
            // 保证操作的幂等
            if ($record['consume_id'] != $dealLoadId) {
                throw new O2OException('优惠券已使用');
            }
            return true;
        }

        $discountParams = array(
            'user_id' => $userId,
            'discount_id' => $discountId,
            'consume_type' => $consumeType,
            'consume_id' => $dealLoadId,
            'discount_type' => intval($discountType),
            'create_time' => date('Y-m-d H:i:s'),
            'extra_info' => json_encode($extraInfo)
        );

        if (!DiscountModel::instance()->addRecord($discountParams)) {
            throw new \Exception('使用投资劵失败');
        }

        return true;
    }

    /**
     * GTM回退投资券，删除投资券凭证
     * @param $userId int 用户id
     * @param $discountId int 投资券id
     * @return bool
     */
    public function cancelConsumeDiscount($userId, $discountId) {
        $record = DiscountModel::instance()->findBy('discount_id='.intval($discountId));
        // 如果记录不存在，直接返回true
        if (!$record) {
            return true;
        }

        if (!DiscountModel::instance()->delRecord($userId, $discountId)) {
            throw new \Exception('回退投资劵失败');
        }

        return true;
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
        $consumeResult = $this->consumeDiscount($userId, $discountId, $consumeId, $discountType, $triggerTime, $consumeType);
        try {
            $request = new RequestExchangeDiscount();
            $request->setOwnerUserId(intval($userId));
            $request->setDiscountId(intval($discountId));
            $request->setDealLoadId($consumeId);
            $request->setTriggerTime(intval($triggerTime));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'consumeDiscount', $request);
            return $response;
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
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
    public function unfreezeDiscount ($userId, $discountId, $triggerTime, $consumeId, $consumeType, $discountType) {
        //删除投资券凭证
        $this->cancelConsumeDiscount($userId, $discountId);
        if (empty($userId) || !is_numeric($userId)) {
            throw new O2OException('用户id不能为空或非数字');
        }

        if (empty($discountId) || !is_numeric($discountId)) {
            throw new O2OException('投资券id不能为空或非数字');
        }

        if (empty($consumeId)) {
            throw new O2OException('交易id不能为空');
        }

        try {
            $request = new RequestExchangeDiscount();
            $request->setOwnerUserId(intval($userId));
            $request->setDiscountId(intval($discountId));
            $request->setDealLoadId($consumeId);
            $request->setTriggerTime(intval($triggerTime));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Discount', 'unfreezeDiscount', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }

        return $response;
    }

    /**
     * canUseDiscount检查投资券是否适用的统一接口
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-11-13
     * @param mixed $userId
     * @param mixed $discountId
     * @param mixed $discountGroupId
     * @param mixed $errorInfo
     * @param mixed $consumeType
     * @param mixed $extraParam,可选key:dealId-标id,money-投资金额,bidDayLimit-投资期限(天)
     * @access public
     * @return void
     */
    public function canUseDiscount($userId, $discountId, $discountGroupId, &$errorInfo, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $extraParam = []) {
        $dealId = isset($extraParam['dealId']) ? $extraParam['dealId'] : '';
        $money = isset($extraParam['money']) ? $extraParam['money'] : false;
        $bidDayLimit = isset($extraParam['bidDayLimit']) ? $extraParam['bidDayLimit'] : 0;


        PaymentApi::log("O2ODiscountService canUseDiscount groupId: {$discountGroupId}, dealId: {$dealId}, money: {$money}", Logger::INFO);
        $discountGroup = $this->getDiscountGroup($discountGroupId);
        if ($discountGroup == false) {
            PaymentApi::log("O2ODiscountService canUseDiscount, empty discount group: ", Logger::ERR);
            return false;
        }

        // 验证标的信息
        $options = $this->validateDealInfo($dealId, $discountGroup['type'], $consumeType, $bidDayLimit);
        if ($options == false) {
            PaymentApi::log("O2ODiscountService checkDiscountUseRules, empty options", Logger::WARN);
            return false;
        }

        $bidDayLimit = $options['bidDayLimit'];
        $category = $options['category'];
        $projectId = $options['projectId'];
        $dealTag = $options['dealTag'];

        // 标的期限
        if ($bidDayLimit < $discountGroup['bidDayLimit']) {
            PaymentApi::log("O2ODiscountService canUseDiscount, bidDayLimit({$bidDayLimit}, {$discountGroup['bidDayLimit']})", Logger::WARN);
            $errorInfo = array('errorCode' => 1, 'discountDayLimit' => $discountGroup['bidDayLimit']);
            return false;
        }

        // 投标金额
        if ($money < $discountGroup['bidAmount']) {
            PaymentApi::log("O2ODiscountService canUseDiscount, money({$money}, {$discountGroup['bidAmount']})", Logger::WARN);
            $errorInfo = array('errorCode' => 2, 'discountGoodsPrice' => $discountGroup['bidAmount']);
            return false;
        }

        $useData = explode(',', $discountGroup['useData']);
        $useDataValue = false;
        $useRules = $discountGroup['useRules'];
        if ($useRules == CouponGroupEnum::DEAL_USE_RULES_CATEGORY) {
            // 获取标的类别
            $useDataValue = $category;
        } else if ($useRules == CouponGroupEnum::DEAL_USE_RULES_PROJECTS) {
            // 获取项目id
            $useDataValue = $projectId;
        } else if ($useRules == CouponGroupEnum::DEAL_USE_RULES_TAGS) {
            // 获取标的tag
            $useDataValue = $dealTag;
        }

        PaymentApi::log("O2ODiscountService canUseDiscount, useRules: {$useRules}({$useDataValue}, {$discountGroup['useData']})", Logger::INFO);
        if ($useDataValue !== false && !empty($useData)) {
            $useType = $discountGroup['useType'];
            if ($useType == CouponGroupEnum::RESTRICT_TYPE_DEAL && !in_array($useDataValue, $useData)) {
                return false;
            }
            if ($useType == CouponGroupEnum::RESTRICT_TYPE_NO_DEAL && in_array($useDataValue, $useData)) {
                return false;
            }
        }

        return true;
    }
}

<?php

namespace core\service\o2o;

use core\service\BaseService;
use libs\utils\Aes;
use libs\utils\Finance;
use libs\utils\PaymentApi;
use core\dao\deal\DealModel;
use core\service\duotou\DtEntranceService;
use core\service\deal\DealTagService;
use core\dao\deal\DealLoanTypeModel;
use core\dao\project\DealProjectModel;
use libs\utils\Logger;
use core\enum\DealEnum;
use core\enum\CouponGroupEnum;
use core\service\o2o\CouponService;

/**
 * 优惠券相关接口
 */
class DiscountService extends BaseService {

    /**
     * discountAesKey
     *
     * @var string
     * @access private
     */
    private $discountAesKey = 'D6oaTHnNyJej4L';

    const SIGN_KEY = 'dVlhTXBEbWNNUnE4cUJOSnAyYnY';

    //还款方式
    const LOAN_TYPE_5 = 5;//按天一次性还款
    const LOAN_TYPE_BY_CROWDFUNDING = 7; // 公益标
    //标的类型
    const DEAL_TYPE_COMPOUND = 1;//通知贷

    // 投资券类型
    const DISCOUNT_TYPE_CASHBACK = 1;   // 返现
    const DISCOUNT_TYPE_RAISE_RATES = 2;// 加息券

    public static $DISCOUNT_TYPES = array(
        self::DISCOUNT_TYPE_CASHBACK => '返现券',
        self::DISCOUNT_TYPE_RAISE_RATES => '加息券',
    );

    // 交易的类型区分
    const CONSUME_TYPE_P2P = 1;             // p2p交易
    const CONSUME_TYPE_DUOTOU = 2;          // 智多鑫交易
    const CONSUME_TYPE_DUOTOU_ORDER = 3;    // 智多鑫订单
    const CONSUME_TYPE_RESERVE = 7;         // 随鑫约

    //allowanceType 状态
    const ALLOWANCE_TYPE_MONEY = 1;             // 现金
    const ALLOWANCE_TYPE_BONUS = 2;             // 红包

    // 投资券发放方式
    const DISCOUNT_GIVE_WITH_INTEREST = 1;  // 随息发放
    const DISCOUNT_GIVE_ONE_TIME = 2;       // 一次性发放

    // 标的要求
    const DEAL_USE_RULES_CATEGORY = 1;  // 产品类别
    const DEAL_USE_RULES_TAGS = 2;      // 标签
    const DEAL_USE_RULES_PROJECTS = 3;  // 所属项目

    // 限制使用方式
    const RESTRICT_TYPE_DEAL = 1;       // 可用于出借
    const RESTRICT_TYPE_NO_DEAL = 2;    // 不可用于出借

    // 投资券赠送方式
    const GIVEN_TYPE_ONLY_SEND = 2;             // 仅可用于赠送


    private static $funcMap = array(
        /**
         * 获取投资券组使用规则
         * @param $groupId int 投资券组id
         * @return array
         */
        'getDiscountGroup' => array('groupId'),
        /**
         * 领取指定投资券规则的投资券
         * @param $userId int 用户id
         * @param $discountRuleId int 投资规则id
         * @param $token string 唯一token
         * @param $bidAmount float 起投资金额或购买克数
         * @param $bidDayLimit int 起投期限
         * @return array
         */
        'acquireRuleDiscount' => array('userId', 'discountRuleId', 'token', 'bidAmount', 'bidDayLimit'),
        /**
         * 领取投资券
         * @param $userId int 用户id
         * @param $discountGroupId int 投资券组id
         * @param $token string 唯一token
         * @param $dealLoadId int 交易id
         * @return array
         */
        'acquireDiscount' => array('userId', 'discountGroupId', 'token', 'dealLoadId'),
        /**
         * 批量领取投资券
         * @param $userIds string 用户列表，多个用逗号','分割，建议用户列表每次在2000个以内
         * @param $groupIds string 投资券组列表，多个用逗号','分割
         * @param $taskId int 任务id
         * @param $seriaNo int 批次号
         * @param $tokenPre string token前缀
         * @param $siteId int 分站id
         * @return int 成功领取的投资券个数
         */
        'batchAcquireDiscount' => array('userIds', 'groupIds', 'taskId', 'serialNo', 'tokenPre', 'siteId'),
        /**
         * 兑换投资券
         * @param $userId int 用户id
         * @param $discountId int 投资券id
         * @param $dealLoadId int 交易id
         * @param $triggerTime int 触发时间
         */
        'exchangeDiscount' => array('userId', 'discountId', 'dealLoadId', 'triggerTime'),
        /**
         * 获取用户未使用的投资券个数
         * @param $userId int 用户id
         * @return array 对应个数
         */
        'getMineUnusedDiscountCount' => array('userId'),
        /**
         * 获取用户未使用的投资券个数
         * @param $userId int 用户id
         * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
         * @param $consumeType int 交易类型，1为p2p，2为duotuo
         * @return int 对应个数
         */
        'getUserUnusedDiscountCount' => array('userId', 'type', 'consumeType'),
        /**
         * 获取用户券列表
         * @param $userId int 用户id
         * @param $status int
         * @param $pageNo int 页数
         * @param $pageSize int 每页最大记录数
         * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
         * @param $consumeType int 交易类型，1为p2p，2为duotuo
         * @param $useStatus int 投资券的状态，0-所有类型;1－可使用(包含待使用和待兑换确认);2-不可使用(包含已使用和已过期)
         * @return array
         */
        'getUserDiscountList' => array('userId','status','pageNo','pageSize','type','consumeType','useStatus', 'hasTotalCount'),
        /**
         * 投资券锁定赠送
         * @param $fromUserId int 转出方
         * @param $discountId int 投资券id
         * @param $toMobile string 接收者手机号
         * @return array
         */
        'lockAndGiveDiscount' => array('fromUserId', 'discountId', 'toMobile'),
        /**
         * 投资券赠送
         * @param $fromUserId int 转出方
         * @param $toUserId int 接受者
         * @param $discountId int 投资券id
         * @param $toMobile string 接收者手机号
         * @return array
         */
        'giveDiscount' => array('fromUserId', 'toUserId', 'discountId', 'toMobile'),
        /**
         * 获取用户可赠送的投资券列表
         * @param $userId int 用户id
         * @param $pageNo int 页码
         * @param $pageSize int 每页个数
         * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
         * @param $consumeType int 交易类型，1为p2p，2为duotuo
         * @return array
         */
        'getUserGivenDiscountList' => array('userId', 'pageNo', 'pageSize', 'type', 'consumeType'),
        /**
         * 获取用户未来将要过期的投资券个数
         * @param $userId int 用户id
         * @param $elapsedTime int 逝去的时间
         * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
         * @param $consumeType int 交易类型，1为p2p，2为duotuo
         * @return int
         */
        'getUserWillExpireDiscountCount' => array('userId', 'elapsedTime', 'type', 'consumeType'),
        /**
         * 获取用户投资券总数
         * @param $userId int 用户id
         * @param $type int 类型，1为返现券，2为加息券，3为黄金券，默认为0，表示不区分
         * @param $status int 投资券状态
         * @param $consumeType int 交易类型，1为p2p，2为duotuo
         * @return int
         */
        'getUserDiscountCount' => array('userId', 'type', 'status', 'consumeType'),
        /**
         * 清除券状态
         * @param $discountId int 投资券id
         * @return array
         */
        'canUseDiscount' => array('userId', 'discountId', 'discountGroupId', 'consumeType', 'extraParam'),
        'freezeDiscount' => array('userId', 'discountId', 'triggerTime', 'consumeId', 'consumeType', 'discountType'),
        'unfreezeDiscount' => array('userId', 'discountId', 'triggerTime', 'consumeId', 'consumeType', 'discountType'),
        'consumeDiscount' => array('userId', 'discountId', 'dealLoadId', 'discountType', 'triggerTime', 'consumeType', 'extraInfo'),
        'cancelConsumeDiscount' => array('userId', 'discountId'),
        'o2oExchangeDiscount' => array('userId', 'discountId', 'dealLoadId', 'dealName', 'couponCode', 'buyPrice', 'discountGoldCurrentOrderId', 'consumeType', 'annualizedAmount'),
        'discountMine' => array('userId', 'page', 'discountType', 'consumeType', 'useStatus'),
        'validateDiscountAndDealinfo' => array('userId', 'discountId', 'discountGroupId', 'discountSign', 'dealInfo', 'money', 'siteId'),
        /**
         * 获取投资券使用凭证
         * @param $discountId int 投资券id
         * @return array
         */
        'getDiscountRecord' => array('discountId'),
        /*
        * 判断用户投资券动态
        * @param $userId int 用户id
        * @return int 1为有，0没有
        */
        'checkUserMoments' => array('userId')
    );

    /**
     * 获取参数加密字符串.
     *
     * @param array $data
     * @access public
     *
     * @return string
     */
    public static function getSignature($data)
    {
        return Aes::signature($data, self::SIGN_KEY);
    }

    /**
     * generateSN
     *
     * @param mixed $discountId
     * @access public
     * @return void
     */
    public static function generateSN($discountId)
    {
        return Aes::encryptHex($discountId, self::discountAesKey);
    }

    /**
     * 获取用户可使用投资券列表
     * @param $userId int 用户id
     * @param $dealId int 交易id
     * @param $money float 交易金额
     * @param $page int 页数
     * @param $pageSize int 每页最大记录数
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
     * @param $consumeType int 交易类型，1为p2p，2为duotuo
     * @return array
     */
    public static function getAvailableDiscountList($userId,$dealId,$money,$page,$pageSize,$type,$consumeType,$bidDayLimit = 0) {
        if ($type == 0 || $type == self::DISCOUNT_TYPE_RAISE_RATES || $type == self::DISCOUNT_TYPE_CASHBACK) {
            if ($consumeType == self::CONSUME_TYPE_DUOTOU) {
                $entranceInfo = (new DtEntranceService())->getEntranceInfo($dealId);
                $lockDay = isset($entranceInfo['lock_day']) ? $entranceInfo['lock_day'] : 0;
                $annualizedAmount = DealModel::instance()->floorfix($money * $lockDay / DealEnum::DAY_OF_YEAR, 2);
            } else if ($consumeType == self::CONSUME_TYPE_RESERVE) {
                $annualizedAmount = DealModel::instance()->floorfix($money * $bidDayLimit / DealEnum::DAY_OF_YEAR, 2);
            } else {
                $annualizedAmount = self::getAnnualizedAmountByDealIdAndAmount($dealId, $money);
                $annualizedAmount = ($annualizedAmount > 0) ? $annualizedAmount : 0;
            }
        } else {
            $annualizedAmount = 0;
        }

        $result = array('total' => 0, 'totalPage' => 0, 'list' => array());
        $options = self::validateDealInfo($dealId, $type, $consumeType, $bidDayLimit);

        if ($money !== false && $money > 0) {
            $options['bidAmount'] = $money;
        }

        $options['bidAmount'] = 0;
        $args = array(
            'ownUserId' => $userId,
            'bidAmount' => $options['bidAmount'],
            'bidDayLimit' => $options['bidDayLimit'],
            'category' => $options['category'],
            'dealTag' => $options['dealTag'],
            'projectId'=> intval($options['projectId']),
            'page' => intval($page),
            'pageSize' => intval($pageSize),
            'hasTotalCount' => 1,
            'type' => $type,
            'annualizedAmount' => $annualizedAmount,
            'consumeType' => $consumeType,
        );

        $response = self::rpc('o2o', 'discounts/getAvailableDiscountList', $args);

        if (!is_array($response) || empty($response)) {
            $result['list'] = array();
            return $result;
        }

        $result['total'] = $response['total'];
        $result['totalPage'] = $response['totalPage'];
        $list = array();
        $hongbaoText = app_conf('NEW_BONUS_TITLE');
        $hongbaoUnit = app_conf('NEW_BONUS_UNIT');
        foreach ($response['data'] as $item ) {
            // 用于在投资确认页展现投资券信息
            $allowanceInfo = '';
            $goodPriceInfo = '';
            if ($item['type'] == self::DISCOUNT_TYPE_RAISE_RATES) {
                $allowanceInfo .= '可获'.$item['goodsPrice'].'%加息，满'.$item['bidAmount'].'元可用';
                $goodPriceInfo .= '获得加息'.$item['goodsPrice'].'%';
                if ($item['goodsMaxPrice'] > 0) {
                    $goodPriceInfo .= '，最高返利'.$item['goodsMaxPrice'].'元';
                }

                if ($item['goodsType'] == self::ALLOWANCE_TYPE_MONEY) {
                    $goodPriceInfo .= '现金';
                } else if ($item['goodsType'] == self::ALLOWANCE_TYPE_BONUS) {
                    $goodPriceInfo .= $hongbaoText;
                }

                if ($item['goodsGiveType'] == self::DISCOUNT_GIVE_WITH_INTEREST) {
                    $goodPriceInfo .= "\n".'随息发放';
                } else if ($item['goodsGiveType'] == self::DISCOUNT_GIVE_ONE_TIME) {
                    $goodPriceInfo .= "\n".'预计24小时内到帐';
                }
            } else if ($item['type'] == self::DISCOUNT_TYPE_CASHBACK) {
                $allowanceInfo = '可获';
                $goodPriceInfo = '获得';
                if ($item['goodsType'] == self::ALLOWANCE_TYPE_MONEY) {
                    $allowanceInfo .= $item['goodsPrice'].'元现金';
                    $goodPriceInfo .= $item['goodsPrice'].'元现金';
                } else if ($item['goodsType'] == self::ALLOWANCE_TYPE_BONUS) {
                    $allowanceInfo .= $item['goodsPrice'].$hongbaoUnit.$hongbaoText;
                    $goodPriceInfo .= $item['goodsPrice'].$hongbaoUnit.$hongbaoText;
                }
                $goodPriceInfo .= "\n".'预计24小时内到帐';
                $allowanceInfo .= '，满'.$item['bidAmount'].'元可用';
            }

            // 为了在前端正常展示，后端做urlencode处理，用于参数透传到confirm.html
            $item['youhuiquan'] = $allowanceInfo;
            // 这里的goodPriceInfo用于透传
            $item['goodPriceInfo'] = $goodPriceInfo;

            // 券的使用规则
            $item['useInfo'] = self::getUseInfo($item);
            $list[] = $item;
        }

        $result['list'] = $list;
        return $result;
    }

    /**
     * 验证标的信息
     * @param $dealId int 标id
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵价券，0为返现券和加息券
     */
    public static function validateDealInfo($dealId, $type, $consumeType = 1, $bidDayLimit = 0) {
        $res = array();
        //随鑫约校验标的信息逻辑
        if ($consumeType == self::CONSUME_TYPE_RESERVE) {
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
        if ($consumeType == self::CONSUME_TYPE_DUOTOU){
            // 根据id查找智多鑫活动信息
            $entranceInfo = (new DtEntranceService())->getEntranceInfo($dealId);
            // 加息券不能用在活期智多鑫
            if ($type == self::DISCOUNT_TYPE_RAISE_RATES && $entranceInfo['lock_day'] == 1) {
                return false;
            }

            $res['bidDayLimit'] = $entranceInfo['lock_day'];
            $res['projectId'] = '';
            $res['dealTag'] = '';
            $res['category'] = '';
            return $res;
        }

        // 返现券和加息券
        $columns = 'advisory_id, project_id, type_id, loantype, deal_type, deal_crowd, deal_tag_name, repay_time';
        $dealInfo = DealModel::instance()->find($dealId, $columns);
        if (empty($dealInfo)) {
            return false;
        }

        // 网贷理财中通知贷、公益标不能投
        if ($dealInfo['deal_type'] == self::DEAL_TYPE_COMPOUND
            || $dealInfo['loantype'] == self::LOAN_TYPE_BY_CROWDFUNDING) {
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
                    . $dealInfo['advisory_id'] . ", blacklist: " . $blackListStr, Logger::INFO);

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

    /**
     * 获取投资年化额(除通知贷),投资券确认页面用
     */
    public static function getAnnualizedAmountByDealIdAndAmount($dealId, $amount) {
        $dealModel = DealModel::instance()->findViaSlave($dealId);
        if (empty($dealModel)) {
            PaymentApi::log("O2OUtils.getAnnualizedAmountByDealIdAndAmount 获取投资年化额找不到交易记录, dealId:{$dealId}, amount:{$amount}", Logger::ERR);
            return 0;
        }
        $finance = new Finance();
        // 计算年化额
        $moneyYear = $finance->getMoneyYearPeriod($amount, $dealModel->loantype, $dealModel->repay_time);
        $rebateRate = $dealModel->getRebateRate($dealModel->loantype);
        $annualizedAmount = round(bcmul($moneyYear , $rebateRate, 2), 2);
        return $annualizedAmount;
    }


    /**
     * 获取投资券的使用限制文案
     */
    private static function getUseInfo(array $discount) {
        $useInfo = '';

        $useData = explode(',', $discount['useData']);
        if ($discount['useRules'] == self::DEAL_USE_RULES_PROJECTS) {
            foreach ($useData as $key=>$projectId) {
                $projectInfo = DealProjectModel::instance()->findViaSlave($projectId, 'name');
                if ($projectInfo) {
                    $useData[$key] = $projectInfo['name'];
                }
            }
        }

        // 赠送判断
        if ($discount['givenStatus'] == self::GIVEN_TYPE_ONLY_SEND) {
            $useInfo .= '仅可用于赠送好友。';
        }
        // 加息券判断
        if ($discount['type'] == self::DISCOUNT_TYPE_RAISE_RATES && $discount['goodsMaxPrice'] > 0) {
            $useInfo .= '最高返利'.$discount['goodsMaxPrice'].'元。';
        }

        if ($discount['useType'] == self::RESTRICT_TYPE_DEAL) {
            $useInfo .= '仅限投资'.implode('、', $useData).'时使用。';
        } else if ($discount['useType'] == self::RESTRICT_TYPE_NO_DEAL) {
            $useInfo .= '不可用于投资'.implode('、', $useData).'。';
        }
        return $useInfo;
    }

    /**
     * 获取用户可使用投资券个数
     * @param $userId int 用户id
     * @param $dealId int 交易id
     * @param $money bool
     * @param $type int 投资券类型，1为返现券，2为加息券，3为黄金抵扣券，0表示不区分
     * @param $consumeType int 交易类型，1为p2p，2为duotuo
     * @param $bidDayLimit int 起投期限
     * @return int
     */
    public static function getAvailableDiscountCount($userId, $dealId, $money, $type, $consumeType, $bidDayLimit) {
        $options = self::validateDealInfo($dealId, $type, $consumeType, $bidDayLimit);
        if ($options == false || empty($userId) || !is_numeric($userId)) {
            return 0;
        }

        // 投资金额
        $options['bidAmount']  = 0;
        if ($money !== false && $money > 0) {
            $options['bidAmount'] = $money;
        }

        $args = array(
            'ownerUserId' => intval($userId),
            'bidAmount' => $options['bidAmount'],
            'bidDayLimit' => $options['bidDayLimit'],
            'category' => $options['category'],
            'dealTag' => $options['dealTag'],
            'projectId'=> intval($options['projectId']),
            'type' => $type,
            'consumeType' => $consumeType,
        );

        return self::rpc('o2o', 'discounts/getAvailableDiscountCount', $args);
    }

    public static function getDiscount($discountId) {
        if (empty($discountId)) {
            return false;
        }
        $args = array(
            'id' =>$discountId
        );
        $res = self::rpc('o2o','discounts/getDiscount', $args);
        return  isset($res['data']) ? $res['data'] : false;
    }

    /**
     * 获取可得利息
     * @param $userId int 用户id
     * @param $dealLoadId int 交易id
     * @param $money float 投资金额
     * @param $discountId int 投资券id
     * @param $appVersion app版本
     * @param $consumeType int 交易类型，1为p2p，2为duotuo
     * @return float
     */
    public static function getExpectedEarningInfo($userId, $dealId, $money, $discountId, $consumeType = 1) {
        if (empty($discountId) || !is_numeric($discountId) || empty($userId) || !is_numeric($userId)) {
            return false;
        }

        $discount = self::getDiscount($discountId);
        if ($discount === false) {
            return false;
        }

        // 权限验证，只有券的属主才能查看
        if ($discount['ownerUserId'] != $userId) {
            self::setError('该券不属于您，无权查看', 1);
            return false;
        }

        // 用于在投资确认页展现投资券信息
        $res = array();
        $allowanceInfo = '';
        $goodPriceInfo = '';
        if ($discount['type'] == self::DISCOUNT_TYPE_RAISE_RATES) {
            // 如果金额为空，或者没有达到最小投资额，则显示初始文案
            if (empty($money) || $money < $discount['bidAmount']) {
                $allowanceInfo .= '可获'.$discount['goodsPrice'].'%加息';
                $goodPriceInfo .= '加息'.$discount['goodsPrice'].'%';
                if ($discount['goodsMaxPrice'] > 0) {
                    $goodPriceInfo .= '，最高返利'.$discount['goodsMaxPrice'].'元';
                }

                if ($discount['goodsType'] == self::ALLOWANCE_TYPE_MONEY) {
                    $goodPriceInfo .= '现金';
                } else if ($discount['goodsType'] == self::ALLOWANCE_TYPE_BONUS) {
                    $goodPriceInfo .= app_conf('NEW_BONUS_TITLE');
                }
            } else {
                if ($consumeType == self::CONSUME_TYPE_DUOTOU) {
                    $entranceInfo = (new DtEntranceService())->getEntranceInfo($dealId);
                    $lockDay = isset($entranceInfo['lock_day']) ? $entranceInfo['lock_day'] : 0;
                    $moneyYear = $money * $lockDay / DealEnum::DAY_OF_YEAR;
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
                if ($discount['goodsGiveType'] == self::DISCOUNT_GIVE_WITH_INTEREST) {
                    $goodPriceInfo .= '加息'.$discount['goodsPrice'].'%，预期可获'.$goodPrice.'元';
                } else if ($discount['goodsGiveType'] == self::DISCOUNT_GIVE_ONE_TIME) {
                    $goodPriceInfo .= '获得'.$goodPrice.'元';
                }

                if ($discount['goodsType'] == self::ALLOWANCE_TYPE_MONEY) {
                    $allowanceInfo .= '元现金';
                    $goodPriceInfo .= '现金';
                } else if ($discount['goodsType'] == self::ALLOWANCE_TYPE_BONUS) {
                    $allowanceInfo .= app_conf('NEW_BONUS_UNIT') . app_conf('NEW_BONUS_TITLE');
                    $goodPriceInfo .= app_conf('NEW_BONUS_TITLE');
                }
            }

            if ($discount['goodsGiveType'] == self::DISCOUNT_GIVE_WITH_INTEREST) {
                $goodPriceInfo .= "\n".'随息发放';
            } else if ($discount['goodsGiveType'] == self::DISCOUNT_GIVE_ONE_TIME) {
                $goodPriceInfo .= "\n".'预计24小时内到帐';
            }

            $allowanceInfo .= '，满'.$discount['bidAmount'].'元可用';
        } else if ($discount['type'] == self::DISCOUNT_TYPE_CASHBACK) {
            $allowanceInfo .= '可获';
            $goodPriceInfo .= '获得';
            if ($discount['goodsType'] == self::ALLOWANCE_TYPE_MONEY) {
                $allowanceInfo .= $discount['goodsPrice'].'元现金';
                $goodPriceInfo .= $discount['goodsPrice'].'元现金';
            } else if ($discount['goodsType'] == self::ALLOWANCE_TYPE_BONUS) {
                $allowanceInfo .= $discount['goodsPrice'] . app_conf('NEW_BONUS_UNIT') . app_conf('NEW_BONUS_TITLE');
                $goodPriceInfo .= $discount['goodsPrice'] . app_conf('NEW_BONUS_UNIT') . app_conf('NEW_BONUS_TITLE');
            }
            $goodPriceInfo .= "\n".'预计24小时内到帐';
            $allowanceInfo .= '，满'.$discount['bidAmount'].'元可用';
        }

        // 这里的discountDetail直接用于展示
        $res['discountDetail'] = $allowanceInfo;
        // 这里的discountGoodPrice用于透传
        $res['discountGoodPrice'] = $goodPriceInfo;
        // wap需要此字段计算最大起投金额
        $res['bidAmount'] = $discount['bidAmount'];
        $res['type'] = $discount['type'];
        return $res;
    }

    public static function checkDiscountUseRules($discountId, $groupId, $dealId, $money, &$errorInfo, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        PaymentApi::log("O2ODiscountService checkDiscountUseRules groupId: {$groupId}, dealId: {$dealId}, money: {$money}", Logger::INFO);
        $discountRecord = self::getDiscountRecord($discountId);
        if ($discountRecord) {
            PaymentApi::log("O2ODiscountService checkDiscountUseRules, discount record exist: $discountId", Logger::ERR);
            return false;
        }
        $discountGroup = self::getDiscountGroup($groupId);
        if ($discountGroup == false) {
            PaymentApi::log("O2ODiscountService checkDiscountUseRules, empty discount group: ", Logger::ERR);
            return false;
        }

        // 验证标的信息
        $options = self::validateDealInfo($dealId, $discountGroup['type'], $consumeType);
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
            $errorInfo = array('errorCode' => 1, 'errorMsg' => '投资劵使用期限需大于' . $discountGroup['bidDayLimit'] . '天');
            return false;
        }

        // 投标金额
        if ($money < $discountGroup['bidAmount']) {
            PaymentApi::log("O2ODiscountService checkDiscountUseRules, money({$money}, {$discountGroup['bidAmount']})", Logger::WARN);
            $errorInfo = array('errorCode' => 2, 'errorMsg' => '最低投资金额为' . $discountGroup['bidAmount'] . '元');
            return false;
        }

        $useData = explode(',', $discountGroup['useData']);
        $useDataValue = false;
        $useRules = $discountGroup['useRules'];
        if ($useRules == 1) {
            // 获取标的类别
            $useDataValue = $category;
        } else if ($useRules == 3) {
            // 获取项目id
            $useDataValue = $projectId;
        } else if ($useRules == 2) {
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

            if ($useType == 1 && !in_array($useDataValue, $useData)) {
                return false;
            }

            if ($useType == 2 && in_array($useDataValue, $useData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        $p2pInterface = array(
            'canUseDiscount',
            'freezeDiscount',
            'unfreezeDiscount',
            'consumeDiscount',
            'cancelConsumeDiscount',
            'o2oExchangeDiscount',
            'validateDiscountAndDealinfo',
            'getDiscountRecord',
        );

        $ncfphInterface = array('discountMine');

        if (in_array($name, $p2pInterface)) {
            return self::rpc('ncfwx', 'discount/'.$name, $args, true);
        } else if (in_array($name, $ncfphInterface)) {
            return self::rpc('ncfwx', 'ncfph/'.$name, $args, true);
        } else {
            return self::rpc('o2o', 'discounts/'.$name, $args);
        }

    }
}

<?php

namespace core\service;

use libs\utils\Aes;
use libs\utils\Logger;
use core\event\O2OExchangeDiscountEvent;
use libs\utils\Monitor;
use core\service\oto\O2ODiscountService;
use core\dao\DiscountModel;
use core\dao\DiscountRateModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Services\TaskService as GTaskService;
use core\service\marketing\MarketingService;
use libs\lock\LockFactory;
use core\service\O2OService;

class DiscountService extends BaseService
{
    const SIGN_KEY = 'dVlhTXBEbWNNUnE4cUJOSnAyYnY';

    /**
     * 监控.
     */
    const DEAL_FAILD   = 'DISCOUNT_DEAL_FAILD';
    const DEAL_FAILD_TYPE   = 'DISCOUNT_DEAL_FAILD_TYPE';
    const DEAL_SUCCESS = 'DISCOUNT_DEAL_SUCCESS';
    const SIGN_FAILD   = 'DISCOUNT_SIGN_FAILD';
    const USE_ERR      = 'DISCOUNT_USE_ERR';
    const DISCOUNT_CONSUME_TASK = 'DISCOUNT_CONSUME_TASK';

    const CACHE_CONSUME_PREFIX = 'discount_consume_';

    const CACHE_DISCOUNT_WX_SHARE_PREFIX = 'discount_service_wx_share_';

    /**
     * 分享模板缓存KEY
     */
    const CACHE_DISCOUNT_WX_SHARE_TEMPLATE_PREFIX = 'discount_service_wx_share_templete_';

    // 投资券类型描述
    public static $goodsTypeDesp = [ 0 => '', 1 => '现金', 2 => '红包'];

    /**
     * discountAesKey
     *
     * @var string
     * @access private
     */
    private $discountAesKey = 'D6oaTHnNyJej4L';

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
     * 投资劵同步.
     *
     * @param int    $userId 用户id
     * @param int    $discountId 投资券id
     * @param int    $dealLoadId 交易id
     * @param string $dealName 标名称
     * @param string $couponCode 优惠码
     * @param float $buyPrice 购买价格
     * @param int $consumeType 交易类型
     * @param int $discountGoldOrderId 黄金券购买黄金订单id
     * @access public
     *
     * @return bool
     */
    public function consumeEvent($userId, $discountId, $dealLoadId, $dealName,
                            $couponCode = 0, $buyPrice = 0, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P,
                            $discountGoldOrderId = 0) {
        // 未使用投资劵
        if ($discountId <= 0) {
            return true;
        }

        if ($discountGoldOrderId == 0) {
            $discountGoldOrderId = \NCFGroup\Common\Library\Idworker::instance()->getId();
        }

        $obj = new GTaskService();
        $event = new O2OExchangeDiscountEvent($userId, $discountId, $dealLoadId, $dealName,
            $couponCode, $buyPrice, $discountGoldOrderId, $consumeType);

        // 失败重试次数
        for ($i = 0; $i < 3; $i++) {
            $result = $obj->doBackground($event, 10, TASK::PRIORITY_NORMAL);
            if ($result) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $userId, $discountId, $dealLoadId,
                    $dealName, $couponCode, $buyPrice, $consumeType, $discountGoldOrderId, "DISCOUNT_SUCCESS")));

                Monitor::add(self::DISCOUNT_CONSUME_TASK);
                return true;
            }
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $userId, $discountId, $dealLoadId,
            $dealName, $couponCode, $buyPrice, $consumeType, $discountGoldOrderId, "DISCOUNT_FAILD")));

        return false;
    }

    /**
     * 检验投资劵是否可用.
     *
     * @param int $discountId
     * @access public
     *
     * @return bool
     */
    public function checkConsume($discountId)
    {
        return \SiteApp::init()->cache->get(self::CACHE_CONSUME_PREFIX.$discountId);
    }

    /**
     * 根据站ID确实是否启用投资劵.
     *
     * @param int $siteId
     * @access public
     */
    public function siteSwitch($siteId = 1)
    {
        $o2oService = new O2ODiscountService();
        $switch = $o2oService->siteSwitch($siteId);
        return $switch == 1 ? true : false;
    }

    /**
     * 获取可用投资劵个数.
     *
     * @param mixed $userId
     * @param mixed $dealId
     * @param int   $siteId
     * @access public
     *
     * @return int
     */
    public function avaliableCount($userId, $dealId, $siteId = 1, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P)
    {
        if (!$this->siteSwitch($siteId)) {
            return 0;
        }
        $o2oService = new O2ODiscountService();
        $result = $o2oService->getAvailableDiscountCount($userId, $dealId, false, 0, $consumeType);

        return $result;
    }

    /**
     * 获取我的投资劵列表.
     *
     * @param mixed $userId
     * @param int   $status
     * @param int   $page
     * @param int   $count
     * @param int   $siteId
     * @access public
     *
     * @return array
     */
    public function mine($userId, $status = 0, $page = 1, $count = 10,
                         $type = CouponGroupEnum::DISCOUNT_TYPE_CASHBACK, $siteId = 1,
                         $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $useStatus = 0) {
        if (!$this->siteSwitch($siteId)) {
            return array('total' => 0, 'totalPage' => 0, 'list' => array());
        }
        $o2oService = new O2ODiscountService();
        $result = $o2oService->getUserDiscountList($userId, $status, $page, $count, $type, $consumeType, $useStatus);

        return $result;
    }

    /**
     * 获取可使用劵列表.
     *
     * @param int   $userId
     * @param int   $dealId
     * @param float $money
     * @param int   $page
     * @param int   $count
     * @param int   $siteId
     * @access public
     *
     * @return array
     */
    public function pickList($userId, $dealId, $money = 0, $page = 1, $count = 10,
                             $type = CouponGroupEnum::DISCOUNT_TYPE_CASHBACK, $siteId = 1,
                             $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        if (!$this->siteSwitch($siteId)) {
            return array('total' => 0, 'totalPage' => 0, 'list' => array());
        }

        $o2oService = new O2ODiscountService();
        $result = $o2oService->getAvailableDiscountList($userId, $dealId, $money, $page, $count, $type, 0, $consumeType);

        if ($result == false) {
            return array('total' => 0, 'totalPage' => 0, 'list' => array());
        }

        $params = array('user_id'=> $userId, 'deal_id'=> $dealId);
        $hongbaoText = app_conf('NEW_BONUS_TITLE');
        $goodsTypeDesp = array(0 => '', 1 => '现金', 2 => $hongbaoText);
        foreach ($result['list'] as &$item) {
            $params['discount_id'] = $item['id'];
            $params['discount_group_id'] = $item['discountGroupId'];
            $item['sign'] = $this->getSignature($params);
            $item['goodsTypeDesp'] = $goodsTypeDesp[intval($item['goodsType'])];
        }

        return $result;
    }

    /**
     * validateDiscountAndDealinfo 校验投资券和标的信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-31
     * @param mixed $userId
     * @param mixed $discountId
     * @param mixed $discountGroupId
     * @param mixed $discountSign
     * @param mixed $dealInfo
     * @param mixed $money
     * @param int $siteId
     * @access public
     * @return void
     */
    public function validateDiscountAndDealinfo($userId, $discountId, $discountGroupId, $discountSign, $dealInfo, $money = false, $siteId = 1) {
        $data = array('errCode' => 0, 'errMsg' => '');
        if ($dealInfo['loantype'] == 7) {
            $data['errCode'] = '-1';
            $data['errMsg'] = '投资劵不可用于公益标';
            return $data;
        }

        if ($this->checkConsume($discountId)) {
            $data['errCode'] = '-1';
            $data['errMsg'] = '此投资劵已经使用';
            return $data;
        }

        $params = array('user_id' => $userId, 'deal_id' => $dealInfo['id'], 'discount_id' => $discountId, 'discount_group_id' => $discountGroupId);
        $signStr = $this->getSignature($params);
        if ($discountSign != $signStr) {
            \libs\utils\Monitor::add(DiscountService::SIGN_FAILD);
            $data['errCode'] = '-1';
            $data['errMsg'] = '参数错误';

            return $data;
        }
        $errorInfo = [];
        $checkResult = (new O2ODiscountService())->checkDiscountUseRulesWithDealinfo($discountGroupId, $dealInfo, $money, $errorInfo, $consumeType, $siteId);
        if (!$checkResult) {
            \libs\utils\Monitor::add(self::USE_ERR);
            $msg = '使用投资劵错误';
            if ($errorInfo) {
                switch ($errorInfo['errorCode']) {
                    case 1:
                        $msg = '投资劵使用期限需大于' . $errorInfo['discountDayLimit'] . '天';
                        break;
                    case 2:
                        $msg = '最低投资金额为' . $errorInfo['discountGoodsPrice'] . '元';
                        break;

                    default:
                        break;
                }
            }
            $data['errCode'] = '-1';
            $data['errMsg'] = $msg;
            return $data;
        }
        return $data;
    }

    /**
     * 投资校验劵信息.
     *
     * @param mixed $userId
     * @param mixed $discountId
     * @param mixed $discountGroupId
     * @param mixed $discountSign
     * @param mixed $dealId
     * @param mixed $loanType
     * @param mixed $money
     * @access public
     *
     * @return array
     */
    public function validate($userId, $discountId, $discountGroupId, $discountSign, $dealId, $loanType, $money = false)
    {
        $data = array('errCode' => 0, 'errMsg' => '');
        if ($loanType == 7) {
            $data['errCode'] = '-1';
            $data['errMsg'] = '投资劵不可用于公益标';

            return $data;
        }

        if ($this->checkConsume($discountId)) {
            $data['errCode'] = '-1';
            $data['errMsg'] = '此投资劵已经使用';

            return $data;
        }

        $params = array('user_id' => $userId, 'deal_id' => $dealId, 'discount_id' => $discountId, 'discount_group_id' => $discountGroupId);
        $signStr = $this->getSignature($params);
        if ($discountSign != $signStr) {
            \libs\utils\Monitor::add(DiscountService::SIGN_FAILD);
            $data['errCode'] = '-1';
            $data['errMsg'] = '参数错误';

            return $data;
        }
        $errorInfo = [];
        $checkResult = (new O2ODiscountService())->checkDiscountUseRules($discountGroupId, $dealId, $money, $errorInfo);
        if (!$checkResult) {
            \libs\utils\Monitor::add(self::USE_ERR);
            $msg = '使用投资劵错误';
            if ($errorInfo) {
                switch ($errorInfo['errorCode']) {
                    case 1:
                        $msg = '投资劵使用期限需大于' . $errorInfo['discountDayLimit'] . '天';
                        break;
                    case 2:
                        $msg = '最低投资金额为' . $errorInfo['discountGoodsPrice'] . '元';
                        break;

                    default:
                        break;
                }
            }
            $data['errCode'] = '-1';
            $data['errMsg'] = $msg;

            return $data;
        }

        return $data;
    }

    /**
     * 获取某交易使用的投资券信息
     */
    public function getConsumeDiscount($dealLoadId) {
        if ($dealLoadId <= 0) {
            return false;
        }

        $record = DiscountModel::instance()->findByViaSlave('consume_id=":dealLoadId"', 'discount_id',
            array(':dealLoadId' => $dealLoadId));

        if (empty($record)) {
            return false;
        }

        $o2oDiscountService = new O2ODiscountService();
        return $o2oDiscountService->getDiscount($record['discount_id']);
    }

    /**
     * 使用的黄金抵价券抵扣的金额
     * @param int $dealLoadId 交易id
     * @return bool|array
     */
    public function getGoldDiscount($dealLoadId) {
        if ($dealLoadId <= 0) {
            return false;
        }

        $record = DiscountRateModel::instance()->findByViaSlave(
            'consume_id=":dealLoadId" and consume_type=":consume_type" and discount_type=":discount_type"',
            'discount_id as discountId, allowance_money as discountMoney',
            array(
                ':dealLoadId' => $dealLoadId,
                ':consume_type' => CouponGroupEnum::CONSUME_TYPE_GOLD,  // 黄金交易
                ':discount_type' => CouponGroupEnum::DISCOUNT_TYPE_GOLD // 黄金券
            )
        );

        if (empty($record)) {
            return false;
        }

        return $record;
    }

    /**
     * isUseDiscount
     *
     * @param mixed $dealLoadId
     * @access public
     * @return void
     */
    public function isUseDiscountRate($dealLoadId)
    {
        if ($dealLoadId <= 0) {
            return false;
        }

        $record = DiscountModel::instance()->findByViaSlave('consume_id=":dealLoadId"', 'discount_id',
            array(':dealLoadId' => $dealLoadId));

        if (empty($record)) {
            return false;
        }

        $o2oDiscountService = new O2ODiscountService();
        $discount = $o2oDiscountService->getDiscount($record['discount_id']);
        if ($discount == false) {
            throw new \Exception($o2oDiscountService->getErrorMsg(), $o2oDiscountService->getErrorCode());
        }

        // 加息券，且随息发放的券
        if ($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES
            && $discount['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_WITH_INTEREST) {
            return true;
        }

        return false;
    }

    /**
     * expectedEarningInfo
     *
     * @param int $userId 用户id
     * @param int $dealId 标id
     * @param float $money 金额
     * @param int $discountId 投资券id
     * @param int $siteId 分站id
     * @access public
     * @return array
     */
    public function expectedEarningInfo($userId, $dealId, $money, $discountId, $siteId = 1, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P)
    {
        $defaultRes = array('discountDetail'=>'', 'discountGoodPrice'=>'');
        if (!$this->siteSwitch($siteId)) {
            // 返回默认的数据
            return $defaultRes;
        }

        $o2oService = new O2ODiscountService();
        $res = $o2oService->getExpectedEarningInfo($userId, $dealId, $money, $discountId, '', $consumeType);
        return ($res === false) ? $defaultRes : $res;
    }

    /**
     * 获取可用的投资券个数
     * @param int $userId 用户id
     * @param int $dealId 标id
     * @param float $money 金额
     * @param int $siteId 分站id
     * @param int $consumeType 交易类型
     * @param int $type 投资券类型，1为返现券，2为加息券，3为黄金券
     * @access public
     * @return int
     */
    public function getAvailableDiscountCount($userId, $dealId, $money = false, $siteId = 1,
                                              $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $type = 0) {
        $defaultRes = 0;
        if (!$this->siteSwitch($siteId)) {
            // 返回默认的数据
            return $defaultRes;
        }
        $o2oService = new O2ODiscountService();
        $res = $o2oService->getAvailableDiscountCount($userId, $dealId, $money,$type,$consumeType);
        return ($res === false) ? $defaultRes : $res;
    }

    /**
     * 投资券赠送
     * @param $fromUserId int 转出方
     * @param $toUserId int 接受者
     * @param $discountId int 投资券id
     * @param int $siteId 分站id
     * @access public
     * @return mixed
     */
    public function giveDiscount($fromUserId, $toUserId, $discountId, $siteId = 1) {
        $defaultRes = false;
        if (!$this->siteSwitch($siteId)) {
            return $defaultRes;
        }

        $o2oService = new O2ODiscountService();
        $res = $o2oService->giveDiscount($fromUserId, $toUserId, $discountId);
        return ($res === false) ? $defaultRes : $res;
    }

    /**
     * getTemplateInfoBySiteId
     *
     * @param int $siteId
     * @access public
     * @return void
     */
    public function getTemplateInfoBySiteId($siteId = 1)
    {
        $now = time();
        $key = self::CACHE_DISCOUNT_WX_SHARE_TEMPLATE_PREFIX.$siteId;
        $templateInfo = \SiteApp::init()->cache->get($key);
        if (empty($templateInfo) || $templateInfo['timeStart'] > $now || $templateInfo['timeEnd'] < $now || $templateInfo['status'] == 0) {
            $marketingService = new MarketingService();
            $templateInfo = $marketingService->getTemplateInfoBySiteId($siteId);
            \SiteApp::init()->cache->set($key, $templateInfo, 86400);
        }
        return $templateInfo;
    }

    public function deleteCacheTemplateInfoBySiteId($siteId)
    {
        return \SiteApp::init()->cache->delete(self::CACHE_DISCOUNT_WX_SHARE_TEMPLATE_PREFIX.$siteId);
    }

    /**
     * getDiscountInfoBySn
     *
     * @param mixed $sn
     * @access public
     * @return void
     */
    public function getDiscountInfoBySn($sn)
    {
        if (empty($sn)) {
            return false;
        }
        $discountId = Aes::decryptHex($sn, $this->discountAesKey);
        if ($discountId <= 0) {
            return false;
        }
        $discountInfo = $this->getDiscountInfoById($discountId);
        return $discountInfo;
    }

    /**
     * generateSN
     *
     * @param mixed $discountId
     * @access public
     * @return void
     */
    public function generateSN($discountId)
    {
        return Aes::encryptHex($discountId, $this->discountAesKey);
    }

    public function convertSnToId($sn)
    {
        if (empty($sn)) {
            return false;
        }
        return Aes::decryptHex($sn, $this->discountAesKey);
    }

    /**
     * getWeixinInfoByUser
     *
     * @param mixed $userId
     * @param string $mobile
     * @access public
     * @return void
     */
    public function getWeixinInfoByUser($userId, $mobile = '')
    {
        $cacheKey = sprintf('discount_service_wx_share_weixin_info_by_user_%s_%s', $userId, $mobile);
        $winxinInfo = \SiteApp::init()->cache->get($cacheKey);
        if (empty($weixinInfo)) {
            if (empty($mobile)) {
                $user = \core\dao\UserModel::instance()->findByViaSlave('id=":userId"', 'mobile', array(':userId' => $userId));
                if (empty($user)) {
                    return false;
                }
                $mobile = $user['mobile'];
            }

            $bind = \core\dao\BonusBindModel::instance()->findByViaSlave('mobile=":mobile" AND status=1 AND openid IS NOT NULL', 'openid', array(':mobile' => $mobile));
            if (empty($bind)) {
                $weixinInfo = array('mobile' => $mobile);
            } else {
                $weixin = (new \core\service\WeixinInfoService())->getWeixinInfo($bind['openid']);
                if (empty($weixin['user_info'])) {
                    $weixinInfo = array('mobile' => $mobile);
                } else {
                    $weixinInfo = $weixin['user_info'];
                    $weixinInfo['mobile'] = $mobile;
                }
            }
            \SiteApp::init()->cache->set($cacheKey, $weixinInfo, 10);
        }

        if (isset($weixinInfo['nickname']) && !empty($weixinInfo['nickname'])) {
            $weixinInfo['nickname'] = $this->removeEmoji($weixinInfo['nickname']);
        }

        return $weixinInfo;
    }

    /**
     * getDiscountInfoById
     *
     * @param mixed $discountId
     * @access public
     * @return void
     */
    public function getDiscountInfoById($discountId, $isShare = true)
    {
        $cacheKey = self::CACHE_DISCOUNT_WX_SHARE_PREFIX.$discountId;

        $discountInfo = \SiteApp::init()->cache->get($cacheKey);
        if (empty($discountInfo)) {
            try {
                if ($isShare == true) {
                    $marketingService = new MarketingService();
                    $shareInfo = $marketingService->getGivenDiscountInfo($discountId);
                    if (!empty($shareInfo)) {
                        $discountInfo = json_decode($shareInfo['discountInfo'], true);
                        $discountInfo['fromUserId']   = $shareInfo['source'];
                        $discountInfo['toUserMobile'] = $shareInfo['mobile'];
                        $discountInfo['ownerUserId']  = $shareInfo['source'];
                        $discountInfo['toUserId']     = $shareInfo['userId'];
                        $discountInfo['openid']       = $shareInfo['openid'];
                        $discountInfo['collectTime']  = date('Y-m-d H:i:s', $shareInfo['createTime']);
                        $expiredTime = 86400;
                    } else {
                        $o2oDiscountService = new O2ODiscountService();
                        $discountInfo = $o2oDiscountService->getDiscount($discountId);
                        if (empty($discountInfo)) {
                            return false;
                        }
                        $expiredTime = 5;
                    }
                }
                //格式化信息Start
                if ($discountInfo['type'] == 1 && ceil($discountInfo['goodsPrice']) == $discountInfo['goodsPrice']) {
                    $discountInfo['goodsPrice'] = intval($discountInfo['goodsPrice']);
                }
                if ($discountInfo['type'] == 1 || $discountInfo['type'] == 2) {
                    $discountInfo['goodsDesc'] = $discountInfo['bidAmountDesc'] = "金额满".number_format($discountInfo['bidAmount'])."元";
                    $discountInfo['bidDayLimitDesc'] = '';
                    if ($discountInfo['bidDayLimit'] > 0) {
                        $discountInfo['goodsDesc'] .= "，期限满{$discountInfo['bidDayLimit']}天";
                        $discountInfo['bidDayLimitDesc'] = "期限满{$discountInfo['bidDayLimit']}天";
                    }
                    $discountInfo['goodsDesc'] .= '可用';
                } else {
                    $discountInfo['goodsDesc'] = $discountInfo['bidAmountDesc'] = "购买满".$discountInfo['bidAmount']."克";
                    $discountInfo['bidDayLimitDesc'] = '';
                    if ($discountInfo['bidDayLimit'] > 0) {
                        $discountInfo['goodsDesc'] .= "，期限满{$discountInfo['bidDayLimit']}天可用";
                        $discountInfo['bidDayLimitDesc'] = "期限满{$discountInfo['bidDayLimit']}天";
                    }
                }
                $discountInfo['goodsTimeDesc'] = sprintf('%s至%s有效', date('m-d H:i', $discountInfo['useStartTime']), date('m-d H:i', $discountInfo['useEndTime']));
                //格式化信息End
                \SiteApp::init()->cache->set($cacheKey, $discountInfo, $expiredTime);
            } catch (\Exception $e) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, json_encode($e))));
                return false;
            }
        }
        return $discountInfo;
    }

    /**
     * updateShareDiscountInfo
     *
     * @param mixed $discountId
     * @param mixed $discountInfo
     * @access private
     * @return void
     */
    private function updateShareDiscountInfo($discountId, $discountInfo)
    {
        $cacheKey = self::CACHE_DISCOUNT_WX_SHARE_PREFIX.$discountId;
        $result = \SiteApp::init()->cache->set($cacheKey, $discountInfo, 86400);
        if ($result == false) {
            $result = \SiteApp::init()->cache->delete($cacheKey);
        }
        return $result;
    }

    /**
     * collectDiscount
     *
     * @param mixed $sn
     * @param mixed $mobile
     * @access public
     * @return void
     */
    public function collectDiscount($sn, $mobile, $openid = '') {
        \libs\utils\Monitor::add('DISCOUNT_WX_SHARE_COLLECTION');
        $discountId = Aes::decryptHex($sn, $this->discountAesKey);
        if ($discountId <= 0) {
            return false;
        }

        $discountInfo = $this->getDiscountInfoById($discountId);

        if (empty($discountInfo)) {
            return false;
        }

        // 悲观锁
        $lockKey = "p2p_discount_collect-discount-share-".$discountId;
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 300)) {
            return false;
        }

        if (!empty($discountInfo['toUserMobile'])) {
            $lock->releaseLock($lockKey);//解锁
            return $discountInfo;
        }

        $user = \core\dao\UserModel::instance()->findByViaSlave('mobile=":mobile"', 'id', array(':mobile' => $mobile));
        if ($user['id'] > 0 && $user['id'] == $discountInfo['ownerUserId']) {
            $discountInfo['isSelf'] = true;
            $lock->releaseLock($lockKey);//解锁
            return $discountInfo;
        }

        if ($discountInfo['givenStatus'] == 1) { //兼容直接发给邀请人
            $toUser = \core\dao\UserModel::instance()->findByViaSlave('id=":id"', 'mobile', array(':id' => $discountInfo['ownerUserId']));
            $discountInfo['isSelf'] = true;
            $discountInfo['fromUserId'] = $user['id'];
            $discountInfo['collectTime'] = date('Y-m-d H:i:s', $discountInfo['updateTime']);
            $discountInfo['openid'] = '';
            $discountInfo['toUserMobile'] = $toUser['mobile'];
            $discountInfo['toUserId'] = $user['id'];
            $this->updateShareDiscountInfo($discountId, $discountInfo);
            return $discountInfo;
        }

        $result = (new MarketingService())->collectDiscount($discountId, $mobile, intval($user['id']), $discountInfo['ownerUserId'], $discountInfo, $openid);
        if ($result) {
            if (empty($result)) {
                $lock->releaseLock($lockKey);//解锁
                return false;
            }
            $discountInfo['fromUserId'] = $discountInfo['ownerUserId'];
            $discountInfo['collectTime'] = date('Y-m-d H:i:s');
            $discountInfo['givenStatus'] = 1;
            $discountInfo['openid'] = $openid;
            $discountInfo['toUserMobile'] = $mobile;
            $discountInfo['toUserId'] = $user['id'];
            $this->updateShareDiscountInfo($discountId, $discountInfo);
            $lock->releaseLock($lockKey);//解锁
            return $discountInfo;
        }

        return false;
    }

    public function removeEmoji($text) {
        return preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $text);
    }

    public function getUserDiscountCount($userId, $type = 0) {
        $o2oDiscountService = new O2ODiscountService();
        return $o2oDiscountService->getUserDiscountCount($userId, $type);
    }


}

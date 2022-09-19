<?php
/**
 * DealListService.php
 * @date 2017-07-19
 * @author yanjun <yanjun5@ucfgroup.com>
 * */
namespace core\service;

use core\service\DealCustomUserService;
use core\service\oto\O2ODiscountService;
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\ncfph\DealService;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\service\DealLoadService;
use core\service\DealTagService;
use core\dao\DealModel;
use core\dao\DealLoanTypeModel;
use core\service\ncfph\DuotouService;

class DealListService extends BaseService {

    //还款方式
    const LOAN_TYPE_MONTH = 5;//按天一次性还款
    const LOAN_TYPE_BY_CROWDFUNDING = 7; // 公益标
    //标的类型
    const DEAL_TYPE_COMPOUND = 1;//通知贷

    /**
     * 排序产品类型
     */
    const ORDER_DEAL_P2P = 'p2p';           // p2p
    const ORDER_DEAL_ZX  = 'zx';            // 专享
    const ORDER_DEAL_DUOTOU = 'duotou';     // 智多新

    /**
     * 获取投资券可用标的列表 包含p2p、专享、智多鑫，排序admin系统配置可配（key: DISCOUNT_DEALLIST_ORDER）
     *
     * @date 2018.11.23
     * @author sunxuefeng@ucfgroup.com
     *
     * @param array $user               用户信息
     * @param int   $bidAmount          投资券起投金额
     * @param int   $bidDayLimit        投资券起投日期
     * @param int   $discountGroupId    券组id
     * @param int   $discountType       投资券类型
     * @param int   $appVersion         app版本
     * @param int   $sourceType         客户端版本
     */
    public function getDealListByDiscount($user, $bidAmount, $bidDayLimit, $discountGroupId, $discountType,
        $appVersion = 100, $sourceType = 1, $pageNum = 1, $pageSize = 20) {

        if ($discountType == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK || $discountType == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES) {
            $dealsList = $this->getDealsListWithoutGold($user, $bidAmount, $bidDayLimit, $appVersion, $sourceType);
        } else if ($discountType == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
            $dealsList = $this->getGoldList($bidAmount, $bidDayLimit);
        }
        $o2oDiscountService = new O2ODiscountService();
        $list = [];
        $buyerFee = 0;
        foreach ($dealsList as $deal) {
            if ($discountType == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
                $consumeType = CouponGroupEnum::CONSUME_TYPE_GOLD;
            } else {
                $consumeType = $deal['consumeType'];
                // 针对投资券的一些特殊过滤逻辑
                if (!$this->canUseDeal($deal, $bidAmount)) {
                    PaymentApi::log("DealListService.canUseDeal 特殊逻辑 dealId:{$deal['id']}, name:{$deal['name']}", Logger::WARN);
                    continue;
                }
            }

            //判断投资券在指定标的是否可用
            if($o2oDiscountService->validateDiscount($discountGroupId, $deal, $consumeType)){
                $list[] = $deal;
                if(isset($deal['buyerFee']) && $deal['buyerFee'] > $buyerFee){    //购买手续费按列表的最大值算
                    $buyerFee = $deal['buyerFee'];
                }
            }
        }
        // 各方不支持，只能自己分页
        // $pageNum 从1开始
        $start = $pageNum > 0 ?($pageNum - 1) * $pageSize : 0;
        return array_slice($list, $start, $pageSize);
    }

    private function getDealsListWithoutGold($user, $bidAmount, $bidDayLimit, $appVersion, $sourceType) {
        // 专享
        $zxDealList = $this->getZxDealsList($user['id'], $bidAmount, $bidDayLimit);
        // p2p
        $p2pDealList = $this->getP2pDealList($user, $sourceType);
        // 智多鑫 475版本以下不显示智多鑫(不包括475)
        $duotouDealList = array();
        if ($appVersion >= 475) {
            $duotouDealList = $this->getDuotouActivityList($bidAmount, $bidDayLimit);
        }
        if ($zxDealList == null) {
            $zxDealList = array();
        }
        if ($duotouDealList == null) {
            $duotouDealList = array();
        }
        if ($p2pDealList == null) {
            $p2pDealList = array();
        }

        // 排序
        $discountDealListConf = app_conf('DISCOUNT_DEALLIST_ORDER');
        $discountDealListConfArr = explode(',', $discountDealListConf);
        // 默认值 p2p > 专享 > 智多新
        // 配置字段 p2p,zx,duotou
        if (empty($discountDealListConf)) {
            $discountDealListConfArr = array(
                self::ORDER_DEAL_P2P,
                self::ORDER_DEAL_ZX,
                self::ORDER_DEAL_DUOTOU,
            );
        }
        $result = array();
        foreach ($discountDealListConfArr as $value) {
            if ($value == self::ORDER_DEAL_P2P) {
                $result = array_merge($result, $p2pDealList);
            }
            if ($value == self::ORDER_DEAL_ZX) {
                $result = array_merge($result, $zxDealList);
            }
            if ($value == self::ORDER_DEAL_DUOTOU) {
                $result = array_merge($result, $duotouDealList);
            }
        }
        return $result;
    }

    //获取投资券信息
    public function getDiscountInfo($discounId) {
        $discount = (new O2OService())->getDiscount($discounId);
        $res = array(
            'discountId' => $discount['id'],
            'discountType' => $discount['type'],
            'discountGroupId' => $discount['discountGroupId'],
            'bidAmount' => $discount['bidAmount'],
            'bidDayLimit' => $discount['bidDayLimit'],
            'goodsPrice' => $discount['goodsPrice'],
            'ownerUserId' => $discount['ownerUserId'],
            'status' => $discount['status'],
            'useStartTime' => $discount['useStartTime'],
            'useEndTime' => $discount['useEndTime'],
        );
        if($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK || $discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES){//获取专享和p2p标的列表
            $res['goodsDesc'] = "金额满".number_format($discount['bidAmount'])."元";
            $res['goodsDesc'] .= $discount['bidDayLimit'] > 0 ? "，期限满{$discount['bidDayLimit']}天可用" : '可用';
            $res['discountTypeName'] = $discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK ? '返现券' : '加息券';

        }elseif($discount['type'] == CouponGroupEnum::DISCOUNT_TYPE_GOLD){//获取优长金标的列表
            $res['goodsDesc'] = "购买满".number_format(floorfix($discount['bidAmount'],3,6),3)."克";
            $res['goodsDesc'] .= $discount['bidDayLimit'] > 0 ? "，期限满{$discount['bidDayLimit']}天可用" : '可用';
            $res['discountTypeName'] = '黄金券';
        }
        return $res;
    }

    //获取专享的标的列表
    private function getZxDealsList($userId, $bidAmount, $bidDayLimit) {

        // 未过滤白名单等相关的标，现在使用DealCustomUserService.getDealCustomUserList方法
        $isRealSite = $this->isRealSite();
        $dealCustomUserService = new DealCustomUserService();
        $deals = $dealCustomUserService->getDealCustomUserList($userId, false, false, 0, array(), false, $isRealSite);

        // 分页 标数目不多
        $dealTagService = new DealTagService();
        foreach ($deals as &$deal) {
            // 为o2o校验组织参数
            $deal['deal_tag_name'] = $deal['deal_tag_name_origin'];
            unset($deal['deal_tag_name_origin']);

            // 月份转天
            $deal['repayTime'] = $deal['loantype'] != self::LOAN_TYPE_MONTH ? $deal['repay_time'] * 30 : $deal['repay_time'];

            $deal['consumeType'] = CouponGroupEnum::CONSUME_TYPE_P2P;
            $deal['category'] = DealLoanTypeModel::instance()->getLoanNameByTypeId($deal['type_id']);
            $deal['tag'] = $dealTagService->getTagByDealId($deal['id'], false);
        }

        return $this->sortForP2pZx($deals);
    }

    private function getP2pDealList($user, $sourceType) {
        $userParam = array(
            'id' => $user['id'],
            'create_time' => $user['create_time'],
        );
        $result = DealService::getDealsListForDiscount($userParam, $sourceType);

        return $result ? $this->sortForP2pZx($result) : array();
    }

    /**
     * DealCustomUserService.getDealCustomUserList 的最后一个参数
     * 按照web/index/index.php中逻辑修改
     *
     * @author sunxuefeng@ucfgroup.com
     * @return bool
     */
    private function isRealSite() {
        if (app_conf('PC_LIST_CACHE_SETNX') && !$this->isMainSite()) {
            return true;
        }
        if ((int)app_conf('SUPERVISION_SWITCH') === 1) {
            return true;
        }
        $isSvOpen = $this->rpc->local('SupervisionBaseService\isSupervisionOpen');
        if ($isSvOpen) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否为主站
     */
    private function isMainSite(){
        $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST'][app_conf('APP_SITE')];
        setLog(array('site_id' => $siteId));
        if(intval($siteId) === 1){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 不能使用投资券的情况过滤
     */
    private function canUseDeal($dealInfo, $discountBidAmount) {

        if (empty($dealInfo)) {
            return false;
        }

        // 网贷理财中通知贷、公益标不能投
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
                PaymentApi::log("Discount advisory blacklist hit, dealId: {$dealInfo['id']}, advisory id: "
                    .$dealInfo['advisory_id'].", blacklist: ".$blackListStr, Logger::WARN);

                return false;
            }
        }

        // 特定标的下（标的带有tag：TZQ_NOCOUPON）不能使用投资券
        $tagNames = $dealInfo['tag'];
        if (is_array($tagNames) && in_array('TZQ_NOCOUPON', $tagNames)) {
            return false;
        }

        // 变现通不能使用投资劵
        if ($dealInfo['tag'] == DealLoanTypeModel::TYPE_BXT) {
            return false;
        }

        // p2p和专享 剩余可投金额要大于等于优惠券起投金额
        if ($dealInfo['consumeType'] == CouponGroupEnum::CONSUME_TYPE_P2P) {
            // 这个是未格式化的浮点数 need_money_detail是带有千分符的版本
            if ($dealInfo['need_money_decimal'] < $discountBidAmount) {
                PaymentApi::log("DealListService.canUseDeal 剩余可投不足 deal:{$dealInfo['need_money_detail']}, discount:{$discountBidAmount}", Logger::WARN);

                return false;
            }
        }

        return true;
    }

    // 获取智多鑫活动的列表
    private function getDuotouActivityList($bidAmount, $bidDayLimit, $pageNum = 1, $pageSize = 100) {
        // 获取智多鑫可用活动列表
        // 1、需要提供handleDeal方法中需要的字段
        // 2、智多鑫排序为 灵活投 > 30天 > 90天 > 180天
        $doutouActivity = DuotouService::getActivityIndexDealsWithUserNum();
        // 排序
        $doutouActivity = $this->sortByLockDay($doutouActivity, $bidAmount, $bidDayLimit);
        $doutouList = [];
        foreach ($doutouActivity as $key => $item) {
            $item['consumeType'] = CouponGroupEnum::CONSUME_TYPE_DUOTOU;
            $doutouList[] = $item;
        }
        return $doutouList;
    }

    // 根据券的期限和起头额 筛选可用的智多鑫活动
    // 按照锁定期排序 从小到大
    private function sortByLockDay($activityList, $bidAmount, $bidDayLimit) {
        $list = array();
        foreach($activityList as $item) {
            $lockDay = $item['lock_day'];
            if ($bidDayLimit > $lockDay) {
                continue;
            }
            $list[$lockDay] = $item;
        }
        ksort($list, SORT_NUMERIC);
        return $list;
    }

    //获取优长金标的列表
    private function getGoldList($bidAmount, $bidDayLimit, $pageNum = 1, $pageSize = 20) {
        $goldService = new GoldService();
        $goldList = $goldService->getGoldList($bidAmount, $bidDayLimit, $pageNum, $pageSize);

            if ($dealResp['data']['loantype'] != self::LOAN_TYPE_MONTH) {
                $bidDayLimit = $bidDayLimit * 30;
            }

        return $goldList['list'];
    }

    /**
     * 按照期限和剩余可投金额排序 主要使用在p2p和专享
     */
    private function sortForP2pZx($deals) {
        $len = count($deals);
        for ($i = 0; $i < $len - 1; $i++) {
            for($j = 0; $j < $len - $i - 1; $j++) {
                if ($deals[$j]['repayTime'] > $deals[$j+1]['repayTime'] ||
                    ($deals[$j]['repayTime'] == $deals[$j+1]['repayTime'] && $deals[$j]['need_money_decimal'] > $deals[$j+1]['need_money_decimal'])) {

                    $tmp = $deals[$j];
                    $deals[$j] = $deals[$j+1];
                    $deals[$j+1] = $tmp;
                }
            }
        }
        return $deals;
    }
}

<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RequestDiscountCount;
use NCFGroup\Protos\Ptp\RequestDiscountMine;
use NCFGroup\Protos\Ptp\RequestDiscountPickList;
use NCFGroup\Protos\Ptp\RequestDiscountExpectedEarningInfo;
use core\service\DiscountService;
use libs\rpc\Rpc;

/**
 * PtpBonusService.
 *
 * @uses ServiceBase
 */
class PtpDiscountService extends ServiceBase
{
    /**
     * 获取可用劵个数.
     *
     * @param RequestDiscountCount $request
     * @access public
     *
     * @return int
     */
    public function count(RequestDiscountCount $request)
    {
        return (new DiscountService())->avaliableCount(
            $request->getUserId(),
            $request->getDealId(),
            $request->getSiteId()
        );
    }

    /**
     * 我的投资劵列表.
     *
     * @param RequestDiscountMine $request
     * @access public
     *
     * @return array
     */
    public function mine(RequestDiscountMine $request)
    {
        $couponList = (new DiscountService())->mine(
            $request->getUserId(),
            $request->getStatus(),
            $request->getPage(),
            $request->getCount(),
            $request->getType(),
            $request->getSiteId()
        );

        //投资券赠送功能
        $rpc = new Rpc();
        $couponInfo = \SiteApp::init()->dataCache->call($rpc, 'local', array('CouponService\getOneUserCoupon', array($request->getUserId())), 10);
        $wxDiscountTemplate = \SiteApp::init()->dataCache->call($rpc, 'local', array('DiscountService\getTemplateInfoBySiteId', array($request->getSiteId())), 10);
        $shareIcon    = urlencode($wxDiscountTemplate['shareIcon']);
        $shareTitle   = $wxDiscountTemplate['shareTitle'];
        $couponList['shareIcon'] = $shareIcon;
        $shareContent = $wxDiscountTemplate['shareContent'];

        $shareHost = app_conf('API_BONUS_SHARE_HOST');
        foreach ($couponList['list'] as &$item) {
            $goodsPrice = $item['goodsPrice'];
            if ($item['type'] == 1 && ceil($item['goodsPrice']) == $item['goodsPrice']) {
                $goodsPrice = intval($goodsPrice);
            }
            $goodsDesc = "金额满".number_format($item['bidAmount'])."元";
            if ($item['bidDayLimit']) {
                $goodsDesc .= "，期限满{$item['bidDayLimit']}天";
            }
            $goodsDesc .= '可用';
            if ($item['type'] == 1) {
                $goodsType = '返现券';
                $goodsPrice = $goodsPrice."元";
            } else {
                $goodsType = '加息券';
                $goodsPrice = $goodsPrice."%";
            }
            $item['shareTitle']   = urlencode(str_replace(array('{COUPON_PRICE}', '{COUPON_TYPE}'), array($goodsPrice, $goodsType), $shareTitle));
            $item['shareUrl']     = urlencode(sprintf('%s/discount/GetDiscount?sn=%s&cn=%s&site_id=%u', $shareHost, (new DiscountService())->generateSN(intval($item['id'])) ,$couponInfo['short_alias'],intval($request->getSiteId())));
            $item['shareContent'] = urlencode(str_replace('{COUPON_DESC}', $goodsDesc, $shareContent));
        }
        return $couponList;
    }

    /**
     * 可用于该项目的投资劵列表.
     *
     * @param RequestDiscountPickList $request
     * @access public
     */
    public function pickList(RequestDiscountPickList $request)
    {
        return (new DiscountService())->pickList(
            $request->getUserId(),
            $request->getDealId(),
            $request->getMoney(),
            $request->getPage(),
            $request->getCount(),
            $request->getType(),
            $request->getSiteId()
        );
    }

    /**
     * 获取劵收益信息
     *
     * @param RequestDiscountExpectedEarningInfo $request
     * @access public
     * @return void
     */
    public function expectedEarningInfo(RequestDiscountExpectedEarningInfo $request)
    {
        return (new DiscountService())->ExpectedEarningInfo(
            $request->getUserId(),
            $request->getDealId(),
            $request->getMoney(),
            $request->getDiscountId(),
            $request->getSiteId()
        );
    }
}

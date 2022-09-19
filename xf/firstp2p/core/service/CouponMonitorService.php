<?php
/**
 * CouponMonitorService.php
 *
 * @date 2016-04-14 10:32
 * @author wangzhen3@ucfgroup.com
 */
namespace core\service;


class CouponMonitorService extends BaseService {

    const TOTAL= 'total';
    const SUCCESS = 'success';
    const FAILED = 'failed';
    const ITEM_PAY = 'pay';
    const ITEM_CONSUME = 'consume';
    const ITEM_REDEEM = 'redeem';

    /**
     * 监控优惠码
     * @param string $item
     * @param string $category
     * @param string $module
     * @param int $dealType
     */
    public static function process($item = self::ITEM_PAY,$category = self::TOTAL,$module = CouponLogservice::MODULE_TYPE_P2P ,$dealType = CouponLogService::DEAL_TYPE_GENERAL){
        switch ($item) {
            case self::ITEM_PAY:
                self::Pay($category, $module, $dealType);
                break;
            case self::ITEM_CONSUME:
                self::Consume($category, $module, $dealType);
                break;
            case self::ITEM_REDEEM:
                self::Redeem($category, $module);
                break;
            default:
                ;
            break;
        }
    }

    /**
     * 通知贷赎回监控
     * @param string $category
     * @param string $module
     */
    public static function Redeem($category = self::TOTAL, $module = CouponLogservice::MODULE_TYPE_P2P){
        switch ($category) {
            case self::TOTAL;
                if($module == CouponLogservice::MODULE_TYPE_P2P){
                    self::RedeemP2P();
                }else{
                    self::RedeemDuotou();
                }
                break;

            case self::SUCCESS;
                if($module == CouponLogservice::MODULE_TYPE_P2P){
                    self::RedeemP2PSuccess();
                }else{
                    self::RedeemDuotouSuccess();
                }
                break;

            case self::FAILED;
                self::RedeemFailed();
                break;

            default:
                ;
            break;
        }
    }

    /**
     * 消费优惠码监控
     * @param string $category
     * @param string $module
     * @param int $dealType
     */
    public static function Consume($category = self::TOTAL, $module = CouponLogservice::MODULE_TYPE_P2P, $dealType = CouponLogService::DEAL_TYPE_GENERAL){
        switch ($category) {
            case self::TOTAL;
                if($module == CouponLogservice::MODULE_TYPE_P2P){
                    self::ConsumeP2P();
                }else{
                    self::ConsumeDuotou();
                }
                break;

            case self::SUCCESS;
                if($module == CouponLogservice::MODULE_TYPE_P2P){
                    if($dealType == CouponLogService::DEAL_TYPE_GENERAL){
                        self::ConsumeNormalSuccess();
                    }elseif($dealType == CouponLogService::DEAL_TYPE_EXCHANGE){
                        self::ConsumeDaJinSuoSuccess();
                    }else{
                        self::ConsumeCompoundSuccess();
                    }
                }else{
                    self::ConsumeDuoTouSuccess();
                }
                break;

            case self::FAILED;
                self::ConsumeFailed();
            break;

            default:
                ;
                break;
        }
    }

    public static function Pay($category = self::TOTAL, $module = CouponLogservice::MODULE_TYPE_P2P, $dealType = CouponLogService::DEAL_TYPE_GENERAL){
        switch ($category) {
            case self::TOTAL;
                if($module == CouponLogservice::MODULE_TYPE_P2P){
                    if (in_array($dealType, CouponLogService::$deal_type_group1)) {
                        self::PayNormal();
                    }else{
                        self::PayCompound();
                    }
                }else{
                    self::PayDuoTou();
                }
                break;

            case self::SUCCESS;
                if($module == CouponLogservice::MODULE_TYPE_P2P){
                    if (in_array($dealType, CouponLogService::$deal_type_group1)) {
                        self::PayNormalSuccess();
                    }else{
                        self::PayCompoundSuccess();
                    }
                }else{
                    self::PayDuoTouSuccess();
                }
                break;

            case self::FAILED;
                if($dealType ==  CouponLogService::DEAL_TYPE_EXCHANGE){
                    self::PayDaJinSuoFailed();
                }else{
                    self::PayFailed();
                }

                break;

            default:
                ;
                break;
        }
    }

    public static function PayNormal(){
        self::Monitor('COUPON_PAY_NORMAL');
    }

    /**
     * 大金所 总数统计
     */
    public static function PayDaJinSuo(){
        self::Monitor('COUPON_PAY_DAJINSUO');
    }

    public static function PayCompound(){
        self::Monitor('COUPON_PAY_COMPOUND');
    }

    public static function PayDuoTou(){
        self::Monitor('COUPON_PAY_DUOTOU');
    }

    public static function PayNormalSuccess(){
        self::Monitor('COUPON_PAY_NORMAL_SUCCESS');
    }

    /**
     * 大金所结算成功
     */
    public static function PayDaJinSuoSuccess(){
        self::Monitor('COUPON_PAY_DAJINSUO_SUCCESS');
    }
    public static function PayCompoundSuccess(){
        self::Monitor('COUPON_PAY_COMPOUND_SUCCESS');
    }

    public static function PayDuoTouSuccess(){
        self::Monitor('COUPON_PAY_DUOTOU_SUCCESS');
    }

    public static function PayFailed(){
        self::Monitor('COUPON_PAY_FAILED');
    }
    /**
     * 大金所返利结算失败
     */
    public static function PayDaJinSuoFailed(){
        self::Monitor('COUPON_PAY_DAJINSUO_FAILED');
    }
    public static function ConsumeP2P(){
        self::Monitor('COUPON_CONSUME_P2P');
    }

    public static function ConsumeDuotou(){
        self::Monitor('COUPON_CONSUME_DUOTOU');
    }

    public static function ConsumeNormalSuccess(){
        self::Monitor('COUPON_CONSUME_NORMAL_SUCCESS');
    }

    /**
     * 大金所优惠码消费成功
     */
    public static function ConsumeDaJinSuoSuccess(){
        self::Monitor('COUPON_CONSUME_DAJINSUO_SUCCESS');
    }
    public static function ConsumeCompoundSuccess(){
        self::Monitor('COUPON_CONSUME_COMPOUND_SUCCESS');
    }

    public static function ConsumeDuoTouSuccess(){
        self::Monitor('COUPON_CONSUME_DUOTOU_SUCCESS');
    }

    public static function ConsumeFailed(){
        self::Monitor('COUPON_CONSUME_FAILED');
    }

    public static function RedeemP2P(){
        self::Monitor('COUPON_REDEEM_P2P');
    }

    public static function RedeemDuotou(){
        self::Monitor('COUPON_REDEEM_DUOTOU');
    }

    public static function RedeemP2PSuccess(){
        self::Monitor('COUPON_REDEEM_P2P_SUCCESS');
    }

    public static function RedeemDuotouSuccess(){
        self::Monitor('COUPON_REDEEM_DUOTOU_SUCCESS');
    }

    public static function RedeemFailed(){
        self::Monitor('COUPON_REDEEM_FAILED');
    }

    private static function Monitor($key,$count = 1){
        \libs\utils\Monitor::add($key,$count);
    }

}

<?php
namespace task\controllers\dealbidsucess;

use task\controllers\BaseAction;
use libs\utils\Logger;
use core\service\o2o\DiscountService;
use core\enum\CouponEnum;

/**
 * 投资完成后使用券
 * Class Create
 * @package task\controllers\dealbidsucess
 */
class ChangeDiscount extends BaseAction {
    /**
     * @todo 建议这里用ncfwx工程提供的消费接口
     */
    public function invoke() {
        // msgbus传递的msg信息本身也需要json_decode
        $param = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ ."," .__LINE__ . ", Task receive params ".json_encode($param));
            $rpcRes = true;
            if (!empty($param['discount_id'])) {
                $rpcRes = DiscountService::o2oExchangeDiscount(
                    $param['user_id'],
                    $param['discount_id'],
                    $param['load_id'],
                    $param['deal_name'],
                    $param['coupon_id'],
                    0,
                    0,
                    $param['consumeType'],
                    $param['annualizedAmount']
                );
            }

            if(!$rpcRes){
                Logger::error(__CLASS__ ."," .__LINE__ . ",  false");
                throw new \Exception('消费优惠券失败');
            }
        } catch (\Exception $ex) {
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}

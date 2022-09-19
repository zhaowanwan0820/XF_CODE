<?php
namespace task\controllers\dealbidsucess;

use task\controllers\BaseAction;
use libs\utils\Logger;
use core\service\coupon\CouponService;
use core\enum\CouponEnum;

/**
 * 投资完成后增加优惠码记录
 * Class Create
 * @package task\controllers\dealbidsucess
 */
class CouponConsume extends BaseAction {

    public function invoke() {
        $param = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ ."," .__LINE__ . ", Task receive params ".json_encode($param));
            $rpcRes = true;
            if ($param ['isDt'] == false) {
                $rpcRes = CouponService::consume(CouponEnum::TYPE, $param['coupon_id'], $param['money'], $param['user_id'], $param['deal_id'], $param['load_id'], 0, $param['site_id'], 0, 0);
            }
            if(!$rpcRes){
                Logger::error(__CLASS__ ."," .__LINE__ . ",  false");
                throw new \Exception('优惠码写入失败');
            }
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
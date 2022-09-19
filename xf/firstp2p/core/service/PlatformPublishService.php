<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 2017/2/8
 * Time: 下午1:39
 */

namespace core\service;

use core\dao\PaymentNoticeModel;
use core\dao\UserModel;
use core\dao\UserCarryModel;
use core\dao\DealModel;
use libs\utils\Logger;

/**
 * Class PlatformPublishService
 * @package core\service
 */
class PlatformPublishService extends BaseService {
    /**
     * 生成平台披露数据
     * @return array
     */
    public function create($dealType = '0,1',$time = 0) {
        $rs = array();

        //累计充值
        $rs['payment'] = PaymentNoticeModel::instance()->getPlatformPayment($time);

        //累计提现
        $rs['carry'] = UserCarryModel::instance()->getPlatformCarry($time);

        //累计投资
        $rs['buy'] = DealModel::instance()->getPublishBuyCountTotal($dealType,$time);

        //累计注册
        $rs['register'] = UserModel::instance()->count("id > 0");

        //累计收益
        $rs['interest'] = DealModel::instance()->getPublishInterestTotal($dealType,$time);

        //累计放款
        $rs['loans'] = DealModel::instance()->getPublishBorrowAmountTotal($dealType,$time);

        $redis = \SiteApp::init()->dataCache->getRedisInstance();

        if(count($rs)>0){
            $redis->del('platform_publish_data');
            $redis->setex('platform_publish_data',7200 , json_encode($rs));
            return $rs;
        }else{
            throw new \Exception('获取数据失败!');
        }
    }
}

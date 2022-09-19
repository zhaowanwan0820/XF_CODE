<?php
/**
 * Created by PhpStorm.
 * User: lvbaosong@ucfgroup.com
 * Date: 2016/6/20
 * Time: 14:12
 */
namespace core\service\risk;

use libs\utils\Risk;
use core\enum\DeviceEnum;

class RiskChargeService extends RiskAbstractService{

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        parent::__construct($bizCode,$platform,$device);
    }
    protected function checkDataCollector($data,$extraData){
        $params = array();
        $params['frms_recharge_time'] = floor(microtime(true) * 1000);
        $params['frms_user_id'] = isset($data['id'])?$data['id']:'';
        $params['frms_recharge_acc'] = isset($data['user_name'])?$data['user_name']:'';
        $params['frms_recharge_ip'] = get_real_ip();
        $params['frms_recharge_fp'] = Risk::getFinger();
        $params['frms_recharge_amount']= isset($data['money'])?$data['money']:'';
        return $params;
    }
}
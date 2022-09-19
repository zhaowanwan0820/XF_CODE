<?php
/**
 * Created by PhpStorm.
 * User: lvbaosong@ucfgroup.com
 * Date: 2016/6/20
 * Time: 14:12
 */

namespace core\service\risk;



use libs\utils\Risk;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
class RiskBidService extends RiskAbstractService{

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        parent::__construct($bizCode,$platform,$device);
    }
    protected function checkDataCollector($data,$extraData){
        $params = array();
        $params['frms_bid_time'] = floor(microtime(true) * 1000);
        $params['frms_bid_ip'] = get_real_ip();
        $params['frms_bid_dev_fp'] = Risk::getFinger();
        $params['frms_bid_no'] = isset($extraData['id'])?$extraData['id']:'';
        $params['frms_bid_type'] = isset($extraData['deal_type'] )?$extraData['deal_type']:'';//标类型
        $params['frms_bid_name'] = isset($extraData['name'])?$extraData['name']:'';
        $params['frms_bid_amount']= isset($data['money'])?$data['money']:'';
        $params['frms_user_id'] = isset($data['id'])?$data['id']:'';
        $params['frms_bid_acc'] = isset($data['user_name'])?$data['user_name']:'';
        $params['frms_bid_phone'] = isset($data['mobile'])?$data['mobile']:'';
        $params['frms_bid_remain']='';
        if($this->platform!=Risk::PF_OPEN_API){
            $params['frms_bid_remain'] = $extraData['start_time'] + $extraData['enddate'] * 24 * 3600 - get_gmtime();
        }
        return $params;
    }


}
<?php
namespace core\service\risk;

use libs\utils\Risk;
use core\enum\DeviceEnum;

class RiskBindService extends RiskAbstractService{

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        parent::__construct($bizCode,$platform,$device);
    }
    protected function checkDataCollector($data,$extraData){
        $params = array();
        $params['frms_card_no']= isset($data['card'])?$data['card']:'';
        $params['frms_user_id'] = isset($data['id'])?$data['id']:'';
        $params['frms_finger_print'] =  Risk::getFinger();
        return $params;
    }
}
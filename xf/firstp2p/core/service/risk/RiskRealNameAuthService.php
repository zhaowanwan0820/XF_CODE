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
class RiskRealNameAuthService extends RiskAbstractService{

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        parent::__construct($bizCode,$platform,$device);
    }
    protected function checkDataCollector($data,$extraData){

        $params = array();
        if (empty($extraData['cardNo']) && !empty($data['idno'])){
            $extraData['cardNo'] = $data['idno'];
        }
        $params['frms_ip'] = get_real_ip();
        $params['frms_create_time'] = floor(microtime(true) * 1000);
        $params['frms_phone_no'] = isset($data['mobile'])?$data['mobile']:'';
        $params['frms_user_name'] = isset($extraData['realName'])?$extraData['realName']:'';
        $params['frms_id_type'] = 'idno';
        $params['frms_finger_print'] = Risk::getFinger();
        $params['frms_id_no'] = isset($extraData['cardNo'])?$extraData['cardNo']:'';

        if(isset($extraData['type'])){
            $params['frms_user_name'] = isset($extraData['name'])?$extraData['name']:'';
            $params['frms_id_type'] ='passport';
            $params['frms_id_no'] = $extraData['idno'] . '(' .$extraData['idno_suffix']. ')';
        }
        $params['frms_user_id'] = isset($data['id'])?$data['id']:'';
        return $params;
    }


}
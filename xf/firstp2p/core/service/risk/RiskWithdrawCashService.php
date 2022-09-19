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

class RiskWithdrawCashService extends RiskAbstractService{

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        parent::__construct($bizCode,$platform,$device);
    }
    protected function checkDataCollector($data,$extraData){
        $params = array();
        if($this->platform==Risk::PF_OPEN_API){
            $params['frms_user_id'] = $data->userId;
            $params['frms_cash_acc'] = $data->userName;
        }else{
            $params['frms_user_id'] = isset($data['id'])?$data['id']:'';
            $params['frms_cash_acc'] = isset($data['user_name'])?$data['user_name']:'';
        }
        $params['frms_cash_amount'] = isset($extraData['money'])?$extraData['money']:0;
        $params['frms_cash_time'] = floor(microtime(true) * 1000);
        $params['frms_cash_ip'] = get_real_ip();
        $params['frms_cash_fp'] =Risk::getFinger();
        return $params;
    }

    protected function strategy($params)
    {
        $result = preg_match('/RISK_001/', json_encode($params));
        return $result == 1 ? true : false;
    }

    public function analysis($result)
    {
        if ($result == false) {
            return '';
        }
        return $result;
    }

}

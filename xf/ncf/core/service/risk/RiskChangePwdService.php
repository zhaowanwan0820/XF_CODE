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

class RiskChangePwdService extends RiskAbstractService{

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        parent::__construct($bizCode,$platform,$device);
    }
    protected function checkDataCollector($data,$extraData){

        $params = array();
        $params['frms_cpwd_ip'] = get_real_ip();
        $params['frms_cpwd_dev'] = Risk::getFinger();
        $params['frms_cpwd_time'] = floor(microtime(true) * 1000);
        if(!($data instanceof \NCFGroup\Common\Extensions\Base\ProtoBufferBase)){
            $params['frms_user_id'] = isset($data['id'])?$data['id']:'';
            $params['frms_cpwd_acc'] = isset($data['user_name'])?$data['user_name']:'';
            $params['frms_cpwd_phone'] =  isset($extraData['phone'])?$extraData['phone']:'';
            $params['frms_cpwd_npwd'] ='';
            $params['frms_cpwd_verify']='idno';
            if(isset($extraData['captcha'])){
                $params['frms_cpwd_verify']='captcha';
            }else if(isset($extraData['old_password'])){
                $params['frms_cpwd_verify']='oldpwd';
            }else if(isset($extraData['phone'])){
                $params['frms_cpwd_verify']='phone';
            }else if(isset($extraData['mobile'])){
                $params['frms_cpwd_verify']='phone';
                $params['frms_cpwd_phone'] =  $extraData['mobile'];
            }
        }else{
            $params['frms_user_id'] = $data->userId;
            $params['frms_cpwd_acc'] = $data->userName;
            $params['frms_cpwd_phone'] =  isset($extraData['mobile'])?$extraData['mobile']:'';
            $params['frms_cpwd_npwd'] ='';
            $params['frms_cpwd_verify']='phone';
        }
        return $params;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: lvbaosong@ucfgroup.com
 * Date: 2016/6/20
 * Time: 14:12
 */

namespace core\service\risk;



use libs\utils\Risk;
use libs\utils\Monitor;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
class RiskRegisterService extends RiskAbstractService{

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        parent::__construct($bizCode,$platform,$device);
    }
    protected function checkDataCollector($data,$extraData){

        $params = array();
        $params['frms_ip'] = get_real_ip();
        $params['frms_create_time'] = floor(microtime(true) * 1000);
        $params['frms_reg_time'] = floor(microtime(true) * 1000);
        $params['frms_invitation_code'] = isset($data['invite'])?$data['invite']:(isset($data['cn'])?$data['cn']:'');
        $params['frms_phone_no'] = isset($data['mobile'])?$data['mobile']:'';
        $params['frms_finger_print'] =  Risk::getFinger();
        $params['frms_user_type'] =  0;//用户类型(0:普通用户1:企业用户)

        $params['frms_password'] =  isset($data['password'])?md5($data['password']):'';
        if($this->platform==Risk::PF_WEB&&$this->device==DeviceEnum::DEVICE_WAP){
            $params['frms_invitation_code'] = isset($data['cn'])?$data['cn']:'';
        }
        if($this->platform==Risk::PF_API){
            $params['frms_phone_no'] = isset($data['phone'])?$data['phone']:'';
        }
        return $params;
    }

    /**
     * 应用风控策略，,具体实现类覆盖此方法
     * @param $riskData 策略判断所依据的数据
     */
    protected  function strategy($riskData){
        try{
            //当前操作设备命中设备黑名单库
            if(!empty($riskData)&&isset($riskData['DEVICE_BLACK'])){
                Monitor::add('RISK_REGISTER_DEVICE_BLACK');
                return false;
            }

        }catch (\Exception $e){
            Logger::error($e->getTraceAsString());
        }
        return true;
    }
    /**
     * 分析风控返回的数据结果,具体实现类覆盖此方法
     * @param $riskData 风控返回的数据结果
     * @return array 分析后的数据结果
     */
    protected  function analysis($riskData){
        $returnValue = array();
        try{
            $jsonRet = json_decode($riskData,true);
            if(!empty($jsonRet) && isset($jsonRet[0]['risks'])){
                foreach($jsonRet[0]['risks'] as $risk){
                    if(isset($risk['verifyPolicy']['code'])){
                        $returnValue[$risk['verifyPolicy']['code']]=$risk['verifyPolicy']['code'];
                    }
                }
            }
        }catch (\Exception $e){
            Logger::error($e->getTraceAsString());
        }
        return $returnValue;
    }

    /**
     * 重写基类check方法，按风控要求记录日志
     * @param array $data 业务数据
     * @param string $type 请求类型:同步或异步 Risk::SYNC|Risk::ASYNC
     * @param array $extraData 业务数据
     */
    public function check($data,$type=Risk::ASYNC,$extraData=array()) {
        //打印日志
        $this->log($data);
        return parent::check($data, $type, $extraData);
    }

    /**
     * 重写基类notify方法，按风控要求记录日志
     * @param array $data
     * @param array $extraData
     */
    public function notify($data = array(), $extraData = array())
    {
        $this->log(array_merge($data, $extraData));
        return parent::notify($data);
    }

    /**
     * 主站注册接口日志支持安全需求 jira:5566
     * @param $data
     */
    private function log($data) {
        $log['invite'] = isset($data['invite']) ? $data['invite'] : (isset($data['cn']) ? $data['cn'] : '');
        if($this->platform==Risk::PF_WEB && $this->device==DeviceEnum::DEVICE_WAP){
            $log['invite'] = isset($data['cn']) ? $data['cn'] : '';
        }

        $log['euid'] = isset($data['euid']) ? $data['euid'] : '';
        $log['userid'] = isset($data['userId']) ? $data['userId'] : '';
        $log['ip'] = get_real_ip();
        $log['headers'] = getAllHeaders();
        $log['mobile'] = isset($data['mobile']) ? $data['mobile'] : '';
        if($this->platform==Risk::PF_API){
            $log['mobile'] = isset($data['phone']) ? $data['phone'] : '';
        }
        $log['finger'] = Risk::getFinger();
        $log['time'] = time();

        Logger::debug("RiskRegisterLog:" . json_encode($log, JSON_UNESCAPED_UNICODE));
    }
}
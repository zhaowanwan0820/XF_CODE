<?php
/**
 * Created by PhpStorm.
 * User: lvbaosong@ucfgroup.com
 * Date: 2016/6/20
 * Time: 14:12
 */

namespace core\service\risk;



use libs\utils\Logger;
use libs\utils\Risk;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use libs\utils\Monitor;
class RiskLoginService extends RiskAbstractService{

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        parent::__construct($bizCode,$platform,$device);
    }
    protected function checkDataCollector($data,$extraData){

        $params = array();
        $params['frms_login_time'] = floor(microtime(true) * 1000);
        $params['frms_login_acc'] = isset($data['username'])?$data['username']:'';
        $params['frms_login_phone'] = isset($data['username'])?$data['username']:'';
        $params['frms_login_ip'] = get_real_ip();
        $params['frms_login_dev'] = Risk::getFinger();
        if($this->platform==Risk::PF_OPEN_API||$this->platform==Risk::PF_API){
            $params['frms_login_acc'] = isset($data['account'])?$data['account']:'';
            $params['frms_login_phone'] = isset($data['account'])?$data['account']:'';
        }
        return $params;
    }
    /**
     * 应用风控策略，,具体实现类覆盖此方法
     * @param $riskData 策略判断所依据的数据
     */
    protected  function strategy($riskData)
    {
        try{
            //当前操作设备命中设备黑名单库
            if (!empty($riskData)&&isset($riskData['DEVICE_BLACK'])) {
                Monitor::add('RISK_LOGIN_DEVICE_BLACK');
                return false;
            }
            //弱密码校验
            if (!empty($riskData)&&isset($riskData['WEAKPWD'])) {
                if ($this->platform==Risk::PF_WEB) {
                    $_SESSION['risk_weak_pwd'] = 1;
                } else {
                    $userName = $this->postParams['frms_login_acc'];
                    \SiteApp::init()->dataCache->getRedisInstance()->setex("risk_rmm_{$userName}", 20, 1);
                }
            }
            //某时段内操作频率过多
            if (!empty($riskData)&&isset($riskData['LOGIN_FREQUENT'])) {
                if ($this->platform==Risk::PF_WEB || $this->platform==Risk::PF_OPEN_API) {
                    $_SESSION['risk_login_frequent'] = 1;
                } else {
                    $userName = $this->postParams['frms_login_acc'];
                    \SiteApp::init()->dataCache->getRedisInstance()->setex("risk_rlf_{$userName}", 20, 1);
                }
            }
            //当前操作设备指纹非法
            if (!empty($riskData)&&isset($riskData['LOGIN_ILLEGAL'])) {
                if ($this->platform==Risk::PF_WEB || $this->platform==Risk::PF_OPEN_API) {
                    $_SESSION['risk_login_illegal'] = 1;
                } else {
                    $userName = $this->postParams['frms_login_acc'];
                    \SiteApp::init()->dataCache->getRedisInstance()->setex("risk_rli_{$userName}", 20, 1);
                }
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
}

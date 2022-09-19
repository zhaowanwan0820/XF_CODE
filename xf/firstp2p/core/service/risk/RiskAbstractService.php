<?php
/**
 * Created by PhpStorm.
 * User: lvbaosong@ucfgroup.com
 * Date: 2016/6/23
 * Time: 14:12
 */
namespace core\service\risk;

use core\event\RiskEvent;
use libs\utils\Logger;
use libs\utils\Risk;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use NCFGroup\Task\Services\TaskService;
use libs\utils\Site;
use libs\utils\Monitor;

abstract class RiskAbstractService{
    /**
     * 接口业务号
     */
    protected $bizCode;
    /**
     * 请求流水号
     */
    protected  $sn;
    /**
     * 调用的平台
     */
    protected $platform;
    /**
     * 调用的端
     */
    protected $device;

    protected $postParams;

    public function __construct($bizCode,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){
        $this->bizCode = $bizCode;
        $this->platform = $platform;
        $this->device = $device;
        $this->sn=Risk::genSn();
    }

    /**
     * 风控检测
     * @param array $data 业务数据
     * @param string $type 请求类型:同步或异步 Risk::SYNC|Risk::ASYNC
     * @param array $extraData 业务数据
     */
    public function check($data,$type=Risk::ASYNC,$extraData=array()){

        try{
            $riskSwitch = app_conf('RISK_SWITCHS');
            if($riskSwitch==0){// 0 关闭检测  1 打开检测
                return true;
            }

            if($riskSwitch==2){// 后台配置统一异步.适用情况手动强制降级,接口调用设置的type参数失效
                $this->asyncExecute($data,Risk::ASYNC,$extraData);
                return true;
            }

            if(Risk::ASYNC==$type){//异步调用
                $this->asyncExecute($data,Risk::ASYNC,$extraData);
                return true;
            }

            if(Risk::isForceSyncToAsync()) {//降级检查,自动同步转异步
                $this->asyncExecute($data, Risk::SYNC_TO_ASYNC, $extraData);
                return true;
            }
            return $this->strategy($this->analysis( $this->syncExecute($data,$extraData)));//同步

        }catch (\Exception $e){
            Logger::error('Risk:'.$e->getTraceAsString());
            return true;
        }

    }

    public function checkSync($data, $extraData)
    {
        $riskSwitch = app_conf('RISK_SWITCHS');
        //降级检查
        if(Risk::isForceSyncToAsync() || $riskSwitch == 0) {
            return false;
        }
        return $this->strategy($this->analysis($this->syncExecute($data, $extraData)));
    }

    /**
     * 异步通知业务结果
     */
    public function notify($data=array(), $extraData=array()){
        try{
            if(app_conf('RISK_SWITCHS')==0){
                return;
            }
            $params = array();
            $params['oid']=$this->sn;
            $params['frms_audit_type'] = Risk::AT_AFTER;
            $params['status']="1";
            $params['log_id'] = Logger::getLogId();//debug日志
            $params['platform'] = $this->platform;//debug日志
            $collectorData = $this->notifyDataCollector($data);
            $event = new RiskEvent(array_merge($params,$collectorData));
            $taskService = new TaskService();
            $res = $taskService->doBackground($event, 1);
            if(!$res){
                Logger::error('Risk notify taskService->doBackground fail'.json_encode($params));
            }
        }catch (\Exception $e){
            Logger::error('Risk:'.$e->getTraceAsString());
        }
    }

    /**
     * 风控检测是否需要人脸识别
     * @param $data
     * @param array $extraData
     * @return bool
     */
    public function checkFace($data, $extraData=array()) {
        try{
            //每个业务单独开关
            $riskSwitch = app_conf('RISK_FACE_SWITCHS_'. substr($this->bizCode, 4));
            if($riskSwitch==0){// 0 关闭人脸检测  1 打开检测
                return true;
            }

            return $this->facial($this->syncExecute($data,$extraData));

        }catch (\Exception $e){
            Logger::error('Risk Check Face:'.$e->getTraceAsString());
            return true;
        }
    }

    /**
     * 子类可根据具体需求覆盖此方法
     * 作用:收集业务参数并封装成风控所需要的数据
     */
    protected function  notifyDataCollector($data=array()){
        return $data;
    }


    /**
     * 同步执行第三方接口调用
     * @param array $data 业务数据
     * @param array $extraData 业务数据
     * @return string 返回结果
     */
    protected  function syncExecute($data,$extraData){
        $params = array_merge($this->getBaseParams(),$this->checkDataCollector($data,$extraData));
        $params['frms_audit_type'] = Risk::AT_MID;
        $this->postParams = $params;
        return Risk::request(Risk::getRequestUrl(),$params);
    }

    /**
     * 异步执行第三方接口调用
     * @param array $data 业务参数
     * @param array $extraData 业务数据
     * @param $type string 异步或同步转异步 Risk::ASYNC | Risk::SYNC_TO_ASYNC
     */
    protected  function asyncExecute($data,$type=Risk::ASYNC,$extraData){
        $params = array_merge($this->getBaseParams(),$this->checkDataCollector($data,$extraData));
        $params['frms_audit_type'] = Risk::AT_MID;
        $this->postParams = $params;
        $event = new RiskEvent($params,$type);
        $taskService = new TaskService();

        $res = $taskService->doBackground($event, 1);
        if($res){
            return true;
        }
        return false;
    }

    /**
     * 应用风控人脸识别判断
     * @param $riskData 策略判断所依据的数据
     */
    protected  function facial($riskData){
        $policy = array();
        try{
            $jsonRet = json_decode($riskData,true);
            if(!empty($jsonRet) && isset($jsonRet[0]['risks'])){
                foreach($jsonRet[0]['risks'] as $risk){
                    if(isset($risk['verifyPolicy']['code'])){
                        $policy[$risk['verifyPolicy']['code']]=$risk['verifyPolicy']['code'];
                    }
                }
            }
            //当前操作命中人脸识别
            if(!empty($policy)&&isset($policy['FACIAL_RECOGNITION'])){
                Monitor::add('RISK_FACIAL_RECOGNITION');
                return false;
            }

        }catch (\Exception $e){
            Logger::error($e->getTraceAsString());
        }
        return true;
    }
    
    /**
     * 应用风控策略，,具体实现类覆盖此方法
     * @param $riskData 策略判断所依据的数据
     */
    protected  function strategy($riskData){
        return true;
    }
    /**
     * 分析风控返回的数据结果,具体实现类覆盖此方法
     * @param $riskData 风控返回的数据结果
     * @return array 分析后的数据结果
     */
    protected  function analysis($riskData){
        return true;
    }

    /**
     * 收集风控接口所需业务数据.具体类实现逻辑
     * @param array $data
     * @return array
     */
    protected abstract function checkDataCollector($data,$extraData);

    /**
     *获取基本请求参数
     */
    protected function getBaseParams(){
        $baseParams = array();
        $baseParams['@type'] = 'cn.com.bsfit.frms.obj.AuditObject';
        $baseParams['frms_order_id']=$this->sn;
        $baseParams['frms_biz_code']     = $this->bizCode;
        $baseParams['frms_branch_site'] = Site::getId();
        $baseParams['frms_from'] = $this->device;//Risk::getDevice($this->platform,$this->device);
        $baseParams['log_id'] = Logger::getLogId();//debug日志
        $baseParams['platform'] = $this->platform;//debug日志
        $baseParams['BSFIT_EXPIRATION'] = Risk::getFingerExpiration();
        $baseParams['BSFIT_OkLJUJ'] = Risk::getFingerCookieName();
        return $baseParams;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: lvbaosong
 * Date: 2016/12/9
 * Time: 14:29
 * 调用风控的滑块验证码服务，风控调用阿里云的服务.
 */

namespace core\service;

use Assert\Assertion;
use libs\utils\Logger;
use libs\utils\Alarm;
class AliyuanService extends BaseService
{
    /**
     * status:0 验证通过 1 验证失败 3服务异常[参照风控给的返回值]
     */
    public   function verify($data){
        try{
            Logger::debug('risk_yz|'.json_encode($data));
            $url = $GLOBALS['sys_config']['HKYZM_URL'];//滑块验证码url
            if(!isset($url)){
                throw new \Exception('HKYZM_URL 不存在!');
            }
            if(!isset($data['csessionid'])){
                $ret= array('status'=>1,'msg'=>'csessionid 不存在');
                return $ret;
            }
            if(!isset($data['sig'])){
                $ret= array('status'=>1,'msg'=>'sig 不存在');
                return $ret;
            }
            if(!isset($data['token'])){
                $ret= array('status'=>1,'msg'=>'token 不存在');
                return $ret;
            }
            if(!isset($data['scene'])){
                $ret= array('status'=>1,'msg'=>'scene 不存在');
                return $ret;
            }
            if(!isset($data['from'])){
                $ret= array('from'=>1,'msg'=>'from 不存在');
                return $ret;
            }
            if(!isset($data['username'])){
                $ret= array('status'=>1,'msg'=>'username 不存在');
                return $ret;
            }
            $ret =  $this->post($url,array_merge($data,array('ip'=>get_real_ip(),'ncf_key'=>'firstp2p')));
            return $ret;
        }catch (\Exception $e){

            Alarm::push('RISK_YZ_ERROR','HTTP请求异常',$e->getTraceAsString());
            $ret= array('status'=>3,'msg'=>'服务异常');
            return $ret;
        }

    }

    /**
     * 设置了请求超时200ms
     */
    private function post($url,$data){
        $startTime = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $errorMsg = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $cost = round(microtime(true)-$startTime,4)*1000;

        $logData = array('errorNo'=>$errorNo,'errorMsg'=>$errorMsg,'httpCode'=>$httpCode,'cost'=>$cost,'data'=>json_encode($result));
        Logger::debug('risk_yz|'.json_encode($logData));
        if($httpCode!=200||$errorNo!=0){
            Alarm::push('RISK_YZ_ERROR','HTTP请求异常');
            $ret= array('status'=>3,'msg'=>'验证码服务异常');
            return $ret;
        }

        if($result==0){
            $ret= array('status'=>0,'msg'=>'');
        }else if($result==1){
            $ret= array('status'=>1,'msg'=>'验证失败');
        }else if($result==3){
            $ret= array('status'=>3,'msg'=>'验证码服务异常');
            Alarm::push('RISK_YZ_ERROR','请求返回结果异常:结果3');
        }else{
            $ret= array('status'=>3,'msg'=>'验证码服务异常');
            Alarm::push('RISK_YZ_ERROR','请求返回结果异常');
        }

        return $ret;
    }

}
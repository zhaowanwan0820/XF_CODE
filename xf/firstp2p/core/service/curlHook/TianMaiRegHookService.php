<?php
/**
 *
 * @author wangfei5<wangfei5@ucfgroup.com>
 */
namespace core\service\curlHook;
use core\service\curlHook\ThirdPartyHookService;
use libs\utils\Logger;

class TianMaiRegHookService extends ThirdPartyHookService {


    //天脉准入
    protected function canRun(&$input){
        // 运营给我的邀请码。嗯。写死。不能给配置的机会
        if($input['inviteCode'] == $GLOBALS['sys_config']['CURL_HOOK_CONF']['TianMaiCoupon']){
            return true;
        }else{
            return false;
        }
    }

    // 默认不检查返回值
    protected function checkRet($ret,$params){
        $ret = json_decode($ret,true);
        if( isset($ret['code']) && ($ret['code'] == 200 || $ret['code'] == 1002) ){
            return true;
        }else{
            return false;
        }
    }

    // 天脉签名
    protected function signParams($input){
        $key = 'tVmInInG&fIrStP2P';
        $signStr = sprintf("%s:%s:%s:%s:%s",$input['orderId'],$input['timeStamp'],$input['userId'],$input['money'],$key);
        $sign = md5(md5($signStr));
        $input['sign'] = $sign;
        return $input;
    }

    public function setCurlHeader($ch){
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array ('application/json;charset=utf8'));
        return true;
    }
}

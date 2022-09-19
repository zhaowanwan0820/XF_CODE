<?php
/**
 *
 * @author wangfei5<wangfei5@ucfgroup.com>
 */
namespace core\service\curlHook;
use core\service\curlHook\ThirdPartyHookService;
use libs\utils\Logger;

class HaHaHookService extends ThirdPartyHookService {

    // 检查准入的条件
    protected function canRun($userInfo){
        $hahaGroupId = $GLOBALS['sys_config']['SITE_USER_GROUP']['caiyitong'];
        if($userInfo['group_id'] == $hahaGroupId){
            return true;
        }else{
            return false;
        }
    }

    // 默认不检查返回值
    protected function checkRet($ret){
        $ret = json_decode($ret,true);
        if( isset($ret['code']) && $ret['code'] == 0 ){
            return true;
        }else{
            return false;
        }
    }

    // 哈哈农庄签名需要userInfo
    protected function signParams($userInfo){
        $flag = md5($userInfo['id'].$userInfo['real_name'].$userInfo['newMobile'].time());
        $req = array(
                'client_id'=>10001,
                'flag' => $flag,
                'uid' =>  $userInfo['id'],
                'oldMobile' => $userInfo['oldMobile'],
                'newMobile' => $userInfo['newMobile'],
                'realName' => $userInfo['real_name'],
                );

        ksort($req);
        reset($req);
        $sortedReq = '';
        while (list ($key, $val) = each($req)) {
            if (!is_null($val)) {
                $sortedReq .= $key . $val;
            }
        }

        $client_secret = '060d79fed6dffbb848ac13f65c4d135c';
        $sign_md5 = md5($client_secret . $sortedReq . $client_secret);
        $req['sign'] = $sign_md5;
        return $req;
    }

}

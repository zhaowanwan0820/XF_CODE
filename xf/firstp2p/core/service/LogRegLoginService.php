<?php

/**
 * LogRegLoginService class file.
 *
 * @author yutao@ucfgroup.com
 *
 * */

namespace core\service;

use core\dao\LogRegLoginModel;

/**
 * LogRegLoginService
 *
 * @packaged default
 * */
class LogRegLoginService extends BaseService {

    /**
     * asciiAccount 返回账号的ascii值
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-10-25
     * @param mixed $string
     * @access private
     * @return void
     */
    private function asciiAccount($string) {
        $result = "";
        for($i=0;$i<strlen($string);$i++){
            $result  .= ord($string[$i])." ";
        }
        return $result;
    }

    /**
     * 插入用户登录注册日志
     * @param  $user_name 用户名
     * @param  $user_id 用户ID
     * @param  $is_register 是否注册, 0非注册，1注册成功，2注册失败，3注册中
     * @param  $is_login 是否登录，0非登陆，1登陆成功，2登陆失败，3登陆中
     * @param  $from_platform 平台，1网信app，2网信wap，3网信pc，4普惠app，5普惠wap，6普惠pc，7企业app和wap，8企业pc
     * @param  $invitation_code 邀请码
     */
    public function insert($user_name, $user_id, $is_register, $is_login,
        $from_platform, $invitation_code = '', $extra = '') {
        $logInfo = array();
        $logInfo['ip'] = get_client_ip();
        //userName maybe include mobile,so format it
        if (!empty($user_name)) {
            $logInfo['user_name'] = user_name_format($user_name);
        }

        $isDebug = app_conf('LOGIN_DEBUG');
        if ($isDebug) {
            $debugInfo['input_account'] = $user_name;
            $debugInfo['trim_account'] = trim($user_name);
            $debugInfo['ascii_account'] = $this->asciiAccount($user_name);
            $debugInfo['trim_asc_account'] = $this->asciiAccount(trim($user_name));
            $debugInfo['md_account'] = md5($user_name);
            $debugInfo['md_trim_account'] =md5($debugInfo['trim_account']);
            $logInfo['debugInfo'] = json_encode($debugInfo);
        }

        $logInfo['account'] = md5($user_name.'_reglog');
        $logInfo['user_id'] = $user_id;
        $logInfo['is_register'] = $is_register;
        $logInfo['is_login'] = $is_login;
        $logInfo['from_platform'] = $from_platform;
        $logInfo['invitation_code'] = $invitation_code;
        $logInfo['extra'] = $extra;
        $logInfo['ua'] = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $logInfo['referer'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $logInfo['time'] = date("Y-m-d H:i:s");
        $logInfo['time_stamp'] = get_gmtime();

        $return = LogRegLoginModel::instance()->insert($logInfo);
        return $return;
    }

}

// END class LogRegLoginService

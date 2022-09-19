<?php
/**
 * 设置或修改收货地址：
 * 获取手机验证码 已登录用户
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2015年7月23日
 */

namespace web\controllers\hongbao;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Block;

class GetCode extends BaseAction
{

    public function invoke()
    {
        $form = new Form('get');
        $form->rules = array(
            'tk' => ['filter' => 'required', 'message' => '参数异常，请刷新页面'],
            't' => ['filter' => 'required', 'message' => '参数异常，请刷新页面'],
            'mobile' => array('filter' => 'reg', "message" => "手机号码格式错误", "option" => array("regexp" => "/^1[3456789]\d{9}$/")),
            'callback' => array("filter" => "reg", "message" => "callbackerror", "option" => array("regexp" => "/^jQuery\d*_\d*$/")),
        );
        $this->callback = $_GET['callback'] ?: 'callback';
        $this->callback = htmlspecialchars($this->callback);

        if (!$form->validate()) {
            $errno = -1;
            $errmsg = $form->getErrorMsg();
            if ($errmsg == 'callbackerror') {
                $this->callback = 'callback';
            }
            return $this->jsonp($errno, $errmsg);
        }

        // 验证refer
        $referer = parse_url($_SERVER['HTTP_REFERER']);
        $refererHost = $referer['host'];

        if ($refererHost != app_conf('MARKETING_DOMAIN')) {
            $errno = -10;
            $errmsg = 'illegal request';
            return $this->jsonp($errno, $errmsg);
        }

        $ip = get_client_ip();
        $check_ip_minute_result = Block::check('SEND_SMS_IP_MINUTE', $ip, false);
        if ($check_ip_minute_result === false) {
            $errno = -11;
            $errmsg = '发送频率超过分钟限制，请稍后再试';
            return $this->jsonp($errno, $errmsg);
        }
        $check_ip_day_result = Block::check('SEND_SMS_IP_TODAY', $ip, false);
        if ($check_ip_day_result === false) {
            $errno = -12;
            $errmsg = '发送频率超过当天限制，请稍后再试';
            return $this->jsonp($errno, $errmsg);
        }

        $mobile = $form->data['mobile'];
        $check_phone_hour_result = Block::check('SEND_SMS_PHONE_HOUR', $mobile, false);
        if ($check_phone_hour_result === false) {
            $errno = -13;
            $errmsg = '手机号码发送频率超过限制，请稍后再试';
            return $this->jsonp($errno, $errmsg);
        }

        $isrsms = false;
        $tokenKey = $form->data['tk'];
        $token = $form->data['t'];
        if (empty($tokenKey) || empty($token)) {
            $errno = -2;
            $errmsg = '参数异常，请刷新页面';
            return $this->jsonp($errno, $errmsg);
        }
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $t = $redis->get('TOKEN_' . $tokenKey);
        $redis->del('TOKEN_' . $tokenKey);
        if ($t != $token) {
            $errno = -2;
            $errmsg = '参数异常';
            return $this->jsonp($errno, $errmsg);
        }

        $is_send = $this->rpc->local('MobileCodeService\isSend',array($mobile, 19));
        if ($is_send != 1){
            $error_msg = $this->rpc->local('MobileCodeService\getError',array($is_send));
            return $this->jsonp($error_msg['code'], $error_msg['message']);
        }
        $res = $this->rpc->local('MobileCodeService\sendVerifyCode',array($mobile, 0, $isrsms, 19));
        $res = json_decode($res, true);
        return $this->jsonp($res['code'], $res['message']);
    }

    private function jsonp($code, $msg, $data = [])
    {
        if ($code == 1) $code = 0;
        $tokenKey = round(microtime(true) * 1000);
        $token = mktoken($tokenKey);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redis->set('TOKEN_' . $tokenKey, $token, 'ex', 600);
        $data['tokenKey'] = $tokenKey;
        $data['token'] = $token;
        $res = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
        $jsonStr = json_encode($res);
        header('Content-Type: application/javascript; charset=utf-8');
        echo $this->callback . "({$jsonStr})";
        die;
    }
}

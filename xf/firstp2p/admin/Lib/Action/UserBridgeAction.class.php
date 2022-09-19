<?php
/**
 * 桥接类
 */

class UserBridgeAction extends CommonAction {
    public function  __construct() {
        $this->auth();
    }

    private function auth() {
        $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
        $redirectAction = isset($_REQUEST['a']) ? $_REQUEST['a'] : 'User';

        $authUrl = 'http://'.$GLOBALS['sys_config']['NCFPH_ADMIN_DOMAIN'];
        $auth = $this->checkLogin($authUrl, false, array('cookie' => "PHPSESSID=$token"));

        $ret = json_decode($auth, true);
        if (!empty($ret) || $ret['isLogin'] == 1) {
            es_session::set(md5(conf("AUTH_KEY")), $ret['adminInfo']);
            $bridgeParams['a'] = $_REQUEST['toAction'];
            $bridgeParams['m'] = $redirectAction;
            //去掉请求中的跳转参数:m,toAction,a,token,仅剩query需要的参数
            unset($_REQUEST['m'], $_REQUEST['token'], $_REQUEST['toAction'], $_REQUEST['a']);
            //合并query参数和底层的module,action参数
            $bridgeParams = array_merge($bridgeParams, $_REQUEST);
            $queryString = http_build_query($bridgeParams, null, '&');
            $location = 'http://'.$_SERVER['HTTP_HOST'].'/m.php?'.$queryString;
            header('Location:'.$location);
        } else {
            echo 'Please Login!';
            exit;
        }
    }

    private function checkLogin($url,$flag = false, $opt = array()) {
        if(empty($url)){
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, '');
        curl_setopt($ch, CURLOPT_REFERER,'');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(isset($opt['cookie'])){
            curl_setopt($ch,CURLOPT_COOKIE,$opt['cookie']);
        }

        if (substr($url, 0, 5) === 'https')
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
        }

        $result = curl_exec($ch);

        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        if($flag) {
            return array('msg'=>$result,'code'=>$errno);
        }
        return $result;
    }
}

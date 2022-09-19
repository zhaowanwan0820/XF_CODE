<?php
/**
 * @author <wenyanlei@ucfgroup.com>
 **/

namespace mobileapp\controllers\ios;

use mobileapp\controllers\BaseAction;

class Index extends BaseAction {

    public function invoke() {
        $android_isok = app_conf('ANDROID_SWITCH');
        if(!$android_isok){
            return;
        }
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strpos($agent, 'android') !== false){
            unset($_GET['ctl'], $_GET['act'], $_GET['city'],$_GET[1]);
            header("Location:/android?".http_build_query($_GET));
            return;
        }
        $env = app_conf("ENV_FLAG");
        $cn = $_COOKIE['link_coupon'] ? $_COOKIE['link_coupon'] : $_GET['cn'];
        $cn = trim($cn);
        // 静态文件都从主站public/static 获取
        if ($env=='lc' || $env=='dev'){
            preg_match('/(?<shorthostname>\w+)\./', gethostname(), $matches);
            $static_host = 'http://'.$matches['shorthostname'].'.firstp2plocal.com';
        }else{
            $static_host = '';
        }

        if($this->isPuhui){
            $this->template = 'mobileapp/views/ios/wxph_ios.html';
            $down_url = app_conf('IOS_PUHUI_DOWNLOAD_URL');
        }else{
            $down_url = app_conf('IOS_DOWNLOAD_URL');
        }

        //添加企业站的下载，覆盖普惠和主站
        if ($_GET['type'] == 'e'){
            $this->template = 'mobileapp/views/ios/e_ios.html';
            $down_url = app_conf('IOS_E_DOWNLOAD_URL');
        }

        $referrer_token = '';
        if (isset($_GET['referrer_token'])) {
            $referrer_token = 'referrer_token='.trim($_GET['referrer_token']);
        }

        $androidUrl = app_conf('ANDROID_DOWNLOAD_URL_REWRITE');
        if ($cn) {
            $urlArray = parse_url($androidUrl);
            if ($urlArray['query']) {
                $androidUrl .= "&cn={$cn}";
            } else {
                $androidUrl .= "?cn=$cn";
            }
        }
        $this->tpl->assign('down_url', $this->removeXss($down_url));
        $this->tpl->assign('androidUrl', $this->removeXss($androidUrl));
        $this->tpl->assign('referrer_token', $referrer_token);
        $this->tpl->assign('static_host',$static_host);
    }
}

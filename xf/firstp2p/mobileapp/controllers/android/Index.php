<?php
/**
 * @author <wenyanlei@ucfgroup.com>
 **/

namespace mobileapp\controllers\android;

use mobileapp\controllers\BaseAction;

class Index extends BaseAction {
    const VARNAME_DOWNLOAD_APK_QINIU = '{cn}';

    public function invoke() {
        $android_isok = app_conf('ANDROID_SWITCH');
        if(!$android_isok){
            return;
        }
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strpos($agent, 'iphone') !== false){
            unset($_GET['ctl'], $_GET['act'], $_GET['city'],$_GET[1]);
            header("Location:/ios?".http_build_query($_GET));
            return;
        }
        $env = app_conf("ENV_FLAG");
        // 静态文件都从主站public/static 获取
        if ($env=='lc' || $env=='dev'){
            preg_match('/(?<shorthostname>\w+)\./', gethostname(), $matches);
            $static_host = 'http://'.$matches['shorthostname'].'.firstp2plocal.com/';
        }else{
            $static_host = '';
        }
        $down_url = app_conf('ANDROID_DOWNLOAD_URL_REWRITE');
        if($this->isPuhui){
            $this->template = 'mobileapp/views/android/wxph_android.html';
            $down_url = app_conf('ANDROID_DOWNLOAD_URL_REWRITE_PUHUI') ?: $down_url;
            $down_url .= '?ispuhui=1';
        }

        //添加企业站的下载，覆盖普惠和主站
        if ($_GET['type'] == 'e'){
            $this->template = 'mobileapp/views/android/e_android.html';
            $down_url = app_conf('ANDROID_E_DOWNLOAD_URL');
        }

        $referrer_token = '';
        if (isset($_GET['referrer_token'])) {
            $referrer_token = '&referrer_token='.trim($_GET['referrer_token']);
            $down_url = $down_url.$referrer_token;
        } elseif (isset($_GET['refl']) && !empty($_GET['refl'])) {
            $down_url = app_conf('ANDROID_DOWNLOAD_QINIU_URL');
            $down_url = str_replace(self::VARNAME_DOWNLOAD_APK_QINIU,trim($_GET['refl']),$down_url);
        }

        /**
         * 下载地址和渠道相关
         */
        if(!empty($_GET['type']) && $_GET['type'] != 'e' ){
            $down_url .= strpos($down_url,'?')? "&type=".$_GET['type']:"?type=".$_GET['type'];
        }

        $this->tpl->assign('down_url', $this->removeXss($down_url));
        $this->tpl->assign('referrer_token', $referrer_token);
        $this->tpl->assign('static_host',$static_host);
    }
}

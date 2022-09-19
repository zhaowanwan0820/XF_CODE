<?php
/**
 * app下载站，自动跳转到下载页
 * @author <pengchanglu@ucfgroup.com>
 **/

namespace mobileapp\controllers\index;

use mobileapp\controllers\BaseAction;

class Index extends BaseAction {

    public function invoke() {
        $android_isok = app_conf('ANDROID_SWITCH');//安卓开关
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        //$agent = 'ua iphone';
        $cn = trim($_GET['cn']);
        $_referrer = isset($_GET['referrer_token']) ? trim($_GET['referrer_token']) : '';
        $_refl = isset($_GET['refl']) ? trim($_GET['refl']) : '';
        $_type = isset($_GET['type']) ? trim($_GET['type']) : '';
        if (!empty($_referrer)) {
            if (!in_array($_referrer, array('weibo','weixin','guanwang'))) {
                $_referrer = '';
            }
            else {
                $_glue = $cn ? '&' : '';
                $_referrer = $_glue.'referrer_token='.$_referrer;
           }
        }
        if(empty($_referrer) && !empty($_refl)){
            $_glue = $cn ? '&' : '';
            $_refl = $_glue.'refl='.$_refl;
        }

        if ($android_isok) {
            if (strpos($agent, 'iphone')) {
                if ($cn) {
                    header("Location:/ios?cn={$cn}&referrer_token=guanwang");
                } else {
                    header("Location:/ios?referrer_token=guanwang");
                }
            } elseif (strpos($agent, 'android')) {
                if ($cn) {
                    header("Location:/android?cn={$cn}{$_referrer}{$_refl}&type={$_type}");
                } else {
                    header("Location:/android?{$_referrer}{$_refl}&type={$_type}");
                }
            } else {
                header("Location:http://www.firstp2p.com/app");
            }
        } else { //旧代码
            $iphone = (strpos($agent, 'iphone')) ? true : false;
            if (!$iphone) {
                header("Location:http://www.firstp2p.com/app");
            }
        }
    }
}

<?php
/**
 * Index.php
 *
 * @date 2014年4月15日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\app;

use web\controllers\BaseAction;

class Index extends BaseAction {

    public function invoke() {

        $android_isok = app_conf('ANDROID_SWITCH');//安卓开关
        $is_m_active = app_conf('IS_M_ACTIVE');//手机下载站是否OK
        if($android_isok){
            if($is_m_active !== ""){
                $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
                if(strpos($agent, 'iphone')){
                    header("Location:http://app.firstp2p.com/ios?type=".$_GET['type']);
                    return;
                }elseif(strpos($agent, 'android')){
                    header("Location:http://app.firstp2p.com/android?type=".$_GET['type']);
                    return;
                }elseif($this->is_firstp2p){
                    // 由于普惠没有app下载页
                    header("Location:https://".app_conf('FIRSTP2P_CN_DOMAIN'));
                    return;
                }
            }
        }else{//旧代码
            if($is_m_active !== ""){
                $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
                $iphone = (strpos($agent, 'iphone')) ? true : false;
                if($iphone){
                    header("Location:http://app.firstp2p.com/");
                    return;
                }elseif($this->is_firstp2p){
                    // 由于普惠没有app下载页
                    header("Location:https://".app_conf('FIRSTP2P_CN_DOMAIN'));
                    return;
                }
            }
        }

    }
}

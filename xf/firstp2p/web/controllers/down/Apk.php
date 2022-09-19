<?php
/**
 * apk 下载
 * @author changlu<pengchanglu@ucfgroup.com>
 **/

namespace web\controllers\down;
use web\controllers\BaseAction;

class Apk extends BaseAction {

    const IS_H5 = false;

    public function init() {
    }

    public function invoke() {
        $apk_url = '';
        $apk_name ='';
        /**
         * 下载地址和渠道相关
         */
        if(!empty($_GET['type']) && $_GET['type'] != 'e' ){
            $urlList = \dict::get('DOWN_URL_LIST');
            if(!empty($urlList)){
                foreach($urlList as $url){
                    list($key,$val,$name) = explode('|', $url);
                    if($key == $_GET['type']){
                        $apk_url = $val;
                        $apk_name = empty($name)? '':$name;
                    }
                }
            }
        }

        if (empty($apk_url)) {
            if(isset($_GET['ispuhui']) && trim($_GET['ispuhui'])){
                $apk_name = trim(app_conf('ANDROID_PUHUI_DOWNLOAD_NAME'));
                $apk_url = trim(app_conf('ANDROID_PUHUI_DOWNLOAD_URL'));
            }else{
                $apk_name = trim(app_conf('ANDROID_DOWNLOAD_NAME'));
                $apk_url = trim(app_conf('ANDROID_DOWNLOAD_URL'));
            }
            $apk_url .= '?referrer_token=guanwang';
        }

        // 这个down/apk地址，是个动态程序流，每次都要回源,不能缓存，比较消耗带宽
        header("Location: ".$apk_url);
        exit;

        // $apk_name = empty($apk_name) ? 'firstp2p' : $apk_name;
        // $code = ''; //cookie 数据
        // if (isset($_GET['cn']) && trim($_GET['cn'])) {
        //     $code = "_" . $_GET['cn'];
        // }
        // $apk_name .= $code;

        // header("Accept-Ranges:bytes");
        // //header("Connection:keep-alive");
        // header("Content-Type:application/vnd.android.package-archive");
        // header("Content-Disposition:attachment;filename=" . $apk_name . ".apk");
        // $headers = get_headers($apk_url, true);
        // header("Content-Length:" . $headers['Content-Length']);
        // readfile($apk_url);
    }
}

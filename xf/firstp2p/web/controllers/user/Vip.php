<?php
/**
 * 从白泽获取vip数据
 * @author <zhaoxiaoan@ucfgroup.com>
 * 
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\UserVipService;

class Vip extends BaseAction {

    private $log_m = '';

    public function init() {

        \FP::import("libs.utils.logger");
        $this->log_m = __CLASS__.','.__FUNCTION__;
        \logger::info($this->log_m.' start');

        $this->form = new Form("get");
        $this->form->rules = array(
            'token' => array('filter' => 'required','message' => '参数错误'),
        );

       if (!$this->form->validate()){
            \logger::info($this->log_m.'param token is empty');
            // 白泽要求返回状态码
            $this->sendHeader();
        }
	}
    
	public function invoke(){

        $data = $this->form->data;

        $user_vip_service = new UserVipService();

        if (empty($data['token']) || $user_vip_service::SYNC_BAIZE_TOKEN != $data['token']){
            \logger::info($this->log_m.'param token error');
            $this->sendHeader();
        }
        if (empty($_FILES['file']['tmp_name'])){
            \logger::info($this->log_m.'param file error');
            $this->sendHeader();
        }
        if ($_FILES["file"]["error"] == UPLOAD_ERR_INI_SIZE){
            \logger::info($this->log_m.'file upload_max_filesize  error');
            $this->sendHeader();
        }
        if ($_FILES["file"]["error"] == UPLOAD_ERR_FORM_SIZE){
            \logger::info($this->log_m.'file max_file_size error');
            $this->sendHeader();
        }
        $data = file_get_contents($_FILES['file']['tmp_name']);

        if (empty($data)){
            \logger::info($this->log_m.'get csv faild');
            $this->sendHeader();
        }
        // 缓存3天时间，异步处理
        $redis = \SiteApp::init()->cache;
        //$ret = \libs\utils\FileCache::getInstance()->set($user_vip_service::VIP_CACHE_KEY, $data, time()+86400*3);
        $ret = $redis->set($user_vip_service::VIP_CACHE_KEY,$data, intval(time()+86400*3));
        if (empty($ret)){
            \logger::info($this->log_m.'cache file faild');
            $this->sendHeader();
        }

        \logger::info($this->log_m.' end');

    }

    private function sendHeader(){
        header('HTTP/1.1 500 Internal Server Error');
        exit;
    }
}

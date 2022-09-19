<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/10/31
 * Time: 16:32
 */


use libs\utils\Curl;

class FiveYearAction extends CommonAction {

    public function __construct() {
        parent::__construct();
    }


    public function addPhoto() {
        $this->display();
    }

    public function doAddPhoto() {

        $params['url'] = trim($_POST['photoUrl']);
        $params['bgColor'] = trim($_POST['bgColor']);
        $params['isBig'] = !empty($_POST['isBig']);

        if (empty($params['url'])) {
            return $this->ajaxReturn(-1, '请上传图片');
        }

        if (empty($params['bgColor'])) {
            return $this->ajaxReturn(-1, '请选择背景色');
        }

        try {
            $this->partyRequest('/common/addPhoto', $params);
        } catch (\Exception $e) {
            return $this->ajaxReturn(-1, $e->getMessage());
        }
        return $this->ajaxReturn(0, "操作成功");
    }

    public function listBossPhoto() {
        try {
            $response = $this->partyRequest('/common/bossPhoto');
        } catch (\Exception $e) {
            return $this->ajaxReturn(-1, $e->getMessage());
        }
        $this->assign("bossWallConfig", $response['data']['bossWallConfig']);
        $this->assign("photos", $response['data']['photoList']);
        $this->display();
    }

    public function bossPhotoSort() {
        $params['photoIds'] = $_POST['photoIds'];
        try{
            $this->partyRequest('/common/bossPhotoSort', $params);
        } catch (\Exception $e) {
            return $this->ajaxReturn(-1, $e->getMessage());
        }

        return $this->ajaxReturn(0, "操作成功");
    }

    //上传图片
    public function uploadImg() {
        $file = current($_FILES);
        if (empty($file) || $file['error'] != 0) {
            return $this->ajaxReturn(-4, "图片为空");
        }

        try {
            if (!empty($file)) {
                $uploadFileInfo = array(
                    'file' => $file,
                    'isImage' => 1,
                    'limitSizeInMB' => 10,
                    'savePath' => "fiveyear"
                );
                $result = uploadFile($uploadFileInfo);
            }
        } catch (\Exception $e) {
           return $this->ajaxReturn(-3, $e->getMessage());
        }
        if(empty($result['errors'])){
            if (get_cfg_var("phalcon.env") == "dev" || get_cfg_var("phalcon.env") == "test") {
                $imgUrl = '//'. $GLOBALS['sys_config']['vfs_ftp']['ftp_host'] . '/' . $result['full_path'];
            } else {
                $imgUrl = "//static.firstp2p.com/". $result['full_path'];
            }
            return $this->ajaxReturn(0, "上传成功", ['imgUrl' => $imgUrl]);
        }

        if(!empty($result['errors'])){
            return $this->ajaxReturn(-1,end($result['errors']));
        }

        return $this->ajaxReturn(-2, '图片上传失败');
    }

    public function ajaxReturn($code, $msg, $data = []) {
        $result = [
          'code' => $code,
          'msg' => $msg,
          'data' => $data
        ];
        echo json_encode($result);
        return true;
    }

    public function partyRequest($action, $params, $json = false) {

        if (get_cfg_var("phalcon.env") == "dev" || get_cfg_var("phalcon.env") == "test") {
            $host = "http://10.20.69.17:8100";
        } else {
            $host = "http://party.ncfwx.com";
        }

        $url = $host . $action;
        if (!$json) {
            $header = ['Content-Type: application/x-www-form-urlencoded', 'charset=utf-8', 'auth-key: Pz4rluMgbLnAjmhWYau0GGzDFKBeZwYyh4A'];
            $result = Curl::post($url, $params, $header, 1);
        } else {
            $header = ['auth-key: Pz4rluMgbLnAjmhWYau0GGzDFKBeZwYyh4A'];
            $result = Curl::post_json($url, $params, 1, $header);
        }
        if (Curl::$errno != 0) {
            throw new \Exception(Curl::$error);
        }
        if (Curl::$httpCode != '200') {
            throw new \Exception('服务地址错误');
        }
        $response = json_decode($result, true);
        if ($response['errorCode'] != 0) {
            throw new \Exception($response['errorMsg']);
        }
        return $response;
    }


}

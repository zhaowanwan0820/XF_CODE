<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

/**
 *
 * 用户头像修改接口
 *
 * @author zhaohui <zhaohui3@ucfgroup.com>
 *
 */
class ModifyUserImage extends AppBaseAction {
    private $allowPostfix =  array('jpg','jpeg','pjpeg','png');
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $result = array();
        if ($_FILES['file']) {
            if (count($_FILES) != 1) {
                $this->setErr('ERR_PARAMS_ERROR', "最多上传一张图片");
                return false;
            }
            $file = $_FILES['file'];
            $prefix = $this->getImagePostFix($file['tmp_name']);
            if(in_array($prefix, $this->allowPostfix)) {
                $uploadFileInfo = array(
                        'file' => $file,
                        'user_id' => $userInfo['id'],
                        'type' => 1,
                );
                $result = $this->rpc->local('UserImageService\uploadUserImageInfo', array($uploadFileInfo));
            } else {
                $this->setErr('ERR_PARAMS_ERROR', "图片格式仅限JPG、PNG，请重新上传图片");
                return false;
            }
        } else {
            $this->setErr('ERR_PARAMS_ERROR',"请选择上传的图片");
            return false;
        }
        if($result['respCode'] == '00') {
            $res = array('res' => $result['respMsg']);
        } else {
            $this->setErr('ERR_MANUAL_REASON',$result['respMsg']);
            return false;
        }
        $this->json_data = $res;
        return true;
    }

    /**
     * 通过二进制流 读取文件后缀信息
     * @param string $filename
     */
    private function getImagePostFix($filename) {
        $file     = fopen($filename, "rb");
        $bin      = fread($file, 2); //只读2字节
        fclose($file);
        $strinfo  = @unpack("c2chars", $bin);
        $typecode = intval($strinfo['chars1'].$strinfo['chars2']);
        $filetype = "";
        switch ($typecode) {
            case 7790: $filetype = 'exe';break;
            case 7784: $filetype = 'midi';break;
            case 8297: $filetype = 'rar';break;
            case 255216:$filetype = 'jpg';break;
            case 7173: $filetype = 'gif';break;
            case 6677: $filetype = 'bmp';break;
            case 13780:$filetype = 'png';break;
            default:   $filetype = 'unknown'.$typecode;
        }
        if ($strinfo['chars1']=='-1' && $strinfo['chars2']=='-40' ) {
            return 'jpg';
        }
        if ($strinfo['chars1']=='-119' && $strinfo['chars2']=='80' ) {
            return 'png';
        }
        return $filetype;
    }
}


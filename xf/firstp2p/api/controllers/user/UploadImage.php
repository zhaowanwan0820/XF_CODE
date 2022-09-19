<?php
/**
 * 图片上传
 * @author weiwei12 <weiwei12@ucfgroup.com>
 */
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class UploadImage extends AppBaseAction {

    private $allowPostfix =  array('jpg','jpeg','pjpeg','png');

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'asAttach' => array('filter' => 'int'),
            'asPrivate' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
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
        $userId = $userInfo['id'];

        if (!$_FILES['file']) {
            $this->setErr('ERR_PARAMS_ERROR',"请选择上传的图片");
            return false;
        }

        // 检查数量
        if (count($_FILES) != 1) {
            $this->setErr('ERR_PARAMS_ERROR', "最多上传一张图片");
            return false;
        }

        // 检查格式
        $file = $_FILES['file'];
        $prefix = $this->getImagePostFix($file['tmp_name']);
        if(!in_array($prefix, $this->allowPostfix)) {
            $this->setErr('ERR_PARAMS_ERROR', "图片格式仅限JPG、PNG，请重新上传图片");
            return false;
        }

        // 处理上传的图片，等比例压缩到1280*800
        $this->resizeImage($file['tmp_name'], 1280, 800);

        $fileInfo = [
            'file' => $file, // 文件域信息数组
            'isImage' => 1, // 是否是图片
            'userId' => $userId,
            ];

        if (!empty($data['asAttach']) && $data['asAttach'] == 1) {
            $fileInfo['asAttachment'] = 1;
            $fileInfo['app'] = $this->getOs() == 1 ? 'IOS':'ANDROID';
        }

        if (!empty($data['asPrivate']) && $data['asPrivate'] == 1) {
            $fileInfo['asPrivate'] = 1;
        }

        // 上传图片
        $result = uploadFile($fileInfo);
        if (empty($result['status']) || $result['status'] != 1) {
            $this->setErr('ERR_MANUAL_REASON', isset($result['errors'][0]) ? $result['errors'][0] : '上传失败');
            return false;
        }

        $imageId = !empty($result['aid']) ? $result['aid'] : 0;
        if ($fileInfo['asPrivate'] == 1) {
            $fileUrl = sprintf($this->getHost().'/common/image?token=%s&image_id=%s', $data['token'], $imageId);
        } else {
            $fileUrl = 'http:' . $GLOBALS['sys_config']['ISTATIC_HOST'] . '/' . $result['full_path'];
        }

//        $imagejson = ['url' => $fileUrl, 'image_id' => $imageId];
        $imagejson = json_encode(['url' => $fileUrl, 'image_id' => $imageId]);
        $this->json_data = ['imagejson' => $imagejson, 'url'=>$fileUrl, 'image_id'=>$imageId];
        return true;
    }

    /**
     * 等比例压缩图片
     * @param  String $src_imagename 源文件名,比如 “source.jpg”
     * @param  int    $maxwidth      压缩后最大宽度
     * @param  int    $maxheight     压缩后最大高度
     * @param  String $savename      保存的文件名,“d:save”
     * @param  String $filetype      保存文件的格式,比如 ”.jpg“
     */
    private function resizeImage($src_imagename, $maxwidth, $maxheight, $savename = '', $filetype = '') {
        $im = imagecreatefromjpeg($src_imagename);
        // 获取到当前图片的宽和高
        $current_width = imagesx($im);
        $current_height = imagesy($im);
        if (($maxwidth && $current_width > $maxwidth) || ($maxheight && $current_height > $maxheight)) {
            if ($maxwidth && $current_width > $maxwidth) {
                $widthratio = $maxwidth / $current_width;
                $resizewidth_tag = true;
            }

            if ($maxheight && $current_height > $maxheight) {
                $heightratio = $maxheight / $current_height;
                $resizeheight_tag = true;
            }

            // 计算压缩比例因子
            if ($resizewidth_tag && $resizeheight_tag) {
                $ratio = $widthratio < $heightratio ? $widthratio : $heightratio;
            }

            if ($resizewidth_tag && !$resizeheight_tag) {
                $ratio = $widthratio;
            }

            if($resizeheight_tag && !$resizewidth_tag) {
                $ratio = $heightratio;
            }

            $newwidth = $current_width * $ratio;
            $newheight = $current_height * $ratio;

            if (function_exists("imagecopyresampled")) {
                $newim = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $current_width, $current_height);
            } else {
                $newim = imagecreate($newwidth, $newheight);
                imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $current_width, $current_height);
            }

            if (empty($savename)) {
                $savename = $src_imagename;
            } else {
                $savename = $savename . $filetype;
            }

            imagejpeg($newim, $savename);
            imagedestroy($newim);
        } else {
            if (!empty($savename) && $savename.$filetype != $src_imagename) {
                $savename = $savename . $filetype;
                imagejpeg($im, $savename);
            }
        }
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

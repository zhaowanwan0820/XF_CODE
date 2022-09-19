<?php
/**
 * 图片上传
 * @author sunxuefeng <sunxuefeng@ucfgroup.com>
 */
namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;

class UploadImage extends AppBaseAction {

    private $allowPostfix =  array('jpg','jpeg','pjpeg','png');

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if ($_FILES['file']) {
            if (count($_FILES) != 1) {
                $this->setErr('ERR_PARAMS_ERROR', "最多上传一张图片");
                return false;
            }
            $file = $_FILES['file'];
            $prefix = $this->getImagePostFix($file['tmp_name']);
            // 处理上传的图片，等比例压缩到800*600
            $this->resizeImage($file['tmp_name'], 800, 600, $file['tmp_name'], '');

            if(in_array($prefix, $this->allowPostfix)) {
                $result = uploadFile(array(
                    'file' => $file, // 文件域信息数组
                    'isImage' => 1, // 是否是图片
                    'limitSizeInMB' => 10240
                ));
            } else {
                $this->setErr('ERR_PARAMS_ERROR', "图片格式仅限JPG、PNG，请重新上传图片");
                return false;
            }
        } else {
            $this->setErr('ERR_PARAMS_ERROR',"请选择上传的图片");
            return false;
        }
        if (isset($result['status']) && $result['status'] == 1) {
            $res = [
                'url' => 'http:' . $GLOBALS['sys_config']['ISTATIC_HOST'] . '/' . $result['full_path']
            ];
        } else {
            $this->setErr('ERR_MANUAL_REASON', isset($result['errors'][0]) ? $result['errors'][0] : '上传失败');
            return false;
        }
        $this->json_data = $res;
        return true;
    }

    /**
    +------------------------------------------------------------------------------
     *                等比例压缩图片
    +------------------------------------------------------------------------------
     * @param  String $src_imagename 源文件名        比如 “source.jpg”
     * @param  int    $maxwidth      压缩后最大宽度
     * @param  int    $maxheight     压缩后最大高度
     * @param  String $savename      保存的文件名    “d:save”
     * @param  String $filetype      保存文件的格式 比如 ”.jpg“
    +------------------------------------------------------------------------------
     */
    private function resizeImage($src_imagename, $maxwidth, $maxheight, $savename, $filetype) {
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

            $savename = $savename . $filetype;
            imagejpeg($newim, $savename);
            imagedestroy($newim);
        } else {
            $savename = $savename . $filetype;
            imagejpeg($im, $savename);
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

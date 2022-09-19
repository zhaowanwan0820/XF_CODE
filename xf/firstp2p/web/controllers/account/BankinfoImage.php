<?php
/**
 * bankinfoImage.php
 *
 * @date 2014年5月26日
 * @author 杨庆 <yangqing@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;

class BankinfoImage extends BaseAction {

    private $allowPostfix =  array('jpg','jpeg','pjpeg','png');
    private $returnMessage = array('code'=>'0000','message'=>'操作成功');
    
    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $data   = array();
        $file   = $_FILES['fileToUpload'];
        $prefix = $this->getImagePostFix($file['tmp_name']);
        if(!empty($file) && in_array($prefix, $this->allowPostfix)) {
            $userId = isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : 0;
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'asPrivate' => 1,
                'limitSizeInMB' => 3,
                'userId' => $userId,
            );
            $result = uploadFile($uploadFileInfo);
            if(!empty($result['aid']) && $result['filename']) {
                $data['image_id'] = $result['aid'];
                $data['filename'] = $result['filename'];
                $this->returnMessage['message'] = $data;
            }else{
                $this->returnMessage = array('code'=>'4001','message'=>'图片尺寸不能大于3M，请重新上传图片');
            }
        }else{
        	$this->returnMessage = array('code'=>'4000','message'=>'图片格式仅限JPG、PNG，请重新上传图片');
        }
        echo json_encode($this->returnMessage);
    }
    
    //通过二进制流 读取文件后缀信息
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

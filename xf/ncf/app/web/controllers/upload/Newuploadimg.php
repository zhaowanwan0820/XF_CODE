<?php

/**
 * Newuploadimg.php
 * @abstract 与之前的上传图片接口逻辑保持一致，更改返回数据
 * @date 2014-11-04
 * @author yutao <yutao@ucfgroup.com>
 */

namespace web\controllers\upload;

use web\controllers\BaseAction;
use libs\vfs\Vfs;

class Newuploadimg extends BaseAction {

    public function init() {
        
    }

    public function invoke() {
        $n = getRequestInt('n', 1);
        $is_priv = getRequestInt('is_priv', 0);
        $userId = isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : 0;
        $uploadFileInfo = array(
            'file' => $_FILES['file'],
            'isImage' => 1,
            'asPrivate' => $is_priv,
            'asAttachment' => 1,
            'userId' => $userId,
        );
        $result = uploadFile($uploadFileInfo);
        if ($result['status'] == 1) {
            $list = $result['data'];
            $file_url = '';
            if ($result['is_priv'] == 1) {
                // 加密参数
                $f = urlencode(aesAuthCode(sprintf('%d|%d', $result['aid'], $userId), 'ENCODE'));
                $file_url = get_domain() . '/attachment-view?file=' . $result['full_path'] . '&f=' . $f;
            } else {
                $file_url = Vfs::$staticHost . $result['full_path'];
            }
            $msg = "上传成功！";
            $file_url_img = trim($file_url, '.');
            $error = 0;
            //echo '<script type="text/javascript">parent.ajax_callback("' . $file_url . '","' . $msg . '",' . $n . ',"' . $file_url_img . '")</script>';
        } else {
            $error = -1;
            $msg = $result['errors'][0];
            //echo '<script type="text/javascript">parent.ajax_callback_error("' . $n . '","' . $msg . '")</script>';
        }
        $ret = array(
            'error' => $error,
            'msg' => $msg,
            'imgUrl' => $file_url_img,
            'n' => $n
        );
        echo json_encode($ret);
        exit;
    }

}

<?php

/**
 * Ajaxuploadimg.php
 *
 * @date 2014年5月22日
 * @author yangqing <yangqing@ucfgroup.com>
 */
namespace web\controllers\upload;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\vfs\Vfs;

class Ajaxuploadimg extends BaseAction {

    public function init() {
        // $this->check_login();
    }

    public function invoke() {
        $n = getRequestInt('n', 1);
        $is_priv = getRequestInt('is_priv', 0);
        $userId = isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : 0;
        $uploadFileInfo = array(
            'file' => $_FILES['file'],
            'isImage' => 1,
            'asAttachment' => 1,
            'asPrivate' => $is_priv,
            'userId' => $userId,
        );
        $result = uploadFile($uploadFileInfo);
        $data = array();
        if ($result['status'] == 1) {
            $list = $result['data'];
            //TODO vfs url access
            $file_url = '';
            if($result['is_priv'] == 1) {
                // 加密参数
                $f = urlencode(aesAuthCode(sprintf('%d|%d', $result['aid'], $userId), 'ENCODE'));
                $file_url = get_domain() . '/attachment-view?file=' .  $result['full_path'] . '&f=' . $f;
            } else {
                $file_url = Vfs::$staticHost . $result['full_path'];
            }
            $msg = "上传成功！";
            $file_url_img = trim($file_url, '.');
            echo '<script type="text/javascript">parent.ajax_callback("' . $file_url . '","' . $msg . '",' . $n . ',"' . $file_url_img . '")</script>';
            return;
        } else {
            $msg = $result['errors'][0];
            echo '<script type="text/javascript">parent.ajax_callback_error("' . $n . '","' . $msg . '")</script>';
           return;
        }
    }

}

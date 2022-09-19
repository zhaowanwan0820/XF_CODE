<?php
namespace api\controllers\common;
/**
 * 看图片
 * @author longbo
 */

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use libs\vfs\VfsHelper;
use libs\utils\Logger;

class Image extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'image_id' => array('filter' => 'required', 'message' => 'image_id is required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $image_id = $data['image_id'];

        // 根据附件表id，查询某条附件数据
        $attachmentDao = new \core\dao\AttachmentModel();
        $attachmentData = $attachmentDao->getAttachmentById($image_id);
        // 只有自己能够查看自己的图片
        if (empty($attachmentData) ||  $attachmentData['user_id'] != $user['id']) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        // 获取VFS上传的图片
        $file = $attachmentData['attachment'];
        $path = pathinfo($file);
        $streamContent = VfsHelper::image($file, true);
        if ($path['extension'] == 'jpg' || $path['extension'] == 'jpeg') {
            header('content-type:image/jpeg');
        } else  {
            header('content-type:application/octet-stream');
            header("Content-Disposition:attachment;filename=". $path['basename']);
        }
        echo $streamContent;
        return true;
    }
}

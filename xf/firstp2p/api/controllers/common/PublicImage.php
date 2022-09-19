<?php
/**
 * User: yangshuo
 */

namespace api\controllers\common;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\vfs\VfsHelper;
use core\service\AttachmentService;

class PublicImage extends AppBaseAction {
    public function init() {
        $_SERVER['HTTP_VERSION'] = 300;
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'image_id' => array('filter' => 'required', 'message' => 'image_id is required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $image_id = intval(\libs\utils\Aes::decryptForDeal($data['image_id']));

        // 根据附件表id，查询某条附件数据
        $attachmentService = new AttachmentService();
        $attachmentData = $attachmentService->getAttachment($image_id);

        // 获取VFS上传的图片
        $file = $attachmentData['attachment'];
        $path = pathinfo($file);

        $imageFile = 'http:' . $GLOBALS['sys_config']['ISTATIC_HOST'] . '/' . str_replace('//' , '/', $file);
        Logger::info('publicimage:'.$imageFile);

        $image = file_get_contents($imageFile);
        Logger::info('getimagecontents'.substr($image, 0, 100));

        if ($path['extension'] == 'jpg' || $path['extension'] == 'jpeg' || $path['extension'] == 'png') {
            header('content-type:image/jpeg');
        } else  {
            header('content-type:application/octet-stream');
            header("Content-Disposition:attachment;filename=". $path['basename']);
        }
        echo $image;
        exit;
    }
}

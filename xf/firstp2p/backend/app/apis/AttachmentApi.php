<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use core\dao\AttachmentModel;

/**
 * 附件相关接口
 */
class AttachmentApi extends ApiBackend {

    /**
     * 通过图片id获取图片信息
     */
    public function getAttachmentById()
    {
        $imageId = $this->getParam('imageId');
        $attachmentData = AttachmentModel::instance()->getAttachmentById($imageId);
        if (!empty($attachmentData)) {
            $attachmentData = $attachmentData->getRow();
        }

        return $this->formatResult($attachmentData);
    }
}

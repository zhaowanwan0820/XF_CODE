<?php
/**
 * 附件表
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

class AttachmentModel extends BaseModel {
    /**
     * 根据附件表主键ID获取数据
     * @param int $id
     */
    public function getAttachmentById($id) {
        return $this->findBy('id=:id', '*', array(':id'=>intval($id)), true);
    }
}
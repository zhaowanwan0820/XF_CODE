<?php
/**
 * MsgTemplateModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

/**
 * 模板 （短信邮件消息合同）
 *
 * @author wenyanlei@ucfgroup.com
 **/
class MsgTemplateModel extends BaseModel
{
    public function getTemplateByName($name, $field = '*') {
        $condition = "name=':name' LIMIT 1";
        return $this->findByViaSlave($condition, $field, array(':name' => $name));
    }
} // END class MsgTemplateModel extends BaseModel

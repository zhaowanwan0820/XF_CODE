<?php
/**
 * UerTagRelationModel class file.
 *
 * @author wangshijie@ucfgroup.com
 */

namespace core\dao;

/**
 * 用户标签
 *
 * @author wangshijie@ucfgroup.com
 */
class UserTagModel extends BaseModel
{

    // 状态:无效
    const STATUS_INEFFECT = 0;

    // 状态:有效
    const STATUS_EFFECT = 1;
    /**
     +------------------------------------------------------
     * @desc
     +------------------------------------------------------
     * @param int $uid 用户id
     * @param int $tag_id 标签id
     +------------------------------------------------------
     * @return 返回查询字段
     +------------------------------------------------------
     */
    public function get_tags_by_ids($tagids, $status = 1) {
        if (empty($tagids)) {
            return array();
        }
        $condition = 'id in (:tagids) AND status=:status';
        $params = array(':tagids' => implode(',', $tagids), ':status' => $status);
        return $this->findAllViaSlave($condition, true, '`id`, `const_name`, `name`', $params);
    }

    /**
     +------------------------------------------------------
     * @desc 获取用户标签
     +------------------------------------------------------
     * @param int $uid 用户id
     +------------------------------------------------------
     * @return 返回查询字段
     +------------------------------------------------------
     */
    public function get_tags_by_const_names($const_names) {
        if (!is_array($const_names)) {
            $const_names = array($const_names);
        }
        foreach ($const_names as &$const_name) {
            $const_name = "'".$this->escape($const_name)."'";
        }

        $condition = sprintf("const_name in (%s)", implode(',', $const_names));
        return $this->findAllViaSlave($condition, true, '`id`');
    }

    public function addTag($constName, $tagDesc) {

        $this->const_name = $this->escape($constName);
        $this->name = $this->escape($tagDesc);
        $this->status = self::STATUS_EFFECT;
        $this->created_at = time();
        if ($this->insert()) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     *
     */
    public function getTagByConstName($constName, $slave = true) {
        $condition = "const_name = '" . $this->escape($constName) . "'";
        return $this->findBy($condition, 'id,const_name,name', array(), $slave);
    }
}

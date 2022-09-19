<?php
/**
 * 用户图片表
 * @author guofeng@ucfgroup.com
 **/

namespace core\dao;

/**
 * @package core\dao
 **/
class UserImageModel extends BaseModel {
    /**
     * 图片类型-头像
     * @var int
     */
    const TYPE_1 = 1;

    /**
     * 是否可访问-公开
     * @var int
     */
    const IS_PRIV_0 = 0;

    /**
     * 是否可访问-私有
     * @var int
     */
    const IS_PRIV_1 = 1;

    /**
     * 是否已删除-未删
     * @var int
     */
    const IS_DELETE_0 = 0;

    /**
     * 是否已删除-已删
     * @var int
     */
    const IS_DELETE_1 = 1;

    /**
     * 通过用户ID、图片类型，获取用户图片信息
     * @param int $userId
     * @param int $type
     * @return \libs\db\model
     */
    public function getUserImageInfo($userId, $type = self::TYPE_1) {
        if(empty($userId)) {
            return false;
        }
        return $this->findBy('user_id=:user_id AND type=:type', '*', array(':user_id' => $userId, ':type'=>intval($type)),true);
    }

    /**
     * 插入用户图片信息
     * @param array $params
     * @return boolean
     */
    public function insertUserImageInfo($params) {
        if (!isset($params['user_id']) || !isset($params['type']) || !isset($params['attachment'])) {
            return false;
        }
        $data = array(
            'user_id' => intval($params['user_id']),
            'type' => isset($params['type']) ? intval($params['type']) : self::TYPE_1,
            'filename' => !empty($params['filename']) ? htmlspecialchars($params['filename']) : '',
            'filesize' => !empty($params['filesize']) ? intval($params['filesize']) : 0,
            'width' => !empty($params['width']) ? intval($params['width']) : 0,
            'height' => !empty($params['height']) ? intval($params['height']) : 0,
            'attachment' => !empty($params['attachment']) ? htmlspecialchars($params['attachment']) : '',
            'is_priv' => !empty($params['is_priv']) ? intval($params['is_priv']) : self::IS_PRIV_0,
            'description' => !empty($params['description']) ? htmlspecialchars($params['description']) : '',
            'remark' => !empty($params['remark']) ? htmlspecialchars($params['remark']) : '',
            'create_time' => time(),
        );
        return $this->db->autoExecute($this->tableName(), $data, 'INSERT');
    }

    /**
     * 更新用户图片信息
     * @param array $params
     * @return boolean
     */
    public function updateUserImageInfo($params) {
        if (!isset($params['user_id']) || !isset($params['type']) || !isset($params['attachment'])) {
            return false;
        }
        $data = array(
            'filename' => !empty($params['filename']) ? htmlspecialchars($params['filename']) : '',
            'filesize' => !empty($params['filesize']) ? intval($params['filesize']) : 0,
            'width' => !empty($params['width']) ? intval($params['width']) : 0,
            'height' => !empty($params['height']) ? intval($params['height']) : 0,
            'attachment' => !empty($params['attachment']) ? htmlspecialchars($params['attachment']) : '',
            'is_priv' => !empty($params['is_priv']) ? intval($params['is_priv']) : self::IS_PRIV_0,
            'description' => !empty($params['description']) ? htmlspecialchars($params['description']) : '',
            'remark' => !empty($params['remark']) ? htmlspecialchars($params['remark']) : '',
            'update_time' => time(),
        );
        $this->db->autoExecute($this->tableName(), $data, 'UPDATE', sprintf('user_id=%d AND type=%d', intval($params['user_id']), intval($params['type'])));
        return $this->db->affected_rows() >= 1 ? true : false;
    }
}

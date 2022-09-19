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
class UserTagRelationModel extends BaseModel
{
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
    public function set_tags($uid, $tag_ids) {
        if (!$uid || !$tag_ids) {
            return true;
        }
        foreach($tag_ids as $tag_id) {
            $rows[] = "($uid, $tag_id)";
        }
        $sql = "REPLACE INTO `firstp2p_user_tag_relation`(`uid`, `tag_id`) VALUES ". implode(',', $rows);
        return $this->execute($sql);
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
    public function get_tags_by_uid($uid, $slave = true) {
        if ($slave === true) {
            return $this->findAllViaSlave('uid=:uid', true, '`tag_id`', array(":uid" => $uid));
        }

        return $this->findAll('uid=:uid', true, '`tag_id`', array(":uid" => $uid));
    }

    /**
     * 获取用户tag的创建时间和tag_id
     * @param $uid
     * @param bool $slave
     */
    public function get_tags_by_uid_ctime($uid, $slave = true){
        if ($slave === true) {
            return $this->findAllViaSlave('uid=:uid', true, '`tag_id`,`created_at`', array(":uid" => $uid));
        }

        return $this->findAll('uid=:uid', true, '`tag_id`,`created_at`', array(":uid" => $uid));
    }
    /**
    +------------------------------------------------------
     * @desc 删除用户标签
     +------------------------------------------------------
     * @param int $uid 用户id
     +------------------------------------------------------
     * @param int $tag_ids 标签ids
     +------------------------------------------------------
     * @return 执行结果
     +------------------------------------------------------
     */
    public function remove_relations($uid, $tag_ids) {
        if (!$uid || !$tag_ids) {
            return true;
        }
        $sql = 'DELETE FROM `firstp2p_user_tag_relation` WHERE `uid`=%s AND `tag_id` IN (%s)';
        return $this->execute(sprintf($sql, $uid, implode(',', $tag_ids)));

    }

}

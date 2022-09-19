<?php
/**
 * UserTagService.php
 * @date 2014-10-15
 * @author wangshijie@ucfgroup.com
 */

namespace core\service;

use core\dao\UserTagModel;
use core\dao\UserTagRelationModel;

/**
 * Class UserTagService
 * @package core\service
 */
class UserTagService extends BaseService {

    /**
     * @desc 根据用户uid获取tags信息
     *
     * @param $union_id
     *
     * @return array
     */
    public function getTags($uid, $slave = true) {

        $uid = intval($uid);
        $data = array();
        $relations = UserTagRelationModel::instance()->get_tags_by_uid($uid, $slave);
        if (empty($relations)) {
            return $data;
        }

        $tagids = array();
        foreach ($relations as $row) {
            $tagids[] = $row['tag_id'];
        }
        $tags = UserTagModel::instance()->get_tags_by_ids($tagids);
        foreach ($tags as $row) {
            $data[$row['id']] = array('tag_id' => $row['id'], 'tag_name' => $row['name'], 'const_name' => $row['const_name']);
        }
        return $data;
    }

    /**
     * 从库对应方法
     */
    public function getTagsViaSlave($uid)
    {
        return $this->getTags($uid, true);
    }

    /**
     * 根据tagsid 获取tags信息
     * @param array $tagsids
     */
    public function getBytagsIds($tagids){
        if (empty($tagids) || !is_array($tagids)){
            return array();
        }
        return $tags = UserTagModel::instance()->get_tags_by_ids($tagids);
    }
    /**
     * @desc 插入或者更新追踪信息
     *
     * @param int $uid
     * @param array $data
     */
    public function setTags($uid, $tagids) {

        $relations = UserTagRelationModel::instance()->get_tags_by_uid($uid);
        $delete_arr = array();
        $tagids = array_flip($tagids);
        foreach ($relations as $row) {
            if (isset($tagids[$row['tag_id']])) {
                unset($tagids[$row['tag_id']]);
                continue;
            }
            $delete_arr[] = $row['tag_id'];
        }

        $this->delUserTags($uid, $delete_arr);

        if (!$tagids) {
            return true;
        }
        return UserTagRelationModel::instance()->set_tags($uid, array_keys($tagids));
    }

    /**
     * @param $uid
     * @param $const_names
     */
    public function setTagsByConstNames($uid, $const_names) {
        $tags = UserTagModel::instance()->get_tags_by_const_names($const_names);
        $tagids = array();

        foreach ($tags as $row) {
            $tagids[$row['id']] = $row['id'];
        }
        return $this->setTags($uid, $tagids);
    }

    /**
     * 返回标签列表
     * @param int $status
     * @return array
     */
    public function lists($status = 1) {
        return UserTagModel::instance()->findAll('status=:status', true, 'id, const_name, name, status', array(':status' => $status));
    }

    /**
     * 删除用户标签
     * @param $uid 用户id
     * @param $tag_ids 标签ids
     * @return boolean 执行结果
     */
    /* public function delUserTags($uid, $tag_ids) {
        return UserTagRelationModel::instance()->remove_relations($uid, $tag_ids);
    } */

    /**
     * 根据标签ID获取用户列表
     * @param int $tag_id 标签ID
     * @return array 用户uid列表
     */
    public function getUidsByTagId($tag_id) {
        $condition = 'tag_id=:tag_id';
        $params = array(':tag_id' => $tag_id);
        $result = UserTagRelationModel::instance()->findAll($condition, true, 'uid', $params);
        $uids = array();
        foreach($result as $row) {
            $uids[] = $row['uid'];
        }
        return $uids;
    }

    /**
     * 删除用户标签
     * @param $uid 用户id
     * @param $tag_ids 标签ids
     * @return boolean 执行结果
     */
    public function delUserTags($uid, $tag_ids) {
        $tags = $this->getTags($uid);
        foreach ($tag_ids as $id) {
            if (!isset($tags[$id])) {
                unset($tag_ids[$id]);
            }
        }
        return UserTagRelationModel::instance()->remove_relations($uid, $tag_ids);
    }

    /**
     * 根据const_name删除用户标签
     * @param $uid 用户id
     * @param $const_names 标签名称
     * return boolean
     */
    public function delUserTagsByConstName($uid, $const_names) {
        return $this->delUserTags($uid, $this->getTagIdsByConstName($const_names));
    }

    /**
     * 增加用户标签
     * @param $uid 用户id
     * @param $tag_ids 标签ids
     * @return boolean 执行结果
     */
    public function addUserTags($uid, $tag_ids) {
        $tags = $this->getTags($uid);
        foreach ($tag_ids as $id) {
            if (isset($tags[$id])) {
                unset($tag_ids[$id]);
            }
        }
        return UserTagRelationModel::instance()->set_tags($uid, array_keys($tag_ids));
    }

    /**
     * 根据const_name添加用户标签
     * @param $uid 用户id
     * @param $const_names 标签名称
     * return boolean
     */
    public function addUserTagsByConstName($uid, $const_names) {
        return $this->addUserTags($uid, $this->getTagIdsByConstName($const_names));
    }

    /**
     * 根据const_name获取标签id
     * @param $uid 用户id
     * @param $const_names 标签名称
     * return array
     */
    public function getTagIdsByConstName($const_names) {
        $tags = UserTagModel::instance()->get_tags_by_const_names($const_names);
        $tagids = array();
        foreach ($tags as $row) {
            $tagids[$row['id']] = $row['id'];
        }
        return $tagids;
    }


    /**
     * getTagByConstNameUserId
     * 根据const_name uid 判断用户是否有该标签
     * @param string $tag_name
     * @param int $uid
     * @access public
     * @return bool
     */
    public function getTagByConstNameUserId($tag_name, $uid) {
        $tags = UserTagModel::instance()->get_tags_by_const_names($tag_name);
        if (count($tags)) {
            $tag = array_pop($tags);
            $tag_id = $tag['id'];
            $relations = UserTagRelationModel::instance()->get_tags_by_uid($uid);

            $tagids = array();
            foreach ($relations as $row) {
                if ($tag_id == $row['tag_id']) {
                    return true;
                }
            }
            // 该用户所有 tag都比较完了,也没有找到

        } else {    // 没有该tag
            return false;
        }
        return false;
    }

    /**
     * 获取用户tag的详细信息
     * @param $tag_name
     * @param $uid
     *
     * @return array
     */
    public function getTagByConstNameUserIdRelationInfo($tag_name,$uid){
        $ret = array();
        $tags = UserTagModel::instance()->get_tags_by_const_names($tag_name);
        if (count($tags)) {
            $tag = array_pop($tags);
            $tag_id = $tag['id'];
            $relations = UserTagRelationModel::instance()->get_tags_by_uid_ctime($uid);

            foreach ($relations as $row) {
                if ($tag_id == $row['tag_id']) {
                    return array('tag_id' => $tag_id,'ctime' => $row['created_at']);
                }
            }
            // 该用户所有 tag都比较完了,也没有找到

        } else {    // 没有该tag
            return $ret;
        }
        return $ret;
    }
    /**
     * 给用户打出身tag, 来源于哪个站点
     */
    public function autoAddUserTag($userId, $tagName, $tagDesc = "") {
        // 取从库
        $tag = UserTagModel::instance()->getTagByConstName($tagName);
        if (empty($tag)) {
            try {
                $tagId = UserTagModel::instance()->addTag($tagName, $tagDesc);
            } catch(\Exception $e) {
                // 并发插入失败，证明有了，取一次, 这次走主库
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $tag = UserTagModel::instance()->getTagByConstName($tagName, false);
                    $tagId = $tag['id'];
                }
            }
        } else {
            $tagId = $tag['id'];
        }
        return UserTagRelationModel::instance()->set_tags($userId, array($tagId));
    }

    /**
     * 获取存管静态白名单tag ID 
     */
    public function getSupervisionStaticWhitelistTagId() {
        $const_name = 'SUPERVISION_STATIC_WHITELIST';
        $tag = UserTagModel::instance()->getTagByConstName($const_name, false);
        $tag_id = $tag['id'];
        return $tag_id;
    }

    /**
     * 获取用户现有标签ID数组
     * @param int $userId
     * @return array
     */
    public function getUserCurrentTags($userId = 0) {
        $currentTags = UserTagRelationModel::instance()->get_tags_by_uid($userId);
        $userCurrentTags = array();
        foreach ($currentTags as $k => $v) {
            $userCurrentTags[] = $v['tag_id'];
        }
        return $userCurrentTags;
    }

    /**
     * 添加存管静态白名单tag
     */
    public function addSupervisionStaticWhitelistTag($userId = 0) {
        $userCurrentTags = $this->getUserCurrentTags($userId); 
        //存管白名单标签ID
        $whiteListTagId = $this->getSupervisionStaticWhitelistTagId();
        if(in_array($whiteListTagId, $userCurrentTags)){
            //用户已有白名单标签
            return true;
        }
        $ret = UserTagRelationModel::instance()->set_tags($userId, array($whiteListTagId));
        return $ret;
    }
}

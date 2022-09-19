<?php

namespace core\dao\deal;

use core\dao\tag\TagModel;

use core\dao\BaseModel;

/**
 * TagModel 
 * 
 * @uses BaseModel
 * @package 
 * @version $id$
 * @author zhanglei5 <zhanglei5@group.com> 
 */
class DealTagModel extends BaseModel {

    /**
     * getTagByDealId  根据deal_id找出tagid 及tagname
     * @author zhanglei5 <zhanglei5@group.com> 
     * 
     * @param int $deal_id 
     * @access public
     * @return void
     */
    function getTagByDealId($deal_id,$isSlave=true) {
        $tagModel = new TagModel();
        $tagname = $tagModel->tableName();
        $sql = "select tag.id,tag.tag_name from {$tagname} as tag left join ".$this->tableName()." as dt on dt.tag_id = tag.id where dt.deal_id = ':deal_id'";
        $param[':deal_id'] = $deal_id;
        return $this->findAllBySql($sql,true,$param,$isSlave);
    }

    /**
     * 根据标的ID，获取TAG数据
     * @param int $dealId
     */
    function getTagInfoByDealId($dealId) {
        // 根据标的ID，获取TAGID
        $dealTagList = $this->findAllViaSlave(sprintf('deal_id=%d', $dealId), true, 'tag_id');
        if (empty($dealTagList)) return array();

        $tagIds = array();
        foreach ($dealTagList as $item) {
            $tagIds[$item['tag_id']] = 1;
        }

        // 根据TAGID，获取TAGNAME
        $tagListDb = TagModel::instance()->findAllViaSlave(sprintf('id IN (%s)', join(',', array_keys($tagIds))), true);
        if (empty($tagListDb)) return array();

        $tagList = array();
        foreach ($tagListDb as $tagItem) {
            $tagList[$tagItem['id']] = $tagItem['tag_name'];
        }
        return $tagList;
    }

    /**
     * 根据TAG名称，获取标的ID数组
     * @param string/array $tagName TAG名称或数组
     * @param boolean $isReturnIds 是否整理dealId
     * @return array
     */
    public function getDealIdsByTagName($tagName, $isReturnIds = true) {
        // 获取指定TAG的数据
        $tagModel = new TagModel();
        if (is_array($tagName)) {
            $tagList = $tagModel->getTags($tagName);
            if (empty($tagList)) {
                return array();
            }
            foreach ($tagList as $tagItem) {
                $tagIds[] = $tagItem['id'];
            }
            $whereParams = 'tag_id IN(:tag_id)';
            $whereValues = array(':tag_id' => join(',', $tagIds));
        }else{
            $tagInfo = $tagModel->getInfoByTagName($tagName);
            if (empty($tagInfo)) {
                return array();
            }
            $whereParams = 'tag_id = :tag_id';
            $whereValues = array(':tag_id' => $tagInfo['id']);
        }
        // 根据tagId，获取符合条件的标的ID
        $dealIdList = $this->findAllViaSlave($whereParams, true, 'deal_id', $whereValues);
        if(empty($dealIdList) || !$isReturnIds) {
            return $dealIdList;
        }
        $dealIds = array();
        foreach ($dealIdList as $dealItem) {
            $dealIds[$dealItem['deal_id']] = 1;
        }
        return array_keys($dealIds);
    }

    function deleteByDealId($deal_id) {
        $sql = "delete from ".$this->tableName()." where deal_id = {$deal_id}";
        return $this->execute($sql);
    }
}

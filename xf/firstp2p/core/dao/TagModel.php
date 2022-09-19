<?php

namespace core\dao;
/**
 * TagModel 
 * TagModel 
 * 
 * @uses BaseModel
 * @package 
 * @version $id$
 * @author zhanglei5 <zhanglei5@group.com> 
 */
class TagModel extends BaseModel {
    /**
     * getTags 
     * @author zhanglei5 <zhanglei5@group.com> 
     * 
     * @param array $tags 
     * @access public
     * @return void
     */
    public function getTags($tags){
        if (is_array($tags)) {
            $condition = implode("','",$tags);
            $sql = "SELECT `id`,`tag_name` FROM ".$this->tableName()." WHERE BINARY tag_name IN ('{$condition}')";
            return $this->findAllBySql($sql);
        } else {
            return false;
        }
    }

    public function insertData($data){
        if(empty($data)){
            return false;
        }
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 获取指定TAG的数据
     * @param string $tagName
     * @return boolean
     */
    public function getInfoByTagName($tagName) {
        if (empty($tagName)) return array();
        return $this->findByViaSlave('tag_name = \':tag_name\'', 'id,tag_name', array(':tag_name' => $tagName));
    }

    /**
     * 根据TAG名称数组，批量获取TAGID
     * @param array $tags
     * @return array
     */
    public function getTagIdsByNameList($tags) {
        $tagIds = array();
        $tagIdList = $this->getTags($tags);
        if (empty($tagIdList)) return $tagIds;
        if (!empty($tagIdList)) {
            foreach ($tagIdList as $item) {
                $tagIds['tagIds'][] = $item['id'];
                $tagIds['tagList'][$item['id']] = $item['tag_name'];
            }
        }
        return $tagIds;
    }
}
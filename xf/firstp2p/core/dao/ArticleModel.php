<?php
/**
 * ArticleModel.php
 * 
 * @date 2014-04-10
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;


/**
 * 文章
 *
 * Class ArticleModel
 * @package core\dao
 */
class ArticleModel extends BaseModel{

    /**
     * 根据文章ID获取文章信息
     *
     * @param $id
     * @return \libs\db\Model
     */
    public function getArticleById($id){
        $sql = "select a.*,ac.type_id from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where a.id = ':id' and a.is_effect = 1 and a.is_delete = 0";
        //$sql = sprintf($sql,$this->escape($id));
        return $this->findBySql($sql, array(':id' => $id));
    }
    /**
     * 根据文章ID获取文章信息
     *
     * @param $id
     * @return \libs\db\Model
     */
    public function getDefaultByTypeAndSite($type,$site){
        $sql = "select a.id from %s as a left join ".DB_PREFIX."article_cate as ac on a.cate_id = ac.id where ac.type_id = %d and a.site_id = %d order by a.sort asc";
        $sql = sprintf($sql,$this->tableName(),$type,$site);
        
        $result = $this->findBySql($sql);
        return $result['id'];
    }
    /**
     * 根据文章分类获取文章列表
     *
     * @param $cate_id
     * @param $isPriority 是否优先展示 默认否
     * @return \libs\db\Model
     */
    public function getListByCateId($cate_id, $count = false, $makePage = false, $page = 1, $pageSize = 10, $order = '', $isPriority = false)
    {
        $condition = sprintf("`cate_id` = '%d' AND `is_delete` = 0 AND `is_effect` = 1 ",$this->escape($cate_id));
        if ($isPriority) {
            $condition .= ' and `sort` = 0';
        }
        if ($order == 'update_time') {
            $condition .= ' order by `update_time` desc ';
        }
        $limit = '';
        if($makePage == true){
            $page = intval($page) >= 1 ? intval($page) : 1;
            $pageSize = intval($pageSize) >= 0 ? intval($pageSize) : 10;
            $limit = sprintf(" LIMIT %d, %d", ($page - 1) * $pageSize, $pageSize);
        }
        if ($count === true) {
            $res['count'] = $this->count($condition);
            $res['list'] = $this->findAll($condition . $limit);
            return $res;
        }
        return $this->findAll($condition . $limit);
    }
    /**
     * 根据文章分类获取文章列表
     *
     * @param $cate_id
     * @param $site_id
     * @return \libs\db\Model
     */
    public function getListByCateIdAndSiteId($cate_id,$site_id){
        $condition = sprintf("`cate_id` = '%d' and `site_id` = '%d' and `is_delete` = 0 and `is_effect` = 1 "
                    ,$this->escape($cate_id),$this->escape($site_id));
        $order = 'order by `sort` desc';
        return $this->findAll($condition.$order);
    }
    /**
     * 根据文章别名获取文章列表
     *
     * @param $uname
     * @param $site_id
     * @return \libs\db\Model
     */
    public function getArticleByUnameAndSite($uname,$site_id){
        $condition = sprintf("`uname` = '%s' and `site_id` = '%d' and `is_delete` = 0 and `is_effect` = 1 "
                    ,$this->escape($uname),$this->escape($site_id));
        return $this->findBy($condition);
    }
    /**
     * 根据ID更新文章点击量
     *
     * @param $id
     * @return model
     */
    public function increaseClick($id){
        $sql = sprintf("update " . DB_PREFIX . "article set click_count = click_count + 1 where id = %d ",$this->escape($id));
        return $this->db->query($sql);
    }

    public function increaseCount($id, $field)
    {
        $sql = sprintf("update " . DB_PREFIX . "article set `%s` = `%s` + 1 where `id` = %d ", $this->escape($field), $this->escape($field), $this->escape($id));
        return $this->db->query($sql);
    }
}

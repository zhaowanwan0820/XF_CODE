<?php
/**
 * ArticleModel.php
 *
 * @date 2018-7-25
 * @author sunxuefeng <sunxuefeng@ucfgroup.com>
 */

namespace core\dao\article;

use core\dao\BaseModel;

/**
 * 文章
 *
 * Class ArticleModel
 * @package core\dao
 */
class ArticleModel extends BaseModel{

    /**
     * 根据文章分类获取文章列表
     *
     * @param $cate_id
     * @return \libs\db\Model
     */
    public function getListByCateId($cate_id, $count = false, $makePage = false, $page = 1, $pageSize = 10, $order = '', $asc = ''){
        $condition = sprintf("`cate_id` = '%d' AND `is_delete` = 0 AND `is_effect` = 1 ",$this->escape($cate_id));
        $limit = '';
        if($makePage == true){
            $page = intval($page) >= 1 ? intval($page) : 1;
            $pageSize = intval($pageSize) >= 0 ? intval($pageSize) : 10;
            $limit = sprintf(" LIMIT %d, %d", ($page - 1) * $pageSize, $pageSize);
        }
        if (!empty($order)) {
            $condition .= sprintf(" order by `%s` %s", $this->escape($order), $this->escape($asc));
        }
        if ($count === true) {
            $res['count'] = $this->count($condition);
            $res['list'] = $this->findAll($condition . $limit);
            return $res;
        }
        return $this->findAll($condition . $limit);
    }

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
}


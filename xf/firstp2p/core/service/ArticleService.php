<?php
/**
 * ArticleService.php
 * 
 * @date 2014-04-10
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;


use core\dao\ArticleCateModel;
use core\dao\ArticleModel;

/**
 * 文章信息
 *
 * Class ArticleService
 * @package core\service
 */
class ArticleService extends BaseService
{
    const EN_ROOT = '/en/index';

    /**
     * 根据文章id获取文章信息
     *
     * @param $id
     * @return mixed
     */
    public function getArticle($id){
        return ArticleModel::instance()->find($id);
    }
    /**
     * 根据文章id获取文章信息
     *
     * @param $id
     * @return mixed
     */
    public function getArticleByID($id){
        return ArticleModel::instance()->getArticleById($id);
    }

    /**
     * 根据分类id获取分类信息
     *
     * @param $id
     * @return mixed
     */
    public function getArticleCate($id){
        return ArticleCateModel::instance()->find($id);
    }

    /**
     * 根据分类名称获取分类信息
     */
    public function getArticleCateByTittle($title)
    {
        $title = addslashes($title);
        return ArticleCateModel::instance()->findBy("title='{$title}' AND is_delete=0");
    }

    /**
     * 根据分类id获取文章列表
     *
     * @param $cate_id
     * @return mixed
     */
    public function getArticleListByCateId($cate_id, $count = false, $makePage = false, $page = 1, $pageSize = 10, $order = '', $isPriority = false){
        return ArticleModel::instance()->getListByCateId($cate_id, $count, $makePage, $page, $pageSize, $order, $isPriority);
    }
    /**
     * 根据分类id获取默认文章ID
     *
     * @param $type
     * @param $site
     * @return int id
     */
    public function getDefaultByTypeAndSite($type,$site){
        return ArticleModel::instance()->getDefaultByTypeAndSite($type,$site);
    }
    /**
     * 根据uname获取默认文章
     *
     * @param $type
     * @param $site
     * @return model
     */
    public function getArticleByUnameAndSite($uname,$site=1){
        return ArticleModel::instance()->getArticleByUnameAndSite($uname,$site);
    }
    /**
     * 根据ID更新文章点击量
     *
     * @param $id
     * @return model
     */
    public function increaseClick($id){
        return ArticleModel::instance()->increaseClick($id);
    }

    public function increaseCount($id, $field){
        return ArticleModel::instance()->increaseCount($id, $field);
    }

}

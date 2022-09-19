<?php
/**
 * ArticleService.php
 *
 * @date 2014-04-10
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service\article;


use core\dao\article\ArticleCateModel;
use core\dao\article\ArticleModel;
use core\service\BaseService;

/**
 * 文章信息
 *
 * Class ArticleService
 * @package core\service
 */
class ArticleService extends BaseService{

    /**
     * 根据文章id获取文章信息
     *
     * @param $id
     * @return mixed
     */
    public function getArticle($id){
        $articles = ArticleModel::instance()->find($id);
        return !empty($articles) ? $articles->getRow() : false;
    }

    /**
     * 根据分类id获取分类信息
     *
     * @param $id
     * @return mixed
     */
    public function getArticleCate($id){
        $articleCates = ArticleCateModel::instance()->find($id);
        return !empty($articleCates) ? $articleCates->getRow() : false;
    }

    /**
     * 根据分类id获取文章列表
     *
     * @param $cate_id
     * @return mixed
     */
    public function getArticleListByCateId($cate_id, $count = false, $makePage = false, $page = 1, $pageSize = 10, $order = '', $asc = ''){
        return ArticleModel::instance()->getListByCateId($cate_id, $count, $makePage, $page, $pageSize, $order, $asc);
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
     * 根据uname获取默认文章
     */
    public function getArticleByUnameAndSite($uname,$site){
        return ArticleModel::instance()->getArticleByUnameAndSite($uname,$site);
    }
}

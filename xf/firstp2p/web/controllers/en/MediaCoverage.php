<?php
namespace web\controllers\en;
use core\service\ArticleService;
use web\controllers\BaseAction;
use libs\web\Form;
class MediaCoverage extends BaseAction
{
    const PAGE_SIZE = 5;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int"),
            'page' => array("filter" => "int", "option" => array("optional" => true)),
        );
        if (!$this->form->validate()) {
            return app_redirect(ArticleService::EN_ROOT);
        }
    }

    public function invoke()
    {

        $params = $this->form->data;
        $cateId = "1101";
        $page = intval($params['page']) ?: 0;

        $articles = $this->rpc->local('ArticleService\getArticleListByCateId', array($cateId, true, true, $page, self::PAGE_SIZE, 'update_time'));

        $results = array();
        foreach ($articles['list'] as $article) {
            $result['id'] = $article['id'];
            $result['title'] = $article['title'];
            $result['content'] = strip_tags($article['content']);
            $result['uname'] = $article['uname'];
            if (!empty($article['image_url'])) {
                $result['image_url'] = removeScheme($article['image_url']);
            } else {
                $result['image_url'] = '';
            }

            $result['update_time'] = date('Y-m-d', $article['update_time']);
            $result['seo_keyword'] = $article['seo_keyword'] ?: '';
            $results[] = $result;
        }

        $count = $articles['count'];
        $pageCount = ceil($count/self::PAGE_SIZE);
        $this->tpl->assign('article_list', $results);
        $this->tpl->assign('pagination', pagination(($page == 0) ? 1 : $page,$pageCount,"3",'page='));
        $this->tpl->assign("title", "News");
        $this->template = 'web/views/v3/en/media_coverage.html';
    }
}

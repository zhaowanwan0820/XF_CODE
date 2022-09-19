<?php
namespace web\controllers\en;
use core\service\ArticleService;
use web\controllers\BaseAction;
use libs\web\Form;

class Index extends BaseAction {
    public function init()
    {

    }

    public function invoke()
    {
        $cateId = "1101";
        $page = "1";
        $articles = $this->rpc->local('ArticleService\getArticleListByCateId', array($cateId, true, true, $page, 5, 'update_time'));
        $results = $this->format($articles['list']);

        $priorityArticles = $this->rpc->local('ArticleService\getArticleListByCateId', array($cateId, true, true, $page, 3, 'update_time', 1));
        $priority = $this->format($priorityArticles['list']);
        $last = 3 - $priorityArticles['count'];
        if ($last > 0) {
            $priority = array_merge($priority, array_slice($results, 0, $last));
        }
        
        $this->tpl->assign('article_list', $results);
        $this->tpl->assign('priority_list', $priority);

    }

    public function format(array $articles)
    {
        $results = [];
        if (empty($articles)) {
          return $results;
        }
        foreach ($articles as $article) {
          $result['id'] = $article['id'];
          $result['title'] = $article['title'];
          $result['content'] = strip_tags($article['content']);
          $result['uname'] = $article['uname'];
          $result['image_url'] = $article['image_url'];
          $result['update_time'] = date('Y-m-d', $article['update_time']);
          $result['seo_keyword'] = $article['seo_keyword'] ?: '';
          $results[] = $result;
        }
        return $results;
    }
}

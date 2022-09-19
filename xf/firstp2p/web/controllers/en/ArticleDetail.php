<?php
namespace web\controllers\en;
use core\service\ArticleService;
use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Site;
class ArticleDetail extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'alias' => array("filter" => "string"),
            'type' => array("filter" => "int"),
            'id' => array("filter" => "int"),
        );
        if (!$this->form->validate()) {
            return app_redirect(ArticleService::EN_ROOT);
        }
    }
  
    public function invoke()
    {
        $params = $this->form->data;
        $alias = $params['alias'];
        $type = $params['type'];
        $id = $params['id'];
        $siteId = Site::getId();

        if (!empty($alias)) {
            $article = $this->rpc->local('ArticleService\getArticleByUnameAndSite', array($alias, $siteId));
        } else {
            $article = $this->rpc->local('ArticleService\getArticle', array($id));
        }

        if (empty($article)) {
            return app_redirect(ArticleService::EN_ROOT);
        }

        $article['update_time'] = date('Y-m-d', $article['update_time']);
        $this->tpl->assign('article', $article);
        $this->tpl->assign('title', $article["title"]);
        $fileName = humpToUnderLine($alias);
        $this->template = $type == 1 ? 'web/views/v3/en/' .$fileName. '.html' : 'web/views/v3/en/media_detail.html';
    }
}
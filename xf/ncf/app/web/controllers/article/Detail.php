<?php

namespace web\controllers\article;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\article\ArticleService;

class Detail extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "string"),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index", 'index'));
        }
    }

    public function invoke()
    {
        $id = $this->form->data['id'];
        $article = $this->rpc->local('ArticleService\getArticleByID', array($id), 'article');

        if (empty($article)) {
            return app_redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url("index", 'index'));
        }

        $this->tpl->assign("content", $article['content']);

    }

}

<?php
/**
 * @date 2018/12/4
 * @author: yangshuo5@ucfgroup.com
 */

namespace api\controllers\findnews;

use api\controllers\AppBaseAction;
use libs\web\Form;

class ArticleDetail extends AppBaseAction
{
    protected $needAuth = false;

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
        $params = $this->form->data;
        $article = $this->rpc->local('ArticleService\getArticle', array($params['id']), 'article');

        $data = [
            'title'   => $article['title'],
            'content' => $article['content'],
            'date'    => date('Y-m-d H:i:s', ($article['update_time'] + date('Z')))
        ];
        $this->json_data = $data;
    }
}
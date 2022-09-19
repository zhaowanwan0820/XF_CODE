<?php

namespace web\controllers\article;

use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class Show extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'atc_id' => array('filter' => 'reg', 'option' => array('regexp' => '/^\d{1,}$/')),
         );

        if (!$this->form->validate()) {
            return app_redirect(url('index', 'index'));
        }
    }

    public function invoke() {
        /* 允许跨站访问
        if (empty($this->appInfo)) {
            return app_redirect(url('index', 'index'));
        }

        $appId = $this->appInfo['id'];
        */

        $data  = $this->form->data;
        $atcId = intval($data['atc_id']);

        $request = new SimpleRequestBase();
        $request->setParamArray(array('id' => $atcId));
        $response = $GLOBALS['openbackRpc']->callByObject(array(
            'service' => 'NCFGroup\Open\Services\OpenArticle',
            'method' => 'getOneByPKId',
            'args' => $request,
        ));

        $article = $response->data;
        if (empty($article) /*|| $article['appId'] != $appId*/ || 2 != $article['status']) { // 2 是下线
            return app_redirect(url('index', 'index'));
        }

        $this->tpl->assign("article", $article);
        $this->template = $article['category'] == 4 ? 'web/views/article/activity.html' : $_GET['is_active'] == 1 ? 'web/views/v3/article/act_show.html' : 'web/views/article/show.html'; //4 为主题活动

        return true;
    }

}

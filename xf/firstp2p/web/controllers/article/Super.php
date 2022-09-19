<?php

namespace web\controllers\article;

use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class Super extends BaseAction {

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
        if (empty($article) || !in_array($article['status'], array(1,2)) || empty($article['content']) ) {
            return app_redirect(url('index', 'index'));
        }
        $article["content"] = explode(',',$article["content"]);
        $this->tpl->assign("article", $article);

        // 可投资标的列表
        $siteId = $this->getSiteId();
        $newUserDealsList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('NewUserPageService\getNewUserDeals', array($siteId)), 30);
        $this->tpl->assign("newUserDealsList", $newUserDealsList);

        // $this->template = $article['category'] == 4 ? 'web/views/article/activity.html' : $_GET['is_active'] == 1 ? 'web/views/v3/article/act_show.html' : 'web/views/article/show.html'; //4 为主题活动
        $this->template = 'web/views/v3/article/super.html';
        return true;
    }

}

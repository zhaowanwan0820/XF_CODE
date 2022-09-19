<?php

/**
 * Index.php
 *
 * @date 2014-04-17
 * @author 杨庆 <yangqing@ucfgroup.com>
 */

namespace web\controllers\article;

use libs\web\Form;
use web\controllers\BaseAction;

class Index extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "string"),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index", 'index'));
        }
    }

    public function invoke() {
        if (empty($this->form->data['id'])){
            return app_redirect(APP_ROOT . "/");
        }
        if (is_numeric($this->form->data['id'])){
            $id = intval($this->form->data['id']);
            $article = $this->rpc->local('ArticleService\getArticleById', array($id));
        }else{
            $uname = addslashes($this->form->data['id']);
            $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][app_conf('APP_SITE')];
            $article = $this->rpc->local('ArticleService\getArticleByUnameAndSite', array($uname, $site_id));
        }

        if (empty($article)) {
            return app_redirect(url("index"));
        }

        if ($GLOBALS ['user_info']) {
            $ret = $this->rpc->local('CouponService\getOneUserCoupon', array($GLOBALS['user_info']['id']));
            if(!empty($ret)){
                $coupon = $ret['short_alias'];
            }
            \es_cookie::set('ARTICLE_LOGIN_COUPON',$coupon);
        }
        if($article['cate_id'] == '155' || $article['cate_id'] == '215'){
            $this->tpl->assign("article", $article);
            $seo_title = $article['seo_title'] != '' ? $article['seo_title'] : $article['title'];
            $this->tpl->assign("page_title", $seo_title);
            $seo_keyword = $article['seo_keyword'] != '' ? $article['seo_keyword'] : $article['title'];
            $this->tpl->assign("page_keyword", $seo_keyword . ",");
            $seo_description = $article['seo_description'] != '' ? $article['seo_description'] : $article['title'];
            $this->tpl->assign("page_description", $seo_description . ",");

        }elseif($article['cate_id'] == '156'){
            $this->tpl->assign("article", $article);
            $seo_title = $article['seo_title'] != '' ? $article['seo_title'] : $article['title'];
            $this->tpl->assign("page_title", $seo_title);
            $seo_keyword = $article['seo_keyword'] != '' ? $article['seo_keyword'] : $article['title'];
            $this->tpl->assign("page_keyword", $seo_keyword . ",");
            $seo_description = $article['seo_description'] != '' ? $article['seo_description'] : $article['title'];
            $this->tpl->assign("page_description", $seo_description . ",");

            $this->tpl->display("web/views/article/index_body.html");
            $this->template=null;
            return false;
        }else{
            return app_redirect(url("index", 'index'));
        }
    }

}

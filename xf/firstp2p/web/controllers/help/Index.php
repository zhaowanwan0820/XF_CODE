<?php

/**
 * Index.php
 *
 * @date 2014-04-17
 * @author 杨庆 <yangqing@ucfgroup.com>
 */

namespace web\controllers\help;

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
        
        // help 没有首页
        if (empty($this->form->data['id'])){
            return app_redirect(APP_ROOT . "/");
        }
        $id = 0;
        $uname = '';
        if (is_numeric($this->form->data['id'])){
            $id = intval($this->form->data['id']);
        }else{
            $uname = addslashes($this->form->data['id']);
        }
        $this->tpl->caching = false;
        $this->tpl->cache_lifetime = 600;  //help缓存10分钟
        $cache_id = md5(MODULE_NAME . ACTION_NAME . trim($uname) . $id);
        if (!$this->tpl->is_cached($this->template, $cache_id)) {
            $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][app_conf('APP_SITE')];
            $type_id = 1;
            $catelist = $this->rpc->local('ArticleCateService\getHelpListByTypeAndSite', array($type_id, $site_id));
            if ($id == 0 && $uname == '') {
                $id = $this->rpc->local('ArticleService\getDefaultByTypeAndSite', array($type_id, $site_id));
                
            } elseif ($id == 0 && $uname != '') {
                $ret = $this->rpc->local('ArticleService\getArticleByUnameAndSite', array($uname, $site_id));
                $id = $ret['id'];
            }
            if(!empty($id)){
                $article = $this->rpc->local('ArticleService\getArticleById', array($id));
            }

            if (!$article || $article['type_id'] != 1) {
                return app_redirect(APP_ROOT . "/");
            } else {
                if ($article['rel_url'] != '') {
                    if (!preg_match("/http:\/\//i", $article['rel_url'])) {
                        if (substr($article['rel_url'], 0, 2) == 'u:') {
                            return app_redirect(parse_url_tag($article['rel_url']));
                        } else
                            return app_redirect(APP_ROOT . "/" . $article['rel_url']);
                    } else
                        return app_redirect($article['rel_url']);
                }
            }
            $this->tpl->assign("cate_list", $catelist);

            if (empty($article)) {
                return app_redirect(url("index"));
            }
            $article['content'] = str_replace("http:".app_conf('STATIC_HOST'),app_conf('STATIC_HOST'),$article['content']);
            $this->tpl->assign("article", $article);
            $seo_title = $article['seo_title'] != '' ? $article['seo_title'] : $article['title'];
            $this->tpl->assign("page_title", $seo_title);
            $seo_keyword = $article['seo_keyword'] != '' ? $article['seo_keyword'] : $article['title'];
            $this->tpl->assign("page_keyword", $seo_keyword . ",");
            $seo_description = $article['seo_description'] != '' ? $article['seo_description'] : $article['title'];
            $this->tpl->assign("page_description", $seo_description . ",");
        }
        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        if (APP_SITE !== 'firstp2p') {
            $this->template = str_replace('web/views', 'web/views/fenzhan', $this->template);
        } else if (in_array($class_path, $this->v2tpls)) {
            $this->template = str_replace('web/views', 'web/views/v2', $this->template);
        }
        $this->tpl->display($this->template,$cache_id);
        $this->template = null;
    }

}

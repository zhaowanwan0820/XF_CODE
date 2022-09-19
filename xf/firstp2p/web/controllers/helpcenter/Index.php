<?php

/**
 * Index.php
 *
 * @date 2014-05-07
 * @author xiaoan 
 */

namespace web\controllers\helpcenter;

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
        $id = 0;
        $uname = '';
        if (is_numeric($this->form->data['id'])){
            $id = intval($this->form->data['id']);
        }else{
            $uname = addslashes($this->form->data['id']);
        }
        $this->tpl->caching = true;
        $this->tpl->cache_lifetime = 600;  //help缓存10分钟 

        $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][app_conf('APP_SITE')];
        if(empty($site_id))$site_id = 1;
        //$hc_list = $this->rpc->local('ArticleCateService\getByPidAndSiteIdList', array(13, $site_id));
        $hc_list = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ArticleCateService\getByPidAndSiteIdList', array(13, $site_id)), $this->tpl->cache_lifetime);
        $this->tpl->assign("hc_list", $hc_list);
       if($id==0 && empty($uname)){
           $this->tpl->assign("max_money", app_conf("MAX_BORROW_QUOTA")/10000);
            $this->template = "web/views/helpcenter/index.html";
            $class_path = strtolower(str_replace('\\', '/', get_class($this)));
            if (APP_SITE !== 'firstp2p') {
                $this->template = str_replace('web/views', 'web/views/fenzhan', $this->template);
            } else if (in_array($class_path, $this->v2tpls)) {
                $this->template = str_replace('web/views', 'web/views/v2', $this->template);
            }
            $this->tpl->display($this->template);
        }else{
            $cache_id = md5(MODULE_NAME . ACTION_NAME . trim($uname) . $id . $GLOBALS['deal_city']['id']);
            if (!$this->tpl->is_cached("article.html", $cache_id)) {
                if ($id == 0 && $uname == '') {
                    $id = $this->rpc->local('ArticleService\getDefaultByTypeAndSite', array(15, $site_id));
                } elseif ($id == 0 && $uname != '') {
                    $ret = $this->rpc->local('ArticleService\getArticleByUnameAndSite', array($uname, $site_id));
                    $id = $ret['id'];
                }
                $article = $this->rpc->local('ArticleService\getArticleById', array($id));
                
                if(!$article)
                {
                    return app_redirect(APP_ROOT."/");
                }else{
                    if($article['rel_url']!='')
                    {
                        if(!preg_match ("/http:\/\//i", $article['rel_url']))
                        {
                            if(substr($article['rel_url'],0,2)=='u:')
                            {
                                return app_redirect(parse_url_tag($article['rel_url']));
                            }
                            else
                                return app_redirect(APP_ROOT."/".$article['rel_url']);
                        }
                        else{
                            return app_redirect($article['rel_url']);
                        }
                    }
                    $article['title'] = $article['title'];
                    $article['content'] = $article['content'];
                    $this->tpl->assign("article",$article);
                    $seo_title = $article['seo_title']!=''?$article['seo_title']:$article['title'];
                    $this->tpl->assign("page_title",$seo_title);
                    $seo_keyword = $article['seo_keyword']!=''?$article['seo_keyword']:$article['title'];
                    $this->tpl->assign("page_keyword",$seo_keyword.",");
                    $seo_description = $article['seo_description']!=''?$article['seo_description']:$article['title'];
                    $this->tpl->assign("page_description",$seo_description.",");
                }
            }
            $this->template = "web/views/helpcenter/article.html";
            $class_path = strtolower(str_replace('\\', '/', get_class($this)));
            if (APP_SITE !== 'firstp2p') {
                $this->template = str_replace('web/views', 'web/views/fenzhan', $this->template);
            } else if (in_array($class_path, $this->v2tpls)) {
                $this->template = str_replace('web/views', 'web/views/v2', $this->template);
            }
            $this->tpl->display($this->template,$cache_id);
        }
        $this->template = null;
    }

}

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

    private $isPhCollege = false;
    const PuHui_College_Cate_Id = 1500;
    const Page_Size = 10;
    private static $puhuiColleges = array(1501, 1502, 1503);

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "string"),
            'page_id' => array("filter" => "int", "option" => array("optional" => true)),
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
        $cache_id = md5( get_class($this) . trim($uname) . $id);
        if (!$this->tpl->is_cached($this->template, $cache_id)) {
            $site_id = \libs\utils\Site::getId();
            $type_id = 1;
            $catelist = $this->rpc->local('ArticleCateService\getHelpListByTypeAndSite', array($type_id, $site_id), 'article');
            if ($id == 0 && $uname == '') {
                $id = $this->rpc->local('ArticleService\getDefaultByTypeAndSite', array($type_id, $site_id), 'article');
                
            } elseif ($id == 0 && $uname != '') {
                $ret = $this->rpc->local('ArticleService\getArticleByUnameAndSite', array($uname, $site_id), 'article');
                $id = $ret['id'];
            }
            if(!empty($id)){
                if (in_array($id, self::$puhuiColleges)) {
                    $page = intval($this->form->data['page_id']) ?: 0;
                    $article = $this->rpc->local('ArticleService\getArticleListByCateId', array($id, true, true, $page, self::Page_Size, 'update_time', 'desc'), 'article');
                    $count = $article['count'];
                    if (isset($article['list']) && is_array($article['list'])) {
                        $article = $article['list'];
                        $articles = [];
                        foreach ($article as $key => $value) {
                            $temp['id'] = $value['id'];
                            $temp['title'] = $value['title'];
                            $temp['content'] = $value['content'];
                            $temp['cate_id'] = $value['cate_id'];
                            $temp['url'] = url("shop","help",array("id" => $value['id']));
                            $temp['date'] = date('Y-m-d h:i:s', $value['update_time']);
                            $temp['image_url'] = $value['image_url'];
                            $articles[] = $temp;
                        }
                    }
                    $cate = $this->rpc->local('ArticleService\getArticleCate', array($id), 'article');
                    $pageCount = ceil($count/self::Page_Size);
                    $this->tpl->assign("page_count", $pageCount);
                    $this->tpl->assign("cate", $cate);
                    $this->tpl->assign("cate_id", $id);
                    $this->tpl->assign("is_ph_college", 1);
                    $this->tpl->assign("articles", $articles);
                    if ($this->is_ajax()) {
                        $ret = [];
                        $ret['page_id'] = $page;
                        $ret['article'] = $articles;
                        echo json_encode(['errno' => 0, 'result' => $ret]);
                        exit;
                    }
                } else {
                    $article = $this->rpc->local('ArticleService\getArticleById', array($id), 'article');
                    if (in_array($article['cate_id'], self::$puhuiColleges)) {
                        $this->isPhCollege = true;
                    }
                }
            }

            if (!$article && $article['type_id'] != 1) {
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
            $puhuiCollege = $this->rpc->local('ArticleCateService\getPhCateList', array(self::PuHui_College_Cate_Id), 'article');
            array_push($catelist, $puhuiCollege);
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
        } else if ($this->isPhCollege == true) {
            $articleNav = $puhuiCollege['sub'];
            if (!empty($article) && $article['cate_id'] == 1502) {
                $articleNav = $articleNav[0];
                unset($articleNav['sub'][0]);
            } elseif ($article['cate_id'] == 1503) {
                $articleNav = $articleNav[1];
            }
            $article['update_time'] = date('Y-m-d h:i:s', $article['update_time']);
            $this->tpl->assign("article", $article);
            $this->tpl->assign("article_nav", $articleNav);
            $this->template = 'web/views/article/index_ph_class.html';
        }
        $this->tpl->display($this->template,$cache_id);
        $this->template = null;
    }

    public function is_ajax()
    {
        return (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") ? true : false;
    }
}

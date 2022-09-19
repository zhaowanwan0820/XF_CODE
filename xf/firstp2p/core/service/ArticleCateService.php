<?php

/**
 * ArticleCateService.php
 * 
 * @date 2014-04-10
 * @author 杨庆 <yangqing@ucfgroup.com>
 */

namespace core\service;

use core\dao\ArticleCateModel;
use core\dao\ArticleModel;
use libs\web\Url;

/**
 * 文章分类信息
 *
 * Class ArticleCateService
 * @package core\service
 */
class ArticleCateService extends BaseService {

    /**
     * 根据分类类型获取文章信息
     *
     * @param int $type 分类类型
     * @param int $site 所属站点
     * @return mixed
     */
    public function getHelpListByTypeAndSite($type, $site) {
        $catelist = ArticleCateModel::instance()->getListByTypeAndSite($type, $site);
        foreach ($catelist as $key_1 => $cate) {
            $articlelist = ArticleModel::instance()->getListByCateIdAndSiteId($cate['id'], $site);
            foreach ($articlelist as $key_2 => $article) {
                if ($article['rel_url'] != '') {
                    if (!preg_match("/http:\/\//i", $article['rel_url'])) {
                        if (substr($article['rel_url'], 0, 2) == 'u:') {
                            $articlelist[$key_2]['url'] = parse_url_tag($article['rel_url']);
                        } else
                            $articlelist[$key_2]['url'] = APP_ROOT . "/" . $article['rel_url'];
                    } else
                        $articlelist[$key_2]['url'] = $article['rel_url'];

                    $articlelist[$key_2]['new'] = 1;
                }
                else {
                    if ($article['uname'] != '')
                        $hurl = url("shop","help",array("id"=>$article['uname']));
                    else
                        $hurl = url("shop","help",array("id"=>$article['id']));
                    $articlelist[$key_2]['url'] = $hurl;
                }
                $articlelist[$key_2]['title'] = $this->_replace_site_text($article['title']);
            }
            $catelist[$key_1]['sub'] = $articlelist;
        }
        return $catelist;
    }
    
    /**
     * 根据父类id和站点id获取数据
     * @param unknown $str
     * @return unknown|mixed
     */
   public function getByPidAndSiteIdList($pid,$siteId){
       if (!is_numeric($pid) || !is_numeric($siteId)) return false;
       $hc_list = ArticleCateModel::instance()->getByPidAndSiteIdList($pid,$siteId);
       foreach ($hc_list as $k=>$v) {
           $help_cate_list = ArticleModel::instance()->getListByCateIdAndSiteId($v['id'], $siteId);
            foreach($help_cate_list as $kk=>$vv)
            {
               if($vv['rel_url']!='')
               {
                   if(!preg_match ("/http:\/\//i", $vv['rel_url']))
                   {
                   if(substr($vv['rel_url'],0,2)=='u:')
                   {
                       $help_cate_list[$kk]['url'] = parse_url_tag($vv['rel_url']);
                   }
                   else
                       $help_cate_list[$kk]['url'] = APP_ROOT."/".$vv['rel_url'];
           }
           else
               $help_cate_list[$kk]['url'] = $vv['rel_url'];
       
           $help_cate_list[$kk]['new'] = 1;
           }
           else
           {
           if($vv['uname']!=''){
               $hurl = url("shop","helpcenter",array("id"=>$vv['uname']));
           }else
               $hurl = url("shop","helpcenter",array("id"=>$vv['id']));
               $help_cate_list[$kk]['url'] = $hurl;
           }
               $help_cate_list[$kk]['title'] = $this->_replace_site_text($vv['title']);
           }
           $hc_list[$k]['sub'] = $help_cate_list;
       }
       
       return $hc_list;
   }
    private function _replace_site_text($str) {
        if (empty($str)) {
            return $str;
        }
    //    $site_domain = $GLOBALS['sys_config']['site_domain']['TPL_SITE_DIR'];
        return str_ireplace('网信理财', app_conf('SHOP_TITLE'), $str);
    }

}

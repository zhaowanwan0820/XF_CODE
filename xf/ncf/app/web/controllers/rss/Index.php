<?php
/**
 * Index.php
 * 新闻类rss基本 控制器入口
 * 
 * @date 2014-04-01
 * @author yangqing <yangqing@ucfgroup.com>
 */

namespace web\controllers\rss;

use libs\web\Form;
use web\controllers\BaseAction;

class Index extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'cate' => array('filter' => 'int'),
            'cat' => array('filter' => 'int'),
            'c' => array('filter' => 'string'),//缩写
            'limit' => array('filter' => 'int'),
            'page' => array('filter' => 'int'),
            'site_id' => array('filter' => 'int'), // 站点id
            'fz' => array('filter' => 'int'),   //是否某个分站, 读取deal_allow_site 所配置的所有site_id
            'output' => array('filter' => 'string'),   //output = json
        );
        if (!$this->form->validate()) {
            return false;
        }
    }

    public function invoke() {
        $cate = $this->form->data['cate'];
        $cate = $cate>0 ? $cate : $this->form->data['cat'];
        $page = $this->form->data['page'];
        $limit = $this->form->data['limit'];
        $cate = intval($cate);
        $c = $this->form->data['c'];
        $output = $this->form->data['output'];
        $site_id = empty($this->form->data['site_id']) ? 0: intval($this->form->data['site_id']);
        //分站站点
        if(!empty($this->appInfo)){
            $site_id = $this->appInfo['id'];
        }

        if($this->form->data['fz']){
            $site_id = get_config_db('DEAL_SITE_ALLOW', $site_id);
            setLog(array('DEAL_ALLOW_SITE' => $site_id));
        }


        if ($site_id === "0"){
            $is_real_site = false;
        }else{
            $is_real_site = true;
        }
        if($c && $cate == 0){
            //$c = $this->rpc->local('DealLoanTypeService\getIdByTag',array($c));
            $c = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealLoanTypeService\getIdByTag', array($c)), 30);
            if($c>0){
                $cate = $c;
            }
        }
        $limit = ($limit>50 || $limit<1)?10:$limit;

        //$deals = $this->rpc->local('RssService\getNewDealList',array($cate,$page,$limit,$site_id,$is_real_site));
        if($output == 'json'){
        //json 输出
            //$deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('RssService\getNewDealList', array($cate,$page,$limit,$site_id,$is_real_site, true)), 30);
            $deals = [];
            header("Content-Type:application/json;charset=utf-8");
            $json = json_encode($deals);
            echo $json;
            exit;
        }else{
        //default xml
            ///$deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('RssService\getNewDealList', array($cate,$page,$limit,$site_id,$is_real_site, false)), 30);
            $deals = '';
            header("Content-Type:text/xml;charset=utf-8");
            echo $deals;
        }
    }

}

<?php
/**
 * 输入时间当日的还款计划列表
 * @author <pengchanglu@ucfgroup.com>
 **/

namespace web\controllers\news;

use libs\web\Form;
use web\controllers\BaseAction;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Hkgg extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'id'=>array("filter"=>'int'),//时间
            'p'=>array("filter"=>'int'),//pageNo
            'ps'=>array("filter"=>'int'),//pageSize
        );
        $this->form->validate();
    }

    public function invoke() {
        app_redirect('/');
        $data = $this->form->data;
        $pn = intval($data['p']);
        $ps = intval($data['ps']);
        if( $pn <= 0 || $pn > 1000 ){
            $pn = 1;
        }
        if( $ps <= 0 || $ps > 20 ){
            $ps = intval(app_conf("PAGE_SIZE"));
            $ps = 50;
        }
        $realTime = 0;
        if(!empty($data['id'])){
            $realTime = strtotime($data['id']);
        }
        // 如果输入时间大于昨天，那就按照昨天来处理
        $yesterday = strtotime("-1 day");
        if( $realTime >= $yesterday  || $realTime <= 1448899200){
            $realTime = $yesterday;
        }
        /*
        if(empty($realTime)){
            $realTime = strtotime("-1 day");
        }
        $realTime = $realTime+7200;
        */
        $ret = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealRepayService\getDealRepayListDurTimeV2', array($realTime,$ps,$pn)), 3600);
        //$ret = $this->rpc->local('DealRepayService\getDealRepayListDurTimeV2',array($realTime,$ps,$pn));
        foreach($ret['list'] as &$one){
            $one['name'] = $this->removePrefix($one['name']);
        }
        $page = new \Page($ret['count'],$ps);
        $p  =  $page->show();
        $this->tpl->assign('pages',$p);
        $this->tpl->assign('day',date('Y-m-d',$realTime));
        $this->tpl->assign('day_read',date('Y年m月d日',$realTime));
        $this->tpl->assign("list",$ret['list']);
        $this->tpl->assign("inc_file", "web/views/v2/news/hkgg.html");
        $this->template = "web/views/v2/news/frame.html";
    }

    /**
    * 还款公告去掉逗号以前的所有内容
    */
    private function removePrefix($str){
        $str = strtr($str, array('，'=>','));
        $pos = strpos($str,",");
        if(!empty($pos)){
            $str = substr($str, $pos+1);
        }
        return $str;
    }
}

<?php
/**
 * 获取银行网点
 * @author <pengchanglu@ucfgroup.com>
 **/

namespace web\controllers\news;

use libs\web\Form;
use libs\utils\Page;
use web\controllers\BaseAction;
use core\service\repay\DealRepayService;

class Hklist extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'p'=>array("filter"=>'int'),//pageNo
            'ps'=>array("filter"=>'int'),//pageSize
            'ajax'=>array("filter"=>'int'),//pageSize
        );
        $this->form->validate();
    }

    public function invoke() {
        return app_redirect(APP_ROOT."/");
        exit;
        $data = $this->form->data;
        $pn = intval($data['p']);
        $ps = intval($data['ps']);
        if( $pn <= 0 || $pn > 1000 ){
            $pn = 1;
        }
        if( $ps <= 0 || $ps > 20 ){
            $ps = intval(app_conf("PAGE_SIZE"));
        }
        $ajax = empty($data['ajax']) ?0:1;
        if($ajax != 1) {
            $ret = \SiteApp::init()->dataCache->call(new DealRepayService(), 'getRepayDealListV2', array(array($ps,$pn,$this->is_firstp2p)), 3600);
            $page = new \Page($ret['count'],$ps);
            $p  =  $page->show();
            $this->tpl->assign('pages',$p);
            $this->tpl->assign("list",$ret['list']);
            $this->tpl->assign("inc_file", "web/views/news/hklist.html");
            $this->template = "web/views/news/frame.html";
        }else{
            $ret = \SiteApp::init()->dataCache->call(new DealRepayService(), 'getRepayDaysV2', array($ps,$pn,$this->is_firstp2p), 3600);
            $jsonArray = array();
            foreach($ret['list'] as $one) {
                $tmp = array();
                $tmp['title'] = sprintf('%s 还款公告',$one['time_readable']);
                $tmp['url'] = sprintf('/news/hkgg/%s',$one['time']);
                $jsonArray[] = $tmp;
            }
            $this->template = "";
            if(!empty($jsonArray)){
                $this->show_success('', '', 1, 0, '',  $jsonArray);
            }else{
                $this->show_error('', '', 1, 0,'');
            }
        }
    }
}
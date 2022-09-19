<?php
/**
 * 首页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\index;

use web\controllers\BaseAction;
use libs\web\Form;

class Cate extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "cate" => array("filter"=>"int"),
        );
        $this->form->validate();
    }

    public function invoke() {
        $cate = $this->form->data['cate'];
        // 借款列表
        //$deals = $this->rpc->local('DealService\getIndexList', array($cate));
        $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getIndexList', array($cate)), 30);
        //修改首页 列表页面 缓存引起标的状态有问题的情况
        $deals['list'][$cate]['list'] = $this->rpc->local('DealService\UserDealStatusSwitch', array($deals['list'][$cate]['list']));
        $this->tpl->assign("deals_list", $deals['list'][$cate]);
        $this->tpl->display('cate.html');
    }
}

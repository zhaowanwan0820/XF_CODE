<?php
/**
 * 列表页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\deals;

use libs\web\Form;
use web\controllers\BaseAction;

class Test extends BaseAction {

    public function init() {
		$this->form = new Form();
	    $this->form->rules = array(
		    "cate" => array("filter"=>"int"),
		    "p" => array("filter"=>"int"),
		    "type" => array("filter"=>"int"),
		    "field" => array("filter"=>"int"),
	    );
	    $this->form->validate();
    }

    public function invoke() {
	    $data = $this->form->data;
	    $page = intval($data['p']);
	    $cate = intval($data['cate']);

        $deals = $this->rpc->local('DealService\getList', array($cate, $data['type'], $data['field'], $page));

	    //$page = new \Page($deals['count'], $deals['page_size']);

	    $this->tpl->assign("page_title", $GLOBALS['lang']['FINANCIAL_MANAGEMENT']);
	    $this->tpl->assign("deal_list", $deals['list']);
	    $this->tpl->assign("deal_type", $deals['deal_type']);
	    //$this->tpl->assign("p", $page->show(array("cate"=>$deals['cate'])));
	    $this->tpl->assign("cate", $deals['cate']);
	    $this->set_nav("投资列表");
	    $this->tpl->display();
    }
}
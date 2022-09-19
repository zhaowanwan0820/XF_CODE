<?php
/**
 * 基金列表页
 * @author yangqing<yangqing@ucfgroup.com>
 **/

namespace web\controllers\jijin;

use libs\web\Form;
use web\controllers\BaseAction;

//require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Index extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "p" => array("filter"=>"int",'message'=>'参数错误'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $current_page = $page = intval($data['p']);
        $pagesize = 10;
        if($page <= 0){
            $page = 1;
        }
        $page = ($page - 1) * $pagesize;
        $fund_list = $this->rpc->local('FundService\getList', array($page,$pagesize));
        //$page = new \Page($fund_list['count'],$pagesize);
        $this->tpl->assign("page_title", $GLOBALS['lang']['FINANCIAL_MANAGEMENT']);
        $this->tpl->assign("fund_list",$fund_list);
        // $this->tpl->assign("pages", ceil($fund_list['count'] / $pagesize) );
        // $this->tpl->assign("current_page", ($current_page == 0) ? 1 : $current_page);
        $this->set_nav("基金理财");
        $this->tpl->assign("pagination",pagination(($current_page == 0) ? 1 : $current_page, ceil($fund_list['count'] / $pagesize)));
        $this->tpl->display();
    }
}

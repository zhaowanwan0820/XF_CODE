<?php
/**
 * Refund.php
 *
 * @date 2014年4月9日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use libs\utils\Page;
use web\controllers\BaseAction;
use core\service\deal\DealService;
use core\dao\BaseModel;

class Refund extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'status'=>array("filter"=>'int'),
            'p'=>array("filter"=>'int'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $user_info = $GLOBALS ['user_info'];
        $user_id = $user_info['id'];
        $data = $this->form->data;
        $status = $data['status'];
        $page = $data['p']<=0 ? 1 : $data['p'];

        $deal_status = 4;
        if($status == 1){
            $deal_status = 5;
        }

        $dealService = new DealService();
        $result = $dealService->getListByUid($user_id,$deal_status,array(($page-1)*app_conf("PAGE_SIZE"),app_conf("PAGE_SIZE")), false);
        $dealList = $result['list'];

        $this->tpl->assign("status",$status);
        $this->tpl->assign("deal_list",$dealList);

        $page = new Page($result['count'],app_conf("PAGE_SIZE"));//初始化分页对象
        $p  =  $page->show();
        $this->tpl->assign('pages',$p);
        $this->tpl->assign("page_title",$GLOBALS['lang']['UC_DEAL_REFUND']);

    	$this->tpl->assign("inc_file","web/views/account/refund.html");
    	$this->template = "web/views/account/frame.html";
    }
}

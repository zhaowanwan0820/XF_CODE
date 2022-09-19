<?php
/**
 * 合同中心-借款列表
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;
use libs\web\Form;
use core\dao\DealLoanTypeModel;
use web\controllers\BaseAction;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Contract extends BaseAction {

    public function init() {
        \es_session::set('before_login','/account/contract');
        $this->check_login();

        $this->form = new Form('get');
        $this->form->rules = array(
                'p' => array('filter' => 'int'),
                'role' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $isP2p = $this->is_firstp2p;
        $user_id = intval ( $GLOBALS ['user_info'] ['id'] );
        $page_num = $data['p'] <= 0 ? 1 : $data['p'];
        $role = isset($data['role']) ? $data['role']:2;
        $result = $this->rpc->local('ContractNewService\getContDealList', array($user_id, $page_num,10,$role,$isP2p));

        if (false === $result) {
            $this->show_error('访问无效，请正确操作！');
        }

        $page = new \Page ( $result['count'], app_conf ( "PAGE_SIZE" ) );

        //专享标类型
        $zxDealTypeId = $this->rpc->local('DealLoanTypeService\getIdByTag', array(DealLoanTypeModel::TYPE_GLJH));

        if($role > 0){
            $this->tpl->assign ('role',$role);
        }else{
            $this->tpl->assign ('role',2);
        }

        if(isset($result['is_agency'])){
            $this->tpl->assign ( "is_agency", $result['is_agency'] );
        }

        if(isset($result['is_advisory'])){
            $this->tpl->assign ( "is_advisory", $result['is_advisory'] );
        }

        if(isset($result['is_borrow'])){
            $this->tpl->assign ( "is_borrow", $result['is_borrow'] );
        }

        if(isset($result['is_entrust'])){
            $this->tpl->assign ( "is_entrust", $result['is_entrust'] );
        }

        if(isset($result['is_canal'])){
            $this->tpl->assign ( "is_canal", $result['is_canal'] );
        }

        foreach ($result['list'] as $key => $value) {
            $result['list'][$key]['loantype_name'] = p2pTextFilter($value['loantype_name'],$value['deal_type']);
        }
        $this->tpl->assign ( "p", $page_num );
        $this->tpl->assign ( "pages", $page->show () );
        $this->tpl->assign ( "userRole", $result['role'] );
        $this->tpl->assign ( "list", $result['list'] );
        $this->tpl->assign ( "zxDealTypeId", $zxDealTypeId );

        $this->tpl->assign ( "page_title", '我的合同' );
        $this->tpl->assign ( "inc_file", 'web/views/account/contract_deal.html' );

        $this->template = "web/views/account/frame.html";
    }
}


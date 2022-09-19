<?php
/**
 * 合同中心-借款列表
 * 调用普惠接口
 * @date 2018年8月20日21:49:19
 **/
namespace web\controllers\account;

use core\dao\DealModel;
use core\dao\DealLoanTypeModel;
use core\service\UserService;
use core\service\ncfph\AccountService;
use libs\web\Form;
use web\controllers\BaseAction;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Contractph extends BaseAction
{

    public function init()
    {
        \es_session::set('before_login', '/account/contract');
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

    public function invoke()
    {
        $data = $this->form->data;
        $isP2p = true;
        $user_id = intval($GLOBALS ['user_info'] ['id']);
        $page_num = $data['p'] <= 0 ? 1 : $data['p'];
        $role = isset($data['role']) ? $data['role'] : 2;
        $accountServcie = new AccountService();
        $result = $accountServcie->getContractDeals($user_id, $page_num,app_conf("PAGE_SIZE"),$role,$isP2p);
        if (false === $result) {
            $this->show_error('访问无效，请正确操作！');
        }
        //专享标类型
        $zxDealTypeId = $this->rpc->local('DealLoanTypeService\getIdByTag', array(DealLoanTypeModel::TYPE_GLJH));

        $page = new \Page ($result['count'], app_conf("PAGE_SIZE"));
        if ($role > 0) {
            $this->tpl->assign('role', $role);
        } else {
            $this->tpl->assign('role', 2);
        }
        if (isset($result['is_agency'])) {
            $this->tpl->assign("is_agency", $result['is_agency']);
        }
        if (isset($result['is_advisory'])) {
            $this->tpl->assign("is_advisory", $result['is_advisory']);
        }
        if (isset($result['is_borrow'])) {
            $this->tpl->assign("is_borrow", $result['is_borrow']);
        }
        if (isset($result['is_entrust'])) {
            $this->tpl->assign("is_entrust", $result['is_entrust']);
        }
        if (isset($result['is_canal'])) {
            $this->tpl->assign("is_canal", $result['is_canal']);
        }
        $this->tpl->assign("p", $page_num);
        $this->tpl->assign("pages", $page->show());
        $this->tpl->assign("userRole", $result['role']);
        $this->tpl->assign("list", $result['list']);
        $this->tpl->assign("zxDealTypeId", $zxDealTypeId);
        $this->tpl->assign("is_ph", 1); // 普惠站点
        $this->tpl->assign("page_title", '我的合同');
        $this->tpl->assign("inc_file", 'web/views/account/contract_deal_ph.html');

        $this->template = "web/views/account/frame.html";
    }
}


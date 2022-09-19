<?php
/**
 * 调用普惠接口
 * 合同中心-合同列表
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use core\dao\DealLoanTypeModel;
use web\controllers\BaseAction;
use core\service\ncfph\AccountService;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Contlistph extends BaseAction
{

    public function init()
    {
        if (!$this->check_login()) return false;
        $this->form = new Form('get');
        $this->form->rules = array(
            'p' => array('filter' => 'int'),
            'id' => array('filter' => 'int'),
            'role' => array('filter' => 'int'),
            'type' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $role = intval($data['role']);
        $user_id = intval($GLOBALS['user_info']['id']);
        $page_num = $data['p'] <= 0 ? 1 : intval($data['p']);
        $deal_id = intval($data['id']);
        if ($deal_id <= 0) {
            return app_redirect(url("index"));
        }

        //专享标类型
        $zxDealTypeId = $this->rpc->local('DealLoanTypeService\getIdByTag', array(DealLoanTypeModel::TYPE_GLJH));
        $accountServcie = new AccountService();
        $result = $accountServcie->getContractList($deal_id, $user_id, $role, $page_num);

        if (empty($result)) {
            return app_redirect(url("index"));
        }

        $page = new \Page ($result['sign_num'], app_conf("PAGE_SIZE"));

        $this->tpl->assign("p", $result['p']);
        $this->tpl->assign("deal", $result['deal']);
        $this->tpl->assign("role", $result['role']);
        $this->tpl->assign("sign_num", $result['sign_num']);
        $this->tpl->assign("is_agency", $result['is_agency']);
        $this->tpl->assign("is_advisory", $result['is_advisory']);
        $this->tpl->assign("is_borrower", $result['is_borrower']);
        $this->tpl->assign("is_loan", $result['is_loan']);
        $this->tpl->assign("is_have_sign", $result['is_have_sign']);
        $this->tpl->assign("contract", $result['contract']);

        $this->tpl->assign("is_ph", 1); // 普惠站点
        $this->tpl->assign("type", 0); // 网贷
        $this->tpl->assign("pages", $page->show());
        $this->tpl->assign("zxDealTypeId", $zxDealTypeId);
        $this->tpl->assign("page_title", '我的合同');
        $this->tpl->assign("inc_file", 'web/views/v3/account/contract_list_ph.html');
        $this->template = "web/views/account/frame.html";
    }
}


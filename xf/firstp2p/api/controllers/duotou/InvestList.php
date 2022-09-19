<?php
/**
 * InvestList controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-08-01
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use libs\utils\Rpc;
use api\conf\Error;

/**
 * 投资列表
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class InvestList extends DuotouBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                    'filter' => 'required',
                    'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'deal_loan_id' => array(
                    'filter' => 'required',
                    'message' => "deal_loan_id is required"
            ),
            'project_id' => array(
                    'filter' => 'required',
                    'message' => "project_id is required"
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke()
    {
        if (!$this->dtInvoke())
            return false;

        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            return $this->assignError('ERR_GET_USER_FAIL');//获取oauth用户信息失败
        }
        $data = $this->form->data;
        $loanId = intval($data['deal_loan_id']);
        $projectId = intval($data['project_id']);
        $vars = array(
                'loanId' => $loanId,
        );
        $rpc = new Rpc('duotouRpc');
        if(!$rpc){
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $request->setVars($vars);
        $responseMoney = $rpc->go('NCFGroup\Duotou\Services\LoanMapping','getLoanNoMappingMoney',$request);
        if (!$responseMoney) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $vars = array(
                'pageNum' => $page,
                'pageSize' => $pageSize,
                'loanId' => $loanId,
        );
        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanMapping',$request);
        if (!$response) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $this->tpl->assign('project_id',$projectId);
        $this->tpl->assign('deal_loan_id',$loanId);
        $this->tpl->assign('noMappingMoney',number_format($responseMoney['data']['money'], 2));
        $this->tpl->assign('token',$data['token']);
        $this->tpl->assign('count',$response['data']['totalNum']);
    }
    public function _after_invoke() {
        $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }

}

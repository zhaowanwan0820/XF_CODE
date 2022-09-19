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

/**
 * 投资列表
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class InvestList extends DuotouBaseAction
{

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
        $userInfo = $this->user;
        $data = $this->form->data;
        $loanId = intval($data['deal_loan_id']);
        $projectId = intval($data['project_id']);
        $vars = array(
            'loanId' => $loanId,
        );

        $responseMoney = $this->callByObject(array('NCFGroup\Duotou\Services\LoanMapping','getLoanNoMappingMoney',$vars));
        if (!$responseMoney) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $vars = array(
                'pageNum' => $page,
                'pageSize' => $pageSize,
                'loanId' => $loanId,
        );
        $response = $this->callByObject(array('NCFGroup\Duotou\Services\DealLoan','getDealLoanMapping',$vars));
        if (!$response) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $res = array(
            "project_id" => $projectId,
            "deal_loan_id" => $loanId,
            "noMappingMoney" => number_format($responseMoney['data']['money'], 2),
            "count" => $response['data']['totalNum'],
        );
        $this->json_data = $res;
    }

}

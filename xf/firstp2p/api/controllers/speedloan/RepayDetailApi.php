<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;

/**
 * RepayDetailApi
 * 还款明细
 *
 * @uses BaseAction
 * @package default
 */
class RepayDetailApi extends SpeedLoanBaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'loanId' => array('filter'=> 'string'),
            'page' => array('filter'=> 'string'),
            'pageSize' => array('filter'=> 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loanService = new \core\service\speedLoan\LoanService();
        $page = !empty($data['page']) ? intval($data['page']) : 1;
        $pageSize = !empty($data['pageSize']) ? intval($data['pageSize']) : 10;
        // 还款流水记录查询
        $repayList = $loanService->getCreditRepayList($data['loanId'], $page, $pageSize);
        $this->json_data = $repayList;
    }
}

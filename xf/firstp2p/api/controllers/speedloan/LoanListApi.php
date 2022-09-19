<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\Creditloan\Enum\CreditLoanEnum;

/**
 * LoanListApi
 * 待还款列表接口
 *
 * @uses BaseAction
 * @package default
 */
class LoanListApi extends SpeedLoanBaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int'),
            'count' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $page = max(1, intval($data['page']));
        $count = isset($data['count']) ? max(1, intval($data['count'])) : 10;
        $loanService = new \core\service\speedLoan\LoanService();
        $loanList = $loanService->getLoanList($userInfo['id'], $page, $count);
        $this->json_data = $loanList;
    }
}

<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\Creditloan\Enum\CreditLoanEnum;

/**
 * RepayList
 * 待还款列表
 *
 * @uses BaseAction
 * @package default
 */
class RepayList extends SpeedLoanBaseAction
{
    const IS_H5 = true;

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
        $userInfo = $userInfo->getRow();
        $userId = $userInfo['id'];

        $page = max(1, intval($data['page']));
        $count = isset($data['count']) ? max(1, intval($data['count'])) : 10;
        $creditSerivce = new \core\service\speedLoan\LoanService();
        $repayList = $creditSerivce->getCreditWaitingRepayList($userId, $page, $count);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('openTimeStart',str_replace(';', ':', app_conf('SPEED_LOAN_SERVICE_HOUR_START')));
        $this->tpl->assign('openTimeEnd',str_replace(';', ':', app_conf('SPEED_LOAN_SERVICE_HOUR_END')));
        $this->tpl->assign('repayList', $repayList);
        $this->tpl->assign('page', $page);
    }
}

<?php
namespace api\controllers\duotou;

/**
 * 投资前尝试划转余额
 * @author longbo
 */
use libs\web\Form;
use api\controllers\DuotouBaseAction;
use libs\utils\Logger;

class PreBid extends DuotouBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_AUTH_FAIL'
            ),
            'money' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
            ),
         );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if (!$this->dtInvoke()) return false;

        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $money = $data['money'];
        if (bccomp($money, 0, 2) != 1) {
            $this->setErr('ERR_MONEY_FORMAT');
            return false;
        }
        $project = [];
        try {
            $project['report_status'] = 1;
            $result = $this->rpc->local(
                'P2pDealBidService\preBid',
                array($user, $project, $money)
            );
        } catch (\Exception $e) {
            return $this->setErr(-1, $e->getMessage());
        }

        $this->json_data = $result;
    }
}

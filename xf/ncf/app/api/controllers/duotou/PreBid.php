<?php
namespace api\controllers\duotou;

/**
 * 投资前尝试划转余额
 * @author longbo
 */
use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\deal\P2pDealBidService;
use core\service\duotou\DtBidService;

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
            'activity_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                 ),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->user;
        $activityId = intval($data['activity_id']);

        $money = $data['money'];
        if (bccomp($money, 0, 2) != 1) {
            $this->setErr('ERR_MONEY_FORMAT');
        }

        try {
            $dtBidService = new DtBidService();
            $user['canUseBonus'] = $dtBidService->canDtUseBonus($activityId, $user['id']);
            $oP2pDealBidService = new P2pDealBidService();
            $result = $oP2pDealBidService->preBid($user, array(), $money);
        } catch (\Exception $e) {
            return $this->setErr(-1, $e->getMessage());
        }

        $this->json_data = $result;
    }
}

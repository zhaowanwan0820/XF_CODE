<?php
namespace api\controllers\deal;

/**
 * 投资前尝试划转余额 
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class PreBid extends AppBaseAction
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
            'id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'source_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'coupon' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $deal_id = $data['id'];
        $money = $data['money'];
        if (bccomp($money, 0, 2) != 1) {
            $this->setErr('ERR_MONEY_FORMAT');
            return false;
        }
        if (deal_belong_current_site($deal_id)) {
            $dealInfo = $this->rpc->local('DealService\getDeal', array($deal_id, true));
        } else {
            $dealInfo = null;
        }
        if (!$dealInfo) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        $source_type = !empty($data['source_type'])? $data['source_type'] : 0;
        $site_id = !empty($data['site_id']) ? $data['site_id'] : 1;
        try {
            $result = $this->rpc->local(
                'P2pDealBidService\preBid',
                array($user, $dealInfo, $money, $source_type, $data['coupon'], $site_id)
            );
        } catch (\Exception $e) {
            return $this->setErr(-1, $e->getMessage());
        }

        $this->json_data = $result;
    }
}

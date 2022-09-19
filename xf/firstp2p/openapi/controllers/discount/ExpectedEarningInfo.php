<?php

namespace openapi\controllers\discount;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestDiscountExpectedEarningInfo;
use libs\utils\Aes;

class ExpectedEarningInfo extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'deal_id' => array('filter' => 'int'),
            'ecid' => array('filter' => 'string'),
            'money' => array('filter' => 'float', 'option' => array('optional' => false)),
            'discount_id' => array('filter' => 'int', 'option' => array('optional' => false)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $siteId = $this->getSiteId();

        $userInfo = $this->getUserByAccessToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = $userInfo->userId;
        if(isset($data['ecid']) && $data['ecid'] != "") {//有加密数据传入
            $dealId = Aes::decryptForDeal($data['ecid']) ;
        } else {
            $dealId = intval($data['deal_id']);
        }

        if (bccomp($data['money'], 0, 2) != 1) {
            $this->setErr('ERR_MONEY_FORMAT');
            return false;
        }
        $money = $data['money'];
        $discountId = $data['discount_id'];
        if ($discountId <= 0) {
            $this->setErr('ERR_PARAMS_ERROR');
        }

        $request = new RequestDiscountExpectedEarningInfo();

        try {
            $request->setUserId(intval($userId));
            $request->setDealId(intval($dealId));
            $request->setMoney(floatval($money));
            $request->setDiscountId(intval($discountId));
            $request->setSiteId(intval($siteId));
        } catch (\Exception $e) {
            $this->errorCode = -99;
            $this->errorMsg  = 'param set error';
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDiscount',
            'method'  => 'expectedEarningInfo',
            'args'    => $request
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg  = 'get info failed';
            return false;
        }

        $this->json_data = $response;
        return true;
    }
}

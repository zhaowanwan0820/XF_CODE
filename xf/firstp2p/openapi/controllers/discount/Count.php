<?php

/**
 * @abstract openapi 获取用户可用劵数
 * @date 2016-02-17
 * @author Wang Shi Jie<wangshijie@ucfgroup.com>
 */

namespace openapi\controllers\discount;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestDiscountCount;
use libs\utils\Aes;

class Count extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'deal_id' => array('filter' => 'int'),
            'ecid' => array('filter' => 'string'),
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

        if(isset($data['ecid']) && $data['ecid'] != "") {//有加密数据传入
            $dealId = Aes::decryptForDeal($data['ecid']) ;
        } else {
            $dealId = intval($data['deal_id']);
        }

        $siteId = $this->getSiteId();
        $request = new RequestDiscountCount();

        $userInfo = $this->getUserByAccessToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userId = $userInfo->userId;

        try {
            $request->setUserId(intval($userId));
            $request->setDealId($dealId);
            $request->setSiteId(intval($siteId));
        } catch (\Exception $e) {
            $this->errorCode = -99;
            $this->errorMsg  = 'param set error';
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDiscount',
            'method'  => 'count',
            'args'    => $request
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg  = 'get count failed';
            return false;
        }

        $this->json_data = $response;
        return true;
    }

}

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
use NCFGroup\Protos\Ptp\RequestDiscountMine;

class Mine extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'offset' => array("filter" => "int", 'option' => array('optional' => true)),
            'count'  => array("filter" => "int", 'option' => array('optional' => true)),
            'status' => array('filter' => 'int', 'option' => array('optional' => true)),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
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

        $count  = $data['count'] >= 1 ? $data['count'] : 10;
        $page   = $data['offset'] > 0 ? intval($data['offset'] / $count) + 1 : 1;
        $status = $data['status'] > 0 ? $data['status'] : 0;
        $discountType = ($data['discount_type'] == 0 || $data['discount_type'] == 1 || $data['discount_type'] == 2)  ? $data['discount_type'] : 1;
        $siteId = $this->getSiteId();

        $userInfo = $this->getUserByAccessToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userId = $userInfo->userId;

        $request = new RequestDiscountMine();

        try {
            $request->setUserId(intval($userId));
            $request->setStatus(intval($status));
            $request->setPage(intval($page));
            $request->setCount(intval($count));
            $request->setType(intval($discountType));
            $request->setSiteId(intval($siteId));
        } catch (\Exception $e) {
            $this->errorCode = -99;
            $this->errorMsg  = 'param set error';
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDiscount',
            'method'  => 'mine',
            'args'    => $request
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg  = 'get count failed';
            return false;
        }

        if ($response == false) {
            $response = array('total' => 0, 'totalPage' => 0, 'list' => array());
        }

        $this->json_data = $response;
        return true;
    }

}

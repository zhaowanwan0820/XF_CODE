<?php

/**
 * DealLoadDetail
 *
 * @date 2015-07-07
 * @author Wang Shi Jie <wangshijie@ucfgroup.com>
 */

namespace openapi\controllers\account;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;
use NCFGroup\Protos\Ptp\RequestDealLoadDetail;

/**
 * 已投项目详情
 *
 * Class DealLoadDetail
 * @package api\controllers\account
 */
class DealLoadDetail extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "int", "message" => "id is error", "option" => array('optional' => true)),
            'dealLoanSize' => array('filter' => 'int'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $return = array();

        $load_id = intval($this->form->data['id']);
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userId = $userInfo->userId;
        $request = new RequestDealLoadDetail();
        $request->setLoadId($load_id);
        $dealLoanSize = intval($this->form->data['dealLoanSize']);
        if ($dealLoanSize > 0)
            $request->setDealLoanSize($dealLoanSize);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDealLoad',
            'method' => 'getDealLoadDetail',
            'args' => $request
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get deal load detail failed";
            return false;
        }
        if ($userId != $response['deal_load']['user_id']) {
            $this->errorCode = -1;
            $this->errorMsg = 'get user failed';
            return false;
        }
        
        $this->json_data = $response;
    }

}

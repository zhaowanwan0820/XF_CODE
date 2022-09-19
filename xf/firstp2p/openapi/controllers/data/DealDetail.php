<?php
namespace openapi\controllers\data;

use libs\web\Form;
use openapi\controllers\DataBaseAction;
use NCFGroup\Protos\Ptp\RequestDealInfo;
use NCFGroup\Protos\Ptp\ResponseDealInfo;

/**
 * 标的详情接口
 * Class DealDetail
 * @package openapi\controllers\data
 */
class DealDetail extends DataBaseAction {

    private $_forbid_deal_status;

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'dealXid' => array("filter" => "string", "message" => "dealXid is error"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

        $this->_forbid_deal_status = array(2, 3, 4, 5);
    }

    public function invoke() {
        $data = $this->form->data;
        $dealId = intval($this->decodeId($this->form->data['dealXid']));
        if (empty($dealId)) {
            throw new \Exception("ERR_PARAMS_ERROR");
        }
        $request = new RequestDealInfo();
        try {
            $request->setForbidDealStatus($this->_forbid_deal_status);
            $request->setDealId($dealId);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $dealInfoResponse = new ResponseDealInfo();
        $dealInfoResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'getDealInfo',
            'args' => $request
        ));

        if ($dealInfoResponse->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get dealInfo failed";
            return false;
        }
        $dealInfo = $dealInfoResponse->toArray();
        $this->json_data = $dealInfo;
        return true;
    }

}

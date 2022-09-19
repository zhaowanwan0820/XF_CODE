<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use core\service\o2o\CouponService;

class UnpickList extends AppBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            // O2O Feature
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;
        $page = intval($data['page']);
        $page = $page ? $page : 1;
        $rpcParams = array($loginUser['id'], $page);
        $unPickList = CouponService::getUnpickList($loginUser['id'], $page);
        $resultJson = array(
            'site_id' => $data['site_id'],
            'unPickList' => $unPickList,
            'unPickListCount' => count($unPickList),
            'usertoken' => $data['token'],
            'discountCenterUrl' => (new \core\service\conf\ApiConfService())->getDiscountCenterUrl(2),
        );

        $this->json_data = $resultJson;
    }
}

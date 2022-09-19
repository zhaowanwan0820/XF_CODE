<?php
/**
 * 兑换记录列表
 */

namespace api\controllers\seller;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;

class AppliedList extends BaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
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
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $page = intval($data['page']);
        $page = $page ? $page : 1;

        $applyList= $this->rpc->local('O2OService\getConfirmedCouponList', array($loginUser['id'], $page));
        $this->tpl->assign('applyList', $applyList);
        $this->tpl->assign('applyListCount', count($applyList));
        $this->tpl->assign('usertoken', $this->form->data['token']);
        $this->template = $this->getTemplate('list');
    }
}

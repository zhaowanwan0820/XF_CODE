<?php

namespace api\controllers\gift;

use libs\web\Form;
use api\controllers\ApiBaseAction;

class Mine extends ApiBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'o2oViewAccess' => array('filter' => 'string', 'option' => array('optional' => true)),
            'status' => array('filter' => 'int', 'option' => array('optional' => true)),
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
//        if(isset($data['o2oViewAccess']) && $data['o2oViewAccess']) {
            \es_session::set('o2oViewAccess','mine');//session中设置页面浏览的来源，方便前端控制关闭逻辑
//        }
        $user_id = $loginUser['id'];
        $page = !empty($data['page']) ? intval($data['page']) : 0;
        $page = $page ? $page : 1;

        // 默认传0，表示不做状态判断
        $status = isset($data['status']) ? intval($data['status']) : 0;
        $rpcParams = array($user_id, $status, $page);
        $couponList = $this->rpc->local('O2OService\getUserCouponList', $rpcParams);
        $this->tpl->assign('couponList', $couponList);
        $this->tpl->assign('couponListCount', empty($couponList) ? 0 : count($couponList));
        $this->tpl->assign('usertoken', $this->form->data['token']);
        $this->tpl->assign('discountCenterUrl', (new \core\service\ApiConfService())->getDiscountCenterUrl(2));
        $this->template = $this->getTemplate('mine');
    }

}

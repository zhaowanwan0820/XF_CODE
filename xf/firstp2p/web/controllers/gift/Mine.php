<?php

namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;

class Mine extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'hasCount' => array('filter' => 'int', 'option' => array('optional' => true)),
            'status' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            return ajax_return(array());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];

        $user_id = $loginUser['id'];
        $page = intval($data['page']);
        $page = $page ? $page : 1;

        // 默认传0，表示不做状态判断
        $status = isset($data['status']) ? $data['status'] : 0;
        $pageSize = 5;
        $rpcParams = array($user_id, $status, $page, $pageSize);
        $couponList = $this->rpc->local('O2OService\getUserCouponList', $rpcParams);
        //如需返回总数，额外查询下
        $hasCount = isset($data['hasCount']) ? $data['hasCount'] : 0;

        $count = 0;
        if ($hasCount) {
            $count = $this->rpc->local('O2OService\getUserCouponCount', array($loginUser['id'], $status));
        }

        $result = array('list' => $couponList, 'count' => $count, 'pageNum' => ceil($count/$pageSize));//分页大小默认是5
        ajax_return($result);
    }

    public function _after_invoke(){
    }

}

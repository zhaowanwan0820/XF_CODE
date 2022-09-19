<?php

namespace web\controllers\gift;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\OtoAcquireLogModel;

class UnpickList extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
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
        // 默认取所有数据吧
        $status = isset($data['status']) ? $data['status'] : OtoAcquireLogModel::UNPICK_ALL;
        $page = intval($data['page']);
        $page = $page ? $page : 1;
        $rpcParams = array($loginUser['id'], $page, $pageSize = 4, $status);//未领取的分页是4
        $unPickList = $this->rpc->local('O2OService\getUnpickList', $rpcParams);
        //如需返回总数，额外查询下
        $hasCount = isset($data['hasCount']) ? $data['hasCount'] : 0;
        if ($hasCount) {
            $count = $this->rpc->local('O2OService\getUnpickCount', array($loginUser['id'], $status));
        }
        $result = array('list' => $unPickList, 'count' => $count, 'pageNum' => ceil($count/$pageSize));
        ajax_return($result);
    }

}

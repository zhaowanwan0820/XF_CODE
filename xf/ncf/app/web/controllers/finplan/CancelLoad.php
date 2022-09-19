<?php

/**
 * 多投宝取消投资
 * CancelLoad.php
 * @author wangchuanlu@ucfgroup.com
 */
namespace web\controllers\finplan;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\duotou\DtCancelService;

class CancelLoad extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'loan_id' => array('filter' => 'int'), // 投资记录ID
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $loanId = intval($this->form->data['loan_id']);
        $user = $GLOBALS['user_info'];
        if(empty($user)) {
            $this->show_error("未登陆");
        }
        $userId = $user['id'];
        $dtCancelService = new DtCancelService();
        $res = $dtCancelService->cancelDealLoan($userId,$loanId);
        $return = array('data'=>$res);
        echo json_encode($return, JSON_UNESCAPED_UNICODE);
        return true;
    }
}

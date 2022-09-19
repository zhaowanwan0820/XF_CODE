<?php
/**
 * Repay.php
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

/**
 * 正常还款执行程序
 * @userLock
 */
class Repay extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
    	$this->form = new Form();
    	$this->form->rules = array(
            "id" => array("filter" => "int"),
            "ids" => array("filter" => "string"),
    	);
    	$this->form->validate();
    }

    public function invoke() {
        // 异步处理，去除前台还款，20141228
        return app_redirect(url("index"));
        $id = $this->form->data['id'];
        $ids = $this->form->data['ids'];
        $arr_repay_id = explode(",", $ids);
        sort($arr_repay_id);

        $result = $this->rpc->local('DealService\repayDeal', array($id, $arr_repay_id));

        if ($result['res'] === true) {
            return $this->show_success($result['msg'], '', 1);
        } else {
            return $this->show_error($result['msg'], "", 1);
        }
    }
}

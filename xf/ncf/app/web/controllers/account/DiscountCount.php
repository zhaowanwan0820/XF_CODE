<?php
/**
 * 我的投资劵
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\o2o\DiscountService;

class DiscountCount extends BaseAction
{
    public function init()
    {
        $this->check_login();

        $this->form = new Form();
        $this->form->rules = array(
            'consume_type' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $ret = ['error' => 2000, 'msg' => $this->form->getErrorMsg()];
            return ajax_return($ret);
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $uid = $GLOBALS['user_info']['id'];

        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : 0;
        $rpcParams = array($uid, 0, $consumeType);
        $total = DiscountService::getUserUnusedDiscountCount($uid, 0, $consumeType);
        $ret = [
            'error' => 0,
            'msg' => 'success',
            'count' => $total['all'],
        ];
        ajax_return($ret);
    }
}

<?php
/**
 * 我的投资劵
 **/
namespace web\controllers\account;

use libs\utils\Site;
use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

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
        $siteId = Site::getId();
        $uid = $GLOBALS['user_info']['id'];

        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : 0;
        $rpcParams = array($uid, 0, $consumeType);
        $total = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('O2OService\getUserUnusedDiscountCount', $rpcParams), 60);
        $ret = [
            'error' => 0,
            'msg' => 'success',
            'count' => intval($total),
        ];

        ajax_return($ret);
    }
}


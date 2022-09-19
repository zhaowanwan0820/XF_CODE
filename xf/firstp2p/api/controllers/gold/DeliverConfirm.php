<?php

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use core\service\GoldDeliverService;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use NCFGroup\Common\Library\Idworker;

class DeliverConfirm extends GoldBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }

    }
    public function invoke() {

        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }
        $this->tpl->assign('usertoken', $data['token']);
        $ticket = Idworker::instance()->getId();
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $ticketRes = $redis->setex($ticket,'300',$user['id']);
        $this->tpl->assign('ticket', $ticket);
        $goodsList = $this->rpc->local('GoldService\getGoodsList', array());
        $gold= $this->rpc->local('GoldService\getGoldByUserId', array($user['id']));
        $gold=number_format($gold,3);
        $this->tpl->assign('mobile',$user['mobile']);
        $this->tpl->assign('user_gold',$gold);
        $this->tpl->assign('goods_list',$goodsList);
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }
}

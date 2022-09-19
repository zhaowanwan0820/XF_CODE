<?php

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\oto\O2OCouponService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class Inviteaward extends BaseAction
{
    public function init()
    {
        if (!$this->check_login()) {
            return false;
        }
        $this->form = new Form();
        $this->form->rules = array(
            'page' => array('filter' => 'int'),
            'pagesize' =>  array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $ret = ['error' => 2000, 'msg' => $this->form->getErrorMsg()];
            return ajax_return($ret);
        }
    }

    public function invoke()
    {
        $user_id = intval($GLOBALS['user_info']['id']);
        $data = $this->form->data;
        $page = $data['page'] ? intval($data['page']) : 1;

        $o2oCouponService = new O2OCouponService();
        $ret = $o2oCouponService->getCouponRewardList($user_id, CouponGroupEnum::ROLE_TYPE_INVITER, $page, 10);

        $list = $ret['list'] ?: [];
        $total = $ret['total'] ?: 0;
        $totalPage = $ret['totalPage'] ?: 0;
        $ret = [
            'error' => 0,
            'msg' => 'success',
            'pagecount' => $totalPage,
            'page' => $page,
            'count' => intval($total),
            'list' => $list,
        ];
        ajax_return($ret);
    }
}

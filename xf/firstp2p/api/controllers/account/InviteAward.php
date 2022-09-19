<?php
/**
 *
 * @date 2019-01-31
 *
 * @author liguizhi <liguizhi@ucfgroup.com>
 */

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\oto\O2OCouponService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class InviteAward extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $offset = $data['offset'] ? intval($data['offset']): 0;
        $count = $data['count'] ? intval($data['count']) : 10;
        $pageNo = floor($offset/$count) + 1;
        $o2oCouponService = new O2OCouponService();
        $ret = $o2oCouponService->getCouponRewardList($user['id'], CouponGroupEnum::ROLE_TYPE_INVITER, $pageNo, $count);
        $this->json_data = $ret['list']?: array();
    }
}

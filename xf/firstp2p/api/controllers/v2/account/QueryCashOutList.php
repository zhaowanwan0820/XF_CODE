<?php

namespace api\controllers\account;


use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;

/**
 * 获取用户最近7天提现记录
 *
 *
 * Class queryCashOutList
 * @package api\controllers\account
 */
class queryCashOutList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['token'])) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $rspn = $this->rpc->local('UserLogService\getCashOutList', array($user['id']));
        $result = array();
        if (!empty($rspn)) {
            foreach ($rspn as $key =>$rv){
                $result[$key]['notice_sn'] = $rv['notice_sn'];
                // 基金格式化下订单数据
                if ($rv['platform'] == \core\dao\PaymentNoticeModel::PLATFORM_FUND_REDEEM)
                {
                    $fundInfo = explode(',', $rv['memo']);
                    $suffix = mb_strlen($fundInfo[1], 'UTF-8') > 10 ? '...' : '';
                    $fundTitle = mb_substr($fundInfo[1], 0, 10, 'UTF-8').$suffix;

                    $result[$key]['notice_sn'] = $rv['notice_sn'].','.$fundTitle;
                }

                $result[$key]['status_cn'] = $rv['status_cn'];
                $result[$key]['pay_time'] = empty($rv['pay_time'])? '-' : to_date($rv['pay_time'],'Y-m-d H:i:s');
                $result[$key]['create_time'] = to_date($rv['create_time'],'Y-m-d H:i:s');
                $result[$key]['money'] = format_price($rv['money'],false);
            }
        }
        $this->json_data = $result;
    }

}


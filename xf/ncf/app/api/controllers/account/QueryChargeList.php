<?php
/*
 *
 * @date 2014-08-21
 * @author xiaoan <zhaoxiaoan@ucfgroup.com>
 */

namespace api\controllers\account;


use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\user\UserLogService;

/**
 * 获取用户最近7天充值记录
 *
 *
 * Class queryChargeList
 * @package api\controllers\account
 */
class queryChargeList extends AppBaseAction {

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
    }

    /**
     * 将firstp2p_payment_notice表更换为firstp2p_supervision_charge表
     * 缺失字段的处理方式见下面注释
     * 大强哥说可以替代
     *
     * @update by sunxuefeng@ucfgroup.com
     */
    public function invoke() {
        $user = $this->user;
        $userLogService = new UserLogService();
        $rspn = $userLogService->get_charge_list($user['id']);
        $result = array();
        if (!empty($rspn)) {
            foreach ($rspn as $key => $rv) {
                $result[$key]['notice_sn'] = $rv['out_order_id'];
                $result[$key]['status_cn'] = $rv['status_cn'];
                // 使用更新时间表示充值成功或者失败时间
                $result[$key]['pay_time'] = empty($rv['update_time'])? '-' : to_date($rv['update_time'],'Y-m-d H:i:s');
                $result[$key]['create_time'] = to_date($rv['create_time'],'Y-m-d H:i:s');
                // firstp2p_supervision_charge 使用分作为单位
                $result[$key]['money'] = format_price(bcdiv($rv['amount'], 100), false);
            }
        }
        $this->json_data = $result;
    }

}


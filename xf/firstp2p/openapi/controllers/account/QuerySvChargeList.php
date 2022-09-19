<?php
namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;

/**
 * 获取用户存管充值记录
 */
class QuerySvChargeList extends BaseAction
{
    static $statusText = [
            0 => '未处理',
            1 => '成功',
            2 => '失败',
        ];
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            "offset" => array("filter" => "int"),
            "count" => array("filter" => "int"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $ctime = time() - 7*24*3600;
        $offset = !empty($data['offset']) ? intval($data['offset']) : 0;
        $count = !empty($data['count']) ? intval($data['count']) : 10;
        $chargeRes = $this->rpc->local('SupervisionFinanceService\getChargeLogs', array($userInfo->userId, $ctime, $count, $offset));
        $list = array();
        if (!empty($chargeRes)) {
            foreach ($chargeRes as $key => $value){
                $newVal = [];
                $newVal['notice_sn'] = $value['out_order_id'];
                $newVal['money'] = format_price(bcdiv($value['amount'],100, 2), false);
                $newVal['status_cn'] = self::$statusText[$value['pay_status']];
                $newVal['create_time'] = $value['create_time'];
                $list[] = $newVal;
            }
        }
        $result['list'] = $list;
        $this->json_data = $result;
        return true;
    }

}


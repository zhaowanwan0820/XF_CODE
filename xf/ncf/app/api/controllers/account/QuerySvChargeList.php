<?php
namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\supervision\SupervisionFinanceService;

/**
 * 获取用户存管充值记录
 */
class QuerySvChargeList extends AppBaseAction
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
            "token" => array("filter" => "required", "message" => "token is required"),
            "offset" => array("filter" => "int"),
            "count" => array("filter" => "int"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $user = $this->user;
        $count = $offset = 0;
        if (!empty($this->form->data['count'])) {
            $offset = $this->form->data['offset'] ?: 0;
            $count = $this->form->data['count'] ?: 0;
        }

        $ctime = time() - 7*24*3600;
        $supervisionFinanceService = new SupervisionFinanceService();
        $chargeRes = $supervisionFinanceService->getChargeLogs($user['id'], $ctime, $count, $offset);
        $result = array();
        if (!empty($chargeRes)) {
            foreach ($chargeRes as $key => $value){
                $newVal = [];
                $newVal['notice_sn'] = $value['out_order_id'];
                $newVal['money'] = format_price(bcdiv($value['amount'], 100, 2), false);
                $newVal['status_cn'] = self::$statusText[$value['pay_status']];
                $newVal['create_time'] = format_date($value['create_time']);
                $result[] = $newVal;
            }
        }

        $this->json_data = $result;
    }
}
<?php
namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\dao\PaymentNoticeModel;

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
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $count = $offset = 0;
        if (!empty($this->form->data['count'])) {
            $offset = $this->form->data['offset'] ?: 0;
            $count = $this->form->data['count'] ?: 0;
        }
        $ctime = time() - 7*24*3600;
        $chargeRes = $this->rpc->local('SupervisionFinanceService\getChargeLogs', array($user['id'], $ctime, $count, $offset));
        $result = array();
        if (!empty($chargeRes)) {
            foreach ($chargeRes as $key => $value){
                $newVal = [];
                $newVal['notice_sn'] = $value['out_order_id'];
                $newVal['money'] = format_price(bcdiv($value['amount'], 100, 2), false);
                $newVal['status_cn'] = self::$statusText[$value['pay_status']];
                $newVal['create_time'] = format_date($value['create_time']);
                $newVal['type_name'] = '快捷充值';
                if ($value['platform'] == PaymentNoticeModel::PLATFORM_WEB) {
                    $newVal['type_name'] = '网银收银台';
                } else if ($value['platform'] == PaymentNoticeModel::PLATFORM_OFFLINE_V2) {
                    $newVal['type_name'] = '大额充值';
                }
                $result[] = $newVal;
            }
        }
        $this->json_data = $result;
    }

}


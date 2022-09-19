<?php
/**
 * MoneyLogDetail
 *
 * @date 2014-10-30
 * @author wangjiansong <wangjiansong@ucfgroup.com>
 */

namespace openapi\controllers\account;


use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;

/**
 * 资金记录列表接口
 *
 * Class MoneyLog
 * @package api\controllers\account
 */
class MoneyLogDetail extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            'id' => array("filter" => "int", "message"=>"id is error"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $detail = $this->rpc->local('UserService\getMoneyLogDetailById', array($params['id']));
        $result = array();
        if (!empty($detail)) {
            $result['id'] = $detail['id'];
            $result['time'] = to_date($detail['log_time']);
            $result['type'] = $detail['log_info'];
            $result['money'] = format_price($detail['money'], false);
            $result['remark'] = $detail['note'];
        } else {
            $this->setErr('ERR_MANUAL_REASON', '记录不存在');
        }
        $this->json_data = $result;
    }

}

<?php

namespace openapi\controllers\user;

use libs\web\Form;
use core\service\UserService;
use openapi\controllers\AdminProxyBaseAction;

class VipRebateLog extends AdminProxyBaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "user_id"   => array("filter" => "string", "option" => array("optional" => true)),
            "mobile"    => array("filter" => "string", "option" => array("optional" => true)),
            "page_num"  => array("filter" => "int",    "option" => array("optional" => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", "参数存在错误");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        if (!trim($params['user_id']) && !trim($params['mobile'])) {
            $this->setErr("ERR_PARAMS_ERROR", "参数不能为空");
            return false;
        }

        $params = array(
            'user_id' => $params['user_id'],
            'mobile'  => $params['mobile'],
            'p'       => intval($params['page_num'])
        );

        $result = $this->revokeAdmin($params);
        $return = [
            'totalRows'  => intval($result['totalRows']),
            'totalPages' => intval($result['totalPages']),
            'nowPage'    => intval($result['nowPage']),
            'dataList'   => [],
        ];

        foreach ($result['list'] as $item) {
            $return['dataList'][] = [
                'rebateTime' => $item['create_time'],
                'userId'     => $item['user_id'],
                'realName'  => $item['real_name'],
                'mobile'     => $item['mobile'],
                'rebateRate' => $item['rebate_rate'] . '%',
                'allowanceMoney' => $item['allowance_money'],
            ];
        }

        $this->json_data = $return;
        return true;
    }
}

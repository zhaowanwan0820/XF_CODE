<?php
namespace openapi\controllers\data;

use libs\web\Form;
use openapi\controllers\DataBaseAction;
use NCFGroup\Protos\Ptp\RequestUserList;
use NCFGroup\Protos\Ptp\RPCErrorCode;

/**
 * @abstract 获取用户列表
 * @author longbo
 */
class UserList extends DataBaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $offset = empty($data['offset']) ? 0 : intval($data['offset']);
        $count = empty($data['count']) ? 10 : intval($data['count']);
        $userRequest = new RequestUserList();
        $userRequest->setSiteId(intval($this->siteId));
        $userRequest->setOffset($offset);
        $userRequest->setCount($count);
        $userRequest->setIsDesensitize(0);
        $userResponse = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpUser',
                'method' => 'getUserBySiteId',
                'args' => $userRequest
            ));
        $userList = array();
        if ($userResponse->resCode === RPCErrorCode::SUCCESS) {
            $userList = $userResponse->getList();
            $userList = array_map(function ($v) {
                $v['userXid'] = $this->encodeId($v['userId']);
                return $v;
            }, $userList);
        }
        $this->json_data = $userList;
    }

}

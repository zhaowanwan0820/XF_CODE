<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/9/15
 * Time: 13:10
 */

namespace openapi\controllers\retail;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;

class GetTransitMoney extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "realName" => array("filter" => "required", "message" => "realName is required"),
            "idno" => array("filter" => "required", "message" => "idno is required"),
            "mobile" => array("filter" => "required","message" => "mobile is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        if (empty($params['mobile']) || empty($params['realName']) || empty($params['idno'])) {
            $this->setErr("ERR_PARAMS_ERROR", '所传参数不能为空');
            return false;
        }
        $ret = $this->rpc->local('UserService\getByMobile',array(intval($params['mobile']),'idno,real_name,id,is_effect'));
        if ($params['idno'] != $ret->idno || $params['realName'] != $ret->real_name) {
            $this->setErr("ERR_PARAMS_ERROR", '该用户不存在');
            return false;
        }
        $userId = $ret->id;
        //在途金额
        $result = array();
        $request = new ProtoUser();
        $request->setAllUserId(array(intval($userId)));
        $result['money'] = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => 'getUnrepayP2pMoneyByUids', 'args' => $request));
        $this->json_data = $result;
    }

}
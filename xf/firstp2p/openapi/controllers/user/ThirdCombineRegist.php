<?php namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\lib\Tools;


/**
 * 第三方用户身份认证加绑卡
 * @author longbo
 */
class ThirdCombineRegist extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'openId' => array("filter" => "required", "message" => "openid is required"),
            'name' => array("filter" => "required", "message" => "name is required"),
            'idNo' => array("filter" => "required", "message" => "idno is required"),
            'bankId' => array("filter" => "required", "message" => "bankId is required"),
            'bankCardNo' => array("filter" => "required", "message" => "bankCardNo is required"),
            //'asgn' => array("filter" => "required", "message" => "asgn is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $combineData['userId'] = $this->_user_id;
        $combineData['realName'] = htmlspecialchars(trim($data['name']));
        $combineData['cardNo'] = htmlspecialchars(trim($data['idNo']));
        $combineData['bankId'] = htmlspecialchars(trim($data['bankId']));
        $combineData['bankCardNo'] = htmlspecialchars(trim($data['bankCardNo']));
        if (!preg_match("/^[\x80-\xff]{6,30}$/", $combineData['realName'])) {
            $this->setErr('ERR_MANUAL_REASON', '姓名只支持中文');
            return false;
        }
        try {
            $result = $this->rpc->local(
                            'PaymentService\combineRegisterForJF',
                            array_values($combineData)
                            );
            $this->json_data = $result;
        } catch (\Exception $e) {
            $this->errorCode = -1;
            $this->errorMsg = $e->getMessage();
            \libs\utils\PaymentApi::log('ThirdCombineRegist fail'.$e->getMessage().":".json_encode($combineData));
            return false;
        }
    }

}

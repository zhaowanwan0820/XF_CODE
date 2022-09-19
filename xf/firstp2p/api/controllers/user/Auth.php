<?php

namespace api\controllers\user;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\PayBaseAction;

/**
 * Auth
 * 用户认证回调接口
 *
 * @uses PayBaseAction
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Pine wangjiansong@ucfgroup.com
 */
class Auth extends PayBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        //$this->form = new Form();
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名错误'),
            'account' => array('filter' => "string"),
            'status' => array('filter' => "string"),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        // TODO 处理逻辑
        $op = intval($data['status']);
        $account = $data['account'];
        if (!in_array($op, array(1, 2))) {
            $this->setErr(0, '非法调用！');
            return false;
        }
        $success = ConstDefine::RESULT_SUCCESS;
        if ($op == 1) {
            if (!$this->rpc->local('UserService\photoPassedPass', array($account))) {
                $success = ConstDefine::RESULT_FAILURE;
            }
        }
        if ($op == 2) {
            if (!$this->rpc->local('UserService\photoPassedReject', array($account))) {
                $success = ConstDefine::RESULT_FAILURE;
            }
        }

        $result = array("success" => $success);
        $this->json_data = $result;
    }
}

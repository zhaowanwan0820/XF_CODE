<?php
/**
 *
 * @abstract 通过oauth_token获取用户信息
 * @author yutao<yutao@ucfgroup.com>
 * @date   2014-11-27
 */
namespace openapi\controllers\common;

use libs\web\Form;
use openapi\controllers\BaseAction;

class GetConf extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "string", 'option' => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        //新手专区是否显示
        $result['isShowNewUserCenter'] = $this->rpc->local('NewUserPageService\isNewUserSwitchOpen', array());
        if(!empty($data['oauth_token'])){
            $userInfo = $this->getUserByAccessToken();
            if (empty($userInfo)) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            if($result['isShowNewUserCenter'] == 1){
                $result['isShowNewUserCenter'] = $this->rpc->local('NewUserPageService\isNewUser', array($userInfo->userId, $userInfo->registerTime)) ? 1 : 0;
            }
        }
        $this->json_data = $result;
    }
}

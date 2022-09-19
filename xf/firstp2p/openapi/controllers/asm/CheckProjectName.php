<?php

/**
 * @abstract openapi  信贷一键上标前 检查项目名称是否存在
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2017-06-12
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Ptp\daos\ProjectDealDAO;

/**
 * 检查项目信息是否存在
 *
 * Class CheckProjectName
 * @package openapi\controllers\asm
 */
class CheckProjectName extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "access_token" => array("filter" => "required", "message" => "access_token is required"),
            "name" => array("filter" => "required", "message" => "name is required"),//项目名称
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
        //校验用户access_token
        $clientInfo = $this->getClientIdByAccessToken();
        if (empty($clientInfo) || $clientInfo['client_id'] !== $params['client_id']) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $result = array(isExist => false);
        if (empty($params['name'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'name不能为空');
            return false;
        }
        //检查项目名称是否已经存在
        $result['isExist'] = ProjectDealDAO::getByNameProject(addslashes($params['name'])) ? true : false;
        $this->json_data = $result;
    }

}

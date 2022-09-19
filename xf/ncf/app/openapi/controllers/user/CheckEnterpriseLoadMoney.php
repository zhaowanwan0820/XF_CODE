<?php
/**
 * 获取企业用户在途资产.
 * User: steven
 * Date: 2017/12/26
 * Time: 下午8:39
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\dao\deal\DealModel;
use core\service\user\UserService;


class CheckEnterpriseLoadMoney extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "credentials_no" => array("filter" => "required", "message" => "credentials_no is required"), //证件号
            "company_name" => array("filter" => "required", "message" => "company_name is required"), //公司名
            "enterprise_user_name" => array("filter" => "required", "message" => "enterprise_user_name is required"), //企业用户名

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
        $enterpriseUserName = $params['enterprise_user_name'];
        $credentialsNo = trim($params['credentials_no']);
        $companyName = trim($params['company_name']);

        $enterpriseUsers1 = UserService::getEnterpriseByCompanyName($companyName);
        $enterpriseUsers2 = UserService::getEnterpriseByCredentialsNo($credentialsNo);

        $enterpriseUsers = array_merge($enterpriseUsers1,$enterpriseUsers2);

        $result = array();
        $userIdArr = array();
        if(count($enterpriseUsers > 0)){
            foreach($enterpriseUsers as $enterpriseUser){
                if( ($enterpriseUser['identifier'] != $enterpriseUserName)
                    || ($enterpriseUser['company_name'] != $companyName)
                    || ($enterpriseUser['credentials_no'] != $credentialsNo)){
                    continue;
                }else{
                    $userIdArr[] = $enterpriseUser['user_id'];
                }
            }
        }

        if(empty($userIdArr)){
            $result['money'] = 0;
        }else{
            $userIdArr = array_unique($userIdArr);
            $dealModel = new DealModel();
            $money = $dealModel->getUnrepayP2pMoneyByUids($userIdArr);
            $result['money'] = $money;
        }
        $this->json_data = $result;
    }

}


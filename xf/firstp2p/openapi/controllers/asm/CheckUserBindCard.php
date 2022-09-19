<?php

/**
 * @abstract openapi  检查用户信息
 * @author liuzhenpeng <liuzhenpeng@ucfgroup.com>
 * @date 2015-07-08
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestUserMobile;

/**
 * 检查用户信息
 *
 * Class CheckUserBindCard
 * @package openapi\controllers\asm
 */
class CheckUserBindCard extends BaseAction
{
    private $user_class = array(1 => '企业', 2 => '个人');

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "access_token" => array("filter" => "required", "message" => "access_token is required"),
            "real_name" => array("filter" => "required", "message" => "real_name is required"),
            "idno" => array("filter" => "required", "message" => "idno is required"),
            "user_types" => array("filter" => "int"),
            "mobile" => array("filter" => "string"),
            "caved_bindcard" => array("filter" => "int"),
            "user_name" => array("filter" => "string"),
            "certificate_type" => array("filter" => "string"),//1- 校验西安银行开户信息 2—不校验西安银行开户信息，3-校验certificate_ent_no对应的uid是否开通存管户
            "certificate_ent_no" => array("filter" => "string"),//如果certificate_type是1或2则这个字段代表企业证件号，如果为3则代表是用户ID,多个用户uid以英文逗号隔开
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
        $user_types = isset($params['user_types']) ? (($params['user_types'] >2 || $params['user_types'] <1) ? 2 : (int) $params['user_types']) : 2;
        $caved_bindcard = (!empty($params['caved_bindcard']) && $params['caved_bindcard'] == 2) ? true : false;
        $params['user_name'] = isset($params['user_name']) ? htmlspecialchars(trim($params['user_name'])) : '';
        if ($user_types == 1 && empty($params['user_name'])) {
            $this->errorCode = 1;
            $this->errorMsg = "企业用户会员名称必填";
            return false;
        }
        if($user_types == 2 && !is_mobile($params['mobile'])){
            $this->errorCode = 1;
            $this->errorMsg = "个人、代理人校验手机号必填";

            return false;
        }

        $res = $this->checkCreditUser($params, $caved_bindcard);
        while($res < 0){
            switch ($res) {
                case -3 :
                    $this->errorCode = 4;
                    $this->errorMsg  = '该' . $this->user_class[$user_types] . '存管未开户';
                    break;
                case -5 :
                    $this->errorCode = 4;
                    $this->errorMsg  = '该存管户非借款户';
                    break;
                case -1 :
                    $this->errorCode = 2;
                    $this->errorMsg  = '该' . $this->user_class[$user_types] . '不存在';
                    break;
                default:
                    $this->errorCode = 3;
                    $this->errorMsg  = '该' . $this->user_class[$user_types] . '未绑定银行卡';
            }

            return false;
        };
        //判断用户是否开通存管
        if (intval($params['certificate_type']) == 3) {
            $uids = $res.','.$params['certificate_ent_no'];
            $result = $this->checkSupervision($uids);
            $result['curr_supervision'] = $result[$res];
            unset($result[$res]);
            $this->json_data = $result;
        }
    }

}

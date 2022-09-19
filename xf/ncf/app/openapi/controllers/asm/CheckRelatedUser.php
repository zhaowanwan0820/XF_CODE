<?php

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\enum\RelatedEnum;
use core\dao\related\RelatedCompanyModel;
use core\dao\related\RelatedUserModel;

/**
 * 检查用户关联信息
 *
 * Class CheckRelatedUser
 * @package openapi\controllers\asm
 */
class CheckRelatedUser extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "idno" => array("filter" => "required", "message" => "idno is required"),
            "channel" => array("filter" => "required", "message" => "channel is required"),
            "user_type" => array("filter" => "required", "message" => "user_type is required"),
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
        $user_type = intval($params['user_type']);
        $channel = intval($params['channel']);
        $idno = trim($params['idno']);

        if (!in_array($user_type,array(RelatedEnum::USER_TYPE_USER,RelatedEnum::USER_TYPE_COMPANY))) {
            $this->setErr("ERR_PARAMS_ERROR", '请正确传输用户类型');
            return false;
        }

        if (!in_array($channel,array(RelatedEnum::CHANNEL_NCFWX,RelatedEnum::CHANNEL_NCFPH))) {
            $this->setErr("ERR_PARAMS_ERROR", '请正确传输渠道类型');
            return false;
        }

        $result = array('isRelated' => false);
        if($user_type == RelatedEnum::USER_TYPE_USER) {
            $relatedUserModel = new RelatedUserModel();
            $result['isRelated'] = $relatedUserModel->isRelatedUser($idno,$channel);
        } else {
            $relatedCompanyModel = new RelatedCompanyModel();
            $result['isRelated'] = $relatedCompanyModel->isRelatedCompany($idno,$channel);
        }
        $this->json_data = $result;
    }

}

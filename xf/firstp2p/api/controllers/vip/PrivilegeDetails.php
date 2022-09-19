<?php

/**
 * 获取特权详情
 * User: yanjun5<yanjun5@ucfgroup.com>
 * Date: 2017/6/29
 * Time: 16:02
 */
namespace api\controllers\vip;

use api\controllers\AppBaseAction;
use libs\web\Form;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

class PrivilegeDetails extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init ();
        $this->form = new Form ();
        $this->form->rules = array (
                "token" => array (
                        "filter" => "required",
                        "message" => "token is required"
                ),
                "privilegeId" => array (
                        "filter" => "required",
                        "message" => "privilegeId is required"
                ),
                "vipGrade" => array (
                        "filter" => "required",
                        "message" => "vipGrade is required"
                )
        );

        if (! $this->form->validate ()) {
            $this->setErr ( "ERR_PARAMS_ERROR", $this->form->getErrorMsg () );
            return false;
        }
        return true;
    }
    public function invoke() {
        $data = $this->form->data;
        $user = $this->getUserByToken ();
        if (empty($user)) {
            $this->setErr ( 'ERR_GET_USER_FAIL' );
            return false;
        }
        $privilegeInfo = $this->rpc->local ( "VipService\getPrivilegeDetail", array (intval($data['vipGrade']), intval($data['privilegeId']), true), VipEnum::VIP_SERVICE_DIR);
        if(empty($privilegeInfo)){
            $this->setErr ( "ERR_PARAMS_ERROR", 'get privilegeInfo fail');
            return false;
        }

        //增加权益详情跳转按钮
        $privilegeInfo['showButton'] = false;
        if ($privilegeInfo['extraInfo']['buttonDesc'] && $privilegeInfo['extraInfo']['buttonUrl']) {
            $privilegeInfo['buttonDesc'] = $privilegeInfo['extraInfo']['buttonDesc'];
            $privilegeInfo['buttonUrl'] = $privilegeInfo['extraInfo']['buttonUrl'];
            $privilegeInfo['showButton'] = true;
        }
        $this->tpl->assign("privilegeDetail", $privilegeInfo);
    }

}

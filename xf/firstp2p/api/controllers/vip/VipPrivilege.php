<?php
/**
 * 所有会员等级&特权
* @author yanjun <yanjun5@ucfgroup.com>
*/
namespace api\controllers\vip;
use api\controllers\AppBaseAction;
use libs\web\Form;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

class VipPrivilege extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "token" => array("filter" => "required", "message" => "token is required"),
                "vipGrade" => array ("filter" => "int")
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }
    public function invoke() {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $vipGradeInfo = $this->rpc->local("VipService\getVipGrade",array($user['id']), VipEnum::VIP_SERVICE_DIR);
        $vipGradeParam =  !empty($data['vipGrade']) ? $data['vipGrade'] : null;
        $vipGradeList = $this->rpc->local("VipService\getVipGradePrivilege",array($user['id'],$vipGradeParam, true), VipEnum::VIP_SERVICE_DIR);
        if(empty($vipGradeList)){
            $this->setErr("ERR_PARAMS_ERROR", 'get vipInfo fail');
            return false;
        }
        $vipInfo['currentVipGrade'] = $vipGradeInfo['service_grade'];
        $vipInfo['vipGradeList'] = $vipGradeList;
        $this->json_data = $vipInfo;
    }
}

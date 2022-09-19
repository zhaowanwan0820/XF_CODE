<?php
/**
 * 会员经验列表
* @author liguizhi <liguizhi@ucfgroup.com>
*/
namespace api\controllers\vip;
use api\controllers\AppBaseAction;
use libs\web\Form;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

class VipPointLog extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "token" => array("filter" => "required", "message" => "token is required"),
                'page' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $pageNum = 15;

        if (!$this->rpc->local("VipService\isShowVip", array($user['id']), VipEnum::VIP_SERVICE_DIR)) {
            return false;
        }
        $vipGradeInfo = $this->rpc->local("VipService\getVipGrade",array($user['id']), VipEnum::VIP_SERVICE_DIR);
        $userPointList = $this->rpc->local("VipPointLogService\getFormatPointByPage", array($user['id'], $page, $pageNum), VipEnum::VIP_SERVICE_DIR);
        $pointInfo = $this->rpc->local("VipService\getExpireInfoAndIncome",array($user['id']), VipEnum::VIP_SERVICE_DIR);
        $result = array(
            'info' => array('point' => $vipGradeInfo['point'], 'expireInfo' => $pointInfo['expireInfo']),
            'income' => $pointInfo['income'] ? : 0,
            'list' => $userPointList,
        );
        $this->json_data = $result;
    }
}


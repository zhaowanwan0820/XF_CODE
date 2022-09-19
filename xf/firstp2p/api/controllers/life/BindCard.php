<?php
/**
 * 网信生活-收银台-去支付绑卡
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\PaymentUserService;

class BindCard extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'isP2pBind' => array('filter' => 'int'),
            'returnUrl' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        try {
            $userInfo = $this->getUserByToken();
            if (empty($userInfo)) {
                throw new \Exception('ERR_GET_USER_FAIL');
            }

            $data = $this->form->data;
            // 用户ID
            $userId = $userInfo['id'];
            $isP2pBind = isset($data['isP2pBind']) ? (int)$data['isP2pBind'] : 0;

            // 获取收银台银行卡列表开关
            $bindCardEnable = (int)app_conf('LIFE_BINDCARD_ENABLE');
            if ($bindCardEnable === 0) {
                throw new \Exception("该功能内测中，将逐步开放，敬请期待！\n如需绑定理财卡，请前往个人中心-个人信息去绑卡。", 1);
            }

            // 获取支付绑卡页面
            $obj = new PaymentUserService();
            $response = $obj->getUserBindCardPage($userId, $data['returnUrl'], $isP2pBind);
            if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
                throw new \Exception($response['errorMsg'], $response['errorCode']);
            }

            $this->json_data = $response['data'];
        } catch (\Exception $e) {
            if ($e->getCode() == 0) {
                $this->setErr($e->getMessage());
            } else {
                $this->setErr('ERR_MANUAL_REASON', $e->getMessage());
            }
            return false;
        }
    }
}
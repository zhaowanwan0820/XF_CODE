<?php
/**
 * 网信生活-收银台-银行卡列表
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\PaymentUserService;

class CardList extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
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

            // 未实名认证不能进入银行卡列表页
            if(empty($userInfo['real_name']) || $userInfo['idcardpassed'] != 1) {
                throw new \Exception('ERR_IDENTITY_NOT_VERIFY');
            }

            // 获取支付绑卡页面
            $obj = new PaymentUserService();
            $response = $obj->getMyCardList($userInfo['id']);
            $this->json_data = $response;
        } catch (\Exception $e) {
            if ($e->getCode() == 0) {
                $this->setErr($e->getMessage());
            } else {
                $this->setErr('ERR_MANUAL_REASON', $e->getMessage());
                $this->errno = $e->getCode();
            }
            return false;
        }
    }
}
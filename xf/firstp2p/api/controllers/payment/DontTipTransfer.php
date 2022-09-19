<?php
namespace api\controllers\payment;

/**
 * 余额划转
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class DontTipTransfer extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL');
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            $this->tpl->assign('error', $this->error);
            return false;
        }

        try {

            $result = (int) $this->rpc->local('SupervisionFinanceService\SetNotPromptTransfer', [$user['id']]);
            $this->json_data = $result;

        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $user['id'], " errMsg:" . $e->getMessage())));
            return $this->setErr('ERR_MANUAL_REASON', '设置失败');
        }

    }
}

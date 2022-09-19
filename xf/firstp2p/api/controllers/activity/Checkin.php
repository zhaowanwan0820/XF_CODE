<?php
namespace api\controllers\activity;

/**
 * User Checkin
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Checkin extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        try {
            $result = $this->rpc->local(
                'CheckinService\checkin',
                array($user['id'])
            );
        } catch (\Exception $e) {
            Logger::error('GetCheckInError:'.$e->getMessage());
            $this->errno = 1;
            $this->error = "哎哟，姿势不对，再来一次。";
            return false;
        }

        $this->json_data = $result;
    }
}

<?php
namespace api\controllers\activity;

/**
 * Get User Checkin Info
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class GetCheckedInfo extends AppBaseAction
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
                'CheckinService\getCheckedInfo',
                array($user['id'])
            );
        } catch (\Exception $e) {
            Logger::error('GetCheckedInfoError:'.$e->getMessage());
            $result = (object)array();
        }

        $this->json_data = $result;
    }

}

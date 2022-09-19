<?php
namespace api\controllers\address;

/**
 * User address delete
 * @author longbo
 */
use libs\web\Form;
use api\controllers\BaseAction;
use libs\utils\Logger;

class Del extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'address id required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if (!($user = $this->getUserByToken())) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        try {
            $result = $this->rpc->local(
                'AddressService\del',
                array($user['id'], intval($data['id']))
            );
        } catch (\Exception $e) {
            Logger::error('Del Address Error:'.$e->getMessage());
            $this->errno = 1;
            $this->error = "删除失败";
            return false;
        }

        $this->json_data = $result;
    }
}

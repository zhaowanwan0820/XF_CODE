<?php
namespace web\controllers\address;

/**
 * User address delete
 * @author longbo
 */
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;

class Del extends BaseAction
{
    const IS_H5 = false;

    public function init()
    {
        if (!$this->check_login()) parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'required', 'message' => 'address id is required'),
        );
        if (!$this->form->validate()) {
            $this->errno = 1;
            $this->error = $this->form->getErrorMsg();
            return false;
        }

        if (!check_token()) {
            $this->errno = 1;
            $this->error = '系统繁忙，请稍后重试.';
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $GLOBALS['user_info'];
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

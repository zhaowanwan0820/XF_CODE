<?php
namespace web\controllers\address;

/**
 * User address delete
 * @author longbo
 */
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;

class Edit extends BaseAction
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
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $GLOBALS['user_info'];

        try {
            $result = $this->rpc->local(
                'AddressService\getOne',
                array(intval($user['id']), intval($data['id']))
            );
        } catch (\Exception $e) {
            Logger::error('Get Address Error:'.$e->getMessage());
            $this->errno = 1;
            $this->error = "获取失败";
            return false;
        }

        $tokenId = md5(microtime(true).rand(1, 9999));
        $token = mktoken($tokenId);
        $result['tokenId'] = $tokenId;
        $result['token'] = $token;

        $this->json_data = $result;
    }
}

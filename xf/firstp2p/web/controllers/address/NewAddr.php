<?php
namespace web\controllers\address;

/**
 * User address
 * @author longbo
 */
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;

class NewAddr extends BaseAction
{
    const IS_H5 = false;

    public function init()
    {
        if (!$this->check_login()) parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'address' => array('filter' => 'required', 'message' => 'address is required'),
            'area' => array('filter' => 'required', 'message' => 'area is required'),
            'consignee' => array('filter' => 'required', 'message' => 'consignee is required'),
            'mobile' => array('filter' => 'required', 'message' => 'mobile is required'),
            'postcode' => array('filter' => 'string', 'option' => ['optional' => true]),
            'isDefault' => array('filter' => 'int', 'option' => ['optional' => true]),
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

        $newData = [
            'address' => $data['address'],
            'area' => $data['area'],
            'consignee' => $data['consignee'],
            'mobile' => $data['mobile'],
        ];

        if (isset($data['isDefault'])) {
            $newData['is_default'] = intval($data['isDefault']);
        }

        if (isset($data['postcode'])) {
            $newData['postcode'] = $data['postcode'];
        }

        try {
            $result = $this->rpc->local(
                'AddressService\add',
                array($user['id'], $newData)
            );
        } catch (\Exception $e) {
            Logger::error('Add Address Error:'.$e->getMessage());
            $this->errno = 1;
            $this->error = "添加失败:".$e->getMessage();
            return false;
        }

        $this->json_data = $result;
    }
}

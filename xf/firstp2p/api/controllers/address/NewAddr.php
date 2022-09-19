<?php
namespace api\controllers\address;

/**
 * User address
 * @author longbo
 */
use libs\web\Form;
use api\controllers\BaseAction;
use libs\utils\Logger;

class NewAddr extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'address' => array('filter' => 'required', 'message' => 'address is required'),
            'area' => array('filter' => 'required', 'message' => 'area is required'),
            'consignee' => array('filter' => 'required', 'message' => 'consignee is required'),
            'mobile' => array('filter' => 'required', 'message' => 'mobile is required'),
            'postcode' => array('filter' => 'string', 'option' => ['optional' => true]),
            'isDefault' => array('filter' => 'int', 'option' => ['optional' => true]),
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

        if (!empty($data['mobile']) && !is_mobile($data['mobile'])){
            $this->setErr('ERR_PARAMS_ERROR', "手机号格式错误");
            return false;
        }

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

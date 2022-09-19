<?php
namespace api\controllers\address;

/**
 * User address
 * @author longbo
 */
use libs\web\Form;
use api\controllers\BaseAction;
use libs\utils\Logger;

class UpdateAddr extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'address id is required'),
            'address' => array('filter' => 'string', 'option' => ['optional' => true]),
            'area' => array('filter' => 'string', 'option' => ['optional' => true]),
            'consignee' => array('filter' => 'string', 'option' => ['optional' => true]),
            'mobile' => array('filter' => 'string', 'option' => ['optional' => true]),
            'isDefault' => array('filter' => 'int', 'option' => ['optional' => true]),
            'postcode' => array('filter' => 'string', 'option' => ['optional' => true]),
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

        $id = intval($data['id']);
        $updateData = [];
        if (isset($data['address'])) {
            $updateData['address'] = $data['address'];
        }
        if (isset($data['area'])) {
            $updateData['area'] = $data['area'];
        }
        if (isset($data['consignee'])) {
            $updateData['consignee'] = $data['consignee'];
        }
        if (isset($data['mobile'])) {
            $updateData['mobile'] = $data['mobile'];
        }
        if (isset($data['isDefault'])) {
            $updateData['is_default'] = intval($data['isDefault']);
        }
        if (isset($data['postcode'])) {
            $updateData['postcode'] = $data['postcode'];
        }
        if (empty($updateData)) {
            $this->errno = 1;
            $this->error = "更新数据为空";
            return false;
        }
        try {
            $result = $this->rpc->local(
                'AddressService\update',
                array(intval($user['id']), $id, $updateData)
            );
        } catch (\Exception $e) {
            Logger::error('Update Address Error:'.$e->getMessage());
            $this->errno = 1;
            $this->error = "更新失败";
            return false;
        }

        $this->json_data = $result;
    }
}

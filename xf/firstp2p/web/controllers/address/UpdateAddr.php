<?php
namespace web\controllers\address;

/**
 * User address
 * @author longbo
 */
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;

class UpdateAddr extends BaseAction
{
    const IS_H5 = false;
    public function init()
    {
        if (!$this->check_login()) parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'required', 'message' => 'address id is required'),
            'address' => array('filter' => 'string', 'option' => ['optional' => true]),
            'area' => array('filter' => 'string', 'option' => ['optional' => true]),
            'consignee' => array('filter' => 'string', 'option' => ['optional' => true]),
            'mobile' => array('filter' => 'string', 'option' => ['optional' => true]),
            'isDefault' => array('filter' => 'int', 'option' => ['optional' => true]),
            'postcode' => array('filter' => 'string', 'option' => ['optional' => true]),
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

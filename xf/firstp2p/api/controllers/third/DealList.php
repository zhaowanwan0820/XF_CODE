<?php
namespace api\controllers\third;
/**
 * 第三方资产标的列表
 * @author longbo
 */

use libs\web\Form;
use api\controllers\AppBaseAction;

class DealList extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'count' => array('filter' => 'int'),
            'platform' => array('filter' => 'required', 'message' => 'platform is null')
            );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $result = $this->rpc->local('ThirdpartyPtpService\dealList', array(trim($data['platform'])));

        $this->json_data = $result;
    }

}


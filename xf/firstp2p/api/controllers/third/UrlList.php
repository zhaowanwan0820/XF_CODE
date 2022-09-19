<?php
/**
 * Config Url Info
 */
namespace api\controllers\third;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use libs\utils\Logger;

class UrlList extends AppBaseAction
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
        $rs = $this->rpc->local('ThirdpartyPtpService\getUrls', [$user['id']]);
        $this->json_data = $rs;
    }
}

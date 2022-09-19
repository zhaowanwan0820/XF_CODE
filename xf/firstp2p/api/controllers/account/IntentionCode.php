<?php
/**
 * 变现通邀请码验证接口
 * @author longbo<longbo@ucfgroup.com>
 */
namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;

class IntentionCode extends AppBaseAction
{
    private $applyUrl = 'account/intention_detail?code=%s';
    private $productUrl = 'help/intention_product';
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "code" => array("filter" => "required", "message" => "code is required"),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke()
    {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $code = trim($this->form->data['code']);
        $token = trim($this->form->data['token']);
        $checkRet = $this->rpc->local('LoanIntentionService\checkQualification', array($user, $code));
        if( $checkRet['errno'] !== 0 ){
            $this->setErr(-1, $checkRet['errmsg']);
            return false;
        } else {
            $this->json_data = array( 
                        'applyUrl' => sprintf($this->applyUrl, $code),
                        'productUrl' => $this->productUrl,
                        );
            return true;
        }
    }
}

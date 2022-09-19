<?php
/**
 * Created by PhpStorm.
 * User: chenyanbing
 * Date: 2017/5/20
 * Time: 15:17
 */
namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class Authorized extends GoldBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
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
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $res = $this->rpc->local('GoldService\Auth', array($user['id']));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$res['errMsg']);
            return false;
        }
        $this->json_data = $res['data'];
    }
}

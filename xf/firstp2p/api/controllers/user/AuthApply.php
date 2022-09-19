<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\ConstDefine;

/**
 * AuthApply
 * 用户实名认证接口
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author wangjiansong@ucfgroup.com
 * @license PHP Version 4 & 5 {@link http://www.php.net/license/3_01.txt}
 */
class AuthApply extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        //$this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $ret = array(
                    'uid' => $loginUser['id'],
                    'real_name' => $loginUser['real_name'],
                    'idcard_passed' => $loginUser['idcardpassed'],
                    'photo_passed' => $loginUser['photo_passed'],
                    'idno' => $loginUser['idno'],
                    'mobile' => $loginUser['mobile'],
                    'merchant' => $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'],
                );
        $query_string = \libs\utils\Aes::buildString($ret);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $ret['signature'] = $signature;

        $this->json_data = $ret;
        return true;
    }
}


<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\BaseAction;

/**
 * 保持用户的session登陆状态
 */
class Session extends BaseAction {
    // 是否启用session
    protected $useSession = true;
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'code' => array('filter' => 'required', 'message'=>'code is empty')
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $redis = \SiteApp::init()->cache;
        $sessionId = $redis->executeCommand('GET',array($data['code']));
        if (empty($sessionId)) {
            $this->setErr('ERR_PARAMS_ERROR', 'sessionId is empty');
        }

        header("P3P: CP=CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR");
        if (!setcookie('PHPSESSID', $sessionId, 0, '/')) {
            $this->setErr('ERR_PARAMS_ERROR', 'sessionid set cookie fail');
        }

        setcookie('session_statuss', 1, 0, '/');
        $redis->executeCommand('DEL', array($data['code']));

        $this->json_data = array();
   }
}

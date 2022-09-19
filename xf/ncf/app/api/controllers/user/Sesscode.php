<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\BaseAction;

/**
 * 保持用户的session登陆状态
 */
class Sesscode extends BaseAction {
    // 是否启用session
    protected $useSession = true;
    // 是否需要授权
    protected $needAuth = true;

    public function init() {
        parent::init();
    }

    public function invoke() {
        $sessionId = $_COOKIE['PHPSESSID'];
        if (empty($sessionId)) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', 'sessionid is empty');
            return false;
        }

        $code = md5(uniqid("m_set_session"));
        \SiteApp::init()->cache->executeCommand('SETEX',array($code, 30, $sessionId));
        $this->json_data = array('code'=>$code);
    }
}

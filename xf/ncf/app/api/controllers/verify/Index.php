<?php
/**
 * 列表页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace api\controllers\verify;

use libs\web\Form;
use api\controllers\BaseAction;

class Index extends BaseAction {

    protected $useSession = true;
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "m" => array("filter"=>"string", "message"=>'ERR_USERNAME_ILLEGAL'),
            "token" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $m = '';
        if (!empty($data['m'])) {
            $m = $this->isWapCall() ? md5(strtolower($data['m'])) : strtolower($data['m']);
        }

        // 这里尽量取用户的手机号
        if (!empty($data['token'])) {
            $userInfo = $this->getUserByToken();
            $m = $userInfo ? md5($userInfo['mobile']) : '';
        }

        if (!empty($m)) {
            \FP::import('libs.utils.es_image');
            \es_image::buildImageVerify(4, 3, 'gif', 48, 22, 'verify_'.$m, '1', false);
        } else if ($this->isWapCall()) {
            \FP::import('libs.utils.es_image');
            $sessionId = session_id();
            \es_image::buildImageVerify(4, 3, 'gif', 48, 22, 'verify_' . $sessionId, '1', false);
        }
    }

    public function _after_invoke() {
    }
}

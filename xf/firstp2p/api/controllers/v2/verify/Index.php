<?php
/**
 * 列表页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace api\controllers\verify;

use libs\web\Form;
use api\controllers\BaseAction;

class Index extends BaseAction {

    protected $useSession = true;
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "m" => array("filter"=>"reg", "message"=>'ERR_USERNAME_ILLEGAL', "option"=>array("regexp"=>"/^[0-9a-fA-F]{32}$/",  'optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if(!empty($this->form->data['m'])){
            $m = $this->form->data['m'];
            require_once(dirname(__FILE__)."/../../../../system/utils/es_image.php");
            \es_image::buildImageVerify(4, 3, 'gif', 48, 22, 'verify_'.$m, '1', false);
        }else {
            $sessionId = session_id();
            require_once(dirname(__FILE__)."/../../../../system/utils/es_image.php");
            \es_image::buildImageVerify(4, 3, 'gif', 48, 22, 'verify_' . $sessionId, '1', false);
        }
    }

    public function _after_invoke() {
    }
}

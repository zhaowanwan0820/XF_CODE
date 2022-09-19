<?php
/**
 * 列表页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace api\controllers\verify;

use libs\web\Form;
use api\controllers\BaseAction;

class Index extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "m" => array("filter"=>"reg", "message"=>'ERR_USERNAME_ILLEGAL', "option"=>array("regexp"=>"/^[0-9a-fA-F]{32}$/",  'optional' => true)),
            "token" => array("filter" => "string"),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $m = strtolower($this->form->data['m']);
        if(!empty($this->form->data['token'])){
            $userInfo = $this->getUserByToken();
            $m = $userInfo ? md5($userInfo['mobile']) : '';
        }
        if(!empty($m)){
            require_once(dirname(__FILE__)."/../../../system/utils/es_image.php");
            // 这里的mode取3，表示只生成小写字符，后面的验证码验证会通过strtolower进行比较
            // 如果修改改值，需要同步修正
            \es_image::buildImageVerify(4, 3, 'gif', 48, 22, 'verify_'.$m, '1', false);
        }
    }

    public function _after_invoke() {
    }
}

<?php
/**
 * Contract.php
 *
 * @date 2017-05-25
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

/**
 * 输出投标合同
 *
 * Class Contract
 * @package api\controllers\deals
 */
class GoldProtocol extends GoldBaseAction {

    const IS_H5 = true;
    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required")
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return $this->return_error();
        }

    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_TOKEN_ERROR');
            return $this->return_error();
        }
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

    /**
     * 错误输出
     */
    public function return_error() {
        parent::_after_invoke();
        return false;
    }


}

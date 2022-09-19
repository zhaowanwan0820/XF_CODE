<?php
/**
 * TransferAgreement.php
 *
 * @author gengkuan <gengkuan@ucfgroup.com>
 */

namespace api\controllers\help;

use libs\web\Form;
use api\controllers\AppBaseAction;

class TransferAgreement extends AppBaseAction{

    const IS_H5 = true;

    protected $redirectWapUrl = '/common_adv';

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->return_error();
        }

        $advid = '用户迁移协议_2016';
        $advtitle = '站用户共享授权声明';
        $this->redirectWapUrl .= "?advid={$advid}&advtitle={$advtitle}";
    }

    /**
     * 输出页面
     */
    public function _after_invoke() {
        return true;
    }
}

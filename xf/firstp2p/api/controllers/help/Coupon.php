<?php
/**
 * Coupon.php
 *
 * @date 2014-04-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\help;

use libs\web\Form;

class Coupon extends Faq{

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->return_error();
        }
        $site_id = (!empty($this->form->data['site_id']))?$this->form->data['site_id']:1;
        // 获取基类
        $grandParent = self::getRoot();
        $grandParent::init();
        return app_redirect(url("help","faq/".app_conf('APP_HELP_ID_COUPON')."?site_id=".$site_id));
    }

}

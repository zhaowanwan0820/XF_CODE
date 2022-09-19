<?php
/**
 * CashBind.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\BaseAction;
//use web\controllers\hongbao\CashBase;

class CashRegister extends BaseAction {

    public function init() {
        app_redirect('/404.html');
        /*if (parent::init() === false) {
            return false;
        }
        // TODO SESSION check
        if (!\es_session::get(self::MOBILE_SESSION_KEY)) {
            return $this->show_error('错误的访问', '', 0 , 1);
        }*/
    }

    public function invoke() {

        /*$this->tpl->assign("host", APP_HOST);
        $this->template = "web/views/hongbao/cash_coupon/register.html";*/

    }
}

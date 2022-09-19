<?php
/**
 * BindMobile.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\discount;

use libs\web\Form;
use web\controllers\discount\HongbaoBase;

class BindMobile extends DiscountBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        if ($this->senderInfo['toUserMobile']) {
            $this->template = 'web/views/wxinvest/discount_no.html';
        } else {
            $this->template = "web/views/wxinvest/discount_pick.html";
        }

    }
}

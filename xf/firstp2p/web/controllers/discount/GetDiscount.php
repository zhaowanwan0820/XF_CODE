<?php
/**
 * GetHongbao.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\discount;

use libs\web\Form;
use web\controllers\discount\DiscountBase;
use libs\weixin\Weixin;

class GetDiscount extends DiscountBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {
        $this->tpl->assign('mobile', $this->mobile);
        $this->template = "web/views/wxinvest/lingdaoquan.html";
    }
}

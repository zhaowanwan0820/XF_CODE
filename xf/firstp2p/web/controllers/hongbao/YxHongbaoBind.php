<?php
/**
 * BindMobile.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\HongbaoBase;

class YxHongbaoBind extends YxHongbao {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        $this->tpl->assign("host", APP_HOST);
        $this->template = "web/views/hongbao/yx/qianghongbao.html";

    }
}

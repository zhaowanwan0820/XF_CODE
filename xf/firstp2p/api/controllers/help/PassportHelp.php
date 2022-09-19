<?php
namespace api\controllers\help;

use libs\web\Form;
use api\controllers\AppBaseAction;

class PassportHelp extends AppBaseAction{

    const IS_H5 = true;

    public function init() {
        parent::init();
    }
    /**
     * 输出页面
     */
    public function _after_invoke() {
    }

}

<?php
/**
 *  Api Index
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace api\controllers\status;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Index extends AppBaseAction {

    public function init() {
        parent::init();
    }

    public function invoke() {
        $this->json_data = array("res"=>"ok");
    }
}

<?php
/**
 * @author <pengchanglu@ucfgroup.com>
 **/

namespace mobile\controllers\index;

use mobile\controllers\BaseAction;

class Index extends BaseAction {

    public function init() {
    }

    public function invoke() {
        return app_redirect(url("app"));
    }
}

<?php
/**
 * View.php
 *
 * @date 2014年6月24日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\worldcup;

use web\controllers\BaseAction;
use core\dao\WorldCupModel;

class View extends BaseAction {

    public function init() {
    }

    public function invoke() {
        if (get_gmtime() >= strtotime("2014-07-13")) {
            $this->tpl->assign("overdue", 1);
            $this->template = "web/views/worldcup/result.html";
        } else {
            $this->tpl->assign("overdue", 0);
            $this->template = "web/views/worldcup/view.html";
        }
        $this->tpl->assign("host", APP_HOST);
    }
}

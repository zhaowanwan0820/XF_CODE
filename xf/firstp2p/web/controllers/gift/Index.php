<?php
/**
 * Index.php
 *
 * @date 2016年1月26日
 * @author mabaoyue <mabaoyue@ucfgroup.com>
 */

namespace web\controllers\gift;

use web\controllers\BaseAction;
use libs\utils\Finance;
use core\dao\BankModel;


class Index extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {

        

        $this->tpl->assign("inc_file","web/views/gift/index.html");
        $this->template = "web/views/gift/frame.html";
    }
}

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

class BindMobile extends HongbaoBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {

        $this->tpl->assign("host", APP_HOST);
        if ($this->bonusGroupInfo['bonus_type_id'] == 3) {
        $this->bonusGroupInfo['money'] = number_format($this->bonusGroupInfo['money'], 0, '', '');
            $this->tpl->assign('bonusDetail', $this->bonusGroupInfo);
        }
        $this->template = "web/views/hongbao/qianghongbao.html";

    }
}

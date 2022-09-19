<?php
/**
 * ----------------------------------------------
 * Card.php
 * ----------------------------------------------
 * 二维码红包活动
 * ----------------------------------------------
 * @date 2014-12-30 16:52:33
 * ----------------------------------------------
 * @author wangshijie<wangshijie@ucfgroup.com>
 * ----------------------------------------------
 */

namespace web\controllers\hongbao;

use web\controllers\BaseAction;
use libs\web\Form;
class Card extends BaseAction {

    public function init() {
        $this->form = new Form("get");
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "option" => array("optional" => true)),
        );
        $this->form->validate();
        $site_id = $this->form->data['site_id'];
        $site_id = $site_id ? $site_id : 1;
        app_redirect('/hongbao/GetHongbao?sn=' . app_conf('BONUS_EVENT_QRCODE') . '&site_id=' .$site_id);
    }

    public function invoke() {

    }
}

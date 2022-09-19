<?php
/**
 * 协议列表
 */

namespace api\controllers\help;

use libs\web\Form;
use api\controllers\AppBaseAction;

class AgreementList extends AppBaseAction
{

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
            "ajax" => array("filter" => "int"),
        );
        $this->form->validate();
        parent::init();
    }

    public function invoke() {
        $site_id = intval($this->form->data['site_id']) ?: 1;
        $ajax = intval($this->form->data['ajax']);

        $res = $this->rpc->local("ApiConfService\getAgreementList", array($site_id));
        if ($ajax) {
            $this->json_data = $res;
        } else {
            $this->tpl->assign('is_h5', 1);
            $this->tpl->assign('data', $res);
        }
    }

}

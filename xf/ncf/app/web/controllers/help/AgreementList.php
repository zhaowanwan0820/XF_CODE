<?php
/**
 * 协议列表
 */

namespace web\controllers\help;

use libs\web\Form;
use web\controllers\BaseAction;

class AgreementList extends BaseAction
{

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
        );
        parent::init();
    }

    public function invoke() {
        $site_id = \libs\utils\Site::getId();
        $ajax = intval($this->form->data['ajax']);
        $res = $this->rpc->local("ApiConfService\getAgreementList", array($site_id), 'conf');
        $this->tpl->assign('is_h5', 1);
        $this->tpl->assign('data', $res);
        $this->template = 'web/views/help/agreement_list.html';
    }

}

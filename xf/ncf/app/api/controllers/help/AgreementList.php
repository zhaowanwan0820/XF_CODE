<?php
/**
 * 协议列表
 */

namespace api\controllers\help;

use libs\web\Form;
use api\controllers\AppBaseAction;

class AgreementList extends AppBaseAction {
    protected $needAuth = false;
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/help/agreement_list';

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
        );
        $this->form->validate();
        parent::init();
    }

    public function invoke() {
        $site_id = intval($this->form->data['site_id']) ?: $this->defaultSiteId;

        $res = $this->rpc->local("ApiConfService\getAgreementList", array($site_id), 'conf');
        $this->json_data = $res;
    }

}

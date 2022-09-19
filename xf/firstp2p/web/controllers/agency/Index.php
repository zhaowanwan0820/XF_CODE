<?php

/**
 * Index.php
 *
 * @date 2014年4月26日
 * @author yangqing <yangqing@ucfgroup.com>
 */

namespace web\controllers\agency;

use web\controllers\BaseAction;
use libs\web\Form;

class Index extends BaseAction {
    
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index",'index'));
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];

        $agency = $this->rpc->local("DealAgencyService\getDealAgency", array($id));
        if (!$agency)
        {
            return app_redirect(url("index"));            
        }
        
        $seo_title = $agency['short_name'] != '' ? $agency['short_name'] : $agency['name'];

        $this->tpl->assign("page_title", $seo_title);

        $seo_keyword = $seo_title;
        $this->tpl->assign("page_keyword", $seo_keyword . ",");

        $seo_description = $agency['brief'];
        $this->tpl->assign("seo_description", $seo_description . ",");

        $this->tpl->assign("agency", $agency);
    }

}

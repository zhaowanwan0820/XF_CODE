<?php
/**
 * 网信超级账户免密协议 
 *
 * @author longbo
 *
 * */

namespace api\controllers\help;

use libs\web\Form;
use api\controllers\AppBaseAction;

class WxFreepaymentAgreement extends AppBaseAction{

    const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

    }
    public function _after_invoke() 
    {
        $site_id = (!empty($this->form->data['site_id'])) ? $this->form->data['site_id'] : 1;
        if(!empty($site_id) && $site_id != 1){
            $GLOBALS['sys_config']['TPL_SITE_DIR'] = $GLOBALS['sys_config']['TPL_SITE_LIST'][$site_id];
        }
        $this->tpl->assign("site_id", $site_id);
        $this->tpl->display($this->template);
    }

}

<?php
/**
 * UserAgreement.php
 *
 * @date 2014-03-31
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\help;

use libs\web\Form;
use api\controllers\AppBaseAction;

class UserAgreement extends AppBaseAction{

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
            "adv" => array("filter" => "string", 'option' => array('optional' => true)),
            "title" => array("filter" => "string", 'option' => array('optional' => true)),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $adv = !empty($data['adv']) ? $data['adv'] : ($data['site_id'] == 100 ? 'regist_protocol' : '客户端用户协议');
        $title = !empty($data['title']) ? $data['title'] : '用户协议';
        $this->tpl->assign("adv", $adv);
        $this->tpl->assign("title", $title);
    }
    /**
     * 输出页面
     */
    public function _after_invoke() {
        $site_id = (!empty($this->form->data['site_id'])) ? intval($this->form->data['site_id']):1;
        if(!empty($site_id) && $site_id != 1){
            $GLOBALS['sys_config']['TPL_SITE_DIR'] = $GLOBALS['sys_config']['TPL_SITE_LIST'][$site_id];
        }
        $this->tpl->display($this->template);
    }

}

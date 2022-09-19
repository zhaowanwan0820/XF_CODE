<?php
/**
 * 嘉年华页面
 *
 * @date 2014-09-14
 */

namespace web\controllers\event;

use libs\web\Form;
use web\controllers\BaseAction;

class Carnival extends BaseAction {

    public function init() {

        $this->form = new Form();
        $this->form->validate();
    }   

    public function invoke() {

        $this->tpl->assign('ios_url', app_conf('IOS_DOWNLOAD_URL'));
        $this->tpl->assign('android_url', app_conf('ANDROID_DOWNLOAD_URL'));
 
    }

}

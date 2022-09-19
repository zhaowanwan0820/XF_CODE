<?php
/**
 * FaqIndex.php
 *
 * @date 2014-04-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\help;

use libs\web\Form;
use api\controllers\AppBaseAction;

/**
 * 常见问题首页
 *
 * Class FaqIndex
 * @package api\controllers\help
 */
class FaqIndex extends AppBaseAction{

    const IS_H5 = true;

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter"=>"int", "message" => 'site_id 格式错误'),
            'token' => array("filter" => "string"),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_ADVID_EMPTY', $this->form->getErrorMsg());
            return false;
        }

        parent::init();
    }
    /**
     * 输出页面
     */
    public function _after_invoke() {
        $this->app_version = $this->getAppVersion();
        $site_id = (!empty($this->form->data['site_id']))?$this->form->data['site_id']:1;
        $GLOBALS['sys_config']['TPL_SITE_DIR'] = $GLOBALS['sys_config']['TPL_SITE_LIST'][$site_id];
        $this->tpl->assign("token", $this->form->data['token']);
        $this->tpl->assign("site", $_SERVER['HTTP_HOST']);
        $this->tpl->assign("app_version", $this->app_version);
        $this->afterInvoke();
        $this->tpl->display($this->template);
    }

    /**
     * 错误输出
     */
    public function return_error() {
        parent::_after_invoke();
        return false;
    }

    protected function getAppVersion($initVersion = 100) {
        return 471;
    }
}

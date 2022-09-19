<?php
/**
 * 多投宝信息披露规则页
 * @author wangchuanlu@ucfgroup.com
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;

class PublishRule extends DuotouBaseAction
{
    const IS_H5 = true;
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke() {
        $phWapUrl = app_conf('NCFPH_WAP_HOST').'/duotou/publish_rule?token='.$data['token'];
        return app_redirect($phWapUrl);

        $pageNum = intval($this->form->data['page_num']);
        $pageSize = intval($this->form->data['page_size']);
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userId = $user['id'];
        $this->tpl->assign('token',$this->form->data['token']);
    }

    public function _after_invoke() {
        $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }

}

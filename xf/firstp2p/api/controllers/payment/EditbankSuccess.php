<?php
/**
 * 提交审核页面
 * @author yanjun<yanjun5@ucfgroup.com>
 *
 */
namespace api\controllers\payment;
use libs\web\Form;
use api\controllers\AppBaseAction;

class EditbankSuccess extends AppBaseAction {
    const IS_H5 = true;
    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'token不能为空'),
        );
        
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            $this->tpl->assign('error',$this->error);
            return false;
        }
        $this->tpl->assign('msgTitle', '提交成功');
        $this->tpl->assign('msg', '我们将在3个工作日内完成审核，请您留意');
        $this->tpl->assign('token', $data['token']);
    }
}
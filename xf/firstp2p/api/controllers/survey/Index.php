<?php
/**
 * Index controller class file.
 *
 * @author 杜学风<duxuefeng@ucfgroup.com>
 * @date   2017-9-12
 **/

namespace api\controllers\survey;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Duotou\Enum\DealEnum;
use libs\utils\Rpc;
use core\service\UserService;

/**
 * 问卷调查的首页
 *
 * @packaged default
 * @author 杜学风<duxuefeng@ucfgroup.com>
 **/
class Index extends AppBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        //为了使该页面能在浏览器中打开
        //以后app和浏览器都可以打开的h5页面写在web里，而不是写在app中
        $_SERVER['HTTP_VERSION'] = 500;
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if (isset($data['token'])) {
            //如果token存在，则检查token
            $userInfo = $this->getUserByToken();
            if (!$userInfo) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            $this->tpl->assign('a_token', $data['token']);
        }
        $this->template = 'api/views/_v471/survey/survey.html';
    }
}

<?php
/**
* check是否可以修改密码，其他平台通行证用户不能修改密码
* @date 2016-04-20
*/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\PassportService;

class CanModifyPwd extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 非本地通行证，禁止修改密码
        if (!empty($loginUser['ppID'])) {
            $bizInfo = (new PassportService())->isThirdPassport($loginUser['mobile']);
            if (!empty($bizInfo)) {
                $app = $bizInfo['platformName'] ?: '注册端';
                $this->setErr('ERR_PARAMS_ERROR','当前账户使用网信通行证登录，请您在"'.$app.'"修改密码');
                return false;
            }
        }

        $this->json_data = [];
        return true;
    }
}

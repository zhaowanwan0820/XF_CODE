<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

/**
 * Protocol
 * 用户服务协议页
 *
 * @uses BaseAction
 * @package default
 */
class Protocol extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
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
        $user_info = $this->getUserByToken();
        $user_info['user_number'] = numTo32($user_info['id']);
        $platform_service_fee_rate = format_rate_for_show($this->rpc->local('CreditLoanService\getCreditLoanServiceRate', array($user_info['id']))*100);

        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('user_info', $user_info);
        $this->tpl->assign('platform_service_fee_rate', $platform_service_fee_rate);
        $this->tpl->assign('sign_time', to_date(get_gmtime(),"Y年m月d日"));
    }
}

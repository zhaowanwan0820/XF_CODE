<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;


/**
 * CheckServiceStatus
 * 检查服务状态
 *
 * @uses BaseAction
 * @package default
 */
class CheckServiceStatus extends SpeedLoanBaseAction
{

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'reqSource' => array('filter'=> 'required', 'message' => 'ERR_PARAMS_ERROR'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo))
        {
            $this->setErr('ERR_AUTH_FAIL');
            return false;
        }
        if (!$this->isServiceTime()) {
            $errmsg = '';
            $tomorrow = date('His') < '235959' ? '次日' : '今日';
            $tomorrow = '';
            if (intval(date('His')) > str_replace(';','', app_conf('SPEED_LOAN_SERVICE_HOUR_END').'00')) {
                $tomorrow = '次日';
            } else if (intval(date('His')) < str_replace(';','', app_conf('SPEED_LOAN_SERVICE_HOUR_START').'00')) {
                $tomorrow = '今日';
            }
            $serviceTime = $tomorrow.str_replace(';',':', app_conf('SPEED_LOAN_SERVICE_HOUR_START'));
            switch ($data['reqSource'])
            {
                case 'loan':
                    $errmsg = '温馨提示：请于'.$serviceTime.'后申请借款';
                    break;
                case 'normal':
                default:
                    $errmsg = '温馨提示：请于'.$serviceTime.' 后操作在线还款';
            }
            $this->setErr('ERR_MANUAL_REASON', $errmsg);
            return false;
        }
    }
}

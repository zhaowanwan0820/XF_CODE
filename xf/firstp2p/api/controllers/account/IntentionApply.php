<?php
/**
 * 变现通邀请码验证接口
 * @author longbo<longbo@ucfgroup.com>
 */
namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;


class IntentionApply extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            'money' => array('filter' => 'int'),
            'time' => array('filter' => 'int'),
            'phone' => array('filter' => 'int'),
            'addr' => array('filter' => 'string'),
            'agreement' => array('filter' => 'int'),
            'code' => array('filter' => 'string'),
            'company' => array('filter' => 'string'),
            'wl' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke()
    {
        $this->form->data['wl'] = urldecode( $this->form->data['wl'] );
        $this->form->data['company'] = urldecode( $this->form->data['company'] );
        $this->form->data['addr'] = urldecode( $this->form->data['addr'] );

        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $code = trim($this->form->data['code']);
        $checkRet = $this->rpc->local('LoanIntentionService\checkQualification', array($user, $code));
        if ($checkRet['errno'] !== 0) {
            $this->setErr('ERR_MANUAL_REASON', $checkRet['errmsg']);
            return false;
        }

        if ($this->form->data['agreement'] != 1) {
            $this->setErr('ERR_MANUAL_REASON', '表单填写有误，请重新填写');
            return false;
        }
        if ($this->form->data['time'] <= 0 || $this->form->data['time'] > 36) {
            $this->setErr('ERR_MANUAL_REASON', '表单填写有误，请重新填写');
            return false;
        }
        $addNewApplyRet = $this->rpc->local('LoanIntentionService\addNewIntention',array($user, $this->form->data));
        if ($addNewApplyRet['errno'] !== 0) {
            if ($addNewApplyRet['errno'] == 5) {
                $errmsg = '申请已经提交，请勿重复申请';
            } elseif ($addNewApplyRet['errno'] == 4) {
                $errmsg = '借款金额必须为1000的整数倍';
            } else {
                $errmsg = '申请提交有误，请稍后重试';
            }
            $this->setErr('ERR_MANUAL_REASON', $errmsg);
            return false;
        }
        $this->json_data = array();
        return true;
    }
}

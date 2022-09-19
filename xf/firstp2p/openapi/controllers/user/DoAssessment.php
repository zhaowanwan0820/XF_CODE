<?php
/**
 * @abstract openapi用户风险评测
 *
 * @author yanjun<yanjun5@ucfgroup.com>
 * @date 2016年 12月 19日
 */
namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\ProtoUser;

class DoAssessment extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required', 'message'=> 'oauth_token is required'),
            'score' => array('filter' => 'required', 'message'=> 'score is null'),
            'question_id' => array('filter' => 'required', 'message'=> 'question_id is null'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (empty($userInfo) || $userInfo->userId <= 0) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if ($userInfo->idcardpassed != 1) {
            $this->setErr('ERR_IDENTITY_NO_VERIFY');
            return false;
        }

        $questionId = intval($data['question_id']);
        $score = intval($data['score']);
        $userId = intval($userInfo->userId);

        $request = new SimpleRequestBase();
        $request->setParamArray(array('userId' => $userId, 'questionId' => $questionId, 'score' => $score));
        $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpRiskAssessment',
                'method' => 'doAssess',
                'args' => $request
        ));

        $this->json_data = $response->toArray();
        return true;
    }

}

<?php
/**
 * 用户风险评测 
 * @author longbo 
 **/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\RiskAssessmentService;

class RiskAssess extends AppBaseAction
{
    const IS_H5 = true;

    public function init()
    {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'backurl' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            $this->return_error();
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        $siteId = \libs\utils\Site::getId();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            $this->return_error();
        }
        
        $RiskAssessmentService = new RiskAssessmentService();
        $question = $RiskAssessmentService->getQuestion();


        if (empty($question)) {
            $this->setErr(0, '评估暂未开放');
            $this->return_error();
        }

        $ura_data = $RiskAssessmentService->getUserRiskAssessmentData($loginUser['id']);
        if (empty($ura_data)) {
            $this->setErr(0, '用户评估数据获取失败');
            $this->return_error();
        }
        if (!isset($question['limit_type'])
            || ($question['limit_type'] == 1 && $ura_data['assess_num'] >= $question['limit_times'])) {
            $this->setErr(0, '您已无评估次数,请稍后重试');
            $this->return_error();
        }
 

        $this->tpl->assign("question", $question);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('siteId', $siteId);
        // var_dump($siteId);
        if(!empty($data['backurl'])){
            $this->tpl->assign('backurl', $data['backurl']);
        }
    }

    public function return_error()
    {
        parent::_after_invoke();
        return false;
    }
}

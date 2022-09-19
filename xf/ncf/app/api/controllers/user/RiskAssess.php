<?php
/**
 * 用户风险评测
 * @author longbo
 **/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\risk\RiskAssessmentService;

class RiskAssess extends AppBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/user/risk_assess';

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'backurl' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        $siteId = \libs\utils\Site::getId();
        $RiskAssessmentService = new RiskAssessmentService();
        $question = $RiskAssessmentService->getQuestion();

        if (empty($question)) {
            $this->setErr(0, '评估暂未开放');
        }

        $ura_data = $RiskAssessmentService->getUserRiskAssessmentData($loginUser['id']);
        if (empty($ura_data)) {
            $this->setErr(0, '用户评估数据获取失败');
        }

        if (!isset($question['limit_type'])
            || ($question['limit_type'] == 1 && $ura_data['assess_num'] >= $question['limit_times'])) {
            $this->setErr(0, '您已无评估次数,请稍后重试');
        }

        $result["question"] = $question;
        $result['token'] = $data['token'];
        $result['siteId'] = $siteId;
        if(!empty($data['backurl'])){
            $result['backurl'] = $data['backurl'];
        }

        $this->json_data = $result;
    }
}

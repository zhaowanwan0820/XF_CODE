<?php
/**
 * 保存风险测评 
 * @author longbo
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\RiskAssessmentService;
use libs\utils\Logger;

class DoAssess extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
            'score' => array('filter' => 'required', 'message'=> 'score is null'),
            'question_id' => array('filter' => 'required', 'message'=> 'question_id is null'),
            'backurl' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        if ($user['idcardpassed'] != 1) {
            return $this->setErr(0, '未进行身份认证');
        }

        $ques_id = intval($data['question_id']);
        $score = intval($data['score']);

        $RiskAssessmentService = new RiskAssessmentService();
        $question = $RiskAssessmentService->getQuestionById($ques_id);
        if (empty($question) || $question['status'] == 0) {
            return $this->setErr(0, '风险评估已经失效');
        }

        if ($question['limit_type'] == 1) {
            $RiskAssessmentService = new RiskAssessmentService();
            $ura_data = $RiskAssessmentService->getUserRiskAssessmentData($user['id'], $ques_id);
            Logger::info("Assess Request. ura_data: " . json_encode($ura_data));
            if (empty($ura_data)) {
                return $this->setErr(0, '用户评估数据获取失败');
            }

            if (!isset($question['limit_type'])
                || ($question['limit_type'] == 1 && $ura_data['assess_num'] >= $question['limit_times'])
            ) {
                return $this->setErr(0, '您已超出评估次数');
            }
        }

        $assess_result = $RiskAssessmentService->assess($user['id'], $ques_id, $score);
        Logger::info("Assess Request. assess_result: " . json_encode($assess_result));
        if (empty($assess_result)) {
            return $this->setErr(0, "评级失败,请重试");
        }

        if(!empty($data['backurl'])){
            $assess_result['backurl'] = $data['backurl'];
        }
        $this->json_data = $assess_result;
    }

}

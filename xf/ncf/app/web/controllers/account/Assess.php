<?php
/**
 * Assess.php
 *
 * @date 2016年6月13日 11:07:33
 * @author weiwei12 <weiwei12@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\risk\RiskAssessmentService;
use libs\utils\Logger;
use libs\web\Form;

class Assess extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'backurl'=>array("filter"=>'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        Logger::info("Assess Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if (empty($GLOBALS['user_info'])) {
            return $this->show_error('请先登录', '', 1, 0, '/user/login');
        }

        $user = $GLOBALS['user_info'];
        if ($user['idcardpassed'] != 1) {
            return $this->show_error('请先进行身份认证。', '', 1, 0, '/account/setup');
        }

        $ques_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (empty($ques_id)) {
            return $this->show_error('参数非法', '', 1, 0, '/account/setup');
        }

        $RiskAssessmentService = new RiskAssessmentService();
        $question = $RiskAssessmentService->getQuestionById($ques_id);
        if (empty($question) || $question['status'] == 0) {
            return $this->show_error('风险评估已经失效,请刷新后重试', '', 1);
        }

        //检查提交的分值
        foreach ($question['subjects'] as $key => $subject) {
            if (!isset($_POST['input' . ($key + 1)])) {
                return $this->show_error('问题' . ($key + 1) . ' 还未作答', '', 1);
            }
            $input_item = $_POST['input' . ($key + 1)];
            if (!is_numeric($input_item)) {
                return $this->show_error('非法操作1,请重试', '', 1);
            }
            //检查提交的分数是否配置过
            $flag = false;
            foreach ($subject['items'] as $item) {
                if ($item['score'] == $input_item) {
                    $flag = true;
                    break;
                }
            }
            if (!$flag) {
                return $this->show_error('非法操作2,请重试', '', 1);
            }
        }

        //有期限限制
        if ($question['limit_type'] == 1) {
            //获取用户评估数据
            $RiskAssessmentService = new RiskAssessmentService();
            $ura_data = $RiskAssessmentService->getUserRiskAssessmentData($GLOBALS['user_info']['id'], $ques_id);
            Logger::info("Assess Request. ura_data: " . json_encode($ura_data));
            if (empty($ura_data)) {
                return $this->show_error('用户评估数据获取失败,请稍后重试', '', 1, 0, '/account/setup');
            }

            if (!isset($question['limit_type'])
                || ($question['limit_type'] == 1 && $ura_data['assess_num'] >= $question['limit_times'])
            ) {
                return $this->show_error('您已无评估次数,请稍后重试', '', 1, 0, '/account/setup');
            }
        }

        //计算分数
        $score = $this->calcScore();

        //评级
        $assess_result = $RiskAssessmentService->assess($GLOBALS['user_info']['id'], $ques_id, $score);
        Logger::info("Assess Request. assess_result: " . json_encode($assess_result));
        if (empty($assess_result)) {
            return $this->show_error('评级失败,请重试', '', 1, 0, '/account/setup');
        }

        $data['status'] = 1;
        $data['info'] = '成功';
        $data['data'] = $assess_result;
        $data['jump'] = !empty($this->form->data['backurl']) ? $this->form->data['backurl'] : '/account/setup';

        Logger::info("Assess Response. data: " . json_encode($data));
        echo json_encode($data);
    }

    private function calcScore()
    {
        $score = 0;
        foreach ($_POST as $key => $val) {
            if (!preg_match('/^input(\d)+$/', $key)) {
                continue;
            }
            $score += intval($val);
        }
        return $score;
    }
}
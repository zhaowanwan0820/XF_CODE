<?php
/**
 * 用户风险评测 
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\UserRiskTestService;

class RiskTest extends BaseAction 
{
    const TEST_KEY = 'DJS_RISK_TEST';

    static $risk_level = array( 
            1 => array('min' => 0, 'max' => 20, 'desc' => '谨慎型'),
            2 => array('min' => 21, 'max' => 40, 'desc' => '稳健型'),
            3 => array('min' => 41, 'max' => 60, 'desc' => '平衡型'),
            4 => array('min' => 61, 'max' => 80, 'desc' => '进取型'),
            5 => array('min' => 81, 'max' => 100, 'desc' => '激进型'),
        );

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'score' => array("filter" => "required", "message" => "score is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $score = intval($data['score']);
        $desc = '';
        $level = 1;
        $score = ($score < 0) ? 0 : $score;
        $score = ($score > 100) ? 100 : $score;
        foreach (self::$risk_level as $key => $val) {
            if ($score >= $val['min'] && $score <= $val['max']) {
                $desc = $val['desc'];
                $level = $key;
                break;
            }
        }
        UserRiskTestService::setTestResult($userInfo->userId, $score);
        $this->json_data = array('score' => $score, 'desc' => $desc, 'level' => $level);
        return;
    }
}

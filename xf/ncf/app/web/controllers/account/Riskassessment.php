<?php
/**
 * RiskAssessment.php
 *
 * @date 2016年6月12日 16:44:33
 * @author weiwei12 <weiwei12@ucfgroup.com>
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\risk\RiskAssessmentService;
use libs\web\Form;
use libs\utils\Site;

class Riskassessment extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'backurl'=>array("filter"=>'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $user = $GLOBALS['user_info'];
        if($user['idcardpassed'] != 1){
            return $this->show_error('请先进行身份认证。');
        }

        $RiskAssessmentService = new RiskAssessmentService();
        $question = $RiskAssessmentService->getQuestion();
        if (empty($question)) {
            return $this->show_error('评估页面暂未开放,请稍后重试');
        }

        //获取用户评估数据
        $ura_data = $RiskAssessmentService->getUserRiskAssessmentData($user['id']);
        if (empty($ura_data)) {
            return $this->show_error('用户评估数据获取失败,请稍后重试');
        }

        if (!isset($question['limit_type'])
            || ($question['limit_type'] == 1 && $ura_data['assess_num'] >= $question['limit_times'])) {
            return $this->show_error('您已无评估次数,请稍后重试');
        }

        $this->tpl->assign("question", $question);
        $backUrl = !empty($this->form->data['backurl']) ? $this->form->data['backurl'] : '';
        $this->tpl->assign("backurl", $backUrl);
        $siteId = Site::getId();
        $this->tpl->assign("siteId", $siteId);
    }
}
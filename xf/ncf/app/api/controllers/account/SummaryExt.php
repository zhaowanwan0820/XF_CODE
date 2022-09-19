<?php

/**
 * SummaryExt.php
 * 
 * Filename: SummaryExt.php
 * Descrition: 获取用户相关统计信息,比较耗时的计算,与summary高频调用分开
 * Author: yutao@ucfgroup.com
 * Date: 16-2-17 下午4:03
 */

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\account\AccountService;
use core\service\risk\RiskAssessmentService;

class SummaryExt extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            "token" => array(
                "filter" => "required",
                "message" => "ERR_GET_USER_FAIL"
            )
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->user;
        $userStatics = AccountService::getUserSummaryExt($userInfo['id']);

        //用户总资产情况
        // 已投资产增加智多鑫待投本金
        $total_corpus = bcadd($userStatics['user_asset']['corpus'], $userStatics['duotou_user_asset']['dt_norepay_principal'], 2);
        $result['total_corpus'] = number_format($total_corpus, 2, '.', '');
        $result['total_income'] = number_format($userStatics['user_asset']['income'], 2, '.', '');
        $result['total_earning_all'] = number_format($userStatics['user_asset']['earning_all'], 2, '.', '');

        //p2p(包括大金所不包括多投)
        $result['corpus'] = number_format($userStatics['p2p_user_asset']['corpus'], 2, '.', '');
        $result['income'] = number_format($userStatics['p2p_user_asset']['income'], 2, '.', '');
        $result['earning_all'] = number_format($userStatics['p2p_user_asset']['earning_all'], 2, '.', '');
        //多投
        $result['dt_corpus'] = number_format($userStatics['duotou_user_asset']['dt_norepay_principal'], 2, '.', '');
        //$result['dt_income'] = number_format($userStatics['duotou_user_asset']['remain_interest'], 2, '.', '');
        $result['dt_earning_all'] = number_format($userStatics['duotou_user_asset']['dt_repay_interest'], 2, '.', '');
        //大金所
        $result['js_corpus'] = number_format($userStatics['js_user_asset']['norepay_principal'], 2, '.', '');
        $result['js_income'] = number_format($userStatics['js_user_asset']['norepay_earnings'], 2, '.', '');
        $result['js_earning_all'] = number_format($userStatics['js_user_asset']['total_earnings'], 2, '.', '');
        //存管
        $result['sv_corpus'] = number_format($userStatics['sv_user_asset']['norepay_principal'], 2, '.', '');
        $result['sv_income'] = number_format($userStatics['sv_user_asset']['norepay_earnings'], 2, '.', '');
        $result['sv_earning_all'] = number_format($userStatics['sv_user_asset']['total_earnings'], 2, '.', '');

        // 风险评估
        $riskData = array();
        // TODO 北京IDC下掉风险评估
        if (get_cfg_var('idc_environment') != 'BEIJINGZHONGJINIDC') {
            if ($userInfo['idcardpassed'] == 1) {
                $riskService = new RiskAssessmentService();
                $riskRes = $riskService->getUserRiskAssessmentData($userInfo['id']);
                $riskData['level_name'] = empty($riskRes['last_level_name']) ? '' : $riskRes['last_level_name'];
                if (isset($riskRes['remaining_assess_num'])) {
                    $riskData['remaining_num'] = intval($riskRes['remaining_assess_num']);
                } else {
                    $riskData['remaining_num'] = 1;
                }
                $riskData['status'] = !empty($riskRes['ques']) ? 1 : 0;
                $result['risk_data'] = $riskData;
            }
        }

        $this->json_data = $result;
    }
}

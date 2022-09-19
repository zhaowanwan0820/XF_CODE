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

class SummaryExt extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array("token" => array("filter" => "required", "message" => "token不能为空"));
        $this->form->validate();

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        }

        $userStatics = $this->rpc->local('AccountService\getUserSummaryExt', array($userInfo['id']));

        $p2pUserStatics = (new \core\service\ncfph\AccountService())->getSummaryExt($userInfo['id']);
        $userStatics = $this->mergeP2pData($userStatics, $p2pUserStatics);

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

        //记录用户浏览时候的资产信息
        $userAssetRecord = array(
            'userAssetRecord',
            __CLASS__,
            __FUNCTION__,
            'userId:'.$userInfo['id'],
            'assetInfo:'.json_encode(
                array(
                    'corpus' => $result['corpus'],
                    'income' => $result['income'],
                    'earning_all' => $result['earning_all'],
                    'js_corpus' => $result['js_corpus'],
                    'js_income'  => $result['js_income'],
                    'js_earning_all' => $result['js_earning_all'],
                    'dt_corpus' => $result['dt_corpus'],
                    'dt_income'  => empty($result['dt_income']) ? 0: $result['dt_income'],
                    'dt_earning_all' => $result['dt_earning_all'],
                    'total_corpus' => $result['total_corpus'],
                    'total_income' => $result['total_income'],
                    'total_earning_all' => $result['total_earning_all'],
                )
            ),
        );
        Logger::debug(implode(',',$userAssetRecord));

        //风险评估
        $riskData = array();
        // TODO 北京IDC下掉风险评估
        if (get_cfg_var('idc_environment') != 'BEIJINGZHONGJINIDC') {
            if ($userInfo['idcardpassed'] == 1) {
                $riskRes = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array($userInfo['id']));
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

    private function mergeP2pData($wxData, $p2pData)
    {
        $fields = [
            'p2p_user_asset',
            'duotou_user_asset',
            'js_user_asset',
            'user_asset',
            'sv_user_asset'
        ];

        $data = [];
        foreach ($fields as $field) {
            $tmp = [];
            foreach ($wxData[$field] as $k => $v) {
                $tmp[$k] = bcadd($wxData[$field][$k], $p2pData[$field][$k], 2);
            }
            $data[$field] = $tmp;
        }

        return $data;
    }

}

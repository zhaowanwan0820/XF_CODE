<?php

/**
 * DealLoadDetail.php
 *
 * @date 2014-03-21
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use libs\web\Form;
use core\service\DealLoanTypeService;

/**
 * 用户已投资的详情页
 * 复用订单详情页部分信息
 *
 * Class DealLoadDetail
 * @package api\controllers\account
 */
class DealLoadDetail extends \api\controllers\deals\Detail {
    const IS_H5 = true;

    public function init() {
        // 因不是直接继承BaseAction，获取主init
        $grandParent = self::getRoot();
        $grandParent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int", "message" => "id is error"),
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->return_error();
        }

        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            $this->return_error();
        }

        $this->form->data['id'] = intval($this->form->data['id']);
    }

    public function invoke() {
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;
        $load_id = $data['id'];
        $deal_load = $this->rpc->local('DealLoadService\getDealLoadDetail', array($load_id, true, true));
        //请求普惠数据---- Start -----//
        if (empty($deal_load) || (isset($deal_load['deal']['deal_type']) && $deal_load['deal']['deal_type'] == '0')) {
            // 网信查不到的直接转到普惠
            $phWapUrl = app_conf('NCFPH_WAP_HOST').'/account/deal_load_detail?id='.$load_id.'&token='.$data['token'];
            return app_redirect($phWapUrl);
        }
        //请求普惠数据---- End -----//

        if (empty($deal_load)) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            $this->return_error();
        }

        if ($deal_load['user_id'] != $loginUser['id']) {
            // 获取oauth用户信息失败
            $this->setErr('ERR_GET_USER_FAIL'); 
            $this->return_error();
        }

        $deal = $deal_load['deal'];
        $deal['repay_time'] = ($deal['deal_type'] == 1 ? ($deal['lock_period'] + $deal['redemption_period']) . '~' : '') . $deal['repay_time'];
        $deal['loantype_name'] = $deal['deal_type'] == 1 ? '提前' . $deal['redemption_period'] . '天申赎' : $deal['loantype_name'];
        $deal['deal_compound_day_interest'] = 0;
        $deal['compound_time'] = '';
        if ($deal['deal_type'] == 1) {
            if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
                $loan_repay_list = $this->rpc->local('DealLoanRepayService\getLoanRepayListByLoanId', array($load_id));
                //利滚利 待赎回 预期收益
                if (empty($loan_repay_list)) {
                    $sum = $this->rpc->local('DealCompoundService\getCompoundMoneyByDealLoadId', array($load_id, get_gmtime()));
                    $deal['deal_compound_day_interest'] = number_format($sum - $deal_load['money'], 2);
                } else { // 申请了赎回的才有到账日期 是否需要考虑已还清的deal_status==5不展示下面
                    foreach ($loan_repay_list as $val) {
                        if ($val['type'] == 9) {
                            $deal_load['income'] = $val['money'];
                        }
                    }
                    $loanRepay = array_pop($loan_repay_list);
                    $deal['compound_time'] = to_date($loanRepay['time'], 'Y-m-d');
                }
            }
            //该笔投资的通知贷状态
            $deal_load_compound_status = $this->rpc->local('DealLoadService\getDealLoadCompoundStatus', array($load_id));
            $deal['deal_compound_status'] = $deal['deal_status'] == 4 && $deal_load_compound_status === '0' ? '3' : strval($deal_load_compound_status);
        }
        $deal['maxRate'] = number_format($deal['max_rate'], 2);
        $deal['createTime'] = to_date($deal['create_time']);

        //增加标的是否属于专项标标识
        $deal['isDealZX'] = $this->rpc->local('DealService\isDealEx', array($deal['deal_type']));
        //状态为投资中或者状态已经还清但是在上线该规则之后还清的显示提示信息
        if ($deal['deal_type'] == 0 && ($deal['deal_status'] == 4 || ($deal['deal_status'] == 5 && ($deal['last_repay_time'] + 28800 - strtotime('2017-03-09')) > 0))) {
            $fankuan_days = floor((time() - $deal['repay_start_time'] - 28800) / 86400) + 1;
            if ($fankuan_days > 7) {
                $deal['p2p_show'] = 1;
                if ($deal['borrow_amount'] > 10000) {
                    $deal['p2p_show_detail'] = '借款人已按照既定的资金用途使用资金。';
                } else {
                    $deal['p2p_show_detail'] = '该项目金额低于1万元（含），不对资金用途进行复核。';
                }
            }
        }
        $this->tpl->assign("deal", $deal);
        $this->tpl->assign("deal_load", $deal_load);

        //合同信息
        list($contract_info['is_attachment'], $contract_info['cont_list']) = $this->rpc->local('ContractInvokerService\getContractListByDealLoadId', array('remoter', $load_id));
        $this->tpl->assign("is_attachment", $contract_info['is_attachment']);
        $this->tpl->assign("contract_list", $contract_info['cont_list']);

        //回款计划
        $loan_repay_list = $this->rpc->local('DealLoanRepayService\getLoanRepayListByLoanId', array($load_id));
        $this->tpl->assign("loan_repay_list", $loan_repay_list);

        $this->tpl->assign("token", $data['token']);

        //查询项目简介
        if ($deal['project_id']) {
            $project = $this->rpc->local('DealProjectService\getProInfo', array('id' => $deal['project_id'], 'deal_id' => $deal['id']));
        }
        $this->tpl->assign('project_intro', isset($project['intro_html']) ? $project['intro_html'] : '');

        //贷后信息
        $this->tpl->assign('post_loan_message', $project['post_loan_message']);

        $this->getCompanyAndLoanList($deal); //复用订单详情页部分信息

        if ($this->app_version < 320) {
            if ($deal['deal_type'] == 1) {
                $this->template = $this->getTemplate('deal_load_detail_v2_tzd');
            }
        } else {
            if ($deal['deal_type'] == 1) {
                $this->setViewVersion('_v32');
                $this->template = $this->getTemplate('deal_load_detail_tzd');
            }
        }
    }

}

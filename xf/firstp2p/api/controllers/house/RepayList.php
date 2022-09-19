<?php
/**
 * 网信房贷 还款计划列表
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.28
 */

namespace api\controllers\house;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use NCFGroup\Protos\Ptp\Enum\HouseEnum;

class RepayList extends AppBaseAction
{

    const IS_H5 = true;
    private static $REPAY_PLAN = array(
        'repay_none' => 1,
        'repay_finish' =>2,
        'repay_finish_overdue' => 3,
        'repay_finish_ahead' => 4
    );

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'order_id' => array('filter' => 'string', 'option' => array('optional' => true))
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (!empty($data['order_id'])) {
            $repayList = $this->rpc->local('HouseService\getRepayList', array($loginUser['id'], $data['order_id']), 'house');
            $this->tpl->assign('payback_plan_result', $repayList);
            // 将还款计划分组：分为待还款、已还款
            foreach ($repayList['payback_plan']['list'] as $key => $item) {
                $item['index'] = $key + 1;
                if ($item['status'] == HouseEnum::STATUS_REPAY_PLAN_NOT ||
                    $item['status'] == HouseEnum::STATUS_REPAY_PLAN_OVERDUE) {
                    $repayPlanNot[] = $item;
                } elseif ($item['status'] == HouseEnum::STATUS_REPAY_PLAN_NORMAL_FINISH ||
                    $item['status'] == HouseEnum::STATUS_REPAY_PLAN_OVERDUE_FINISH ||
                    $item['status'] == HouseEnum::STATUS_REPAY_PLAN_AHEAD_FINISH) {
                    $repayPlanFinish[] = $item;
                }
            }
            $this->tpl->assign('repayPlanNot', $repayPlanNot);
            $this->tpl->assign('repayPlanFinish', $repayPlanFinish);
            // 如果有未结清的，获取最近一期应还款的日期
            if (!empty($repayPlanNot)) {
                $endNotRepayPlan = current($repayPlanNot);
                $endNotRepayPlanDate = array(
                    'month' => date('m', $endNotRepayPlan['payback_date']),
                    'day' => date('d', $endNotRepayPlan['payback_date']),
                    'money' => $endNotRepayPlan['all_money']
                );
                $this->tpl->assign('endNotRepayPlanDate', $endNotRepayPlanDate);
            }

            foreach ($repayList['payback_plan']['list'] as $item){
                $list[] = $item;
            }
            $this->tpl->assign('planList', json_encode($list,JSON_UNESCAPED_UNICODE));
        }
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('repay_status', self::$REPAY_PLAN);
        $this->template = $this->getTemplate('repayment_plan');
    }
}

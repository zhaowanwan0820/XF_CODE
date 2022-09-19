<?php
/**
 * 异步查询借款期间收益与收益率
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\deal;

use app\models\dao\Deal;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\DealLoanTypeService;
use core\service\DealService;

/**
 * 异步查询借款期间收益与收益率
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/
class Async extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'deal_id' => array('filter' => 'string'),
            'principal' => array('filter' => 'float', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            return ajax_return(array());
        }
    }

    public function invoke() {
        $deal_id  = $ec_id = $this->form->data['deal_id'];
        //deal_type 标识是否为多投标的
        $deal_type = empty($this->form->data['deal_type']) ? '' : $this->form->data['deal_type'];
        if($deal_type != 'duotou') {
            $deal_id = Aes::decryptForDeal($ec_id);
        }
        $principal = $this->form->data['principal'];
        //deal_type 标识是否为多投标的
        //$deal_type = $this->form->data['deal_type'];
        $user_id = intval($GLOBALS['user_info']['id']);
        $res_arr = array();
        //业务日志参数
        $this->businessLog['busi_name'] = '异步查询收益率';
        if (!$user_id || $deal_id <= 0 || $principal<0) {
            return ajax_return($res_arr);
        }

        $user_info = $this->rpc->local("UserService\getUserViaSlave", array($user_id));
        $earning = $this->rpc->local("EarningService\getEarningMoney", array($deal_id, $principal, true));

        $rate = empty($principal) ? 0 : $earning / $principal * 100;
        $res_arr['money'] = number_format(round($earning, 2), 2);
        $res_arr['money_repay']= round($earning, 2) + $principal;
        $res_arr['rate'] = number_format(round($rate, 2), 2);

        $all_bonus = $this->rpc->local('BonusService\get_useable_money', array($user_id));

        if($principal == 0){
            $use_bonus['money'] = 0;
        }else{
            $use_bonus = $this->rpc->local('BonusService\get_useable_money', array($user_id,$principal,false, '', '', true));
        }

        // 加上存管账户余额(不含红包)
        $balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($user_id));
        $userTotalMoney = bcadd($balanceResult['supervisionBalance'],$user_info['money'],2);

        if($all_bonus['money'] == 0){
            $res_arr['tips'] = '';
        }elseif(bccomp($principal, bcadd($userTotalMoney, $all_bonus['money'], 2), 2) == 1){
            $res_arr['tips'] = '您的余额不足，请先充值。';
        }elseif(bccomp($principal, bcadd($userTotalMoney, $use_bonus['money'], 2), 2) == 1){
            $res_arr['tips'] = '您的' . app_conf('NEW_BONUS_TITLE') . '不符合投资条件，请先充值。';
        }else{
            $res_arr['tips'] = sprintf('本次使用账户现金%.2f元，系统自动为您使用' . app_conf('NEW_BONUS_TITLE') . '%.2f' . app_conf('NEW_BONUS_UNIT') . '。',bcsub($principal,$use_bonus['money'], 2), $use_bonus['money']);
        }

        if($deal_type == 'duotou'){
            $res_arr['dtb'] = $this->rpc->local("ContractPreService\getDtbContractPre", array($deal_id, $user_id,$principal));
        }else{
            $res_arr['contract'] = $this->rpc->local('ContractInvokerService\getFetchedDealContractList',array('viewer', $deal_id, $user_id, $principal));
        }

        return ajax_return($res_arr);
    }
}

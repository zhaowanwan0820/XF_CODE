<?php
/**
 * 异步查询借款期间收益与收益率
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\deal;

use core\enum\DealEnum;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\deal\DealLoanTypeService;
use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\deal\EarningService;
use core\service\bonus\BonusService;
use core\service\account\AccountService;
use core\service\contract\ContractInvokerService;
use core\service\contract\ContractPreService;
use core\enum\UserAccountEnum;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractTplIdentifierEnum;
use libs\utils\Logger;

class Async extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'deal_id' => array('filter' => 'string'),
            'principal' => array('filter' => 'float', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'string', 'option' => array('optional' => true)),
            'tpl_identifier_id' => array('filter' => 'string', 'option' => array('optional' => true)), // 1:顾问协议 2:债权转让协议
        );
        if (!$this->form->validate()) {
            return ajax_return(array());
        }
    }

    public function invoke() {
        $deal_id  = $ec_id = $this->form->data['deal_id'];
        //deal_type 标识是否为多投标的
        $deal_type = $this->form->data['deal_type'];
        if($deal_type != 'duotou') {
            $deal_id = Aes::decryptForDeal($ec_id);
        }
        $principal = $this->form->data['principal'];
        //deal_type 标识是否为多投标的
        $deal_type = $this->form->data['deal_type'];
        // 智多新合同才需要$tpl_identifier_id这个参数
        $tpl_identifier_id = isset($this->form->data['tpl_identifier_id']) ? $this->form->data['tpl_identifier_id'] : 1;
        $user_id = intval($GLOBALS['user_info']['id']);
        $res_arr = array();

        if (!$user_id || $deal_id <= 0 || $principal<0) {
            return ajax_return($res_arr);
        }



        $user_info = isset($GLOBALS['user_info']) ? $GLOBALS['user_info'] : array();
        $earningService = new EarningService();
        $earning = $earningService->getEarningMoney($deal_id, $principal, true);

        $rate = $principal == 0 ? 0 : $earning / $principal * 100;
        $res_arr['money'] = number_format(round($earning, 2), 2);
        $res_arr['money_repay']= round($earning, 2) + $principal;
        $res_arr['rate'] = number_format(round($rate, 2), 2);


        $all_bonus = BonusService::getUsableBonus($user_id, false, 0, false, $GLOBALS['user_info']['is_enterprise_user']);

        $use_bonus['money'] = 0;
        
        if($principal == 0){
            $use_bonus['money'] = 0;
        }else{
            $use_bonus = BonusService::getUsableBonus($user_id,false,$principal, '', $GLOBALS['user_info']['is_enterprise_user']);
        }
        // 是否可以使用红包
        if (isset($user_info['canUseBonus']) && empty($user_info['canUseBonus'])){
            $all_bonus['money'] = 0;
            $use_bonus['money'] = 0;
            Logger::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$user_info['canUseBonuse']);
        }

        // 红包使用总开关
        $isBonusEnable = BonusService::isBonusEnable();
        if (empty($isBonusEnable)){
            $all_bonus['money'] = 0;
            $use_bonus['money'] = 0;
            Logger::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$isBonusEnable);
        }

        // 加上存管账户余额(不含红包)
        $balanceResult = AccountService::getAccountMoney($user_id,UserAccountEnum::ACCOUNT_INVESTMENT);
        $userTotalMoney = bcadd($balanceResult['money'],$user_info['money'],2);

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
            //TODO $tpl_identifier_id == 2这种判断为临时措施，需要紧急上线。下次加借款合同时，使用标准的合同列表方式获取。
            if($tpl_identifier_id == 2){
                $res_arr['dtb'] = ContractInvokerService::getFetchedContract('pre',array(), $deal_id, ContractServiceEnum::TYPE_DT, ContractTplIdentifierEnum::DTB_TRANSFER);
            }else{
                $res_arr['dtb'] = ContractInvokerService::getDtbContractPre('pre',$deal_id, $user_id, $principal);
            }
        }else{
            $res_arr['contract'] = ContractInvokerService::getFetchedDealContractList('viewer', $deal_id, $user_id, $principal);
        }

        return ajax_return($res_arr);
    }
}

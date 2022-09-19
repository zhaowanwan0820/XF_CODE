<?php
/**
 * BidConfirm controller class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;

/**
 * 投标确认接口
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class BidConfirm extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'id' => array('filter'=>'required', 'message'=> 'ERR_DEAL_NOT_EXIST'),
            'money' => array('filter'=>array($this, "valid_money"), 'message'=> 'ERR_MONEY_FORMAT'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function valid_money($value) {
        if ($value == null) {
            return true;
        }
        if (floatval($value) == 0) {
            return false;
        }
        if (!preg_match("/^[-]{0,1}[\d]*(\.\d{1,2})?$/", $value)) {
            return false;
        }
        return true;
    }

    public function invoke() {
        $data = $this->form->data;

        $user_info = $this->getUserByToken();
        if (!$user_info) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$user_info = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $user_info;
        $remain = $user_info['money'];

        $deal = $this->rpc->local("DealService\getDeal", array(intval($data['id'])));

        if (!$deal) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        //p2p标仅仅允许投资户投资
        if($this->rpc->local('DealService\isP2pPath', array($deal))){
            if(!$this->rpc->local('UserService\allowAccountLoan', array($user_info['user_purpose']))){
                $this->setErr('ERR_INVESTMENT_USER_CAN_BID', $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
                return false;
            }
        }

        $result['productID'] = $deal['id'];
        $result['type'] = $deal['type_match_row'];
        $result['title'] = $deal['old_name'];
        // 临时去掉rate中的百分号
        $result['rate'] = $deal['income_total_show_rate'];
        $result['timelimit'] = $deal['repay_time'] . ($deal['loantype']==5 ? "天" : "个月");
        $result['total'] = $deal['borrow_amount_wan_int'];
        $result['avaliable'] = $deal['need_money_detail'];
        $result['repayment'] = $deal['loantype_name'];
        $result['stats'] = $deal['deal_status'];
        $result['mini'] = $deal['min_loan_money_format'];
        $result['remain'] = number_format($remain, 2);
        $result['income_base_rate'] = $deal['income_base_rate'];
        $result['income_ext_rate'] = $deal['income_ext_rate'];
        $result['deal_tag_name'] = $deal['deal_tag_name'];
        $result['type_tag'] = $deal['type_tag'];

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($user_info['id']));
        $result['bonus'] = strval($bonus['money']);

        // 如果传了money参数，则根据传递的参数计算收益
        if ($data['money']) {
            $result['money_loan'] = number_format($data['money'], 2, ".", "");
            $earning = $this->rpc->local("EarningService\getEarningMoney", array($deal['id'], $data['money'], true));
        } else {
            if (in_array($deal['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
                $result['money_loan'] = $deal['crowd_min_loan'];
            } else {
                $result['money_loan'] = $remain + $result['bonus'] >$deal['need_money_decimal'] ? number_format($deal['need_money_decimal'], 2, ".", "") : number_format($remain + $result['bonus'], 2, ".", "");
                //$result['money_loan'] = $deal['max_loan'];
            }
            $earning = $this->rpc->local("EarningService\getEarningMoney", array($deal['id'], $result['money_loan'], true));
        }

        $result['expire_rate'] = number_format($deal['expire_rate'], 2) . "%";
        $result['expire_earning'] = number_format($earning, 2);

        $result['contract'] = array();
        if($deal['contract_tpl_type']){
            $contpre = $this->rpc->local("ContractPreService\getDealContPreTemplate", array($deal['id']));
            $cont_url = get_http().get_host()."/deal/contractpre?token={$data['token']}&money={$result['money_loan']}&id={$deal['id']}";

            if (((substr($deal['contract_tpl_type'], 0, 5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'], 0, 5)) === 'NQYZR')) {
                $contract = array(
                    array("name" => $contpre['loan_cont']['contract_title'],"url" => $cont_url.'&type=1'),
                );
            } else {
                $contract = array(
                    array("name" => $contpre['loan_cont']['contract_title'],"url" => $cont_url.'&type=1'),
                    array("name" => $contpre['warrant_cont']['contract_title'],"url" => $cont_url.'&type=4'),
                    array("name" => $contpre['lender_cont']['contract_title'],"url" => $cont_url.'&type=5'),
                );
                if($contpre['buyback_cont']){
                    $contract[] = array("name" => $contpre['buyback_cont']['contract_title'],"url"=> $cont_url.'&type=7');
                }
            }
            $result['contract'] = $contract;
        }

        $this->json_data = $result;
    }

} // END class BidConfirm extends AppBaseAction

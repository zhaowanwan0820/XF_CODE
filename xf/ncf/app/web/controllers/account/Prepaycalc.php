<?php
/**
 * 借款人自助提前还款
 * @author jinhaidong@ucfgroup.com
 */

namespace web\controllers\account;

use libs\utils\Finance;
use libs\web\Form;
use web\controllers\BaseAction;
use core\service\DealPrepayService;
use core\dao\DealLoanTypeModel;


class Prepaycalc extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->show_error('参数错误！','',1);
            exit;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $dealId = $params['id'];
        if(!$dealId){
            $this->show_error('参数错误！','',1);
            exit;
        }
        $user_info = $GLOBALS['user_info'];

        $endDate = date('Y-m-d');
        try{
            if(date('H') == 23) {
                throw new \Exception('您可在0点至23点发起提前还款!',1001);
            }
            $deal_loan_type_dao = new DealLoanTypeModel();
            $type_tag = $deal_loan_type_dao->getLoanTagByTypeId($this->type_id);
            if(($type_tag == DealLoanTypeModel::TYPE_XFFQ)) {
                throw new \Exception('消费分期类产品不可发起提前还款!',1002);
            }

            $ds = new DealPrepayService();
            $dealInfo = $ds->prepayCheck($dealId);
            $deal = $dealInfo['deal_base_info'];
            $dealExt = $dealInfo['deal_ext_info'];
            if($deal['user_id'] != $user_info['id']) {
                throw new \Exception('只能对自己的借款发起提前还款');
            }

            $calcInfo = $ds->prepayCalc($deal,$dealExt,$endDate,true);
            $calcInfo['prepay_date'] = to_date($calcInfo['prepay_time'],'Y-m-d');
            $calcInfo['prepay_money'] = number_format($calcInfo['prepay_money'],2);
            $calcInfo['remain_principal'] = number_format($calcInfo['remain_principal'],2);
            $calcInfo['prepay_interest'] = number_format($calcInfo['prepay_interest'],2);
            $calcInfo['prepay_compensation'] = number_format($calcInfo['prepay_compensation'],2);
            $calcInfo['loan_fee'] = number_format($calcInfo['loan_fee'],2);
            $calcInfo['consult_fee'] = number_format($calcInfo['consult_fee'],2);
            $calcInfo['guarantee_fee'] = number_format($calcInfo['guarantee_fee'],2);
            $calcInfo['pay_fee'] = number_format($calcInfo['pay_fee'],2);
        }catch (\Exception $ex) {
            $result['status'] = $ex->getCode();
            $result['info'] = $ex->getMessage();
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
            exit;
        }
        $this->show_success($calcInfo,'data',1);
    }
}
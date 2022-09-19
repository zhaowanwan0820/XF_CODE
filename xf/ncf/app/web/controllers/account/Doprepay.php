<?php
/**
 * 借款人自助提前还款
 * @author jinhaidong@ucfgroup.com
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\DealPrepayService;
use core\dao\DealLoanTypeModel;
use libs\utils\Logger;


class Doprepay extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'date' => array('filter' => 'string','require'=>true),
            'money' => array('filter' => 'string','require' => true),
        );
        if (!$this->form->validate()) {
            $this->show_error('参数错误！','',1);
            exit;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $dealId = $params['id'];
        $endDate = $params['date'];
        $money = $params['money'];
        if(!$dealId || !$endDate || !$money){
            $this->show_error('参数错误！','',1);
            exit;
        }

        $user_info = $GLOBALS['user_info'];
        try{
            if(date('H') > 23) {
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

            if($deal->deal_type == 1) {
                throw new \Exception('通知贷不可发起提前还款!');
            }

            if($deal['user_id'] != $user_info['id']) {
                throw new \Exception('只能对自己的借款发起提前还款');
            }

            $dealExt = $dealInfo['deal_ext_info'];
            $calcInfo = $ds->prepayCalc($deal,$dealExt,$endDate,true);


            if(bccomp($calcInfo['prepay_money'],$user_info['money'],2) == 1) {
                throw new \Exception('<div class="mt28 tc"><div class="mb5">您的账户余额不足</div>如需还款，还需充值 <span class="color-red2">'.($calcInfo['prepay_money']-$user_info['money']).'</span> 元</div> ',1003);
            }

            if($endDate != date('Y-m-d') || bccomp($calcInfo['prepay_money'],$money,2) != 0) {
                Logger::error("Doprepay fail endDate:{$endDate},nowDate:".date('Y-m-d').", calcInfo".json_encode($calcInfo).",money:".$money);
                throw new \Exception('确认还款时间与发起提前还款时间不为同一天，请重新发起提前还款!',1004);
            }

            $ds->prepayPipeline($dealId,$endDate,0,array('adm_id'=>$user_info['id'],'adm_name'=>$user_info['user_name']),true);
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
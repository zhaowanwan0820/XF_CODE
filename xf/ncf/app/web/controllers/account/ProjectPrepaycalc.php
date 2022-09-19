<?php
/**
 * 借款人自助提前还款 - 提前还款试算
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;


class ProjectPrepaycalc extends BaseAction
{

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
        $project_id = $params['id'];
        if(empty($project_id)){
            $this->show_error('参数错误！','',1);
            exit;
        }
        $user_info = $GLOBALS['user_info'];

        $end_day = date('Y-m-d');
        try{
            if(date('H') == 23) {
                throw new \Exception('您可在0点至23点发起提前还款!',1001);
            }

            // 校验当前登陆用户
            $project_info = $this->rpc->local("DealProjectService\getProInfo", array($project_id));
            if ($user_info['id'] != $project_info['user_id']) {
                throw new \Exception('只能对自己的借款发起提前还款');
            }

            $calc_info = $this->rpc->local("DealProjectPrepayService\prepayCalcProject", array($project_id, $end_day));
            $calc_info['project_id'] = $project_id;
            $calc_info['prepay_date'] = to_date($calc_info['prepay_time'],'Y-m-d');
            $calc_info['prepay_money'] = number_format($calc_info['prepay_money'],2);
            $calc_info['remain_principal'] = number_format($calc_info['remain_principal'],2);
            $calc_info['prepay_interest'] = number_format($calc_info['prepay_interest'],2);
            $calc_info['prepay_compensation'] = number_format($calc_info['prepay_compensation'],2);
            $calc_info['loan_fee'] = number_format($calc_info['loan_fee'],2);
            $calc_info['consult_fee'] = number_format($calc_info['consult_fee'],2);
            $calc_info['guarantee_fee'] = number_format($calc_info['guarantee_fee'],2);
            $calc_info['pay_fee'] = number_format($calc_info['pay_fee'],2);
            $this->show_success($calc_info,'data',1);
        }catch (\Exception $ex) {
            $result['status'] = $ex->getCode();
            $result['info'] = $ex->getMessage();
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
            return;
        }
    }
}

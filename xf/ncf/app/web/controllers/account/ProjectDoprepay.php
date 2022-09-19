<?php
/**
 * 借款人提前还款 - 执行还款
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

use libs\utils\Logger;


class ProjectDoprepay extends BaseAction
{

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
        $project_id = $params['id'];
        $end_day = $params['date'];
        $money = $params['money'];
        if(empty($project_id) && empty($end_day) && empty($money)){
            $this->show_error('参数错误！','',1);
            exit;
        }

        $user_info = $GLOBALS['user_info'];
        try{
            if(date('H') > 23) {
                throw new \Exception('您可在0点至23点发起提前还款!',1001);
            }

            // 校验当前登陆用户
            $project_info = $this->rpc->local("DealProjectService\getProInfo", array($project_id));
            if ($user_info['id'] != $project_info['user_id']) {
                throw new \Exception('只能对自己的借款发起提前还款');
            }

            $calc_info = $this->rpc->local("DealProjectPrepayService\prepayCalcProject", array($project_id, $end_day));
            if(bccomp($calc_info['prepay_money'],$user_info['money'],2) == 1) {
                throw new \Exception('<div class="mt28 tc"><div class="mb5">您的账户余额不足</div>如需还款，还需充值 <span class="color-red2">'.($calc_info['prepay_money']-$user_info['money']).'</span> 元</div> ',1003);
            }

            if($end_day != date('Y-m-d') || bccomp($calc_info['prepay_money'],$money,2) != 0) {
                Logger::error("Doprepay fail end_day:{$end_day},nowDate:".date('Y-m-d').", calc_info".json_encode($calc_info).",money:".$money);
                throw new \Exception('确认还款时间与发起提前还款时间不为同一天，请重新发起提前还款!',1004);
            }

            // 执行借款人提前还款
            $this->rpc->local("DealProjectPrepayService\prepayPipelineProject", array($project_id, array('adm_id'=>$user_info['id'],'adm_name'=>$user_info['user_name']), true, $end_day));
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

<?php
/**
 * 多投宝赎回页面
 * @author wangyiming@ucfgroup.com
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Rpc;
use core\service\DealCompoundService;

/**
 * 个人中心-多投宝赎回
 *
 * Class Finplanshow
 * @package web\controllers\account
 */
class Finplanshow extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        if(app_conf('DUOTOU_SWITCH') == '0') {
            $this->show_tips("系统维护中，请稍后再试！","系统维护");
            exit;
        }
        if(!is_duotou_inner_user()) {
            $this->show_tips("没有权限,仅内部员工可以查看智多新内容！","没有权限");
            exit;
        }
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
        $deal_load_id = $params['id'];
        if(!$deal_load_id){
            $this->show_error('参数错误！','',1);
            exit;
        }

        $vars = array(
            'id' => $deal_load_id,
        );

        $rpc = new Rpc('duotouRpc');
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanByIdForShow',$request);
        if(!$response) {
            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
        }
        $deal_load = $response['data'];
        $user_info = $GLOBALS['user_info'];
        if($deal_load['userId'] != $user_info['id']){
            $this->show_error('没有权限！','',1);
            exit;
        }
        $first_day = strtotime(date('Y-m-d', $deal_load['createTime']));
        $data = array();
        $data['title'] = "今日申请转让，最快下一个工作日到账";
        $data['sum'] = format_price($deal_load['money']);
        $data['money'] = format_price($deal_load['money']);
        $data['name'] = $deal_load['projectInfo']['name'];
        $data['date'] = $deal_load['projectInfo']['expiryInterest'];
        $data['feeDays'] = $deal_load['projectInfo']['feeDays'];
        $data['feeRate'] = sprintf('%.2f',$deal_load['projectInfo']['feeRate']);
        $data['redemptionStartTime'] = $deal_load['projectInfo']['redemptionStartTime'];
        $data['redemptionEndTime'] = $deal_load['projectInfo']['redemptionEndTime'];

        $data['ownDay'] = $deal_load['loadDays']; // 已经持有天数（自然天）
        $data['transferPromptText'] = $deal_load['projectInfo']['transferPromptText'];

        //赎回服务费
        $data['manageFee'] = format_price($deal_load['manageFee']);
        //未到账收益
        $data['norepayInterest'] = format_price($deal_load['norepayInterest']);
        //多少本金等待赎回
        $data['allRedeemMoney'] = $deal_load['allRedeemMoney'];
        //下一天时间
        $data['nextDay'] = date("Y-m-d",strtotime("+1 day"));

        /*去掉节假日
        $dcs = new DealCompoundService();
        $is_holiday = $dcs->checkIsHoliday(date('Y-m-d'));
        if($is_holiday){
            //$data['is_holiday'] = '（由于节假日，到账日顺延至'.$first_day.'）';
            $data['is_holiday'] = $is_holiday ? true : false;
        }*/
        $this->show_success($data,'data',1);
        
        exit;
    }
}

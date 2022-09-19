<?php
/**
 * Loadshow.php
 * @date 2014-01-07
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

/**
 * 个人中心-利滚利赎回
 *
 * Class Loadshow
 * @package web\controllers\account
 */
class Loadshow extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'string'),
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

        if (app_conf('SWITCH_COMPOUND_REDEEM') == 1) {
            $this->show_error('当前不可赎回！','',1);
            exit;
        }

        $deal_load = $this->rpc->local('DealLoadService\getDealLoadDetail', array($deal_load_id));
        $user_info = $GLOBALS['user_info'];
        if($deal_load['user_id'] != $user_info['id']){
            $this->show_error('没有权限！','',1);
            exit;
        }
        if($deal_load['deal']['deal_type'] != 1){
            $this->show_error('此投资不可赎回！','',1);
            exit;
        }
        $first_time = $this->rpc->local('DealCompoundService\getLatestRepayDay', array($deal_load['deal_id']));
        $sum = $this->rpc->local('DealCompoundService\getCompoundMoneyByDealLoadId', array($deal_load_id,$first_time['repay_time']));
        $first_day = to_date($first_time['repay_time'],'Y-m-d');
        $data = array();
        $data['title'] = "今日申请赎回，{$first_day}到账";
        $data['sum'] = format_price($sum);
        $data['name'] = $deal_load['deal']['name'];
        $data['money'] = format_price($deal_load['money']);

        $is_holiday = $first_time['is_holiday'];
        if($is_holiday){
            $data['is_holiday'] = '（由于节假日，到账日顺延至'.$first_day.'）';
        }
        $this->show_success($data,'data',1);
        exit;
    }
}

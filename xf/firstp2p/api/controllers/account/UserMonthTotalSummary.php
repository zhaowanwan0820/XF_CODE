<?php
/**
 * 用户资金记录月账单
 * @author gengkuan@ucfgroup.com
 * @date 2019-2-27 14:58:46
 **/

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;

class UserMonthTotalSummary extends AppBaseAction
{

    public function init()
    {
      parent::init();
          $this->form = new Form("post");
          $this->form->rules = array(
              "token" => array("filter"=>"required"),
              'class'=>array("filter"=>"required"),// 1为收益 2为投资
          );
          if (!$this->form->validate()) {
              $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
              return false;
          }
    }

    public function invoke()
    {
       $user = $this->getUserByToken();
          if (empty($user)) {
              $this->setErr('ERR_GET_USER_FAIL');
              return false;
          }
        $data = $this->form->data;
        $return = array();
        $month_list = array();
        $standardTime = strtotime(date('Y-m', time()) . '-01 00:00:01');
        for ($i = 0; $i <= 5; $i++) {
            $s= 5-$i;
            $mlist['Y'] = date('Y', strtotime("-{$s} month", $standardTime));
            $mlist['m'] = date('m', strtotime("-{$s} month", $standardTime));
            $month_list[] = $mlist;
            unset($mlist);
        }
        // p2p 投资收益 支付收益 是多投宝结息
        $p2pInterestRebate = array('支付收益','付息', '提前还款补偿金',  '提前还款利息',   '超额收益');
        $p2preward =  array( '返现券返利', '加息券返利', '邀请返利', '投资返利', '平台贴息', '贴息', '使用红包充值');
        $p2pPrincipalBid = array('投资放款', '投资扣款');// p2p 本金支出 多投宝叫投资扣款
        if(1  == $data['class'] ){
            $type =  $p2pPrincipalBid;
        }elseif(2  == $data['class']){
            $type =  $p2pInterestRebate;
        }else{
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
        foreach ($month_list as $val) {
            $year = $val['Y'];
            $month = $val['m'];
            $startTime = to_timespan($year . "-" . $month . "-01");
            $endTime = to_timespan(date($year . "-" . $month . "-01") . ' +1 month') - 1;
            $uid = $user['id'];
            $result = $this->rpc->local('UserLogService\getTotalSummaryByTime', array($uid, $startTime, $endTime,$type));
            $ret["year"] = $year;
            $ret["month"] = $month;
          if( 2 == $data['class']  ){
              $ret['m'] = $result[0]['m']+$result[1]['m'];
             $result = $this->rpc->local('UserLogService\getTotalSummaryByTime', array($uid, $startTime, $endTime,$p2preward));
             $ret['m_reward'] = $result[0]['m']+$result[1]['m'];
          }else{
              $ret['m'] = abs($result[0]['lm']+$result[1]['lm']);//柱状图要正数 取绝对值
          }
            $return[] =$ret;
        }
        $this->json_data = $return;
    }



}

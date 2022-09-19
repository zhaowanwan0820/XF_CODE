<?php
/**
 * DealRepay class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace app\models\dao;

use app\models\service\Finance;

/**
 * 还款记录,每当满标后进行放款时生成一系列还款记录，也即还款计划
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class DealRepay extends BaseModel
{
    /**
     * 执行还款
     *
     * @param boolean $ignore_impose_money 是否执行逾期罚息
     * @return void
     **/
    public function repay($ignore_impose_money = false)
    {
        if($this->status > 0){
            return false;
        }
        $time = get_gmtime();
        if(to_date($this->repay_time, "Y-m-d") >= to_date($time, "Y-m-d")){
            $this->status = 1; //准时
        }else{
            $this->impose_money = $this->feeOfOverdue();
            $this->status = 2; //逾期
        }
        $this->true_repay_time = get_gmtime();
        if($this->save()){
            $deal = Deal::instance()->find($this->deal_id);
            $deal->repay_money += $this->repay_money;
            $deal->last_repay_time = $this->true_repay_time;
            $deal->save();
            $user = new User();
            $user = $user->find($this->user_id);
	     // TODO finance 偿还本息 | 旧文件不处理
            $user->changeMoney(-$this->repay_money, "偿还本息", "编号".$deal['id'].' '.$deal['name']);
            if($this->status == 2 && !$ignore_impose_money){
		  // TODO finance 逾期罚息 | 旧文件不处理
                $user->changeMoney(-$this->impose_money, "逾期罚息". __FILE__ . ' ' . __FUNCTION__, "编号".$deal['id'].' '.$deal['name']);
            }
            //add 2014-1-21 caolong
            $deal['share_url'] = $this->getShareUrl($this->deal_id);
            $content = "您好，您在".app_conf("SHOP_TITLE")."的融资项目“<a href=\"".$deal['share_url']."\">".$deal['name']."</a>”成功还款" . format_price($this->repay_money, 0) . "元，";
            $next_repay = $this->getNextRepay();
            $next_repay_id = null;
            if($next_repay){
                $next_repay_id = $next_repay->id;
                $content .= "本融资项目的下个还款日为".to_date($next_repay['repay_time'],"Y年m月d日")."，需要本息". format_price($next_repay['repay_money'], 0) . "元。";
                $GLOBALS['db']->query("UPDATE ".DB_PREFIX."deal SET next_repay_time = '".$next_repay['repay_time']."' WHERE id=".$this->deal_id);
            }
            else{
                $content .= "本融资项目已还款完毕！";
            }

            //最后一笔
            if($next_repay_id == null){
                $deal->repayCompleted();
            }


            send_user_msg("",$content,0,$user['id'],get_gmtime(),0,true,8);
            //短信通知
            if(app_conf("SMS_ON")==1&&app_conf('SMS_SEND_REPAY')==1){
                $notice = array(
                    "site_name" => app_conf('SHOP_TITLE'),
                    "real_name" => $user['real_name'],
                    "repay"     => $this->repay_money,
                );       
                \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_DEAL_LOAD_REPAY_SMS', $notice, $user['id']);
            }        

            syn_deal_status($this->deal_id);
            sys_user_status($user['id'],false,true);

            $impose_money = DealLoanRepay::instance()->repayDealLoan($this, $next_repay_id, $ignore_impose_money);
            if ($impose_money) {
                $deal_repay = $this->find($this->id);
                $deal_repay->impose_money = $impose_money;
                $deal_repay->update_time = get_gmtime();
                $deal_repay->save();
                if($this->status == 2 && !$ignore_impose_money){
                    $user->changeMoney(-$impose_money, "逾期罚息". __FILE__ . ' ' . __FUNCTION__, "编号".$deal['id'].' '.$deal['name']);
                }
            }
        }
    }

    //获取还款url地址
    private function getShareUrl($dealId='') {
        $durl = '';
        if(!empty($dealId)) {
            $durl = url("index","deal",array("id"=>$dealId));
            $durl = get_domain().$durl;
            if($GLOBALS['user_info']){
                if(app_conf("URL_MODEL")==0){
                    $durl .= "&r=".base64_encode(intval($GLOBALS['user_info']['id']));
                }else{
                    $durl .= "?r=".base64_encode(intval($GLOBALS['user_info']['id']));
                }
            }
        }
        return $durl;
    }
    
    /**
     * 获取下次还款
     *
     * @return DealRepay or null
     **/
    public function getNextRepay(){
        return $this->findBy("deal_id=$this->deal_id and id>$this->id limit 1");
    }

    /**
     * 计算是否能够还款
     *
     * @return boolean
     **/
    public function canRepay()
    {
        $day_of_ahead_repay = $GLOBALS['dict']['DAY_OF_AHEAD_REPAY'];
        if($this->status == 0 && (int)$this->repay_time <= (get_gmtime() + $day_of_ahead_repay * 24 * 3600)){
            return true;
        }    
        return false;
    }

    /**
     *  检查是否已经逾期
     *
     * @return void
     **/
    public function isOverdue()
    {
        return to_date(get_gmtime(), "Y-m-d") >= to_date($this->repay_time, "Y-m-d");
    }

    /**
     * 逾期天数
     *
     * @return integer
     **/
    public function daysOfOverdue()
    {
        if($this->status == 0 && $this->isOverdue()){
            return floor((get_gmtime() - $this->repay_time)/(24 * 60 * 60));
        } else {
            return 0;
        }
    }

    /**
     * 逾期费用
     *
     * @return float
     **/
    public function feeOfOverdue()
    {
        $deal = Deal::instance()->find($this->deal_id);
        $principal = $this->principal;
        //对于按月付息，本金单独计算
        if($deal->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']){
            $repay_times = $deal->getRepayTimes();
            $principal = $deal->borrow_amount / $repay_times; //计算每期正常情况下应还本金
        }
        return Finance::overdue($principal, $this->daysOfOverdue(), floatval($deal->rate)/100, floatval($deal->overdue_rate));
    }
} // END class DealRepay extends BaseModel

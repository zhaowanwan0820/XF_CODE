<?php
/**
 * 智选计划提前还款接口
 *
 * @author 
 */
class QueryWiseAdvanceRepayment extends ItzApi{
    public $logcategory = "QueryWiseAdvanceRepayment.query";

    public function run($wise_borrow_id){
        Yii::log("RequestData: wise_borrow=$wise_borrow_id;", "info", $this->logcategory);
        $data = array();
        $time = date('Ymd', time());
        $n_midnight = strtotime('midnight');

        //参数验证
        if (empty($wise_borrow_id)) {
            $this->code = 8001;
            return $this;
        }

        //项目信息不能为空
        $borrowInfo = ItzWiseBorrow::model()->findByAttributes(array('wise_borrow_id'=>$wise_borrow_id));
        if (empty($borrowInfo->id)) {
            $this->code = 8013;
            return $this;
        }

        //项目未融满
        if ($borrowInfo->finish_time == 0) {
            $this->code = 8012;
            return $this;
        }

        //等额本息不支持提前还款
        if ($borrowInfo->style == 5) {
            $this->code = 8007;
            return $this;
        }

        //项目到期日，不能进行提前还款
        if ($time == date('Ymd',$borrowInfo->repayment_time)) {
            $this->code = 8003;
            return $this;
        }

        //提前还款时间限制, 当天日期大于项目融满日期, 小于项目到期日期, 就可以执行提前还款
        if ($time >= date('Ymd',$borrowInfo->repayment_time) || $time <= date('Ymd',$borrowInfo->formal_time)) {
            $this->code = 8008;
            return $this;
        }

        //如果正在提前还款中，不允许查询
        $count_result = ItzAdvanceRepayment::model()->count('wise_borrow_id=:wise_borrow_id AND status > 0 AND borrow_type=3', array(':wise_borrow_id'=>$borrowInfo->wise_borrow_id));
        if ($count_result > 0) {
            $this->code = 8009;
            return $this;
        }

        //如果此项目正好是今日还息，则必须还息完才可以启动提前还款 
        $count = ItzStatRepay::model()->count(" wise_borrow_id=:wise_borrow_id AND repay_status=0 AND repay_type=2 AND from_unixtime(repay_time,'%Y%m%d') <= ".$time, array(':wise_borrow_id'=>$wise_borrow_id));
        if ($count > 0) {
            $this->code = 8005;
            return $this;
        }
        
        //校验智选计划正常还款是否结束
        $t_repay_wise_borrow = ItzWisePlanCollection::model()->count(" repay_time=$n_midnight and status in (0,2,3,4) ");
        if($t_repay_wise_borrow > 0){
            $this->code = 8014;
            return $this;
        }

        //获取待还利息期数
        $interest_periods_result = ItzStatRepay::model()->findAllBySql("SELECT id from itz_stat_repay where wise_borrow_id =:wise_borrow_id and repay_status = 0 and repay_type = 2 and interest > 0 ",array(':wise_borrow_id'=>$wise_borrow_id));
        $wait_interest_periods = count($interest_periods_result);
        if ((int)$wait_interest_periods <= 0) {
            $this->code = 8010;
            return $this;
        }  

        //获取待还本金和，利息和
        $capital_condition = new CDbCriteria;
        $capital_condition->select = " sum(capital) as capital,
                                       sum(interest) as interest,
                                       max(repay_time) as repay_time";
        $capital_condition->condition = " wise_borrow_id=:wise_borrow_id AND repay_status=0 AND repay_type = 2";
        $capital_condition->params[':wise_borrow_id']  = $wise_borrow_id;
        $capital_res = ItzStatRepay::model()->findAll($capital_condition);   

        //验证还本时间, 还本金额（borrow与borrow_collection对比）
        if ($capital_res[0]->repay_time != $borrowInfo->repayment_time){
            $this->code = 8004;
            return $this;
        }

        //验证本金和利息金额正确性
        if (!FunctionUtil::float_equal(round($capital_res[0]->capital, 2), round($borrowInfo->account_yes, 2), 3) || FunctionUtil::float_bigger_equal(0, $capital_res[0]->interest, 3)) {
            $this->code = 8011;
            return $this;
        }

        //集合数据
        $data['repay_time'] = $capital_res[0]->repay_time;
        $data['capital'] = $capital_res[0]->capital;
        $data['repay_interest'] = $capital_res[0]->interest;
        $data['wait_interest_periods'] = $wait_interest_periods;

        if(empty($data)){
            $this->code = 8006;
            return $this;
        }

        $this->code = 0;
        $this->data = $data;
        return $this;
    } 
}

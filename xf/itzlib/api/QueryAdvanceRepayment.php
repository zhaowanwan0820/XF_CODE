<?php
/**
 * 提前还款接口
 *
 * @author 
 */
class QueryAdvanceRepayment extends ItzApi{
    public $logcategory = "AdvanceRepayment.query";

    public function run($borrow_id){
        Yii::log("RequestData: borrow=$borrow_id;", "info", $this->logcategory);
        $data = array();
        $time = date('Ymd', time());

        //参数验证
        if (empty($borrow_id) || !is_numeric($borrow_id) || $borrow_id <= 0) {
            $this->code = 8001;
            return $this;
        }

        //项目信息不能为空
        $borrowInfo = Borrow::model()->findByPk($borrow_id);
        if (empty($borrowInfo->id)) {
            $this->code = 3002;
            return $this;
        }

        //项目状态行必须为存续期
        if ($borrowInfo->status != 3) {
            $this->code = 8002;
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
        $count_result = ItzAdvanceRepayment::model()->count('status > 0 and borrow_id=:borrow_id', array(':borrow_id'=>$borrowInfo->id));
        if ($count_result > 0) {
            $this->code = 8009;
            return $this;
        }

        //如果此项目正好是今日还息，则必须还息完才可以启动提前还款 
        $count = BorrowCollection::model()->count(" status in(0, 2, 3) AND borrow_id=:borrow_id AND from_unixtime(repay_time,'%Y%m%d') <= ".$time, array(':borrow_id'=>$borrow_id));
        if ($count > 0) {
            $this->code = 8005;
            return $this;
        }

        //获取待还利息期数
        $interest_periods_result = BorrowCollection::model()->findAllBySql("select `order` from dw_borrow_collection where borrow_id =:borrow_id and status = 0 and type = 1 and interest > 0 group by `order`",array(':borrow_id'=>$borrow_id));
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
        $capital_condition->condition = "borrow_id = $borrow_id and status = 0 and type =1";
        $capital_res = BorrowCollection::model()->findAll($capital_condition);   

        //验证还本时间, 还本金额（borrow与borrow_collection对比）
        if ($capital_res[0]->repay_time != $borrowInfo->repayment_time){
            $this->code = 8004;
            return $this;
        }

        //验证本金和利息金额正确性
        if($borrowInfo->style == 5){
            if (FunctionUtil::float_bigger(round($capital_res[0]->capital, 2), round($borrowInfo->account_yes, 2), 3) || FunctionUtil::float_bigger_equal(0, $capital_res[0]->interest, 3)) {
                $this->code = 8011;
                return $this;
            }
        }else{
            if (!FunctionUtil::float_equal(round($capital_res[0]->capital, 2), round($borrowInfo->account_yes, 2), 3) || FunctionUtil::float_bigger_equal(0, $capital_res[0]->interest, 3)) {
                $this->code = 8011;
                return $this;
            }
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

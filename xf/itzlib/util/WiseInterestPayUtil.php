<?php
/**
 * WiseInterestPayUtil 
 * 智选集合使用
 * 按月的周期为30天
 */
class WiseInterestPayUtil{
    
    /*
     * 分配利息入口函数
     * @param Array $data
     * repayment_time, borrow_time, year_apr, account
     * borrow_style:
     * 0 按日计息  按月付息 到期还本息
     * 1 按日计息 到期还本
     * 2 按日计息 月底付息 到期还本
     * 3 按日计息 按季度付息  到期还本息
     * 4 等额本金
     * 5 等额本息  还款期数  repay_months ，如果还款期数和还款日期不一致，那么按照最后一个月的本金算日利息进行修正
     * */
    public static function EqualInterest($data = array()){ 
        if (isset($data['borrow_style'])){
            $borrow_style = $data['borrow_style'];
        }
        //借款的总金额
        if (!(isset($data['account']) && is_numeric($data['account']) && $data['account']>0)){
             return "";
        }
                
        //借款的年利率
        if (!(isset($data['year_apr']) && is_numeric($data['year_apr']) && $data['year_apr']>0)){
            return "";
        }
        $data['wise_year_limit'] = 360;
        if($data['month_limit']==1){
        	$data['wise_year_limit'] = 365;
        }
        
        //借款的时间
        $delay_value_days = isset($data['delay_value_days']) ? ($data['delay_value_days']==2 ? 1 : $data['delay_value_days']) : 0;
        if (isset($data['borrow_time']) && $data['borrow_time']>0){
            $data['borrow_time'] = strtotime("midnight", $data['borrow_time']) + $delay_value_days*24*60*60;
        }else{
            $data['borrow_time'] = strtotime("midnight", time()) + $delay_value_days*24*60*60;
        }

        switch ($borrow_style) {
            case 0:  //按日计息  按月付息 到期还本息
            case 1:  //按日计息  到期还本息
                return self::EqualNextMonthByDay($data);
                break;
            case 2:  //按日计息 月底付息  到期还本息
                return self::EqualEndMonthByDay($data);
                break;
            case 3:  //按日计息 按季度付息  到期还本息
                return self::EqualNextQuarterByDay($data);
                break;
            case 4: //等额本金，按月付款
                return self::EqualPrincipal($data);
                break;
            case 5: //等额本息，按月付款
                return self::EqualPrincipalInterest($data);
                break;
            case 6: //活期，按日计息，按日付息
                return self::EqualCurrentInterest($data);
                break;    
            default:
                return "";
                break;
        }
    }


    /**
     * 智选计划用户退出时本息和计算函数
     * @param $capital int 退出本金
     * @param $term int 计息天数
     * @param $extra_reward_type int 加息类型0无加息1平台加息2新手加息
     * @param $tender_time 投资时间
     * @return mixed|string
     */
    public static function WisePlanInterest($capital, $term, $user_id, $extra_reward_type=0,  $tender_time=0){
        Yii::log(" func_get_args :".json_encode(func_get_args()), 'info', __FUNCTION__);
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        //参数校验
        if(empty($capital) || empty($user_id) || !FunctionUtil::float_bigger_equal($capital, 0, 2)){
            $return_result['code'] = 2052;
            return $return_result;
        }
        //超级投资人2不计算差息
        if($user_id == Yii::app()->c->linkconfig["super_user_two"]){
            $return_result['data']['quit_apr'] = 0;
            $return_result['data']['seller_quit_fee'] = 0;
            $return_result['data']['capitalAndInterest'] = $capital;
            return $return_result;
        }
        //期限参数校验
        if(empty($term) || !is_numeric($term) ){
            $return_result['code'] = 5048;
            return $return_result;
        }
        //新手加息时，没有手续费，只有固定利率
        if($extra_reward_type == 2){
            $novice_borrow_apr = Yii::app()->c->linkconfig['zxjh_config']['novice_borrow_apr'];
            if(!is_numeric($novice_borrow_apr) || $novice_borrow_apr<=0){
                $return_result['code'] = 5027;
                return $return_result;
            }
            $return_result['data']['quit_apr'] = $novice_borrow_apr;
            $return_result['data']['seller_quit_fee'] = 0;
            $return_result['data']['capitalAndInterest'] = round($capital+($capital*$novice_borrow_apr*0.01/365*$term), 2);
            return $return_result;
        }

        //老手标退出时利率
        $downAprTime = Yii::app()->c->linkconfig["zxjh_config"]['low_apr_time'];
        $downAprTime = strtotime($downAprTime);
        if ($tender_time >= $downAprTime) {
            // 投资时间 大于 2018-05-23
            $wise_plan_apr_list = Yii::app()->c->linkconfig['zxjh_config']['wise_plan_lowapr_list'];
        } else {
            $wise_plan_apr_list = Yii::app()->c->linkconfig['zxjh_config']['wise_plan_apr_list'];
        }
        
        if(empty($wise_plan_apr_list) || !is_array($wise_plan_apr_list)){
            $return_result['code'] = 5027;
            return $return_result;
        }
        //匹配最小利率
        $quit_apr = 0;
        foreach($wise_plan_apr_list as $w_key => $w_apr){
            if($w_key > ($term+1)){
                continue;
            }
            if($quit_apr == 0){
                $quit_apr = $w_apr;
                continue;
            }
            if(FunctionUtil::float_bigger_equal($w_apr, $quit_apr, 2)){
                $quit_apr = $w_apr;
            }
        }
        if($quit_apr == 0){
            $return_result['code'] = 5027;
            return $return_result;
        }
        //起息180天（不含）以上退出不收费；
        $seller_quit_fee = 0;
        //起息30天内（含）退出收费1%；
        if($term < 30){
            $seller_quit_fee = round((0.008/365 * $capital * $term), 2);
        }
        //起息30（不含）到180天（含）退出收费0.5%；
        if($term >= 30 && $term < 180){
            $seller_quit_fee = round((0.005/365 * $capital * $term), 2);
        }
        $return_result['data']['quit_apr'] = $quit_apr;
        $return_result['data']['seller_quit_fee'] = $seller_quit_fee;
        $return_result['data']['capitalAndInterest'] = round((pow(1+($quit_apr*0.01/365), $term) * $capital), 2);
        return $return_result;
    }
    /**
     * 计息核心函数   按日计息  按季付息   到期还本
     */
    static function EqualNextQuarterByDay($data = array()){
        //到期日
        if (isset($data['repayment_time']) && $data['repayment_time']>0){
            $repayment_time = strtotime("midnight", $data['repayment_time']);
        }else{
            return "";
        }
        $borrow_time = $data["borrow_time"];

        //借款日
        $borrow_day = date("d", $borrow_time);

        //借款时间必须在还款时间之前
        if ($borrow_time > $repayment_time){
            return "";
        }
        //日利率
        $daily_apr = $data["year_apr"]/($data['wise_year_limit']*100);

        //总利息=投资额*日息*投资天数
        $invest_days = round(($repayment_time-$borrow_time)/(24*60*60));
        if($invest_days == $data['wise_year_limit']){ //360天的时候就不用去除了 直接约掉
            $total_interest = FunctionUtil::roundToEven($data["account"] * $data["year_apr"] )/100;
        }else{
            $total_interest = FunctionUtil::roundToEven(100 * $data["account"] * $daily_apr * $invest_days)/100;
        }
        if($total_interest<=0) {$total_interest=0.01;}

        $i = 0;
        $all_interest = 0;
        $all_days = 0;
        while(self::DateNextQuarter($borrow_time, $borrow_day,$data['wise_year_limit']) < $repayment_time){
            $borrow_time_next_month = self::DateNextQuarter($borrow_time, $borrow_day,$data['wise_year_limit']);
            $interest = round($data["account"] * $daily_apr * round(($borrow_time_next_month - $borrow_time)/(24*60*60)), 2);
            $_result[$i]['repayment_account'] = $interest;
            $_result[$i]['repayment_time'] = $borrow_time_next_month;
            $_result[$i]['repayment_time_cn'] = date("Y-m-d",$borrow_time_next_month);
            $_result[$i]['interest'] = $interest;
            $_result[$i]['capital'] = 0;
            $_result[$i]['days'] = round(($borrow_time_next_month - $borrow_time)/(24*60*60));
            $_result[$i]['surplus_capital']   = $data['account'];
            $borrow_time = $borrow_time_next_month;
            $all_interest += $interest;
            $all_days +=  $_result[$i]['days'];
            $i++;
            $total_interest = round($total_interest - $interest, 2);
        }
        if($total_interest<=0) {$total_interest=0.01;}
        
        $_result[$i]['repayment_account'] = $total_interest ;
        $_result[$i]['repayment_time']    =  $repayment_time;
        $_result[$i]['repayment_time_cn'] = date("Y-m-d",$repayment_time);
        $_result[$i]['interest']          = $total_interest;
        $_result[$i]['capital']           = 0;
        $_result[$i]['days']              = round(($repayment_time - $borrow_time)/(24*60*60));
        $_result[$i]['surplus_capital']   = $data['account'];
        
        $i++;
        $_result[$i]['repayment_account'] = $data["account"];
        $_result[$i]['repayment_time']    = $repayment_time;
        $_result[$i]['repayment_time_cn'] = date("Y-m-d",$repayment_time);
        $_result[$i]['interest']          = 0;
        $_result[$i]['capital']           = $data["account"];
        $_result[$i]['days']              = round(($repayment_time - $borrow_time)/(24*60*60));
        $_result[$i]['surplus_capital']   = 0;
        $_result[$i]['all_interest']      = round($all_interest + $total_interest,2);
        $_result[$i]['all_days']          = $all_days + $_result[$i]['days'];
                
        return $_result;
    }
    
    /*
     * 等额本息算法  每月等额还本息额 = p * R*(1+R)的N次方   / (1+R)的N次方 - 1
     * P贷款本金
     * R 月利率
     * N 还款期数 1年12期     还款时间>28 按月底的最后一天算
     * 
     * */
    static function EqualPrincipalInterest($data = array()){
        //借款的期数 每月算一期  一年12期
        if (!(isset($data['repay_months']) && is_numeric($data['repay_months']) && $data['repay_months']>0)){
            return "";
        }
        $borrow_time = $data["borrow_time"];
        
        //等额本息算法调整，还款时间保持一致，第一期晚投资的用户需要按天扣掉利息
        $interest_remove = 0;$day_remove = 0;
        $day_apr = $data["year_apr"]/100/$data['wise_year_limit'];
        if( (isset($data["formal_time"]) && $data["formal_time"]!="") && $data['delay_value_days']!=2 ){
            //formal_time 项目开始时间  00:00:00
            $formal_time_start = strtotime(date("Y-m-d",$data["formal_time"]));
            //borrow_time 投资开始时间  00:00:00
            $borrow_time_start = strtotime(date("Y-m-d",$data["borrow_time"]));
            if($borrow_time_start>$formal_time_start){//投资时间差了几天
               $borrow_time =  $formal_time_start;
               $day_remove =   ($borrow_time_start -  $formal_time_start)/86400;
                
               $interest_remove =   $day_apr * $day_remove * $data["account"];//第一期的修正利息
            }
        }
        
        //借款日
        $borrow_day = date("d", $borrow_time);
        
        $month_apr = $data["year_apr"]/100/12;
        //每月等额本息额
        $each_month_pay = ( $data["account"] * $month_apr * pow((1+$month_apr),$data['repay_months']) )/ ( pow((1+$month_apr),$data['repay_months'])-1);
        $all_interest = 0;
        $all_days = 0;
        $surplus_capital = $data["account"]; //剩余本金
        
        $next_month = time();//初始化
        $sum_capital = 0;
        for($i=0;$i<$data['repay_months'];$i++){
            $next_month = self::DateNextMonth($borrow_time,$borrow_day,$data['wise_year_limit']);
            //第n个月的还贷本金 
            $n_capital = ( $data["account"] * $month_apr * pow((1+$month_apr),$i) )/ ( pow((1+$month_apr),$data['repay_months'])-1);
            //如果最后一期本金
            if($i == $data['repay_months']-1) {
                $n_capital = round($data['account'] - $sum_capital,2);
            }
            $_result[$i]['repayment_account'] =  round($each_month_pay-(($i==0)?$interest_remove:0),2);
            $_result[$i]['repayment_time']    =  $next_month;
            $_result[$i]['repayment_time_cn'] =  date("Y-m-d",$next_month);
            $_result[$i]['interest']          =  round($each_month_pay-$n_capital-(($i==0)?$interest_remove:0),2);
            $_result[$i]['capital']           =  round($n_capital,2) ;
            $_result[$i]['days']              =  round(($next_month - $borrow_time)/(24*60*60))-(($i==0)?$day_remove:0);
            $sum_capital += $_result[$i]['capital'];
            //等额本息付息表差4分钱BUG修复
            $_result[$i]['repayment_account'] =  $_result[$i]['interest'] + $_result[$i]['capital'];
            
            $surplus_capital = $surplus_capital - $_result[$i]['capital'];
            $_result[$i]['surplus_capital']   = round($surplus_capital, 2);
            if(FunctionUtil::float_bigger(0, $_result[$i]['surplus_capital'], 2)){
                $_result[$i]['surplus_capital'] = 0;
            }
            // $all_interest += ($each_month_pay-$n_capital-(($i==0)?$interest_remove:0));
            $all_interest += $_result[$i]['interest'];
            $all_days     +=  $_result[$i]['days'];
            $borrow_time = $next_month;
        }
        //判断期数算出的时间和录入项目的时间是否一致，不一致需要修改差额
        if(time()> strtotime("2014-08-26") && isset($data["repayment_time"])&&$data["repayment_time"]!=""){ //还需要兼容以前的算法
            $day_fix = 0;$interest_fix = 0;
            if($next_month != $data["repayment_time"] ){
                $day_fix = ($data["repayment_time"] - $next_month)/86400; //可能为负数
                if(abs($day_fix)>10){
                    //Yii::log("EqualPrincipalInterest day_fix big than 10:".$day_fix,"error");
                }
                $interest_fix = $day_fix * $_result[$i-1]['capital'] *$day_apr;
                $_result[$i-1]['repayment_account'] =  round($_result[$i-1]['repayment_account'] + $interest_fix,2);
                $_result[$i-1]['interest']          =  round($_result[$i-1]['interest'] + $interest_fix,2);
                $_result[$i-1]['days']              =  $_result[$i-1]['days'] + $day_fix;
                $_result[$i-1]['repayment_time']    =  $data["repayment_time"];
                $_result[$i-1]['repayment_time_cn'] =  date("Y-m-d",$data["repayment_time"]);
                $all_interest = $all_interest + $interest_fix;
                $all_days     = $all_days + $day_fix;
            }
        }

        $_result[$i-1]['all_interest'] = round($all_interest,2)>0?round($all_interest,2):0.01;
        $_result[$i-1]['all_days'] = $all_days;
        return $_result;
    }  
    
    /*
     * 等额本金算法  
     * 每期还款额= 贷款本金÷贷款期数+（本金-已归还本金累计额）×月利率 
     * 
     * */
    static function EqualPrincipal($data = array()){
        if (!(isset($data['repay_months']) && is_int($data['repay_months']) && $data['repay_months']>0)){
            return "";
        }
        $borrow_time = $data["borrow_time"];
        //借款日
        $borrow_day = date("d", $borrow_time);   
            
        $month_apr = $data["year_apr"]/100/12;
        //每月归还本金
        $each_month_pay  = round($data["account"]/$data['repay_months'], 2);
        $surplus_capital =  $data["account"]; //剩余本金
        $all_days = $all_interest = 0;
		$sum_capital = 0;
        for($i=0;$i<$data['repay_months'];$i++){
            $next_month = self::DateNextMonth($borrow_time,$borrow_day,$data['wise_year_limit']);
            
			//如果最后一期本金
            if($i == $data['repay_months']-1) {
                $each_month_pay = round($data['account'] - $sum_capital,2);
            }
			
			//每月归还利息
            $each_month_interest = ($data["account"]-$each_month_pay*$i)* $month_apr;
            $_result[$i]['repayment_account'] =  round($each_month_pay+ $each_month_interest,2);
            $_result[$i]['repayment_time']    =  $next_month;
            $_result[$i]['repayment_time_cn'] =  date("Y-m-d",$next_month);
            $_result[$i]['interest']          =  round($each_month_interest,2);
            $_result[$i]['capital']           =  $each_month_pay;
            $_result[$i]['days']              = round(($next_month - $borrow_time)/(24*60*60));
            $surplus_capital -= $each_month_pay;
            $_result[$i]['surplus_capital']   = round($surplus_capital,2);
			$all_interest +=$_result[$i]['interest'];
			$sum_capital += $each_month_pay;
			$borrow_time = $next_month;
            $all_days +=  $_result[$i]['days'];
        }
		$_result[$i-1]['all_interest']        = round($all_interest,2);
        $_result[$i-1]['all_days'] = $all_days;
		return $_result;
    } 
    
    
    /**
     * 计息核心函数   按日计息  按月付息   到期还本
     *
     */
    static function EqualNextMonthByDay($data = array()){
        //到期日
        if (isset($data['repayment_time']) && $data['repayment_time']>0){
            $repayment_time = strtotime("midnight", $data['repayment_time']);
        }else{
            return "";
        }
        $borrow_time = $data["borrow_time"];
        //借款日
        $borrow_day = date("d", $borrow_time);
        //借款时间必须在还款时间之前
        if ($borrow_time > $repayment_time){
            return "";
        }
        //日利率
        $daily_apr = $data["year_apr"]/($data['wise_year_limit']*100);

        //总利息=投资额*日息*投资天数
        $invest_days = round(($repayment_time-$borrow_time)/(24*60*60));
        if($invest_days == $data['wise_year_limit']){ //360天的时候就不用去除了 直接约掉
            $total_interest = FunctionUtil::roundToEven($data["account"] * $data["year_apr"] )/100;
        }else{
            $total_interest = FunctionUtil::roundToEven(100 * $data["account"] * $daily_apr * $invest_days)/100;
        }
        if($total_interest<=0) {$total_interest=0.01;}

        //对于到期还本付息，按日计息的情况
        if (isset($data['borrow_style']) && $data['borrow_style']== 1){
            $_result[0]['repayment_account'] =  $total_interest;
            $_result[0]['repayment_time']    =  $data['repayment_time'];
            $_result[0]['repayment_time_cn'] =  date("Y-m-d",$data['repayment_time']);
            $_result[0]['interest']          =  $total_interest;
            $_result[0]['capital']           =  0;
            $_result[0]['days']              =  $invest_days;
            $_result[0]['surplus_capital']   =  $data["account"];
            
            $_result[1]['repayment_account'] =  $data["account"];
            $_result[1]['repayment_time']    =  $data['repayment_time'];
            $_result[1]['repayment_time_cn'] =  date("Y-m-d",$data['repayment_time']);
            $_result[1]['interest']          =  0;
            $_result[1]['capital']           =  $data["account"];
            $_result[1]['days']              =  $invest_days;
            $_result[1]['surplus_capital']   =  0;
            $_result[1]['all_interest']      =  $total_interest;
            $_result[1]['all_days']          =  $invest_days;
            
            return $_result;
        }
        
        $i = 0;
        $all_interest = 0;
        $all_days = 0;
        while(self::DateNextMonth($borrow_time, $borrow_day,$data['wise_year_limit']) < $repayment_time){
            $borrow_time_next_month = self::DateNextMonth($borrow_time, $borrow_day,$data['wise_year_limit']);
            $interest = round($data["account"] * $daily_apr * round(($borrow_time_next_month - $borrow_time)/(24*60*60)), 2);
            $_result[$i]['repayment_account'] = $interest;
            $_result[$i]['repayment_time'] = $borrow_time_next_month;
            $_result[$i]['repayment_time_cn'] = date("Y-m-d",$borrow_time_next_month);
            $_result[$i]['interest'] = $interest;
            $_result[$i]['capital'] = 0;
            $_result[$i]['days'] = round(($borrow_time_next_month - $borrow_time)/(24*60*60));
            $_result[$i]['surplus_capital']   = $data['account'];
            $borrow_time = $borrow_time_next_month;
            $all_interest += $interest;
            $all_days +=  $_result[$i]['days'];
            $i++;
            $total_interest = round($total_interest - $interest, 2);     
        }
        if($total_interest<=0) {$total_interest=0.01;}
        $_result[$i]['repayment_account'] = $total_interest;
        $_result[$i]['repayment_time']    =  $repayment_time;
        $_result[$i]['repayment_time_cn'] = date("Y-m-d",$repayment_time);
        $_result[$i]['interest']          = $total_interest;
        $_result[$i]['capital']           = 0;
        $_result[$i]['days']              = round(($repayment_time - $borrow_time)/(24*60*60));
        $_result[$i]['surplus_capital']   = $data['account'];
        
        $i++;
        $_result[$i]['repayment_account'] = $data["account"];
        $_result[$i]['repayment_time']    = $repayment_time;
        $_result[$i]['repayment_time_cn'] = date("Y-m-d",$repayment_time);
        $_result[$i]['interest']          = 0;
        $_result[$i]['capital']           = $data["account"];
        $_result[$i]['days']              = round(($repayment_time - $borrow_time)/(24*60*60));
        $_result[$i]['surplus_capital']   = 0;
        $_result[$i]['all_interest']      = round($all_interest + $total_interest,2);
        $_result[$i]['all_days']          = $all_days + $_result[$i]['days'];
                
        return $_result;
    }
    
    /**
     * 计息核心函数   到期还本，月底付息，按日计息
     *
     */
    static function EqualEndMonthByDay ($data) {
        //到期日
        if (isset($data['repayment_time']) && $data['repayment_time']>0){
            $repayment_time = strtotime("midnight", $data['repayment_time']);
        }else{
            return "";
        }
        
        $borrow_time = $data["borrow_time"];
        //借款日
        $borrow_day = date("d", $borrow_time);
        //借款时间必须在还款时间之前
        if ($borrow_time > $repayment_time){
            return "";
        }
        //日利率
        $daily_apr = $data["year_apr"]/($data['wise_year_limit']*100);

        //总利息=投资额*日息*投资天数
        $invest_days = round(($repayment_time-$borrow_time)/(24*60*60));
        $total_interest = FunctionUtil::roundToEven(100 * $data["account"] * $daily_apr * $invest_days)/100;

        if($total_interest<=0) {$total_interest=0.01;}
        
        $i = 0;
        $tmp_time = $borrow_time;
        while(strtotime("-1 day", strtotime("first day of next month", $tmp_time)) < $repayment_time){
            $borrow_time_next_month = strtotime("-1 day", strtotime("first day of next month", $tmp_time));
            $interest = round($data["account"] * $daily_apr * round(($borrow_time_next_month - $borrow_time)/(24*60*60)), 2);
            if ( $interest > 0 ){
                $_result[$i]['repayment_account'] = $interest;
                $_result[$i]['repayment_time'] = $borrow_time_next_month;
                $_result[$i]['repayment_time_cn'] = date("Y-m-d",$borrow_time_next_month);
                $_result[$i]['interest'] = $interest;
                $_result[$i]['capital'] = 0;
                $_result[$i]['days'] = round(($borrow_time_next_month - $borrow_time)/(24*60*60));
                $_result[$i]['surplus_capital']   = $data['account'];
                $i++;
            }
            $borrow_time = $borrow_time_next_month;
            $tmp_time = strtotime("+1 day", $borrow_time_next_month);
            $total_interest = round($total_interest - $interest, 2);
        }
        if($total_interest<=0) {$total_interest=0.01;}
        $_result[$i]['repayment_account'] = $total_interest;
        $_result[$i]['repayment_time'] = $repayment_time;
        $_result[$i]['repayment_time_cn'] = date("Y-m-d",$repayment_time);
        $_result[$i]['interest'] = $total_interest;
        $_result[$i]['capital'] = 0;
        $_result[$i]['days'] = round(($repayment_time - $borrow_time)/(24*60*60));
        $_result[$i]['surplus_capital']  = $data['account'];
        $i++;
        $_result[$i]['repayment_account'] = $data["account"];
        $_result[$i]['repayment_time'] = $repayment_time;
        $_result[$i]['repayment_time_cn'] = date("Y-m-d",$repayment_time);
        $_result[$i]['interest'] = 0;
        $_result[$i]['capital'] = $data["account"];
        $_result[$i]['days'] = round(($repayment_time - $borrow_time)/(24*60*60));
        $_result[$i]['surplus_capital'] = 0;
        
        return $_result;
    }
    
    /**
     * 活期，按日计息，按日付息
     *
     */
    static function EqualCurrentInterest($data = array()){
        #参数验证
        if (isset($data['repayment_time']) && $data['repayment_time']>0){
            $repayment_time = strtotime("midnight", $data['repayment_time']);
        }else{
            return "";
        }
        #借款时间必须在还款时间之前
        if ($data['borrow_time'] >= $repayment_time){
            return "";
        }
        
        #利息=投资额*天数*日利率
        $days = round(($repayment_time-$data['borrow_time'])/(24*60*60));           #计息天数 
        $interest = round($data['account']*$days*$data['year_apr']/($data['wise_year_limit']*100), 2);       #利息
        
        $_result[0]['repayment_account'] =  round($interest+$data["account"], 2);
        $_result[0]['repayment_time']    =  $data['repayment_time'];
        $_result[0]['repayment_time_cn'] =  date("Y-m-d",$data['repayment_time']);
        $_result[0]['interest']          =  $interest;
        $_result[0]['capital']           =  $data["account"];
        $_result[0]['days']              =  $days;
        $_result[0]['surplus_capital']   =  $data["account"];
        $_result[0]['all_interest']      =  $interest;
        $_result[0]['all_days']          =  $days;
        return $_result;
    }

    /**
     * 按月  30天一个月 或 自然月
     */ 
    public static function DateNextMonth($now, $date = 0,$limit=360) {
    	if($limit == 360){
	        return strtotime('+30 day',$now);
    	}else{ 
	        $mdate = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	        list($y, $m, $d) = explode('-', (is_int($now) ? strftime('%Y-%m-%d', $now) : $now));
	        
	        if ($date)
	        	$d = $date;
	        if (++$m == 13){
	        	$m = 1;
	        	++$y;
	        }
	        if ($m == 2)
	        	$d = (($y % 4) === 0) ? (($d <= 29) ? $d : 29) : (($d <= 28) ? $d : 28);
	        else
	        	$d = ($d <= $mdate[$m]) ? $d : $mdate[$m];
	        
	        return mktime(0, 0, 0, $m, $d, $y);
    	}
    }
    
    /**
     * 按季  90天一季度 或 自然月
     */ 
    public static function DateNextQuarter($now, $date = 0,$limit=360) {
    	if($limit == 360){
	        return strtotime('+90 day',$now);
    	}else{
	        $mdate = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	        list($y, $m, $d) = explode('-', (is_int($now) ? strftime('%Y-%m-%d', $now) : $now));
	        
	        if ($date)
	        	$d = $date;
	        $m += 3;
	        if ($m > 12){
	        	$m -= 12;
	        	++$y;
	        }
	        if ($m == 2)
	        	$d = (($y % 4) === 0) ? (($d <= 29) ? $d : 29) : (($d <= 28) ? $d : 28);
	        else
	        	$d = ($d <= $mdate[$m]) ? $d : $mdate[$m];
	        
	        return mktime(0, 0, 0, $m, $d, $y);
    	}
    }


	/**
	 * 简单根据利率和天数计算收益
	 * @param $add_apr
	 * @param $money
	 * @param $days
	 * @param int $limit
	 * @return float
	 */
    public static function getIncomeByAprAndDays($add_apr,$money,$days,$limit=365){
		$add_apr_income = $add_apr/100 * $money * $days/$limit;
		return round($add_apr_income,2);
	}

    
}

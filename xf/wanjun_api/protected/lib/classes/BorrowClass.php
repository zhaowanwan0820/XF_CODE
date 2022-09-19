<?php
/*
 * borrow
 */

class BorrowClass  {

    /**
     * 获取标列表
     **/
    public function getBorrowList($order="",$offset=0,$limit=10,$more_attributes=array(),$more_criteria=NULL,$with_array = array()){
        $BorrowModel = new Borrow();
        $criteria = new CDbCriteria; 
        if(!empty($order)) $criteria->order = $order;
        $criteria->offset = $offset;
        if($limit!="ALL")  $criteria->limit = $limit;
        $attributes = array();
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if($more_criteria) $criteria->mergeWith($more_criteria);
        if(!empty($with_array)){
            $BorrowResult =$BorrowModel->with($with_array)->findAllByAttributes($attributes,$criteria);
        }else{
            $BorrowResult =$BorrowModel->findAllByAttributes($attributes,$criteria);
        }
        return $BorrowResult;
     }

    public function getBorrowListByAttr($attributes, $order="",$offset=0,$limit=10,$more_criteria=NULL){
        $BorrowModel = new Borrow();
        $criteria = new CDbCriteria;
        if(!empty($order)) $criteria->order = $order;
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        if($more_criteria) $criteria->mergeWith($more_criteria);
        $BorrowResult = $BorrowModel->findAllByAttributes($attributes,$criteria);
        return $BorrowResult;
    }

    public function getCountByAttr($attributes, $order="",$offset=0,$limit=10,$more_criteria=NULL){
        $BorrowModel = new Borrow();
        $criteria = new CDbCriteria;
        if(!empty($order)) $criteria->order = $order;
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        if($more_criteria) $criteria->mergeWith($more_criteria);
        $count = $BorrowModel->countByAttributes($attributes,$criteria);
        return $count;
    }
    
    /**
     * 获取标详情
     **/
    public function getBorrowDetail($id){
        $BorrowModel = new Borrow();
        $criteria = new CDbCriteria; 
        $attributes = array(
          "id"    =>   $id,   
        );
        $BorrowResult =$BorrowModel->findByAttributes($attributes,$criteria);
        return $BorrowResult;
     }

    /**
     * 获取标详情
     **/
    public function getBorrowDetailByAttr($attributes, $criteria){
        $BorrowModel = new Borrow();
        $BorrowResult = $BorrowModel->findByAttributes($attributes,$criteria);
        return $BorrowResult;
     }
         
    
    /*
     * 获取企业的其他借款项目
     */
    public function getCorpOtherTenders($corp_id,$offset,$limit){
        $BorrowuploadModel = new Borrowupload(); 
        $criteria = new CDbCriteria; 
        $criteria->offset = $offset;
        if(!empty($limit)){
            $criteria->limit = $limit;
        }
        $BorrowuploadResult = $BorrowuploadModel->findAll($criteria);
        return $BorrowuploadResult;
    }
     
     /**
     * 获取标的评论
     */
     public function getBorrowComment($borrowId,$offset,$limit){
        $CommentModel = new Comment();
        $criteria = new CDbCriteria; 
        $criteria->offset = $offset;
        if(!empty($limit)) {
            $criteria->limit = $limit;
        }
        $attributes = array(
            "article_id"    =>   $borrowId,   
        );
        $CommentResult =$CommentModel->findAllByAttributes($attributes,$criteria);
        return $CommentResult;
     }

	/**
     * 获取一条投资记录详细数据
     * @param: int $id 
     * @return: CActiveRecord $tenderResult
     */
    public function getTenderById($id) {
        $BorrowTenderBakModel = new BorrowTender();
        $tenderBakResult = $BorrowTenderBakModel->findByPk($id);
        return $tenderBakResult;
    }

    /**
     * 获取一条备份的投资记录详细数据
     * @param: int $id 
     * @return: CActiveRecord $tenderResult
     */
    public function getTenderBakById($id) {
        $BorrowTenderModel = new BorrowTenderBak();
        $tenderResult = $BorrowTenderModel->findByAttributes( array('pid' => $id) );
        return $tenderResult;
    }
     
     /**
     * 获取标的投资记录
     * 
     */
     public function getBorrowTender($borrowId,$offset=0,$limit=10,$order="t.id desc",$more_attributes=array(),$more_criteria=null){
        $BorrowTenderModel = new BorrowTender();
        $criteria = new CDbCriteria;
	    if($order)  $criteria->order = $order; 
        $criteria->offset = $offset;
        if($limit!="ALL")  $criteria->limit = $limit;
        $attributes = array(
            "borrow_id"    =>   $borrowId,   
        );
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        $BorrowTenderResult =$BorrowTenderModel->with("userInfo","borrowInfo")->findAllByAttributes($attributes,$criteria);
        return $BorrowTenderResult;
     }

     public function getCountBorrowTender($borrowId){
        $BorrowTenderModel = new BorrowTender();
        $criteria = new CDbCriteria;
        $attributes = array(
            "borrow_id"    =>   $borrowId,
        );
        $count = $BorrowTenderModel->countByAttributes($attributes,$criteria);
        return $count;
    }

    public function getCountBorrowTenderByUserId($userId){
        $BorrowTenderModel = new BorrowTender();
        $criteria = new CDbCriteria;
        $attributes = array(
            "user_id"    =>   $userId,
        );
        $count = $BorrowTenderModel->countByAttributes($attributes,$criteria);
        return $count;
    }

     /**
      * 获取用户的投资记录
      */
      public function getUserBorrowTender($user_id,$offset=0,$limit=10,$order="",
        $more_attributes=array(),$more_criteria=null,$count_flag=false,$with_array = array() ){
        $BorrowTenderModel = new BorrowTender();
        $criteria = new CDbCriteria; 
        $criteria->offset = $offset;
        if($order) $criteria->order = $order;
        if($count_flag) $limit = "ALL";
        if($limit!="ALL") $criteria->limit = $limit;
        $attributes = array(
            "user_id"    =>   $user_id,   
        );
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        if($count_flag) return $BorrowTenderModel->countByAttributes($attributes,$criteria);
        $BorrowTenderResult =$BorrowTenderModel->findAllByAttributes($attributes,$criteria);
        return $BorrowTenderResult;  
      }

     /**
      * 获取用户的投资记录
      */
      public function getGuarantorBorrowTender($guarantorid,$offset,$limit){
        $BorrowTenderModel = new BorrowTender();
        $criteria = new CDbCriteria;
        $criteria->offset = $offset;
        if(!empty($limit)) {
            $criteria->limit = $limit;
        }
        $attributes = array(
            "guarantors"    =>   $guarantorid,
        );
        $BorrowTenderResult =$BorrowTenderModel->findAllByAttributes($attributes,$criteria);
        return $BorrowTenderResult;
      }
      
    /**
      * 获取单个用户的付息还本记录
      */
    public function getUserBorrowCollection($user_id,$offset=0,$limit=10){
        $userBorrowTender = self::getUserBorrowTender($user_id,0,null);
        if(count($userBorrowTender)>0){
            $tender_array = array();
            foreach($userBorrowTender as $row){
                $tender_array[] = $row->id;
            }
            $BorrowCollectionModel = new BorrowCollection();
            $criteria = new CDbCriteria; 
            $criteria->addInCondition("tender_id", $tender_array);
            $criteria->offset = $offset;
            if(!empty($limit)){
                $criteria->limit = $limit;
            }
            $attributes = array();
            $BorrowCollectionResult =$BorrowCollectionModel->findAllByAttributes($attributes,$criteria);
            return $BorrowCollectionResult; 
        }else{
            return null;
        }
         
    }   

	/**
     * 计算付本还息数据
     *
     *
     */
    public function equalInterest($data){
        
        if (isset($data['borrow_style']) && $data['borrow_style']!=""){
            $borrow_style = $data['borrow_style'];
        }

        if ($borrow_style==0){
            return self::equalNextMonthByDay($data);
        }elseif ($borrow_style==1){
            $data['type'] = 'all';
            return self::equalNextMonthByDay($data);
        }elseif ($borrow_style==2){
            return self::equalEndMonthByDay($data);
        }

    }

    // 用于计算29、30、31投资的特殊情况
    public function dateNextMonth($now, $date = 0) {
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

    /**
     * 计息核心函数
     *
     * @param Array $data, repayment_time, borrow_time, year_apr, account,
     * @return Array
     */
    //到期还本，按月付息，按日计息
    //到期还本付息，按日计息(type=all)
    public function equalNextMonthByDay($data){
        
        //到期日
        if (isset($data['repayment_time']) && $data['repayment_time']>0){
            $repayment_time = strtotime("midnight", $data['repayment_time']);
        }else{
            return "";
        }

        //借款的总金额
        if (isset($data['account']) && is_numeric($data['account']) && $data['account']>0){
            $account = $data['account'];
        }else{
            return "";
        }

        //借款的年利率
        if (isset($data['year_apr']) && is_numeric($data['year_apr']) && $data['year_apr']>0){
            $year_apr = $data['year_apr'];
        }else{
            return "";
        }
        
        $delay_value_days = isset($data['delay_value_days']) ? $data['delay_value_days'] : 0;
        //借款的时间
        if (isset($data['borrow_time']) && $data['borrow_time']>0){
            $borrow_time = strtotime("midnight", $data['borrow_time']) + $delay_value_days*24*60*60;
        }else{
            $borrow_time = strtotime("midnight", time()) + $delay_value_days*24*60*60;
        }
        //借款日
        $borrow_day = date("d", $borrow_time);
        //借款时间必须在还款时间之前
        if ($borrow_time > $repayment_time){
            return "";
        }
        
        //日利率
        $daily_apr = $year_apr/(365*100);

        //总利息=投资额*日息*投资天数
        $invest_days = round(($repayment_time-$borrow_time)/(24*60*60));
        $total_interest = floor(100 * $account * $daily_apr * $invest_days)/100;

        //对于到期还本付息，按日计息的情况
        if (isset($data['type']) && $data['type']=="all"){
            $_result[0]['repayment_account'] = $account + $total_interest;
            $_result[0]['repayment_time'] = $data['repayment_time'];
            $_result[0]['interest'] = $total_interest;
            $_result[0]['capital'] = $account;
            $_result[0]['days'] = $invest_days;
            return $_result;
        }
        $i = 0;
        while(self::dateNextMonth($borrow_time, $borrow_day) < $repayment_time){
            $borrow_time_next_month = self::dateNextMonth($borrow_time, $borrow_day);
            $interest = round($account * $daily_apr * round(($borrow_time_next_month - $borrow_time)/(24*60*60)), 2);
            $_result[$i]['repayment_account'] = $interest;
            $_result[$i]['repayment_time'] = $borrow_time_next_month;
            $_result[$i]['interest'] = $interest;
            $_result[$i]['capital'] = 0;
            $_result[$i]['days'] = round(($borrow_time_next_month - $borrow_time)/(24*60*60));
            $borrow_time = $borrow_time_next_month;
            $i++;
            $total_interest = round($total_interest - $interest, 2);
        }
        $_result[$i]['repayment_account'] = $account + $total_interest;
        $_result[$i]['repayment_time'] = $repayment_time;
        $_result[$i]['interest'] = $total_interest;
        $_result[$i]['capital'] = $account;
        $_result[$i]['days'] = round(($repayment_time - $borrow_time)/(24*60*60));
        
        return $_result;
    }


    /**
     * 计息核心函数
     *
     * @param Array $data, repayment_time, borrow_time, year_apr, account,
     * @return Array
     */
    //到期还本，月底付息，按日计息
    function equalEndMonthByDay ($data) {
        //到期日
        if (isset($data['repayment_time']) && $data['repayment_time']>0){
            $repayment_time = strtotime("midnight", $data['repayment_time']);
        }else{
            return "";
        }

        //借款的总金额
        if (isset($data['account']) && is_numeric($data['account']) && $data['account']>0){
            $account = $data['account'];
        }else{
            return "";
        }

        //借款的年利率
        if (isset($data['year_apr']) && is_numeric($data['year_apr']) && $data['year_apr']>0){
            $year_apr = $data['year_apr'];
        }else{
            return "";
        }

        $delay_value_days = isset($data['delay_value_days']) ? $data['delay_value_days'] : 0;
        //借款的时间
        if (isset($data['borrow_time']) && $data['borrow_time']>0){
            $borrow_time = strtotime("midnight", $data['borrow_time']) + $delay_value_days*24*60*60;
        }else{
            $borrow_time = strtotime("midnight", time()) + $delay_value_days*24*60*60;
        }
        //借款时间必须在还款时间之前
        if ($borrow_time > $repayment_time){
            return "";
        }
        //日利率
        $daily_apr = $year_apr/(365*100);

        //总利息=投资额*日息*投资天数
        $invest_days = round(($repayment_time-$borrow_time)/(24*60*60));
        $total_interest = floor(100 * $account * $daily_apr * $invest_days)/100;

        //对于到期还本付息，按日计息的情况
        if (isset($data['type']) && $data['type']=="all"){
            $_result['repayment_account'] = $account + $total_interest;
            $_result['repayment_time'] = $data['repayment_time'];
            $_result['interest'] = $total_interest;
            $_result['capital'] = $account;
            $_result['days'] = $invest_days;
            return $_result;
        }

        $i = 0;
        $tmp_time = $borrow_time;
        while(strtotime("-1 day", strtotime("first day of next month", $tmp_time)) < $repayment_time){
            $borrow_time_next_month = strtotime("-1 day", strtotime("first day of next month", $tmp_time));
            $interest = round($account * $daily_apr * round(($borrow_time_next_month - $borrow_time)/(24*60*60)), 2);
            if ( $interest > 0 ){
                $_result[$i]['repayment_account'] = $interest;
                $_result[$i]['repayment_time'] = $borrow_time_next_month;
                $_result[$i]['interest'] = $interest;
                $_result[$i]['capital'] = 0;
                $_result[$i]['days'] = round(($borrow_time_next_month - $borrow_time)/(24*60*60));
                $i++;
            }
            $borrow_time = $borrow_time_next_month;
            $tmp_time = strtotime("+1 day", $borrow_time_next_month);
            $total_interest = round($total_interest - $interest, 2);
        }
        $_result[$i]['repayment_account'] = $account + $total_interest;
        $_result[$i]['repayment_time'] = $repayment_time;
        $_result[$i]['interest'] = $total_interest;
        $_result[$i]['capital'] = $account;
        $_result[$i]['days'] = round(($repayment_time - $borrow_time)/(24*60*60));

        return $_result;
    }
    
    //传入borrow_tender表中的debt_type,获得对应的borrow表中的type
    static public  function debtTypeToBorrowtype ($debtType) {
      switch ($debtType) {
          case 1://爱担保
          case 2://爱担保债券
                return '2';
                break;
          case 5://爱融租
          case 6://爱融租债权
                return '5';
                break;
          case 7://爱保理
          case 8://爱保理债权
                return '6';
                break;
          case 9://爱收藏
          case 10://爱收藏债券
                return '7';
                break;
          case 11://省心影视C套餐
          case 12://省心影视C套餐债权
                return '302';
                break;
          default:
                return FALSE;
                break;
      }
      //…
    }
    
    //传入borrow表中的debt_type,和是否为债券，获得对应的borrow_tender中的debt_type
    static public  function borrowTypeToDebtType($borrowType, $isDebt = false) {
        if($isDebt){//如果是直投
            switch($borrowType){
                case 2://爱担保
                    return '1';
                    break;
                case 5://爱融租
                    return '5';
                    break;
                case 6://爱保理
                    return '7';
                    break;
                case 7://爱收藏
                    return '9';
                    break;
                case 302://省心影视C套餐
                    return '11';
                    break;
                default:
                    return FALSE;
                    break;
            }
        }else{//如果是债权
            switch($borrowType){
                case 2:
                    return '2';
                    break;
                case 5:
                    return '6';
                    break;
                case 6:
                    return '8';
                    break;
                case 7:
                    return '10';
                    break;
                case 302:
                    return '12';
                    break;
                default:
                    return FALSE;
                    break;
            }
        }
    }
    

}

<?php
/**
 * 逾期还款API
 *
 */
class OverdueRepayment extends ItzApi{
    public $logcategory = "overdue.repayment.query";

    public function query($borrow_id){  
        Yii::log("RequestData: borrow=$borrow_id;", "info", $this->logcategory);
        $data = array();
        $time = date('Y-m-d', time());

        #参数验证
        if (empty($borrow_id) || !is_numeric($borrow_id) || $borrow_id <= 0) {
            $this->code = 1003;
            return $this;
        }

        #项目信息不能为空
        $borrowInfo = $this->getBorrowInfo($borrow_id);
        if (empty($borrowInfo->id)) {
            $this->code = 3002;
            return $this;
        }

        #项目状态行必须为存续期
        if ($borrowInfo->status != 3) {
            $this->code = 9001;
            return $this;
        }

        #还款方式不是等额本息
        if ($borrowInfo->style == 5) {
            $this->code = 9002;
            return $this;
        }

        #只有项目到期日才能公布逾期
        if ($time != date('Y-m-d',$borrowInfo->repayment_time)) {
            $this->code = 9003;
            return $this;
        }

        #如果正在逾期还款中，不允许查询
        $compensate_count = ItzOverdueRepayment::model()->count(' borrow_id =:borrow_id and status > 0',array(':borrow_id'=>$borrowInfo->id));
        if ($compensate_count > 0) {
            $this->code = 9004;
            return $this;
        }

        #还款记录中存在错误状态的记录 
        $count = BorrowCollection::model()->count(' borrow_id =:borrow_id AND status in (0,5,8,9) AND repay_time <:repay_time',array(':borrow_id'=>$borrowInfo->id,':repay_time'=>$borrowInfo->repayment_time));
        if ($count > 0) {
            $this->code = 9005;
            return $this;
        }

        #检查还款表最后一期本息的状态
        $return_code = $this->checkLastCollection($borrowInfo);
        if ($return_code != 0) {
            $this->code = $return_code;
            return $this;
        }
        
        #检查暂停本息的记录
        $return_code = $this->checkCompensateData($borrowInfo);
        if ($return_code != 0) {
            $this->code = $return_code;
            return $this;
        }

        #返回最后一期的利息
        $interest_res = $this->getCollectionData($borrowInfo,'interest');
        
        #返回最后一期的本金
        $capital_res = $this->getCollectionData($borrowInfo,'capital');
        if (empty($capital_res)) {
            $this->code = 8006;
            return $this;
        }

        #验证返回的本金总数
        $totalCaptial_result = $this->checkCaptialLegal($borrowInfo);
        if ($totalCaptial_result !=0) {
            $this->code  = $totalCaptial_result;
            return $this;
        }
        
        #组合返回的数据
        $data[] = $interest_res;
        $data[] = $capital_res;
        
        $this->code = 0;
        $this->data = $data;
        return $this;
    }

    #检查最后一期本息是否处于暂停还本息的状态
    public function checkLastCollection($borrowInfo){

        #最后一期的利息
        $interest_count = BorrowCollection::model()->count(' borrow_id = :borrow_id AND status in (0,8,9) AND repay_time = :repay_time AND interest>0',array(':borrow_id'=>$borrowInfo->id,':repay_time'=>$borrowInfo->repayment_time));
         
        #最后一期的本金
        $capital_count  = BorrowCollection::model()->count(' borrow_id = :borrow_id AND status not in(5,10) AND repay_time = :repay_time AND capital>0',array(':borrow_id'=>$borrowInfo->id,':repay_time'=>$borrowInfo->repayment_time));

        if ($interest_count > 0) {
            $return_code = 9011; 
        } elseif($capital_count > 0) {
            $return_code = 9012;
        } else {
            $return_code = 0;
        }
        return $return_code;
    }


    #根据类型获取本息数据
    public  function  getCollectionData($borrowInfo,$dataType){

        $tmp = array();
        $data_condition = new CDbCriteria;
        $data_condition->select                = " FROM_UNIXTIME(repay_time,'%Y-%m-%d') AS repay_time,
                                                   sum(capital) AS capital,
                                                   sum(interest) AS interest ";
        $data_condition->condition             = " borrow_id = :borrow_id AND `status` = 5 AND $dataType > 0 AND repay_time = :repay_time";
        $data_condition->params[':repay_time'] = $borrowInfo->repayment_time;
        $data_condition->params[':borrow_id']  = $borrowInfo->id;
        $data_res = BorrowCollection::model()->findAll($data_condition);
        if ($this->isEmptyData($data_res[0],$dataType)) {
            return array();
        }
        $tmp['repay_time'] = $data_res[0]->repay_time;
        $tmp['capital']    = $data_res[0]->capital;
        $tmp['interest']   = $data_res[0]->interest;
        return $tmp;
    }

    #检查暂停本息表的数据
    public function checkCompensateData($borrowInfo){
        $time       = date('Y-m-d', time());
        $errorCode  = 0;
        $dataType   = array(2,3);
        $pre_result =  DwBorrowCompensatePre::model()->findAll(array(
            "select"    => array("type,status"),  
            "condition" => " borrow_id = :borrow_id AND FROM_UNIXTIME(addtime,'%Y-%m-%d')=:today_time", 
            "params"    => array(":borrow_id"=>$borrowInfo->id,":today_time"=>$time)
            ));        
        $result_count = count($pre_result);

        #暂停数据数量异常（今日）
        if ($result_count == 0 || $result_count>2) {
            return '9006';
        }

        #如果只有一条，一定为本金的暂停记录
        if ($result_count == 1) {
            if($pre_result[0]['type'] != 2){
                return '9007';
            }
        }   
        foreach ($pre_result as  $pre_data) {
            if (!in_array($pre_data['type'],$dataType)) {//数据类型有误
                return '9008';
            }
            $key = array_search($pre_data['type'], $dataType);
            unset($dataType[$key]);
            if ($pre_data['type'] == 2 && !in_array($pre_data['status'],array(2,8))) {//本金状态有误
                return '9009';
            } 
            if ($pre_data['type'] == 3 && !in_array($pre_data['status'],array(2,6,7,8))){//利息状态有误
                return '9010';
            }
        }
        return  $errorCode;
    }

    #检测本金/利息返回数组的有效信息是否为空
    public function isEmptyData($data, $dataType){
        if ($dataType == 'interest') {
            if (empty($data['interest']) || empty($data['repay_time'])) {
                return true;
            } else {
                return false;
            }
        } elseif($dataType == 'capital' ) {
            if (empty($data['capital']) || empty($data['repay_time'])) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    #获取项目信息
    public function getBorrowInfo($borrow_id){
        $borrowInfo = Borrow::model()->find(array(
            'select'    => 'id,status,style,repayment_time,account_yes',    
            'condition' => 'id = :borrow_id',
            'params'    => array(':borrow_id'=>$borrow_id)
            ));
        return $borrowInfo;
    }

    #检验本金总额的合法性
    public function checkCaptialLegal($borrowInfo){
        $tmp = array();
        $data_condition = new CDbCriteria;
        $data_condition->select                = " sum(capital) AS capital ";
        $data_condition->condition             = " borrow_id = :borrow_id AND `status` in(5,10) AND  capital> 0 AND repay_time = :repay_time";
        $data_condition->params[':repay_time'] = $borrowInfo->repayment_time;
        $data_condition->params[':borrow_id']  = $borrowInfo->id;
        $data_res = BorrowCollection::model()->findAll($data_condition);
        if (empty($data_res[0]->capital)) {
            return '8006';
        }
        if (!FunctionUtil::float_equal(round($borrowInfo->account_yes,2),round($data_res[0]->capital,2), 2)) {
            return '9013';
        }else{
            return '0';
        }
    }
}
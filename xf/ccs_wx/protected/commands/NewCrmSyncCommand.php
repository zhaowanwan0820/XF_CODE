<?php
/**
  *
  * 数据同步脚本
**/
class NewCrmSyncCommand extends CConsoleCommand 
{
    /**
     * 每小时执行一次，同步投资记录
     * $date 起始日期，只在初次执行时有效
     * $allot 是否只记录分配和待分配用户投资数据
     * $status 是否计入业绩
     */
    public function actionTender($date = "" ,$allot = 1,$status = 1)
    {
        //获取表中当前最大的tender_id ,取之后的就行了
        $max_tender_id = $this->getMaxTenderId();
        $criteria = new CDbCriteria;
        $criteria->addCondition("id > $max_tender_id");
        if($date && $start_time = strtotime($date)) {
            $criteria->addCondition("addtime > $start_time");
        }
        $now = time();
        //获取总数,防止一次取太多
        $count = BorrowTender::model()->countByAttributes([],$criteria);
        echo "total:".$count."\n";
        $loop = ceil($count/1000); //循环次数
        $criteria->select = "id,user_id,borrow_id,debt_type,addtime,account_init";
        $criteria->limit = 1000;
        $criteria->order = "id";
        $borrows = $admins = [];
        for($i=0;$i<$loop;$i++){
            $CrmNewTender = new CrmNewTender;
            $criteria->offset = $i*1000;
            echo "handle:".($i*1000)."\n";
            $tenders = BorrowTender::model()->findAllByAttributes([],$criteria);
            foreach($tenders as $tender){
                //获取用户分配信息
                $crmUser = CrmUser::model()->findByPk($tender->user_id);
                if($allot && empty($crmUser)){ // 不在分配用户表中的用户不记录其投资信息。
                    continue;
                }
                $CrmNewTenderC = clone $CrmNewTender;
                $CrmNewTenderC->user_id = $tender->user_id;
                $CrmNewTenderC->tender_id = $tender->id;
                $CrmNewTenderC->borrow_id = $tender->borrow_id;
                $CrmNewTenderC->debt_type = $tender->debt_type;
                $CrmNewTenderC->tender_time = $tender->addtime;
                $CrmNewTenderC->account_init = $tender->account_init;
                if(isset($borrows[$tender->borrow_id])){ 
                    $borrowInfo = $borrows[$tender->borrow_id];
                }else{
                    $borrow = Borrow::model()->findByPk($tender->borrow_id);
                    $borrowInfo = $borrows[$tender->borrow_id] = $borrow?$borrow->attributes:[];
                }
                if($borrowInfo){
                    $CrmNewTenderC->borrow_name = $borrowInfo['name'];
                    $CrmNewTenderC->apr = $borrowInfo['apr'];
                    if($borrowInfo['delay_value_days'] == 2){
                        $days = intval($borrowInfo['project_duration']);
                    } else {
                        $days = 0;
                        if($borrowInfo["repayment_time"]){
                            $datetime1 = date_create( date('Y-m-d', $borrowInfo["formal_time"]) );
                            $datetime2 = date_create( date('Y-m-d', $borrowInfo["repayment_time"]));
                            $interval = date_diff($datetime1, $datetime2);
                            $days = $interval->format("%a");
                        }
                    }
                    $CrmNewTenderC->project_duration = $days;
                }
                //计算是否为首投
                $criteria2 = new CDbCriteria;
                $criteria2->addCondition("id < ".$tender->id);
                $count = BorrowTender::model()->countByAttributes(["user_id"=>$tender->user_id],$criteria2);
                $CrmNewTenderC->first = $count?0:1;
                //这个用户已经分配了才记录相关客维信息
                if($crmUser->is_allot){
                    //获取客维信息
                    if(isset($admins[$crmUser->admin_id])){
                        $crmAdmin = $admins[$crmUser->admin_id];
                    } else{
                        $crmAdmin = $admins[$crmUser->admin_id] = CrmAdmin::model()->findByAttributes(["admin_id"=>$crmUser->admin_id ])->attributes;
                    }
                    //设置客维及其客维组长
                    $CrmNewTenderC->admin_id = $crmUser->admin_id;
                    $CrmNewTenderC->admin_pid = $crmAdmin["p_id"]?:0;
                    //直投，并且投资前有效电联的用户投资才记为其业绩，如果想数据全不计入业绩，一笔笔手动确认，设置参数status为0
                    $CrmNewTenderC->status = $status && ($tender->debt_type%2 == 1) && $crmUser->is_call == 0 && $crmUser->call_time < $tender->addtime?1:0;
                }
                $CrmNewTenderC->addtime = $now;
                $res = $CrmNewTenderC->save();
                if(!$res) {//记录错误信息
                    echo "error: tender_{$tender->id} insert error! \n";
                }
            }
            Yii::app()->dwdb->flushPdoInstance();
            Yii::app()->crmdb->flushPdoInstance();
        }
        echo "over \n";
    }

    /**
     * 每小时执行一次，同步用户动态
     */
    public function actionDynamic()
    {
        $now = time();
        $start_time = $now - 3600;
        $end_time = $now-1;
        
        //鉴权未回调
        $this->addOpenNobackDynamic($start_time - 1200,$end_time - 1200);//回调等20分钟才判定为失败
        //鉴权失败
        $this->addOpenErrorDynamic($start_time,$end_time);
        //充值未回调
        $this->addRechargeNobackDynamic($start_time - 1200,$end_time - 1200);
        //充值失败
        $this->addRechargeErrorDynamic($start_time,$end_time);
        //投资失败
        $this->addTenderErrorDynamic($start_time,$end_time);
        //提现成功
        $this->addCashSuccessDynamic($start_time,$end_time);
        //提现失败
        $this->addCashErrorDynamic($start_time,$end_time);
    }

    private function addOpenNobackDynamic($start_time,$end_time)
    {
        $open_errors_sql = "SELECT user_id,create_time as addtime FROM itz_open_account_record where create_time between :start_time and :end_time and status = 0";
        $this->addDynamic($open_errors_sql,1,$start_time,$end_time);
    }

    private function addOpenErrorDynamic($start_time,$end_time)
    {
        $open_errors_sql = "SELECT user_id,create_time as addtime FROM itz_open_account_record where create_time between :start_time and :end_time and status = 2";
        $this->addDynamic($open_errors_sql,2,$start_time,$end_time);
    }

    private function addRechargeNobackDynamic($start_time,$end_time)
    {
        $recharge_errors_sql = "SELECT user_id,addtime FROM dw_account_recharge where addtime between :start_time and :end_time and status = 0";
        $this->addDynamic($recharge_errors_sql,3,$start_time,$end_time);
    }

    private function addRechargeErrorDynamic($start_time,$end_time)
    {
        $recharge_errors_sql = "SELECT user_id,addtime FROM dw_account_recharge where addtime between :start_time and :end_time and status = 2";
        $this->addDynamic($recharge_errors_sql,4,$start_time,$end_time);
    }

    private function addTenderErrorDynamic($start_time,$end_time)
    {
        $tender_errors_sql = "SELECT user_id,addtime FROM dw_borrow_pre where addtime between :start_time and :end_time and status in (3,5)";
        $this->addDynamic($tender_errors_sql,6,$start_time,$end_time);
    }

    private function addCashSuccessDynamic($start_time,$end_time)
    {
        $cash_success_sql = "SELECT user_id,addtime,remark FROM dw_account_cash where addtime between :start_time and :end_time and status = 3";
        $this->addDynamic($cash_success_sql,7,$start_time,$end_time);
    }

    private function addCashErrorDynamic($start_time,$end_time)
    {
        $cash_error_sql = "SELECT user_id,addtime,remark FROM dw_account_cash where addtime between :start_time and :end_time and status in (2,4,5)";
        $this->addDynamic($cash_error_sql,8,$start_time,$end_time);
    }

    private function addDynamic($sql,$type,$start_time,$end_time)
    {
        $now = time();
        $dynamics = Yii::app()->dwdb->createCommand($sql)->bindParam(":start_time",$start_time,PDO::PARAM_INT)->bindParam(":end_time",$end_time,PDO::PARAM_INT)->queryAll();
        $userDynamicC = new CrmUserDynamic;
        echo "$type:".count($dynamics)."\n";
        foreach($dynamics as $dynamic){
            $userDynamic = clone $userDynamicC;
            $userDynamic->user_id = $dynamic['user_id'];
            $userDynamic->datetime = $dynamic['addtime'];
            $userDynamic->remark = "";
            $userDynamic->type = $type;
            $userDynamic->addtime = $now;
            $userDynamic->save();
        }
    }

    private function getMaxTenderId()
    {
        $sql = "SELECT max(tender_id) as max_tender_id from crm_new_tender";
        $result = Yii::app()->crmdb->createCommand($sql)->queryScalar();
        return $result?:0;
    }
}
?>
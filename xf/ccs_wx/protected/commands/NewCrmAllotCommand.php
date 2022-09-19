<?php
/**
  *
  * 数据同步脚本
**/
class NewCrmAllotCommand extends CConsoleCommand 
{
    /**
     * 释放久未联系的用户（默认7天）
     */
    public function actionNoncon($days = "7")
    {
        echo "start \n";
        $starttime = strtotime("-{$days} days midnight");
        $criteria2 = new CDbCriteria;
        $criteria2->addCondition("is_allot = 1");
        $criteria2->addCondition("allot_time < $starttime");
        $criteria2->addCondition("is_call <> 1");
        $count = CrmUser::model()->countByAttributes([],$criteria2);
        echo "total:$count \n";
        $loop = ceil($count/1000);
        $criteria2->limit = 1000;
        $now = time();
        for($i=0;$i<$loop;$i++){
            $criteria2->offset = $offset = $i*1000;
            echo "handle:".($offset)."~".($offset+1000)."\n";
            $users = CrmUser::model()->findAllByAttributes([],$criteria2);
            $crm_user_logC = new CrmUserLog();
            foreach($users as $crm_user){
                $crm_user_log = clone $crm_user_logC;
                $crm_user_log->log_type = "to_sea_noncon";
                $crm_user_log->admin_id = 0;
                $crm_user_log->user_id = $crm_user->user_id;
                $crm_user_log->addtime = $now;
                $res = $crm_user_log->save();
                if($res){
                    $crm_user->admin_id = 0;
                    $crm_user->is_allot = 0;
                    $crm_user->allot_time = 0;
                    $crm_user->is_call = 0;
                    $crm_user->call_time = 0;
                    $crm_user->updatetime = $now;
                    $res2 = $crm_user->save();
                }else{
                    echo "user:{$crm_user->user_id} handle error! \n";
                }
            }
            Yii::app()->crmdb->flushPdoInstance();
        }
        echo "over \n";
    }


    /**
     * 释放13个月以上的用户至公海
     */
    public function actionNormal($days = "395")
    {
        echo "start \n";
        $starttime = strtotime("-{$days} days midnight");
        $criteria2 = new CDbCriteria;
        $criteria2->addCondition("is_allot = 1");
        $criteria2->addCondition("allot_time < $starttime");
        $count = CrmUser::model()->countByAttributes([],$criteria2);
        echo "total:$count \n";
        $loop = ceil($count/1000);
        $criteria2->limit = 1000;
        $now = time();
        for($i=0;$i<$loop;$i++){
            $criteria2->offset = $offset = $i*1000;
            echo "handle:".($offset)."~".($offset+1000)."\n";
            $users = CrmUser::model()->findAllByAttributes([],$criteria2);
            $crm_user_logC = new CrmUserLog();
            foreach($users as $crm_user){
                $crm_user_log = clone $crm_user_logC;
                $crm_user_log->log_type = "to_sea_normal";
                $crm_user_log->admin_id = 0;
                $crm_user_log->user_id = $crm_user->user_id;
                $crm_user_log->addtime = $now;
                $res = $crm_user_log->save();
                if($res){
                    $crm_user->admin_id = 0;
                    $crm_user->is_allot = 0;
                    $crm_user->allot_time = 0;
                    $crm_user->is_call = 0;
                    $crm_user->call_time = 0;
                    $crm_user->updatetime = $now;
                    $res2 = $crm_user->save();
                }else{
                    echo "user:{$crm_user->user_id} handle error! \n";
                }
            }
            Yii::app()->crmdb->flushPdoInstance();
        }
        echo "over \n";
    }

    /**
     * 释放3个月未投资的用户进公海
     */
    public function actionNoninvest($days = "91")
    {
        echo "start \n";
        $starttime = strtotime("-{$days} days midnight");
        $endtime = $starttime + 86399;
        $now = time();
        $sql = "SELECT u.user_id from crm_user u left join crm_new_tender nt on u.user_id = nt.user_id and nt.tender_time > u.allot_time where u.allot_time between :starttime and :endtime group by u.user_id having count(nt.id) = 0";
        $nonInvesters = Yii::app()->crmdb->createCommand($sql)
            ->bindParam(":starttime",$starttime,PDO::PARAM_INT)
            ->bindParam(":endtime",$endtime,PDO::PARAM_INT)
            ->queryAll();
        $count = count($nonInvesters);
        echo "total:$count \n";
        $crm_user_logC = new CrmUserLog();
        foreach($nonInvesters as $key=>$value) {
            $crm_user = CrmUser::model()->findByPk($value['user_id']);
            $crm_user_log = clone $crm_user_logC;
            $crm_user_log->log_type = "to_sea_noninvest";
            $crm_user_log->admin_id = 0;
            $crm_user_log->user_id = $crm_user->user_id;
            $crm_user_log->addtime = $now;
            $res = $crm_user_log->save();
            if($res){
                $crm_user->admin_id = 0;
                $crm_user->is_allot = 0;
                $crm_user->allot_time = 0;
                $crm_user->is_call = 0;
                $crm_user->call_time = 0;
                $crm_user->updatetime = $now;
                $res2 = $crm_user->save();
            }else{
                echo "user:{$crm_user->user_id} handle error! \n";
            }
            if($key % 1000 == 999){
                Yii::app()->crmdb->flushPdoInstance();
            }
        }
        echo "over \n";
    }
}
?>
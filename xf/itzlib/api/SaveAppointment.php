<?php
/**
 * 预约
 *
 * @author 
 */
class SaveAppointment extends ItzApi{
    public $logcategory = "invest.appointment.save";
    public $appointment_list_key = 'appointment_list';

    /**
     *  预约投标
     **/
    public function run($user_id=0, $is_open=1, $type=1, $borrow_type_rule=''){
        Yii::log("RequestData: user_id=$user_id; is_open=$is_open; type=$type; borrow_type_rule=$borrow_type_rule", "info", $this->logcategory);
        $data = $this->data = array();
        $appointment_sort = $appointment_id = 0;

        //必要参数判断
        if(empty($user_id) || !is_numeric($user_id) || $user_id<=0 || empty($borrow_type_rule)){
            $this->code=1003;
            return $this;
        }
        //判断用户是否存在
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo->user_id)) {
            $this->code = 2004;
            return $this;
        } 

        //判断用户是否已实名认证或手机认证
        if((int)$userInfo->real_status !== 1 || (int)$userInfo->phone_status !== 1) {
            $this->code = 2006;
            return $this;
        }

        //查询是否开启预约
        $appointmentCheck = ItzAppointment::model()->findAllByAttributes(array("user_id"=>$user_id));
        if (!empty($appointmentCheck['0']->user_id)) {
            $appointment_id = $appointmentCheck['0']->id;
        }       

        //存入预约投标，集合数据
        $appointment['user_id'] = $user_id;
        $appointment['borrow_type_rule'] = $borrow_type_rule;
        $appointment['is_open'] = $is_open;
        $appointment['type'] = $type;

        //获取队列总人数
        $appointment_list_count = Yii::app()->dqueue->llen($this->appointment_list_key);
        if($appointment_id>0) { //更改预约
            $appointment['id'] = $appointment_id;
            //获取排序号
            $org = QueryAppointment::api()->run($user_id)->result;
            $appointment_sort = isset($org['data']['appointment_sort'])?$org['data']['appointment_sort']:0;
            if($is_open){ //更新或重新开启
                if((int)$appointment_sort>0) {//根据排序号更新至redis
                    $result = Yii::app()->dqueue->lset($this->appointment_list_key, $appointment_sort-1, json_encode($appointment)); //更新状态
                    if($result == null || $result == false) {
                        Yii::log("False, lset($this->appointment_list_key, $appointment_sort, ".print_r($appointment,true)."); result:$result", "error", $this->logcategory);
                    } else {
                        Yii::log("lset($this->appointment_list_key, $appointment_sort, ".print_r($appointment,true)."); result:$result", "info", $this->logcategory);
                    }
                } else { //增加到队尾
                    $redis_result = Yii::app()->dqueue->rpush($this->appointment_list_key, json_encode($appointment));
                    if($redis_result == null || $redis_result == false) {
                        Yii::log("False, rPush($this->appointment_list_key, ".json_encode($appointment).": appointment_sort:$appointment_sort", "error", $this->logcategory);
                    } else {
                        Yii::log("rPush($this->appointment_list_key, ".json_encode($appointment).": appointment_sort:$appointment_sort", "info", $this->logcategory);
                    }
                    $appointment_sort = $redis_result;
                    $appointment_list_count--;
                }
            } else {
                //只要是关闭，均移除队列
                if((int)$appointment_sort>0) {
                    $org_single = Yii::app()->dqueue->lindex($this->appointment_list_key, $appointment_sort-1);
                    $lrem_result = Yii::app()->dqueue->lrem($this->appointment_list_key, 1, $org_single);
                    if($lrem_result == null || $lrem_result == false) {
                        Yii::log("False, lRem($this->appointment_list_key, $appointment_sort, $org_single; result:$lrem_result", "error", $this->logcategory);
                    } else {
                        Yii::log("lRem($this->appointment_list_key, $appointment_sort, $org_single; result:$lrem_result", "info", $this->logcategory);
                    }
                    $appointment_sort = 0;
                    $appointment_list_count--;
                } 
            }
            
            //更新数据库
            $appointmentInfo = BaseCrudService::getInstance()->update("ItzAppointment",$appointment, 'id');
            if (false == $appointmentInfo) { //更新预约失败
                $this->code = 7002;
                return $this;
            }

        } else { //新增预约
            if($is_open!=1) {
                $this->code = 7005;
                return $this;
            }

            $appointment['addtime'] = time();
            $appointment['addip'] = FunctionUtil::ip_address();
            $appointmentInfo = BaseCrudService::getInstance()->add("ItzAppointment",$appointment);
            if (false == $appointmentInfo) {
                $this->code = 7003;
                return $this;
            }
            unset($appointment['addtime']);
            unset($appointment['addip']);

            //存入redis
            $appointment_id = $appointment['id'] = $appointmentInfo['id'];
            $redis_result = Yii::app()->dqueue->rpush($this->appointment_list_key, json_encode($appointment));
            if($redis_result == null || $redis_result == false) {
                Yii::log("False, rPush($this->appointment_list_key, ".json_encode($appointment).": appointment_sort:$appointment_sort", "info", $this->logcategory);
            } else {
                Yii::log("rPush($this->appointment_list_key, ".json_encode($appointment).": appointment_sort:$appointment_sort", "info", $this->logcategory);
            }

            //获取排号
            $appointment_sort = $redis_result;
            $appointment_list_count++;
            Yii::log("appointment_sort:$appointment_sort", "info", $this->logcategory);

        }

        //查看更新或新增是否成功
        $data['user_id']=$user_id;
        $data['appointment_sort']=$appointment_sort;
        $data['appointment_list_count']=$appointment_list_count;
        $data['appointment_id']=$appointment_id;
        $this->code = 0;
        $this->data = $data;
        return $this;
    }

}

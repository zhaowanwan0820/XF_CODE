<?php
/**
 * 查询
 *
 * @author 
 */
class QueryAppointment extends ItzApi{
    public $logcategory = "invest.appointment.query";
    public $appointment_list_key = 'appointment_list';

    /**
     *  预约投标
     **/
    public function run($user_id){
        Yii::log("RequestData: user_id=$user_id;", "info", $this->logcategory);
        $data = $this->data = array();
        //必要参数判断
        if(empty($user_id) || !is_numeric($user_id) || $user_id<=0){
            $this->code=1003;
            return $this;
        }
        //判断用户是否存在
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo->user_id)) {
            $this->code = 2004;
            return $this;
        } 
        //查询用户是否开启预约
        $appointmentCheck = ItzAppointment::model()->findAllByAttributes(array("user_id"=>$user_id, "is_open"=>1));
        if (empty($appointmentCheck['0']->user_id)) {//此用户未开启预约
            $this->code = 7001;
            return $this;
        }
        //获取队列总人数
        $appointment_list_count = Yii::app()->dqueue->llen($this->appointment_list_key);

        //获取列表
        $flag = true;
        for ($i=0, $j=0; $flag; $i++) { 
            if($i*1000 >= $appointment_list_count) {
                $flag = false;
                break;
            }
            $appointmentList = Yii::app()->dqueue->lrange($this->appointment_list_key, $i*1000, $i*1000+999);
            foreach ($appointmentList as $key => $appointment_value) {
                $appointment = json_decode($appointment_value);
                if((int)$user_id === (int)$appointment->user_id){
                    $appointment->appointment_sort = $i*1000+$key+1;
                    $data = (array)$appointment;
                    $flag = false;
                    break;
                }
            }
        }
        //无此用户预约详情，请联系管理员
        if (empty($data)) {
            $this->code = 7004;
            return $this;
        }
        $data['appointment_list_count']=$appointment_list_count;
        $this->code = 0;
        $this->data = $data;
        return $this;
    }

}

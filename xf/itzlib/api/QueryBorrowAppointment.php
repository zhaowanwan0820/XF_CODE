<?php
/**
 * 查询
 *
 * @author 
 */
class QueryBorrowAppointment extends ItzApi{
    public $logcategory = "invest.appointment.queryborrow";
    public $appointment_list_key = 'appointment_list';
    public $appointment_borrow_hash_key = 'hash_appointment_list_borrow_';

    /**
     *  查询用户的预约资格
     **/
    public function run($user_id, $borrow_id = 0){
        Yii::log("RequestData: user_id=$user_id;", "info", $this->logcategory);
        $data = $result_value = array();

        //必要参数判断
        if(empty($user_id) || !is_numeric($user_id) || $user_id<=0){
            $this->code=2003;
            return $this;
        }
        //判断用户是否存在
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo->user_id)) {
            $this->code = 2004;
            return $this;
        }

        $time = time();

        if($borrow_id  > 0){
            $borrowInfo = Borrow::model()->findByPk($borrow_id);
            if(empty($borrowInfo->id)){
                $this->code = 3002;
                return $this;   
            }

            if($borrowInfo->appointment_money <= 0){
                $this->code = 7006;
                return $this;
            }

            if(($borrowInfo->formal_time-$time) <= 600){
                $this->code = 7007;
                return $this;
            }

            //根据user_id borrow_id查询 borrow_hash
            $result = Yii::app()->dqueue->hmget($this->appointment_borrow_hash_key.$borrow_id, $user_id);

            if(empty($result[0])){
                $this->code = 7008;
                return $this;
            }

            $result_value = json_decode($result[0]);
            if($result_value->invest_money == 0){
                $preRes = DwBorrowPre::model()->findAll(array('condition'=>" borrow_id = $borrow_id and user_id = $user_id and invest_type = 1 "));
                if(!empty($preRes)){
                    $this->code = 7009;
                    return $this;
                }
            }else{
                $this->code = 7009;
                return $this;
            }
                        
        }else{
            //查询可预约的项目
            $borrow_id = '';
            $borrowList = Borrow::model()->findAll(array('condition'=>"status = 101 and formal_time-$time>600 and appointment_money > 0 and priority_type in(0,1) ")); 
            foreach ($borrowList as $borrow_key => $borrow) {
                $result = Yii::app()->dqueue->hmget($this->appointment_borrow_hash_key.$borrow->id, $user_id);
                if(empty($result[0])){
                    continue;
                }

                $result_value = json_decode($result[0]);

                if($result_value->invest_money == 0){
                    $preRes = DwBorrowPre::model()->findAll(array('condition'=>" borrow_id = $borrow->id and user_id = $user_id and invest_type = 1 "));
                    if(!empty($preRes)){
                        continue;
                    }
                }else{
                    continue;
                }
                $borrow_id .= $borrow->id.','; 
            }

            if(empty($borrow_id)){
                $this->code = 7008;
                return $this;
            }

        }

        if(empty($result_value)){
            $this->code = 7004;
            return $this;
        }

        $data['appointment_id'] = $result_value->id;
        $data['borrow_id'] = rtrim($borrow_id, ',');
        $data['user_id'] = $user_id;
        $this->code = 0;  
        $this->data = $data;
        return $this;

    }

}



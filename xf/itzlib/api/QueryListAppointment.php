<?php
/**
 * 查询
 *
 * @author 
 */
class QueryListAppointment extends ItzApi{
    public $logcategory = "invest.appointment.querylist";
    public $appointment_list_key = 'appointment_list';
    public $appointment_list_borrow_key = 'appointment_list_borrow_';
    /**
     *  预约投标
     **/
    public function run($borrow_id=0, $page=1, $page_size=10){
        Yii::log("RequestData: borrow_id=$borrow_id; page=$page; page_size=$page_size;", "info", $this->logcategory);
        $data = array();

        if($borrow_id>0) {
            //判断用户是否存在
            $borrowInfo = Borrow::model()->findByPk($borrow_id);
            if(empty($borrowInfo->id)) {
                $this->code = 3002;
                return $this;
            }
            //根据borrowid获取队列
            $appointmentBorrowList = RedisService::getInstance()->lRange($this->appointment_list_borrow_key.$borrow_id, $page, $page_size);
            $data = (array)$appointmentBorrowList;
            Yii::log("lRange: borrow_id=$borrow_id;  page=$page; page_size=$page_size; appointmentBorrowList: ".json_encode($appointmentBorrowList), "info", $this->logcategory);
        } else {
            //获取队列
            $appointmentList = RedisService::getInstance()->lRange($this->appointment_list_key, $page, $page_size);
            $data = (array)$appointmentList;
            Yii::log("lRange: borrow_id=$borrow_id;  page=$page; page_size=$page_size; appointmentList: ".json_encode($appointmentList), "info", $this->logcategory);

        }
        //数据为空
        if (empty($data)) {
            $this->code = 7004;
            return $this;
        }
        $this->code = 0;
        $this->data = $data;
        return $this;
    }

}

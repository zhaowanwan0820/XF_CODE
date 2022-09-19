<?php
class ContractService extends ItzInstanceService{
    /**
     * 合同任务添加
     * @param $user_id int yong
     * @param $type 0权益兑换 1真实债转
     * @param $tender_id
     * @param $borrow_id
     * @param $borrow_type 1尊享 2普惠 3工场 4智多鑫
     * @param $platform_no int 权益兑换商城
     * @return bool
     */
    public function addContract($user_id, $type, $tender_id, $borrow_id, $borrow_type,$platform_no=0){
        if(empty($user_id) || !in_array($type, [1,0]) || empty($tender_id) || empty($borrow_id) || !in_array($borrow_type, [1,2,3,4,5])){
            Yii::log("addContract params error,user_id:$user_id, type:$type, tender_id:$tender_id, borrow_id:$borrow_id,borrow_type:$borrow_type", "error");
            return false;
        }

        //项目类型
        switch($borrow_type){
            case 1: $model_name = "ContractTask"; break;
            case 2: $model_name = "PHContractTask"; break;
            case 3:
            case 4:
            case 5:
            $model_name = "OfflineContractTask";
                break;
        }

        //校验是否已存在
        $count = $model_name::model()->count("tender_id=:tender_id", array(':tender_id' => $tender_id));
        if ($count > 0) {
            Yii::log("addContract params error,tender_id[$tender_id] already exist", "error");
            return false;
        }

        //存储合同处理任务
        $now_time = time();
        $contract_task =  new $model_name();
        $contract_task->type = $type;
        $contract_task->tender_id = $tender_id;
        $contract_task->user_id = $user_id;
        $contract_task->investtime = $now_time;
        $contract_task->addtime = $now_time;
        $contract_task->borrow_id = $borrow_id;
        $contract_task->platform_no = $platform_no;

        //字段名字区分
        if(in_array($borrow_type, [3,4,5])){
            $contract_task->platform_id = $borrow_type;
        }

        if(false == $contract_task->save()){
            Yii::log("addContract insert error:".print_r($contract_task->getErrors()), "error");
            return false;
        }
        return true;
    }

}

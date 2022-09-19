<?php
class ContractService extends ItzInstanceService{
    /**
     * 合同任务添加
     * @param $user_id int yong
     * @param $type 0权益兑换 1真实债转
     * @param $tender_id
     * @param $borrow_id
     * @param $borrow_type 1尊享 2普惠 3资产花园
     * @param $e_debt_template 资产花园时必传
     * @return bool
     */
    public function addContract($user_id, $type, $tender_id, $borrow_id, $borrow_type, $e_debt_template=''){
        if(empty($user_id) || !in_array($type, [1,0]) || empty($tender_id) || empty($borrow_id) || !in_array($borrow_type, [1,2,3])){
            Yii::log("addContract params error,user_id:$user_id, type:$type, tender_id:$tender_id, borrow_id:$borrow_id,borrow_type:$borrow_type", "error");
            return false;
        }
        //资产花园时，合同模板ID必传
        if(empty($e_debt_template) && $borrow_type == 3){
            Yii::log("addContract e_debt_template：$e_debt_template error ", "error");
            return false;
        }
        //项目类型
        switch($borrow_type){
            case 1: $model_name = "ContractTask"; break;
            case 2: $model_name = "PHContractTask"; break;
            case 3: $model_name = "AgContractTask"; break;
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
 
        //字段名字区分
        if($borrow_type == 3){
            $contract_task->project_id = $borrow_id;
            $contract_task->e_debt_template = $e_debt_template;
        }else{
            $contract_task->borrow_id = $borrow_id;
        }

        if(false == $contract_task->save()){
            Yii::log("addContract insert error:".print_r($contract_task->getErrors()), "error");
            return false;
        }
        return true;
    }

}

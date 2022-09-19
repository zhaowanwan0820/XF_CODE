<?php
/**
 * TAM系统API
 */
class UserService extends ItzInstanceService
{

   
    public function __construct()
    {
    	parent::__construct();


    }

    /**
     * 用户账户变更&流水记录
     * @param $data
     * @return bool
     */
    public function addLog($data){
        //处理php的浮点数
        $need_to_parse = array("money", "use_money", "lock_money", "withdraw_free", "recharge_amount");
        foreach($data as $key=>&$value){
            if(in_array($key, $need_to_parse)){
                $value = round($value, 2);
            }
        }
        if(!isset($data['user_id']) || empty($data['user_id']) || !is_numeric($data['user_id'])){
            Yii::log('addLog user_id not exists '.print_r($data, true), 'error');
            return false;
        }

        if(!isset($data['money']) && $data['money'] == 0){
            Yii::log("addLog money[{$data['money']}] error ", 'error');
            return false;
        }

        try{
            //记录流水
            $result = BaseCrudService::getInstance()->add("AgAccountLog", $data);
            if(false == $result){
                Yii::log('addLog add AccountLog error','error');
                return false;
            }
            $account['user_id'] = $data['user_id'];
            if(isset($data['use_money'])){
                $account['use_money'] = $data['use_money'];
            }
            if(isset($data['lock_money'])){
                $account['lock_money'] = $data['lock_money'];
            }
            if(isset($data['withdraw_free'])){
                $account['withdraw_free'] = $data['withdraw_free'];
            }
            if(isset($data['recharge_amount'])){
                $account['recharge_amount'] = $data['recharge_amount'];
            }

            //账户信息变更
            $result = BaseCrudService::getInstance()->update("AgUserAccount", $account, "user_id");
            if(false == $result){
                Yii::log('addLog edit AgUserAccount error','error');
                return false;
            }
            return true;
        }catch(Exception $ee){
            Yii::log('addLog Exception '. print_r($ee,true),'error');
            return false;
        }
    }


}
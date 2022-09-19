<?php
/**
 * 智选计划资金冻结
 * @param int    $user_id        用户id
 * @param int    $borrow_id      项目id
 * @param float  $money          投资钱数
 * @param string $invest_device  投资设备
 * @param string $coupon_ids     所用优惠券ID
 * @param int    $invest_type    投资类型
 * return array
 */
class WisePlanInvestPre extends ItzApi{
    public $logcategory = "wise.invest.borrow.pre";//日志类别
    private $pre_list_key = "wise_invest_borrow_pre";//pre redis队列key
    private $single_borrow_hash_pre = "wise_hash_invest_users_";//单个项目HASH KEY

    /**
     * 记录日志
     */
    public function echoLog($yiilog, $level='error') {
        $server_ip = empty($_SERVER["SERVER_ADDR"])?"":$_SERVER["SERVER_ADDR"];
        $yiilog = 'SERVER_IP:'.$server_ip.",".date('Y-m-d H:i:s').','.$yiilog."\n";
        if ($level == 'email') {
            $level = "error";
            $title = '智选计划投资冻结资金报警:';
            FunctionUtil::alertToAccountTeam($title.$yiilog);
        }
        Yii::log(print_r($yiilog, true), $level, $this->logcategory);
    }

    /**
     * 设置redis信息 pre 队列以及单个项目hash信息
     */
    private function saveHash($pre_data, $borrow_id, $uuid, $hash_data) {
        if (empty($pre_data) || empty($borrow_id) || empty($uuid) || empty($hash_data)) {
            self::echoLog("saveHash return false:".print_r(func_get_args(),true));
            return false;
        }
        Yii::app()->dqueue->multi();
        try {
            //进入borrow_pre 取号
            $push_result = Yii::app()->dqueue->rPush($this->pre_list_key, json_encode($pre_data));
            if ($push_result == false || $push_result == null) {//这里存入redis失败记录日志 仍继续，有pre表做备份，不影响投资流程
                self::echoLog("redis push fail,list_key:{$this->pre_list_key},pre_data:".print_r($pre_data,true));
                Yii::app()->dqueue->discard();//redis事务回滚
                return false;
            }
            //存进单个项目HASH 
            $hash_result = Yii::app()->dqueue->hset($this->single_borrow_hash_pre.$borrow_id, $uuid, json_encode($hash_data));
            if ($hash_result == false || $hash_result == null) {
                self::echoLog("Redis hash:".$this->single_borrow_hash_pre.$borrow_id." set fail, key:".$uuid.",value:".json_encode($hash_data));
                Yii::app()->dqueue->discard();//redis事务回滚
                return false;
            }
            Yii::app()->dqueue->exec();// 提交redis事务
            return true;
        } catch (RedisException $e) { //redis出现异常，记录日志，继续冻结
            self::echoLog("Redis hash:".$this->single_borrow_hash_pre.$borrow_id." set fail, key:".$uuid.",value:".json_encode($hash_data));
            return false;
        }
    }

    /**
     * 保存到redis，失败在尝试
     */
    public function saveRedis ($pre_data, $borrow_id, $uuid, $hash_data) {
        $hash_result = $this->saveHash($pre_data, $borrow_id, $uuid, $hash_data);
        if ($hash_result == false) {//如果第一次取号失败，在重试一次
            $hash_result = $this->saveHash($pre_data, $borrow_id, $uuid, $hash_data);
            if ($hash_result == false) {//如果第二次还是失败，跳过继续
                return false;
            }
        }
        return true;
    }
    
    /**
     * 更新冻结状态
     */
    private function updateFreezeStatus($borrow_id, $uuid, $hash_data) {
        $hash_result = Yii::app()->dqueue->hset($this->single_borrow_hash_pre.$borrow_id, $uuid, json_encode($hash_data));
        if ($hash_result === false || $hash_result === null) {
            return false;
        }
        return true;
    }

    /**
     * 更新冻结状态
     */
    public function saveFreezeStatus($borrow_id, $uuid, $hash_data) {
        $hash_result = $this->updateFreezeStatus($borrow_id, $uuid, $hash_data);
        if ($hash_result == false) {
            $hash_result = $this->updateFreezeStatus($borrow_id, $uuid, $hash_data);
            if ($hash_result == false) {
                return false;
            }
        }
        return true;
    }
    
}

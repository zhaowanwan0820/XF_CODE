<?php
/**
 * @file CreditService.php
 * @author (kuangjun@xxx.com)
 * @date 2013/12/14
 * 积分类
 **/

class CreditService extends  ItzInstanceService {
	
	protected $expire1 = 1200;
    protected $expire2 = 86400;
    protected $secondaryFlag = false;
    
    public function __construct(  )
    {
        parent::__construct();
    }
    public function getCredit($user_id){
        $CreditModel = new Credit();
        $CreditResult = $CreditModel->findByAttributes(array("user_id"=>$user_id));
        if(!empty($CreditResult)){
            return $CreditResult->getAttributes();
        }else{
            return array();
        }
    }

    //会员积分类型表查询
	public function getCreditTypeInfo($attributes){
	    $CreditTypeModel = new CreditType();
        $CreditTypeResult = $CreditTypeModel->findByAttributes($attributes);
        if(!empty($CreditTypeResult)){
            return $CreditTypeResult->getAttributes();
        }else{
            return null;
        }
	}
    
    public function getCreditLogs($offset=0,$limit=10,$order="",$more_attributes=array(),$more_criteria=null){
        $returnResult = array();
        $criteria = new CDbCriteria; 
        $criteria->offset = $offset;
        if($order) $criteria->order = $order;
        if($limit!="ALL") $criteria->limit = $limit;
        $attributes = array();
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        $CreditLogModel = new CreditLog();
        $CreditLogResult = $CreditLogModel->findAllByAttributes($attributes,$criteria);
        if(!empty($CreditLogResult)){
            foreach ($CreditLogResult as $row) {
               $returnResult[] = $row->getAttributes();
            }
            return $returnResult;
        }else{
            return null;
        }
    }

    /**
     * 获取积分流水的条数 焦民政
     */
    public function getCreditLogsCount($offset=0,$limit=10,$order="",$more_attributes=array(),$more_criteria=null){
        $returnResult = array();
        $criteria = new CDbCriteria;
        $criteria->offset = $offset;
        if($order) $criteria->order = $order;
        if($limit!="ALL") $criteria->limit = $limit;
        $attributes = array();
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        $CreditLogModel = new CreditLog();
        return $CreditLogModel->countByAttributes($attributes,$criteria);
    }
    
    /**
     * 迁移的函数 更新积分 武晓青
     * @param $user_id 会员ID
     * @param $credit_type_code 积分类型代码
     * @param $value 变动积分值
     * @param $op_user 操作者
     */
    public function UpdateCredit($data = array()) {
        Yii::log ( __FUNCTION__." ".print_r(func_get_args(),true),'debug');
        $user_id = (int)$data['user_id'];
        $nid = $data['nid'];     //礼品兑换类型
        $value = $data['value']; //所需积分数 兑换礼品的积分||需要给用户加上的积分
        $op_user = $data['op_user']; //操作者

        $now = time();//获得当前时间的吗秒数
        $now_date = strtotime(date('Y-m-d'));//获得当天0点的秒数

        //在会员积分类型表 dw_credit_type 查询该nid类型
        $credit_type = self::getCreditTypeInfo(array("nid"=>$data['nid']));
        if (!$credit_type) {
            Yii::log("error nid type","error");
            return false;
        }

        //如果 兑换礼品积分为0 ？则赋值礼品积分类型表中value字段值 ：否则 赋值兑换礼品积分
        $value = 0==$value?(int)$credit_type['value']:(int)$value;
        $type_id = (int)$credit_type['id']; //该积分兑换类型ID
        $cycle = (int)$credit_type['cycle'];//周期
        $type_name = $credit_type['name'];  //名称
        $award_times = (int)$credit_type['award_times']; //奖励次数
        $interval    = (int)$credit_type['interval'];    //时间间隔

        switch ($cycle) {
            case 1: //积分周期：一次

                //在dw_credit_log中，当前的用户且兑换积分类型。则返回true。
                if (self::getCreditLogs(0,10,"",array("user_id"=>$user_id,"type_id"=>$type_id))) {
                    return true;
                }
                break;
            case 2://积分周期：每天
                $criteria = new CDbCriteria; 
                $criteria->condition = "addtime >= ".$now_date;
                //在dw_credit_log中查找当前的用户且兑换积分类型。并且插入时间>=当天0点时间戳的数据。此处有bug，addtime只大于当天0点。并没有小于当天24点
                $tmp = self::getCreditLogs(0,10,"",array("user_id"=>$user_id,"type_id"=>$type_id),$criteria);
                
                //如果返回值 > 奖励次数，并且奖励次数不等0。 则为true
                if (count($tmp) >= $award_times && 0 != $award_times) {
                    return true;
                }
                break;
            case 3: //积分周期：每分钟
                $start_time = $now - $interval * 60;
                $criteria = new CDbCriteria; 
                $criteria->condition = "addtime >= ".$start_time;
                $tmp = self::getCreditLogs(0,10,"",array("user_id"=>$user_id,"type_id"=>$type_id),$criteria);
                if (count($tmp) >= $award_times && 0 != $award_times) {
                    return true;
                }
                break;  
            case 4: //积分周期：不限
                $tmp = self::getCreditLogs(0,10,"",array("user_id"=>$user_id,"type_id"=>$type_id));
                if (count($tmp) >= $award_times && 0 != $award_times) {
                    return true;
                }
                break;
            default :
                return false;
                break;
        }
		//开启事务
        Yii::app()->db->beginTransaction();
        //查询当前用户的积分
        $credit = Credit::model()->findBySql("select * from dw_credit where user_id=:user_id for update", array(':user_id'=>$user_id));
        $result = false;

        //如果op == 2为减号  此处是更新缓存的积分 代码用到的。但已被注释了
        if($data['op'] == 2){
            $op = '-';
        } else {
            $op = '+';
        }
        //添加
        if (count($credit) == 0) {
			//解锁
			Yii::app()->db->rollback();
            $_data = array(
                'user_id' => $user_id,
                'value' => $value,
                'op_user' => $op_user
            );
            $result = BaseCrudService::getInstance()->add('Credit', $_data);
        } else {// 更新
            if($data['op'] == 2){
                $used_value = $credit["used_value"] + $value;
                $value = $credit["value"] - $value;  //用户积分 减去 如果 兑换礼品积分为0 ？则 赋值礼品积分类型表中value字段值 ：否则 赋值兑换礼品积分
            } else {
                $used_value = $credit["used_value"];
                $value = $credit["value"] + $value;  //用户积分加上
            }

            //更新用户积分表数据
            $result = BaseCrudService::getInstance()->update("Credit",array(
                "user_id" => $user_id,
                "value"   => $value,    //积分
                "used_value" => $used_value,
                "op_user" => $op_user, //操作者
                "updatetime"=> $now,   //当前时间
                "updateip"  =>FunctionUtil::ip_address(), //最后更新Ip地址
            ),"user_id");
			//更新
			Yii::app()->db->commit();
        }
        //(user_cache)更新缓存的积分
        ////$sql = "update `{user_cache}` set credit = credit {$op} {$value} where user_id='{$user_id}'";
        ////$result = $mysql->db_query($sql);

        $logInfo = array(
            "user_id" => $user_id,
            "value"   => $value,    //积分
            "used_value" => $used_value,
            "op_user" => $op_user, //操作者
            "updatetime"=> $now,   //当前时间
            "updateip"  =>FunctionUtil::ip_address(), //最后更新Ip地址
        );
        if (!$result) {
            Yii::log('update dw_credit fail'.print_r($logInfo, true),'error','creditServiceError');
            return false;
        }

        $data['credit_nid'] = $data['nid'];//2016-12-06 yanxuefa
        unset($data['nid']);    //销毁礼品兑换类型
        $data['type_id'] = $type_id; //礼品兑换类型表中的id
        
        //向creditlog表中添加数据，记录流水
        $data['user_id'] = $user_id;
        $creditLog = BaseCrudService::getInstance()->add("CreditLog",$data);
        if (!$creditLog ) {
            Yii::log('add dw_credit_log fail'.print_r($data, true),'error','creditServiceError');
        }
        
        return true;
    }

    /**
     * 用户积分兑换记录
     * @param  [type] $user_id [description]
     * @param  [type] $page    [description]
     * @param  [type] $limit   [description]
     * @return [type]          [description]
     */
    public function getCreditExchangeLog($user_id,$page,$limit){
        $return = array("code"=>100,"info"=>"网络错误","data"=>array());
        if(empty($user_id)){
            $return['info'] = "user_id is empty";
            return $return;
        }
        try{
            $page = empty($page) ? 1 : (int)$page;
            $limit = empty($limit) ? 10 : (int)$limit;
            $count_sql = "select count(`id`) as num from dw_credit_exchange_log where user_id=:user_id";
            $result = Yii::app()->db->createCommand($count_sql)->bindParam(":user_id",$user_id,PDO::PARAM_STR)->queryRow();
            if($result === false){
                Yii::log("get user credit_exchange_log num error,user_id=".$user_id,'error',__FUNCTION__);
                $return['info'] = "get user credit_exchange_log num error";
                return $return;
            }
            $total = $result['num'];
            if($total > 0){
                $sql = "select id,user_id,good_id,good_name,addtime,dateline,amount,used_credit from dw_credit_exchange_log where user_id=:user_id order by addtime desc limit :offset,:limit";
                $offset = ($page-1)*$limit;
                $command = Yii::app()->db->createCommand($sql);
                $command->bindParam(":user_id",$user_id,PDO::PARAM_STR);
                $command->bindParam(":offset",$offset,PDO::PARAM_INT);
                $command->bindParam(":limit",$limit,PDO::PARAM_INT);
                $result = $command->queryAll();
                if($result === false){
                    Yii::log("get user credit_exchange_log list error,params:".print_r(func_get_args(),true),'error',__FUNCTION__);
                    $return['info'] = "get user credit_exchange_log list error";
                    return $return;
                }
            }else{
                $result = array();
            }
            $return['data']['total'] = $total;
            $return['data']['list'] = $result;
            $return['code'] = 0;
            return $return;
        }catch(Exception $e){
            Yii::log($e->getMessage(),'error',__FUNCTION__);
            $return['info'] = "get user coupon list error";
            return $return;
        }
        
    }
	
}

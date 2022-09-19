<?php
/**
 * 直投冻结资金API
 * @param int    $user_id        用户id
 * @param int    $borrow_id      项目id
 * @param float  $money          投资钱数 
 * @param string $invest_device  投资设备
 * @param string $coupon_ids     所用优惠券ID
 * @param int    $invest_type    投资类型
 * return array
 */
class InvestBorrowPre extends ItzApi{
    public $logcategory = "invest.borrow.pre";//日志类别
    private $pre_list_key = "invest_borrow_pre";//pre redis队列key
    private $single_borrow_hash_pre = "hash_invest_users_";//单个项目HASH KEY

    public function run($user_id,$borrow_id,$money,$invest_device="",$coupon_ids="",$invest_type=0, $request_no=''){
        try {
            $now_time = time();//到达api的时间,即定义为用户投资时间，与项目开卖时间会根据投资类型不同进行判断
            $data = array();//返回的数据

            //基本参数验证是否为不为空且大于0的数字
            if (!$this->isIllegalNumber($user_id) || !$this->isIllegalNumber($borrow_id) || !$this->isIllegalNumber($money)) {
                self::echoLog("RequestData: user_id=$user_id or borrow_id:$borrow_id or money:$money is not number");
                $this->code = '3001';
                return $this;
            }

            //获取项目信息  切换主库获取准确的项目余额以及开卖时间
            Yii::app()->db->switchToMaster();
            $borrow_info = Borrow::model()->findByPk($borrow_id);
            if (empty($borrow_info)) {
                self::echoLog("RequestData: borrow_id=$borrow_id,the borrow's info is empty");
                $this->code = '3002';
                return $this;
            }

            //判断项目状态是否为11, 按理说3-11的状态均不可以，但是暂时不加
            if ($borrow_info['status'] == 11) {
                self::echoLog("the borrow status=11");
                $this->code = '3002';
                return $this;
            }
		
            //判断项目是否过期  
            if ($this->isBorrowExpired($borrow_info, $now_time)) {
                self::echoLog("the borrow is overtime,formal_time:{$borrow_info['formal_time']}, valid_time:{$borrow_info['valid_time']}");
                $this->code = '3012';
                return $this;
            }

            //项目余额判断  
            if (FunctionUtil::float_bigger_equal($borrow_info['account_yes'], $borrow_info['account'], 3)) {//项目已经融满
                self::echoLog("borrow_account_yes:{$borrow_info['account_yes']},borrow_account:{$borrow_info['account']}");
                $this->code = '3009';
                return $this;
            }

            //项目状态是1-开放投资中, 100预发布中, 101预告中允许投资
            if (!in_array($borrow_info['status'], array(1, 100, 101))){
                self::echoLog("the borrow status not in 1,100,101");
                $this->code = '3002';
                return $this;
            }
           
            //本次投资的金额大于项目所剩余额
            $temp_account_yes = $borrow_info['account_yes'] + $money;
            if (FunctionUtil::float_bigger($temp_account_yes, $borrow_info['account'], 3)) {
                self::echoLog("borrow_account_yes:{$borrow_info['account_yes']},borrow_account:{$borrow_info['account']},money:{$money}");
                $this->code = '3010';
                return $this;
            }

            //投资类型只能是普通投资和预约投资
            if (!in_array($invest_type, array(0,1))) { 
                self::echoLog("RequestData: invest_type=$invest_type,the invest_type is illegal");
                $this->code = '3001';
                return $this;
            }

            //不根据项目状态来判断，根据服务器时间来判断
            if ($borrow_info['formal_time'] > $now_time && $invest_type == 0) {//正常投资时候，要在项目开卖时间之后
                self::echoLog("the borrow is not time to sold ,formal_time:{$borrow_info['formal_time']},now_time:{$now_time},invest_type:{$invest_type}");
                $this->code = '3013';
                return $this;
            }

            //预约投资只能在开卖的10分钟之前进行投资
            if ($invest_type == 1 && ($borrow_info['formal_time'] - $now_time) <= 600 && $now_time < $borrow_info['formal_time']) {
                self::echoLog("the appoint invest is overtime,now_time:{$now_time},formal_time:{$borrow_info['formal_time']},invest_type:{$invest_type}");
                $this->code = '3013';
                return $this;
            }

            //如果投资时间在开卖时间之后,将invest_type强置为0
            if ($now_time >= $borrow_info['formal_time']) {
                $invest_type = 0;
            }    

            //投资设备默认可以为空，不为空的话只能取以下几个值，产品定的必须这几个值
            $invest_device = strtolower($invest_device); //强制转化为小写
            if (!empty($invest_device) && !in_array($invest_device, array("android","pc","ios","wap"))) {
                self::echoLog("invest_device:{$invest_device}, it is wrong device type");
                $this->code = '3001';
                return $this;
            } 
           
            //整合Pre需要的信息
            $uuid = $this->generateUUid();//唯一ID
            $process_time = time();//进入redis的时间

            //进入Redis borrow_pre  取号占位置
            $pre_data = array(
                "user_id" => $user_id,
                "borrow_id"=> $borrow_id,
                "money" => $money,
                "coupon_ids" => $coupon_ids,
            	'request_no' => $request_no,
            	'borrow_type' => $borrow_info['type'],
                "uniqid_key" => $uuid,
                "addtime" => $process_time
            );

            //存入单个项目Hash   
            $hash_data = array(
                "user_id" => $user_id,
                "org_money" => $money,
                "borrow_id" => $borrow_id,
                "freeze_status" => 1
            );

            //进入 pre redis 以及单个项目的hash 
            $hash_result = $this->saveRedis($pre_data, $borrow_id, $uuid, $hash_data);      
            if ($hash_result == false) {
                self::echoLog("set pre redis and set hash return false，pre_list_key:{$this->pre_list_key},pre_data:".print_r($pre_data,true).",single_borrow_hash_pre:".$this->single_borrow_hash_pre.$borrow_id.", uuid:{$uuid},hash_data:".print_r($hash_data,true),"email");
            }
            
            /****************************** 冻结资金开始 ************************************************/
            $pre_data["invest_type"] = $invest_type;
            $pre_data["invest_device"] = $invest_device;
            $pre_data["borrow_info"] = $borrow_info;
            $freeze_result = $this->freezeMoney($pre_data);
            if ($freeze_result["code"] == 0) {
                $hash_data = $freeze_result["pre_data"];//pre表存入的数据返回来也会存到hash中
                $hash_data["freeze_status"] = 2;//冻结状态更改为成功
                $hash_data["org_money"] = $money;// 原始用户投资的钱
                $hash_data["pre_id"] = $hash_data["id"];//pre表的id
                unset($hash_data["id"]);
                $this->code = 0;
                //组合返回结果
                $data = array(
                    "user_id"=> $user_id,
                    "borrow_id" => $borrow_id,
                    "pre_id" => $hash_data["pre_id"],
                    "money" => $hash_data["money"],
                    "addtime" => $process_time,
                );
            } else {
                $hash_data["freeze_status"] = 3;//冻结状态更改了失败
                $this->code = $freeze_result["code"];
            }
            /******************************* 冻结资金结束 ************************************************/

            //成功冻结资金后，更新redis内容和状态 redis处理，失败后均重试一次
            $hash_result = $this->saveFreezeStatus($borrow_id, $uuid, $hash_data);
            if ($hash_result == false) {
                self::echoLog("saveFreezeStatus return false，borrow_id:{$borrow_id}, uuid:{$uuid},hash_data:{$hash_data}","email");
            }

        } catch(Exception $e) {
            self::echoLog("InvestBorrowPre Fail:".print_r($e->getMessage(),true), "email");
            $this->code = "3001";
            return $this;
        }

        $this->data = $data;
        return $this;
    }
    
    /**
     * 判断参数是否是合法数字
     */
    public function isIllegalNumber($param) {
        if (empty($param) || !is_numeric($param) || FunctionUtil::float_bigger_equal(0, $param, 3)) {
            return false;
        }
        return true;
    }

    /**
     * 生成uuid
     */
    public function generateUUid() {
        $uuidWrapper = new UuidWrapper();
        $uuid = $uuidWrapper->getHex(); 
        return "invest_".$uuid;
    }
    
    /**
     * 冻结资金
     */
    private function freezeMoney($params) {
        if (empty($params)) {
            return false; 
        }
        $return_result = array(
            "code"=> 0,//用于前端显示不一样的错误信息
        );
        Yii::app()->db->beginTransaction();
        try {
            //查看用户账户
            $account_info = Account::model()->findBySql('select * from dw_account where user_id=:user_id for update',array(':user_id'=>$params["user_id"]));
            if (empty($account_info)) {
                $return_result["code"] = '3001';
                Yii::app()->db->rollback();
                self::echoLog("the user 's account is not exist,user_id:".$params["user_id"]);
                return $return_result;
            }

            //处理优惠券
            $coupon_detail = array();
            $coupon_value = 0.00;
            $coupon_type = 0;
            $account_virtual_money = 0.00;
            //零钱计划不能使用优惠券，此外优惠券id不为空的，进行处理优惠券部分
            if (!empty($params["coupon_ids"]) && ($params["borrow_info"]['type'] < 2000 || $params["borrow_info"]['type'] >=3000 ) ) {
                $couponIds = explode(",", $params["coupon_ids"]);
                $useCoupon = CouponService::getInstance()->useTenderCoupons($params["user_id"], $couponIds, $params["money"], $params["borrow_info"]);
                if ($useCoupon['code'] != 0) {
                    Yii::app()->db->rollback();
                    self::echoLog('useCouponError code:'.$useCoupon['code'].' info:'.$useCoupon['info']);
                    $return_result["code"] = '3032';
                    return $return_result;
                }
                $coupon_detail = $useCoupon['data']['coupon_detail'];//优惠券详情
                $coupon_type = $useCoupon['data']['coupon_type'];//优惠券类型
                $coupon_value =  $useCoupon['data']['virtual_money'];//优惠券的面额/优惠券的加息年化 根据type不同决定
                if (in_array($useCoupon['data']['coupon_type'], array(1,2))) { //普通优惠券
                    $account_virtual_money = $useCoupon['data']['virtual_money'];//使用优惠券的金额
                }
            }

            //判断余额
            if (FunctionUtil::float_bigger($params["money"], ($account_info['use_money'] + $account_virtual_money), 3)) {
                $return_result["code"] = '2009';
                Yii::app()->db->rollback();
                self::echoLog("the user 's use_money is not enough");
                return $return_result;
            }

            //直投使用资金成分  资金使用顺序： 优惠券 > 本金 > 充值(未满15天) > 充值(满15天) > 利息
            $real_invest_capital = 0;//真实使用本金的钱
            $real_invest_interest = 0;//真实使用的利息的钱
            $real_invest_recharge = 0;//真实使用的充值的钱
            $real_use_withdraw_free = 0;//真实用的本息和
            $recharge_detail = array();//此次投资使用充值金额的明细

            //先使用优惠券的金额，剩下的是使用真钱的金额
            $real_invest_money = round(($params["money"] - $account_virtual_money), 2);//本次投资的金额减去优惠券的金额，即本次投资需要使用的真钱
            //先使用本金的钱  
            $real_invest_capital = round(min($real_invest_money, $account_info['invested_money']), 2);
            //使用充值的钱
            $real_invest_recharge = round(min($account_info['recharge_amount'], ($real_invest_money - $real_invest_capital)), 2);
            //使用利息的钱       
            $real_invest_interest = round(($real_invest_money - $real_invest_capital - $real_invest_recharge), 2);

            //如果使用了充值的钱，需要根据充值详情凑钱
            if (FunctionUtil::float_bigger($real_invest_recharge, 0, 3)) {
                //获取此次使用充值钱的详情明细
                $recharge_detail_info = AccountService::getInstance()->getRechargeDetail($params["user_id"], $real_invest_recharge);
                //得到的结果不能为false或是空
                if ($recharge_detail_info == false || empty($recharge_detail_info['recharge_detail'])) {
                    $return_result["code"] = '3001';
                    self::echoLog("getRechargeDetail return false or getRechargeDetail is empty,user_id:".$params["user_id"].',real_invest_recharge:'.$real_invest_recharge);                                               
                    Yii::app()->db->rollback();
                    return $return_result;
                }           
                //批处理充值详情入表
                $detail_sqls = ""; 
                foreach ($recharge_detail_info['recharge_detail'] as $detail) {
                    $recharge_detail[$detail['id']] = (string)round($detail['money'], 2);//收集使用了每笔充值金额的钱
                    $tmp = array();
                    $tmp['id'] = $detail['id'];
                    $tmp['use_recharge_money'] = $detail['use_recharge_money'] - $detail['money'];
                    $tmp['no_use_recharge_money'] = $detail['no_use_recharge_money'] + $detail['money'];
                    $detail_sqls .= "('".implode("','", $tmp)."'),";//拼凑sql的value值
                }
                $detail_sqls = rtrim($detail_sqls , ",");
                if (!empty($detail_sqls)) {
                    $detail_sql = "INSERT INTO dw_account_recharge_detail (id, use_recharge_money, no_use_recharge_money) VALUES $detail_sqls ON DUPLICATE KEY".
                    " UPDATE  use_recharge_money=VALUES(use_recharge_money), no_use_recharge_money=VALUES(no_use_recharge_money)";
                    $command = Yii::app()->db->createCommand($detail_sql)->execute();
                    self::echoLog("dw_account_recharge_detail SQl:".$detail_sql, "info");
                    if (false == $command) {
                        $return_result["code"] = '3001';
                        Yii::app()->db->rollback();
                        return $return_result;
                    }
                }
            }

            //拼接money_detail
            $real_use_withdraw_free = $real_invest_capital + $real_invest_interest; //此次一共使用的本息和
            $tmp_tender_money_detail = array(
                'money_total' => (string)round($params["money"], 2),//此次投资的金额
                'money_real'  => (string)round($real_invest_money, 2),//使用真钱的金额
                'money_real_recharge' => (string)round($real_invest_recharge, 2),//真钱中使用充值的成分
                'money_invested'=> (string)round($real_invest_capital, 2),//真钱中使用本金的成分
                'recharge_detail'=> $recharge_detail  //充值的钱的成分 
            );
            $smoney_detail = serialize($tmp_tender_money_detail);
           
            //存进pre表
            $dwborrowpre = new DwBorrowPre();
            $dwborrowpre->borrow_id = $params["borrow_id"];
            $dwborrowpre->user_id = $params["user_id"];
            $dwborrowpre->borrow_type = $params["borrow_info"]['type'];
            $dwborrowpre->money = $real_invest_money;//本次打算投资的钱
            $dwborrowpre->virtualmoney = $account_virtual_money;//奖励资金 
            $dwborrowpre->new_cps_cookie = ChannelService::getInstance()->getUnionList(true);//新市场推广系统cookie字段
            $dwborrowpre->money_detail = $smoney_detail;//资金详情
            $dwborrowpre->coupon_value = $coupon_value;//优惠券值（抵现券是面额，加息券是加息利率）
            $dwborrowpre->coupon_type = $coupon_type;//使用优惠券类型
            $dwborrowpre->coupon_detail = serialize($coupon_detail);//优惠券详情
            $dwborrowpre->invest_device = $params["invest_device"];//投资设备
            $dwborrowpre->invest_type = $params["invest_type"];//投资类型 0正常 1预约
            $dwborrowpre->request_no = $params["request_no"];//使用优惠券类型
            $dwborrowpre->addtime = $params["addtime"];
            $dwborrowpre->addip   = FunctionUtil::ip_address(); //获取IP地址;
            $pre_result = $dwborrowpre->save();
            if (false == $pre_result) {
                $return_result["code"] = '3001';
                self::echoLog('DwBorrowPre save error '.print_r($dwborrowpre->getErrors(), true));
                Yii::app()->db->rollback();
                return $return_result;
            }
            $pre_id = $dwborrowpre->id;//获得刚插入的id

            $log = array();
            $log['related_id'] = $pre_id;//关联ID
            $log['related_type'] = 'pre';//关联的表
            $log['borrow_id'] = $params["borrow_id"];//项目ID
            $log['borrow_type'] = $params["borrow_info"]['type'];//项目类型
            $log['log_type'] = 'invest_frost';//新流水类型
            $log['user_id'] = $params["user_id"];//用户ID
            $log['type'] = $this->getBorrowTypeName($params["borrow_info"]['type']). "_frost";//老流水类型
            $log['direction'] = 0;//资金方向 0-冻结 1-加 2-减  
            $log['transid'] = "pre_".$pre_id;//流水号
            $log['money'] = $real_invest_money;//本次交易金额
            $log['total'] = $account_info['total'];//账户余额
            $log['use_money'] =  $account_info['use_money'] - $log['money'];//可用余额减少
            $log['no_use_money'] =  $account_info['no_use_money'] + $log['money'];//冻结金额加，不包括虚拟账户金额
            $log['collection'] =  $account_info['collection'];//代收本息和
            $log['withdraw_free'] = $account_info['withdraw_free'] - $real_use_withdraw_free;//本息资金成分
            $log['recharge_amount'] = $account_info['recharge_amount'] - $real_invest_recharge;//充值资金成分
            $log['invested_money'] = $account_info['invested_money'] - $real_invest_capital;//本金资金成分
            $log['virtual_money'] = $account_virtual_money;
            $log['no_use_virtual_money'] = $account_info['no_use_virtual_money'] + $account_virtual_money;//虚拟账户冻结加
            $log['to_user'] = 0;
            $log['remark'] = "投资项目{$params["borrow_id"]}冻结款";
            Yii::log('tender frozen addlog'.print_r($log, true),'info', $this->logcategory);

            //资金成分如果减为负数，报警
            if (FunctionUtil::float_bigger(0, $log['withdraw_free'], 3) 
                || FunctionUtil::float_bigger(0, $log['recharge_amount'], 3)
                || FunctionUtil::float_bigger(0, $log['invested_money'], 3)) {
                self::echoLog('the user account is wrong ,error_data'.print_r($log, true),"email");
            }
            $addlogret = AccountService::getInstance()->addLog($log);
            if (false == $addlogret) {
                $return_result["code"] = '3001';
                self::echoLog('AccountService addLog error');
                Yii::app()->db->rollback();
                return $return_result;
            }
            
            Yii::app()->db->commit();
            $return_result["pre_data"] = $dwborrowpre->getAttributes();
            unset($return_result["pre_data"]["new_cps_cookie"]);
            return $return_result;
        } catch(Exception $e) {
            $return_result["code"] = '3001';
            self::echoLog("InvestBorrowPre Fail:".print_r($e->getMessage(),true));
            Yii::app()->db->rollback();
            return $return_result;
        }
    }

   /**
    * 记录日志
    */
    private function echoLog($yiilog, $level='error') {
        $server_ip = empty($_SERVER["SERVER_ADDR"])?"":$_SERVER["SERVER_ADDR"];
        $yiilog = 'SERVER_IP:'.$server_ip.",".date('Y-m-d H:i:s').','.$yiilog."\n";
        if ($level == 'email') {
            $level = "error";
            $title = '投资冻结资金报警:';
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
    private function saveRedis ($pre_data, $borrow_id, $uuid, $hash_data) {
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
    * 判断项目是否过期
    */
    private function isBorrowExpired($borrow_info, $now_time) {
        if ($borrow_info['formal_time'] + $borrow_info['valid_time'] * 86400 < $now_time) {
            return true;
        }
        return false; 
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
    private function saveFreezeStatus($borrow_id, $uuid, $hash_data) {
        $hash_result = $this->updateFreezeStatus($borrow_id, $uuid, $hash_data);
        if ($hash_result == false) {
            $hash_result = $this->updateFreezeStatus($borrow_id, $uuid, $hash_data);
            if ($hash_result == false) {
                return false;
            }
        }
        return true; 
    }
    
   /**
    * 获取项目类型的名称
    */
    private function getBorrowTypeName($borrow_type) {
        $borrow_type_name = 'invest';
        if ($borrow_type == '5') {
            $borrow_type_name = 'lease';
        } elseif ($borrow_type == '6'){
            $borrow_type_name = 'factoring';
        }elseif($borrow_type == '7'){
            $borrow_type_name = 'art';
        }elseif($borrow_type >= '100' && $borrow_type < '1000'){
            $borrow_type_name = 'shengxin';
        }elseif($borrow_type >= '2000' && $borrow_type < '3000'){
            $borrow_type_name = 'lingqian';
        }elseif($borrow_type == '3000'){
            $borrow_type_name = 'zhixuan';
        }elseif($borrow_type == '3200'){
            $borrow_type_name = 'ygzhixuan';
        }
        return  $borrow_type_name;
    }
}

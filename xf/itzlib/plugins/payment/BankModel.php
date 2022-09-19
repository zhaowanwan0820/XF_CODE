<?php
class BankModel{

    public function getSafeCard( $user_id, $card_number ){

        #获取需要解绑的其他银行卡
        $ItzSafeCard = new ItzSafeCard;
        $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = "`user_id`='".$user_id."' and `status` in (1,2,9)";
        if($card_number)
        {
            $CDbCriteria->condition .= " and card_number ='".$card_number."'";
        }
        return $ItzSafeCard->findAllByAttributes(array(),$CDbCriteria);
    }

    public function getSafeCardExt($safe_card_ids){
        $ItzSafeCardExpresspayment = new ItzSafeCardExpresspayment;
        $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = "`safe_card_id` in (".implode(',',$safe_card_ids).") and state in (0,2)";
        return $ItzSafeCardExpresspayment->findAllByAttributes(array(),$CDbCriteria);
    }

    #获得支付通道
    public function getPaymentNid($id){

        $sql='
            select nid
            from dw_payment
            inner join itz_expresspayment on dw_payment.id = itz_expresspayment.payment_id
            where itz_expresspayment.id=:id
            ';
        $r=Payment::model()->findBySql($sql,array(':id'=>$id));

        $nid = '';

        if(!empty($r->nid))
        {
            $nid = $r->nid;
        }
        return $nid;
    }

    #检测银行卡状态
    public function checkCardStatus( $card,$is_new ){
        $cdb=new CDbCriteria;
        if($is_new)
        {
            $cdb->condition='status=2 and card_number=:card_number';
        }
        else
        {
            $cdb->condition='status in (1,2) and card_number=:card_number';
        }
        $cdb->params=array(':card_number'=>$card);
        return ItzSafeCard::model()->find($cdb);
    }

    #选择通道
    public function selectPayment($param){

        $nids = array();

        #自定义通道
        if(!empty($param['nid']))
        {
            $sql='
                select nid
                from itz_expresspayment 
                left join  dw_payment on itz_expresspayment.payment_id=dw_payment.id
                where dw_payment.nid="'.$param['nid'].'" and itz_expresspayment.state=1 and itz_expresspayment.bindlevel>-1
            ';
            $r=ItzExpresspayment::model()->findBySql($sql);
            if($r)
            {
                $nids[] = $param['nid'];
            }
        }
        else
        {
            #express id
            $ids = array();

            #根据银行查询可用支持的通道
            $cdb=new CDbCriteria;
            $cdb->condition='`status`=1 and bank_id='.$param['bank_id'];
            $bank_limit =ItzExpresspaymentBanklimit::model()->findAll($cdb);
            if($bank_limit)
            {
                foreach($bank_limit as $v)
                {
                    $ids[] = $v['expresspayment_id'];
                }
            }
            if($ids)
            {
                #查出可用的通道
                $cdb=new CDbCriteria;
                $cdb->condition='bindlevel > -1 and `state`=1 and rechargelevel > -1 and id in ('.implode(',',$ids).')';
                $cdb->order='bindlevel';
                $expresspayment=ItzExpresspayment::model()->findAll($cdb);
                if($expresspayment)
                {
                    foreach($expresspayment as $v)
                    {
                        $nids[] = $this->getPaymentNid($v->id);
                    }
                }
            }
        }
        return $nids;
    }

    #百分比分配
    public function randPayment($payment_id,$payment_ids){
        
        #支持连连的时候 才会考虑连连
        if(in_array(1,$payment_ids))
        {
            if(in_array($payment_id,array(3,4)))
            {
                if(rand(1,100)<71)
                {
                    $payment_id = 1;
                }
            }
        }
        return $payment_id;
    }

    #获取银行ID
    public function getBankId($param){

        #银行ID
        $bank_id = 0;
        //通过银行编码
        if(isset($param['bank_code']) && !empty($param['bank_code'])){
            //银行编码配置
            $bank_code = Yii::app()->c->linkconfig['bank_code'];
            $bank = array_search($param['bank_code'],$bank_code);
            if($bank != false){
                $bank_id = $bank;
                return $bank_id;
            } 
        }
        $factory=new ExpressPaymentFactory;

        #使用连连查询银行卡信息
        $lianlian=$factory->getPayment('lianlianpay');
        $lianlian_result=$lianlian->bankCardQuery($param['card'],$param['phone'],$param['user_id']);
        if($lianlian_result->ret_code == '0000')
        {
            if(!empty($lianlian_result->bank_name))
            {
                #根据银行卡名称去数据库获取ID
                $bank = BaseCrudService::getInstance()->get("ItzBank", "", 0, 1, "" ,array("lianlian_bank_name"=>$lianlian_result->bank_name));
                if(!empty($bank[0]['bank_id']))
                {
                    $bank_id = $bank[0]['bank_id'];
                }
            }
            Yii::log('APP>>>BankModel>getBankId>ok'.json_encode($lianlian_result),'info');
        }
        else
        {
            Yii::log('APP>>>BankModel>getBankId>lianlian_ret_code!=000:::'.json_encode($lianlian_result),'error');
        }

        #连连获取到 直接返回
        if($bank_id!=0)
        {
            return $bank_id;
        }

        #使用易宝查询银行卡信息
        $yeepay=$factory->getPayment('yeepay');
        $card_info=$yeepay->bankcardCheck($param['card']);
        if(!empty($card_info['bankname']))
        {
            $bank = BaseCrudService::getInstance()->get("ItzBank", "", 0, 1, "" ,array("yeepay_bank_name"=>$card_info['bankname']));
            if(!empty($bank[0]['bank_id']))
            {
                $bank_id = $bank[0]['bank_id'];
            }
            Yii::log('APP>>>BankModel>getBankId>ok'.json_encode($card_info['bankname']),'info');
        }
        else
        {
            Yii::log('APP>>>BankModel>getBankId>yeepay_bankname_empty:::'.json_encode($card_info),'error');
        }

        return $bank_id;
    }

    #添加充值流水
    public function addAccountRecharge($data){
        $accountRecharge=new AccountRecharge();
        foreach($data as $key=>$v)
        {
            $accountRecharge->$key  = $v;
        }
        $accountRecharge->status    = 0;

        if($accountRecharge->save())
        {
            return true;
        }
        else
        {
            Yii::log('APP>>>insert dw_account_recharge :save-data-'.json_encode($data).':getErrors-'.json_encode($accountRecharge->getErrors()),'error');
            return false;
        }
    }

    #更新充值流水
    public function updateAccountRecharge($trade_no,$data){
        if(empty($trade_no))
        {
            return false;
        }

        #设置当前设备状态为登录
        $r = AccountRecharge::model()->updateAll(
            $data,
            "`trade_no`='".$trade_no."'"
        );

        if($r)
        {
            return true;
        }
        else
        {
            Yii::log('APP>>>update dw_account_recharge :save-data-'.json_encode($data),'error');
            return false;
        }
    }

    #获得银行的id name
    public function getBankList(){
        $result = array();

        $banks = BaseCrudService::getInstance()->get("ItzBank", "", 0, 'ALL');
        if($banks)
        {
            foreach($banks as $v)
            {
                $result[$v['bank_id']] = $v['bank_name'];
            }
        }

        return $result;
    }

    #获得充值信息
    public function getRechargeSum($payment,$cardInfo)
    {
        #结果
        $result = array(
            'day'   =>0,
            'month' =>0
            );

        #获得今日充值
        $sql = "
            select sum(money) as money
            from dw_account_recharge
            where status=1 and user_id=".$cardInfo->user_id." and bank_id='".$cardInfo->bank_id."' and payment='".$payment."' and `card_number`='".$cardInfo->card_number."' and `addtime`>=".strtotime(date('Y-m-d'))."
            group by user_id
        ";
        $list = Yii::app()->db->createCommand($sql)->queryAll();
        if(!empty($list[0]['money']))
        {
            $result['day'] = $list[0]['money'];
        }

        #获得今月充值
        $sql = "
            select sum(money) as money
            from dw_account_recharge
            where status=1 and user_id=".$cardInfo->user_id." and bank_id='".$cardInfo->bank_id."' and payment='".$payment."' and `card_number`='".$cardInfo->card_number."' and `addtime`>=".strtotime(date('Y-m'))."
            group by user_id
        ";
        $list = Yii::app()->db->createCommand($sql)->queryAll();
        if(!empty($list[0]['money']))
        {
            $result['month'] = $list[0]['money'];
        }

        return $result;
    }

    #设置临时快捷卡
    public function setSafeCard($uid,$card_number,$phone_number,$device)
    {
        #查询银行是否存在
        $safeCard=ItzSafeCard::model()->findByAttributes(array('card_number'=>$card_number,'user_id'=>$uid));

        #获得银行id
        $bank_id = $this->getBankId(array(
            'card'      =>$card_number,
            'phone'     =>$phone_number,
            'user_id'   =>$uid
            ));
        if($bank_id==0)
        {
            return false;
        }

        if(empty($safeCard)){
            $safeCard=new ItzSafeCard;
            $safeCard->user_id      =$uid;
            $safeCard->type         ='11';
            $safeCard->card_number  = $card_number;
            $safeCard->bank_id      = $bank_id;//银行卡号暂时写空
            $safeCard->status       = 0;//未确认
            $safeCard->phone        = $phone_number;
            $safeCard->device       = $device;
            $safeCard->addtime      = time();
            $safeCard->modtime      = time();
            $dbr1=$safeCard->save();
        }
        else
        {
            $safeCard->phone        = $phone_number;
            $dbr1=$safeCard->save();
        }
        return $dbr1;
    }

    #返回给app本次支持的最大金额
    public function getRechargeLimit($uid,$device=0,$card_number=''){
        
        $money = 0;

        #获取快捷卡信息
        $cardInfo=ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));
        if(empty($cardInfo))
        {
            if($card_number)
            {
                $cardInfo = ItzSafeCard::model()->findByAttributes(array('card_number'=>$card_number,'user_id'=>$uid));
            }
        }
        if(empty($cardInfo))
        {
            return $money;
        }

        #获得可用通道（没有超过单笔限额）
        $r = $this->getPays($cardInfo->bank_id,$device);
        if(empty($r))
        {
            return $money;
        }
        //$pays = $this->checkPays($uid);
        //$kq_pid = $this->getKqPid();
        $moneys = array();
        foreach($r as $v){
        	/* if ($pays == 1) {
        		if( $v['type_id'] == 0 && $v['payment_id'] == $kq_pid ){
        			continue;
        		}
        	}else{
        		if( $v['type_id'] == 1 && $v['payment_id'] == $kq_pid ){
        			continue;
        		}
        	} */
        	
            #获得充值记录总金额
            $sum_money = $this->getRechargeSum($v['payment_id'],$cardInfo);
			
            #计算可充金额（单笔限额，（单日限额-今天充值金额），（单月限额-本月充值金额））
            $moneys[] = min($v['every_limit'],($v['daily_limit']-$sum_money['day']),($v['monthly_limit']-$sum_money['month']));
        }
        $money = round(max($moneys),2);
        if($money<0)
        {
            $money = 0;
        }
        
        return $money;
    }
    

    #返回给app本次支持的最大金额
    public function getRechargeLimitError($uid,$device=0,$card_number=''){
        
        #获取快捷卡信息
        $cardInfo=ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));
        if(empty($cardInfo)){
            if($card_number){
                $cardInfo = ItzSafeCard::model()->findByAttributes(array('card_number'=>$card_number,'user_id'=>$uid));
            }
        }
        if(empty($cardInfo)){
            return null;
        }

        #获得可用通道（没有超过单笔限额）
        $r = $this->getPays($cardInfo->bank_id,$device);
        if(empty($r)){
        	$now_time = time();
        	//查询维护时间
        	$bankInfo=ItzExpresspaymentBanklimit::model()->findBySql(
        			"select stop_begin_time,stop_end_time from itz_expresspayment_banklimit where 
        			bank_id={$cardInfo->bank_id} and status=0 and stop_begin_time>0 and stop_end_time>0 and stop_begin_time<{$now_time} and stop_end_time>{$now_time} limit 1"
            );
        	if(!empty($bankInfo)){
        		$stop_begin_time = $bankInfo->stop_begin_time;
        		$stop_end_time = $bankInfo->stop_end_time;
        		if(date('Y',$stop_begin_time) == date('Y',$stop_end_time)){  //判断是否同年
        			if(date('Ym',$stop_begin_time) == date('Ym',$stop_end_time)){//判断是否同月
        				if(date('Ymd',$stop_begin_time) == date('Ymd',$stop_end_time)){//判断是否同日
        					$error_msg = "该银行在".date('H:i',$stop_begin_time)."-".date('H:i',$stop_end_time)."维护，请稍后再试，或者使用网银充值";
        				}else{
        					$error_msg = "该银行在".date('d号H:i',$stop_begin_time)."-".date('d号H:i',$stop_end_time)."维护，请稍后再试，或者使用网银充值";
        				}
        			}else{
        				$error_msg = "该银行在".date('m月d号H:i',$stop_begin_time)."-".date('m月d号H:i',$stop_end_time)."维护，请稍后再试，或者使用网银充值";
        			}
        		}else{
        			$error_msg = "该银行在".date('Y年m月d号H:i',$stop_begin_time)."-".date('Y年m月d号H:i',$stop_end_time)."维护，请稍后再试，或者使用网银充值";
        		}
        		return array(
        			'code'=>1,
        			'info'=>$error_msg
        		);
        	}
        	
            return 1;#该银行维护中，请使用PC网银充值
        }

        #月限额
        $monthly_limit  = false;

        #日限额
        $daily_limit    = false;

        $moneys = array();
        foreach($r as $v){
            #获得充值记录总金额
            $sum_money = $this->getRechargeSum($v['payment_id'],$cardInfo);

            #月限额
            if(($v['monthly_limit']-$sum_money['month'])>0){
                $monthly_limit = true;
            }

            #日限额
            if(($v['daily_limit']-$sum_money['day'])>0){
                $daily_limit = true;
            }
        }

        if($monthly_limit==false){
            return 2;
        }

        if($daily_limit==false){
            return 3;
        }
    }
    /**
     * 快钱一键支付和消费鉴权判断
     */
	public function checkPays($uid,$nid='kuaiqianpay'){
		$uid = intval($uid);
		if (empty($uid)) {
			return 0;
		}
		$sql = " select safe_card_id,expresspayment_id from itz_safe_card_expresspayment where user_id={$uid} and state=2 and nid='{$nid}'";
		$isBindCard = Yii::app()->db->createCommand($sql)->queryRow();
		$savePciFlag = 0; 	  // 消费鉴权
		if($isBindCard){
			$savePciFlag = 1; //一键支付
		}
		return $savePciFlag;
	}
	public function getKqPid(){
		$pid = 0;
		$nid = 'kuaiqianpay';
		$sql = " select id from dw_payment where nid='{$nid}'";
		$info = Yii::app()->db->createCommand($sql)->queryRow();
		if($info){
			$pid = $info['id'];
		}
		return $pid;
	}
    #获得可用充值渠道列表
    public function getPays($bank_id=0,$device=0){

        #PC 支付 去除 连连
        $ext_where = '';
        if($device==6)
        {
            $ext_where = " and e.payment_id!='45'";
        }

        $sql='select e.id,e.payment_id,l.type_id,l.every_limit,l.daily_limit,l.monthly_limit from itz_expresspayment_banklimit as l
              inner join itz_expresspayment as e on l.expresspayment_id = e.id and e.rechargelevel > -1 and e.state=1
              where  l.status = 1 and l.bank_id='.$bank_id.$ext_where;
        return Yii::app()->db->createCommand($sql)->queryAll();
    }


	/**
	 * app 新的提示
	 * @param $uid
	 * @param int $device
	 * @param string $card_number
	 * @return array
	 */
	public function getRechargeLimitError2($uid,$device=0,$card_number='',$money=0){
		$returnData = [
			'type'=>4,
		];
		#获取快捷卡信息
		$cardInfo=ItzSafeCard::model()->findByAttributes(array('user_id'=>$uid,'status'=>2));
		if(empty($cardInfo)){
			if($card_number){
				$cardInfo = ItzSafeCard::model()->findByAttributes(array('card_number'=>$card_number,'user_id'=>$uid));
			}
		}
		if(empty($cardInfo)){
			$returnData['type'] = 0;
			return $returnData;
		}

		#获得可用通道（没有超过单笔限额）
		$r = $this->getPays($cardInfo->bank_id,$device);
		if(empty($r))
		{
			return $returnData;
		}
		#月限额
		$monthly_limit  = false;

		#日限额
		$daily_limit    = false;

		#单笔限额
		$every_limit    = false;

		$moneys = array();
		foreach($r as $v){
			#获得充值记录总金额
			$sum_money = $this->getRechargeSum($v['payment_id'],$cardInfo);

			#月限额
			if(($v['monthly_limit']-$sum_money['month']) < $money){
				$monthly_limit = true;
				$monthly_limit_money = $v['monthly_limit'];
			}

			#日限额
			if(($v['daily_limit']-$sum_money['day'])< $money){
				$daily_limit = true;
				$daily_limit_money = $v['daily_limit'];
			}

			#单笔限额
			if($v['every_limit']<$money){
				$every_limit = true;
				$every_limit_money = $v['every_limit'];
			}

		}

		if($every_limit==true){
			$returnData['type'] = 4;
			$returnData['limit'] = $every_limit_money;
			return $returnData;
		}
		if($daily_limit==true){
			$returnData['type'] = 3;
			$returnData['limit'] = $daily_limit_money;
			return $returnData;
		}
		if($monthly_limit==true){
			$returnData['type'] = 2;
			$returnData['limit'] = $monthly_limit_money;
			return $returnData;
		}
		
		return $returnData;

	}

}

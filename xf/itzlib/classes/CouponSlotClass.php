<?php
class CouponSlotClass {
    private static $arrInstance;
    /**
     * 支持多个对象的单例 
     */
    static public function getInstance(){ 
        $className = get_called_class();
        if(!isset(self::$arrInstance[$className])){
            self::$arrInstance[$className] = new $className();
        }
        return self::$arrInstance[$className];
    }
    
    /**
     * 触发优惠券发放
     * @param type $code
     */
    public function couponSlot($uid, $code, $affix=array()){
        return false; //停止发放优惠券
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
        $reurnResult = true;
        $uid = intval($uid);
        if(empty($uid)){
        	Yii::log('couponSlot UserId Empty,'.'@code:'.$code,'error');
        	return false;
        }
        $couponSlot = ItzCouponSlot::model()->findByAttributes(array('code'=>$code));
        if($couponSlot->status == 0 || empty($couponSlot->coupon_ids)){
            Yii::log('couponSlot '.$uid.'@'.$code.' ItzCouponSlot '.$couponSlot->name.' status eq 0','error');
            return false;
        }

        $couponTypes = ItzCouponType::model()->findAllByPk($couponSlot->coupon_ids,'status = 1');

        #发放总金额
        $total_money = 0;
        foreach($couponTypes as $couponType){
            if(!empty($affix)){
                foreach($affix as $name=>$value){
                    if(!empty($value)) $couponType->$name=$value;
                }
            }
            $begin_time = empty($couponType->begin_times)?time():$couponType->begin_times;
            if($couponType->expire_type == 0){
                if(empty($couponType->expire_times)){
                    Yii::log('couponSlot '.$uid.'@'.$code.' ItzCouponType '.$couponType->name.' expire_times empty ','error'); continue;
                }else{
                    $expire_time = $couponType->expire_times;
                }
            }else{
                $expire_time = $this->calExpireTime($couponType->begin_times, $couponType->expire_type, $couponType->expire_times);
            }

            if($expire_time <= time()){
                Yii::log('couponSlot '.$uid.'@'.$code.' ItzCouponType '.$couponType->name.' expire_time < time expire_time:'.$expire_time,'error'); continue;
            }
            if($couponType->amount == 0){
                Yii::log('couponSlot '.$uid.'@'.$code.' ItzCouponType '.$couponType->name.' amount eq 0 ','error'); continue;
            }
            if(empty($couponType->remark)){
            	//加息和加息体验券
            	if($couponType->type==3 || $couponType->type==4){
                	$couponType->remark = $couponType->name;
            	}else{
            		$couponType->remark = $couponType->name.'（'.floor($couponType->amount).'）元';
            	}
            }
            
            $couponData = array(
                'user_id'       => $uid,
                'sn'            => $this->generateSN($uid,$couponSlot->code,$couponType->id),
                'status'        => 0,
                'src'           => $couponSlot->code,
                'type'          => $couponType->type,
                'amount'        => $couponType->amount,
                'least_invest_amount' => $couponType->invest_amount,
                'begin_time'    => $begin_time,
                'expire_time'   => $expire_time,
                'remark'        => $couponType->remark,
                'borrow_type'   => $couponType->borrow_type,
                'borrow_id'     => $couponType->borrow_id,
            	'experience_days' => intval($couponType->experience_days),
            	'interest_max_money' => intval($couponType->interest_max_money),
            	'expire_notice_sms' => intval($couponType->expire_notice_sms),
                'addtime'       => time(),
            );
            $result = $this->addCoupon($couponData);
            if($result == false){
                Yii::log('addCoupon error '. print_r($couponData,true),'error'); continue;
            }
            $total_money += $couponData['amount'];
            /* 2016-03-02号注释coupon_log的insert[coupon_log表弃用]
            $logData = array(
                'user_id'        => $couponData['user_id'],
                'transid'        => $couponData['src'] . '_' . $couponData['user_id'] . '_' . $result['id'],
                'type'           => $couponData['src'],
                'direction'         => 1,
                'from_user'      => 270,
                'to_user'        => $couponData['user_id'],
                'amount'         => $couponData['amount'],
                'addtime'        => time(),
            );
            $this->addCouponLog($logData);
            */
        }
		//阳光智选新手限时加息券
		$sunShineNoviceAddApr = Yii::app()->c->linkconfig['sunShineNoviceAddApr'];
        if($couponSlot->code == $sunShineNoviceAddApr['couponCode']){
			return $result;
		}
        
		$remind = array();
		$remind['sent_user'] = 0;
		$remind['receive_user'] = $uid;
			
        //投资奖励提醒设置
        if($couponSlot->code == 'FST' || $couponSlot->code == 'LST' || $couponSlot->code == 'MST'){
			$messageModel = new Message;
            $messageModel->sent_user = 0;
            $messageModel->receive_user = $uid;
            $messageModel->name = '恭喜您抢得'.$couponSlot->name.'奖项！';
            $messageModel->status = 0;
            $messageModel->type = 'reward';
            $messageModel->sented = '';
            $messageModel->deltype = 0;
            $messageModel->content = '爱亲您好:恭喜您抢得'.$couponSlot->name.'的项目奖励,获得面值'.$total_money.'元的优惠券！请您及时登录ITZ账户查看。';
            $messageModel->addtime = time();
            $messageModel->addip = Yii::app()->request->userHostAddress;
            if(!$messageModel->save(false)){
                Yii::log('addMessage error '. print_r($messageModel->getErrors(),true),'error');
            }
        }
        if($couponSlot->code == 'BIR'){
			 $userModel=User::model()->findByPk($uid);
			 $remind['nid'] = "birthday";
             if($userModel->real_status==1)
				 $remind['data']['realname'] = $userModel->realname;
			 else
				 $remind['data']['realname'] = $userModel->username;
             $remind['type'] = "birthday";
             $remind['mtype'] = "Birthday";
             $remind['status'] = 0;
             $addMessageResult = NewRemindService::getInstance()->SendToUser($remind,false,false,true);
             if($addMessageResult == false){
                 Yii::log("addsms error","error");
             }
             /*$sender=new SmsClass();
             if(!$sender->sendToUser($uid,$userModel->phone,'爱亲'.$userModel->realname.'您好，今天是您的生日！ITZ祝您生日快乐！身体健康！送您红包一枚，祝您幸福每一天！')){
                Yii::log('addsms error','error');
             }*/
        }
        
        //
        //注册奖励提醒设置 $couponSlot->code == 'REG' || 20150527 版本7339去掉
        if(  $couponSlot->code == 'SPR'){
			$remind['nid'] = "award";
			$remind['data']['tomoney'] = $total_money;
			$remind['type'] = "award_invest";
			$remind['mtype'] = "awardspr";
			$remind['status'] = 0;
			NewRemindService::SendToUser($remind);
        }

         //注册奖励提醒设置
        if($couponSlot->code == 'DBREGM' ||$couponSlot->code =='SJJYREGM'){
			$remind['nid'] = "award";
			$remind['data']['tomoney'] = $total_money;
			$remind['type'] = "award_invest";
			$remind['mtype'] = "awardprg";
			$remind['status'] = 0;
			NewRemindService::SendToUser($remind);
        }


        //实名 和 手机认证 奖励提醒设置
        if($couponSlot->code == 'AuthRealName' || $couponSlot->code == 'AuthPhone'){

            $msg_name = ($couponSlot->code == 'AuthRealName') ? '实名' : '手机';
			$remind['nid'] = "reward";
			$remind['data']['msgname'] = $msg_name;
			$remind['data']['tomoney'] = $total_money;
			$remind['type'] = "reward";
			$remind['mtype'] = "rewardauth";
			$remind['status'] = 0;
			$result = NewRemindService::SendToUser($remind,true,false,false);
			if($result == false){
				Yii::log('addMessage error ','error');
			}
        }

        // 注册V4.0
        if ($couponSlot->code == 'PRG') {
            $userModel=User::model()->findByPk($uid);
            $remind['phone'] = $userModel->phone;
            $remind['data']['tomoney'] = $total_money;
            $remind['mtype'] = "awardprg";
            NewRemindService::SendToUser($remind, true, false, true);
        }

        return $total_money;
    }

    /**
     * 计算过期时间
     * 1. 如果过期类型为0，表示指定进项时间，直接返回 expireTime
     * 2. 否则根据实际生效时间 fromTime 进行偏移计算。
     * @param $beginTime
     * @param $expireType
     * @param $expireTime
     * @return int
     */
    public function calExpireTime($beginTime, $expireType, $expireTime)
    {
        if ($expireType == 0) {
            return $expireTime;
        } elseif ($expireType == -1) {
            // 新过期时间计算方式, expireTime 为天数
            $fromTime = $beginTime == 0 ? time() : $beginTime;
            if (($expireTime%30)>0) {
                $days = $expireTime;
                $offset = "+{$days} days";
            } else {
                $months = $expireTime / 30;
                $offset = "+{$months} month";
            }
            $result = strtotime($offset . ' -1 days', $fromTime);
            return strtotime("23:59:59", $result);
        } else {
            // 旧过期时间计算方式, expireType 记录的月份
            $fromTime = $beginTime == 0 ? time() : $beginTime;
            $months = $expireType;
            $offset = "+{$months} month";
            $result = strtotime($offset . ' -1 days', $fromTime);
            return strtotime("23:59:59", $result);
        }
    }

    /**
     * 生成优惠券SN
     * @param type $uid
     * @param type $couponSlot
     * @param type $couponType
     * @return type
     */
    protected function generateSN($uid,$couponSlotCode,$couponTypeId){
        $sn = $couponSlotCode;
        $sn .= $couponTypeId;
        $sn .= $uid;
        $sn .= substr(time(),6,4);
        $sn .= rand(100,999);
        return $sn;
    }
    
    
    //公用 insert coupon方法 (*不建议直接使用)
    public function addCoupon($data){
        Yii::log ( __FUNCTION__." ".print_r(func_get_args(),true),'debug');
        $DwCouponModel = new Coupon();
        $DwCouponModel->attributes = $data;
        if($DwCouponModel->save(false)){
            return $DwCouponModel->getAttributes();
        }else{
            Yii::log("DwCouponModel error: ".print_r($DwCouponModel->getErrors(),true),"error");
            return false;
        }
    }
    /* 2016-03-02号注释coupon_log的insert[coupon_log表弃用]
    // couponLog表 insert操作
    public function addCouponLog($data){
        $DwCouponLogModel = new CouponLog();
        $DwCouponLogModel->attributes = $data;
        if($DwCouponLogModel->save(false)){
            return $DwCouponLogModel;
        }else{
            Yii::log("DwCouponLogModel error: ".print_r($DwCouponLogModel->getErrors(),true),"error");
            return false;
        }
    }
    */
}

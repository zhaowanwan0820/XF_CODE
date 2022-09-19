<?php

/**
 * BorrowClass .
 *
 * 优惠券申请,这个类用于把申请生成券
 */

class CouponApplicationClass {

    //主函数,待生成券的申请ID
    public function run($applicationId) {
        //开启事务
        $transaction = Yii::app() -> dwdb -> beginTransaction();
        $model = CouponApplication::model() -> findByPk($applicationId);
        //发送标记。默认为0，当为1的时候，则是代表脚本正在跑这条记录，这条记录正在生成优惠券，那么其他进程不允许再生成
        if ($model -> sendflag == 0) {
            $model -> sendflag=1;
            $model->save();
            //获得申请上的全部用户,放到数组中
            $arr = $model -> _getUserByUserlist();
            //应用解析这些用户的函数
            //$funArray = array('actionReview_form_getUserName', 'actionReview_form_getUserPhone');
            //开始解析
			if($model->usertype==0){
				$funArray = array('actionReview_form_getUserName');
			}else if($model->usertype==1)
			{
				$funArray = array('actionReview_form_getUserPhone');
			}

            $info = $this -> sendTicket($arr, $funArray);
            //计算出结果，并更新申请记录
            $chenggong = count($info['uidArray']);
            $shibai = count($info['errorArray']);
            $nums = $chenggong + $shibai;
            $cuowumingdan = implode("，", $info['errorArray']);
            //开始发券
            if (count($info['uidArray'])>0) {
                foreach ($info['uidArray'] as $key => $value) {
                    $couponModel = new Coupon;
                    $couponModel -> sn = $model -> src . str_pad(rand(0, 999), 3, 0, STR_PAD_LEFT) . ($value + 4) . str_pad(rand(0, 999), 3, 0, STR_PAD_LEFT);
                    if($model->begin_time==0){
                        $couponModel -> status = 1;
                        $couponModel -> begin_time = time();
                    }else{
                        $couponModel -> status = 0;
                        $couponModel -> begin_time = $model -> begin_time;
                    }
                    //发出来就是有效的
                    $couponModel -> src = $model -> src;
                    $couponModel -> type = $model -> coupon_type;
                    $couponModel -> amount = $model -> amount;
                    $couponModel -> least_invest_amount = $model -> least_invest_amount;
                    $couponModel -> expire_time = $model -> expire_time;
                    $couponModel -> borrow_type = $model -> borrow_type;
                    $couponModel -> borrow_id = $model -> borrow_id;
                    $couponModel -> remark = $model -> remark;
                    $couponModel -> addtime = time();
                    $couponModel -> user_id = $value;
                    $rr = $couponModel -> save();
                    if ($rr) {
                        echo "Add Coupon successufuly  uid=".$value;
                        $userModel = User::model() -> findByPK($value);
                        $title = "恭喜您获得优惠券" . $model -> amount . "元";
                        //发送站内信
                        if ($model -> send_message == 1) {
                            $messageSender = new MessageClass();
                            $messageSender -> send($userModel -> user_id, 270, $title, $model -> template);
                        }
                        //发送短信demo
                        if ($model -> send_sms == 1) {
                            $smsSender = new SmsClass();
                            $smsSender -> sendToUser($userModel -> user_id, $userModel -> phone, $model -> template);
                        }
                        //发送邮件demo
                        if ($model -> send_mail == 1) {
                            $mailSender = new MailClass();
                            $mailSender -> sendToUser($userModel -> user_id, $userModel -> email, $title, $model -> template . "<br/>感谢您对爱投资的支持<br/>");
                        }
                    } else {
                        $json=json_encode($couponModel->getErrors());
                        Yii::log("Add Coupon ERROR  uid=".$value."\n  ".$json, 'error');
                        continue;
                    }
                    /* 2016-03-02号注释coupon_log的insert[coupon_log表弃用]
                    $logData = array('user_id' => $couponModel -> user_id, 'transid' => $couponModel -> src . '_' . $couponModel -> user_id . '_' . $couponModel -> id, 'type' => $couponModel -> src, 'direction' => 1, 'from_user' => 270, 'to_user' => $couponModel -> user_id, 'amount' => $couponModel -> amount, 'addtime' => time(), );
                    $CouponLog = new CouponSlotClass();
                    $CouponLog -> addCouponLog($logData);
                    */
                }
            }
            //发送成功
            $model -> send_info = "本次共发送优惠券：" . $nums . "张。<br/>其中成功发送：" . $chenggong . "张。<br/>发送失败;" . $shibai . "张。<br/>错误用户名单：<br/>" . $cuowumingdan;
            $model -> state = 1;
            if ($model -> save()) {
                $mailer = new itzUserClass();
                $mailer -> SendEmailToCustomer('【优惠券申请提醒】' . $model -> application_name, $model -> userInfo -> username . '于' . date('Y-m-d H:i:s', $model -> addtime) . '提交{' . $model -> application_name . '}的优惠券申请已发送，请知晓<br/>发送结果：<br/>' . $model -> send_info);
                Yii::app() -> dwdb -> commit();
            } else {
                Yii::app() -> dwdb -> rollback();
            }
            $model->sendflag=0;
            $model->save();
        }
    }

    /**
     * $userList  一个数组，用来存放用户名
     * $FunArray  一个数组，依次存放处理用户的函数名
     */
    private function sendTicket($userList, $funArray) {
        $info['uidArray'] = array();
        //存放取到的用户id
        $info['errorArray'] = array();
        //错误的用户名
        foreach ($userList as $v) {
            $flag = true;
            foreach ($funArray as $funv) {
                $rs = $this -> $funv($v);
                if (!empty($rs)) {
                    $info['uidArray'][] = $rs;
                    $flag = true;
                    break;
                    //取到就跳出循环
                } else {
                    $flag = false;
                }
            }
            if ($flag == FALSE) {
                $info['errorArray'][] = $v;
            }
        }
        return $info;
    }

    //通过传过来用户名，获取id
    private function actionReview_form_getUserName($value) {
        $attributes = array('username' => $value);
        $userModel_username = User::model() -> findAllByAttributes($attributes);
        if (!empty($userModel_username)) {
            $tmp = $userModel_username[0] -> getAttributes(array('user_id'));
            return $tmp['user_id'];
        } else {
            return null;
        }
    }

    //通过传过来用户手机号，获取id
    private function actionReview_form_getUserPhone($value) {
        $attributes = array('phone' => $value, 'phone_status' => 1);
        $userModel_userphone = User::model() -> findAllByAttributes($attributes);
        if (!empty($userModel_userphone)) {
            $tmp = $userModel_userphone[0] -> getAttributes(array('user_id'));
            return $tmp['user_id'];
        } else {
            return null;
        }
    }

}

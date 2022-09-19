<?php

class ExclusivePurchaseCommand extends CConsoleCommand
{
    public function actionCredentials()
    {
        Yii::log(__CLASS__." credentials run", 'info');

        $sql = "SELECT p.id,p.purchase_user_id,p.order_no,l.batch_no from xf_exclusive_purchase as p left join xf_exclusive_purchase_payment_log as l on p.id = l.exclusive_purchase_id  where p.status > 1 and p.pay_status = 2 and l.status  = 2 and p.credentials_url = '' ";
        $xf_exclusive_purchase     = Yii::app()->phdb->createCommand($sql)->queryAll();
        if ($xf_exclusive_purchase) {
            foreach ($xf_exclusive_purchase as $key => $value) {
                try {
                    $re = ExclusivePurchaseService::getInstance()->download_receipt($value);

                    $sql = " UPDATE xf_exclusive_purchase SET credentials_url= '{$re}' WHERE id = {$value['id']}";
                    $res = Yii::app()->phdb->createCommand($sql)->execute();
                    if ($res === false) {
                        throw new Exception('更新求购表回单地址失败 id:'.$value['id']);
                    }
                } catch (Exception $e) {
                    Yii::log(__CLASS__." credentials error:".$e->getMessage(), 'error');
                    Yii::app()->phdb->rollback();
                }
            }
        } else {
            Yii::log(__CLASS__." credentials no data ", 'info');
        }
    }

    public function actionOverdue()
    {
        Yii::log(__CLASS__." overdue run", 'info');

        $now = time();//过期
        $sql = "SELECT * from xf_exclusive_purchase where status = 0 and end_time < {$now}";
        $xf_exclusive_purchase     = Yii::app()->phdb->createCommand($sql)->queryAll();
  
        if ($xf_exclusive_purchase) {
            foreach ($xf_exclusive_purchase as $key => $value) {
                try {
                    Yii::app()->phdb->beginTransaction();
                    $remark = $value['remark'] . date('Y-m-d H:i:s').' 脚本执行过期 ';
                    $sql = " UPDATE xf_exclusive_purchase SET status = 5,remark = '{$remark}' WHERE id = {$value['id']}";
                    $res = Yii::app()->phdb->createCommand($sql)->execute();
                    if ($res === false) {
                        throw new Exception('更新状态失败 id:'.$value['id']);
                    }
                    $sql = " UPDATE xf_purchase_assignee SET frozen_quota = frozen_quota - {$value['wait_capital']} WHERE user_id = {$value['purchase_user_id']}";
                    $res = Yii::app()->phdb->createCommand($sql)->execute();
                    if ($res === false) {
                        throw new Exception('扣减冻结金额失败 id:'.$value['id']);
                    }
                    //投资记录更新
                    $sql1 = "SELECT * from firstp2p_deal_load where status = 1  and exclusive_purchase_id={$value['id']}  and user_id={$value['user_id']} ";
                    $pd_load = Yii::app()->phdb->createCommand($sql1)->queryRow();
                    if($pd_load){
                        $sql2 = " UPDATE firstp2p_deal_load SET exclusive_purchase_id = 0  WHERE exclusive_purchase_id={$value['id']}  and user_id={$value['user_id']}  and status=1 ";
                        $res = Yii::app()->phdb->createCommand($sql2)->execute();
                        if ($res === false) {
                            throw new Exception('更新普惠投资记录失败 id:'.$value['id']);
                        }
                    }

                    //投资记录更新
                    $sql3 = "SELECT * from offline_deal_load where status = 1  and exclusive_purchase_id={$value['id']}  and user_id={$value['user_id']} ";
                    $zdx_load = Yii::app()->offlinedb->createCommand($sql3)->queryRow();
                    if($zdx_load){
                        $sql4 = " UPDATE offline_deal_load SET exclusive_purchase_id = 0  WHERE exclusive_purchase_id={$value['id']}  and user_id={$value['user_id']}  and status=1 ";
                        $res = Yii::app()->offlinedb->createCommand($sql4)->execute();
                        if ($res === false) {
                            throw new Exception('更新zdx_load投资记录失败 id:'.$value['id']);
                        }
                    }

                    Yii::app()->phdb->commit();
                } catch (Exception $e) {
                    Yii::log(__CLASS__." Overdue error:".$e->getMessage(), 'info');
                    Yii::app()->phdb->rollback();
                }
            }
        } else {
            Yii::log(__CLASS__." overdue no data ", 'info');
        }
    }
     
    public function actionTran()
    {
        $data=[
            'batch_no'=>'13716970622000099',
            'id'=>'13716970622099',
            'purchase_amount'=>0.1,
            'name'=>'刘春华',
            'bank_card'=>'6214830167984383',
            'bankcode'=>'CMBCHINA',

        ];
        $request = new YopRequest(YopConfig::APP_KEY, YopConfig::PRIVATE_KEY);
        $request->addParam("merchantno", YopConfig::MERCHANT_NO);

        //加入请求参数
        $request->addParam("batchNo", $data['batch_no']);//商户生成的唯一请求号
        $request->addParam("orderId", $data['order_no']);//商户生成的唯一订单号
        $request->addParam("amount", $data['purchase_amount']);//金额
        $request->addParam("accountName", $data['name']);//收款帐户的开户名称
        $request->addParam("accountNumber", $data['bank_card']);//收款帐户的卡号
        $request->addParam("bankCode", $data['bankcode']);//银行编码
      

        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/balance/transfer_send", $request);

        if ($response->validSign==1) {
            $re = $this->object_array($response);
            Yii::log(__CLASS__." payment request:".json_encode($data, JSON_UNESCAPED_UNICODE)."  yibao return ".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');
        }
        var_dump($re);
    }

    public function actionQuery()
    {
        $value['batch_no']='13716970622000009';
        $value['order_no']='13716970622009';
        $re = ExclusivePurchaseService::getInstance()->download_receipt($value);
        var_dump($re);
        # code...
    }

    public function actionDes()
    {
        $source =  'jicJcA06tHT08rfKhxNYE5NHdS_cT4QgYZfUwCfOFrBRt-TLE89m1MdNbZaPHQTjgozNvIEvUyHGR5i0S5hgdl1iHNkGKukO0e2a1pF5dMN2SRdzcvWy_hIVN1SXhPc37NFF8U7BEXo_9rQn8WKOpUtaatwnqxyVQxr9YZZDTckuyud_jqaJqqeBUftqOmPLRweyY7dEDXWncWWDzsF5GJN9QZNppFyzwCnIrgw6BtXAqFqkm8wV868Fi680Mwv1lOPB_qfd61V_qHm8HK-J_o4uMpqLBQ_whdHNWk2dW8mNZ9f_lFTW0Sr8sAbwMvSpNkLq92-qJOWuMHaEQydPJQ$duV4cjYk3SPVJZKRJPYfjVf8jGJMkw7z4bNSR8ECJ5z_hVj3xlTQh0-5iPhBo9NNPE7wlyL9XuxD2B96vPqZNO9_fbDqKYnyR3kDNKdQuBanh8PN-dS8XnHFIJgR4mzr7k6AZj6BZ4LpWxPx4b7vtBvbxVR9eOAdxw2KAKtk879jLtCJVhP7IgMURJoPzJ1guKEXHvgOUlVM3vyq3_CBUIW5IC_7UPF3dEwF_pwUUWy2CHoIPMMykDMSI2EdckIcJ6b2cJTAUlV3Y2QYr-UQ3tC0U9t8KL2eiexkV5JAHn2Aa0E8zkeOR2_GegZWHzviRMO62inV5afjwsckDo3kWbyd5YWowp4RprUZsI0ze0nKwvdtV9vfAHE9JREAXK6TU37kjIMN2ODKdUJwpS6hZyCVbbGSkpTr2WY_FznYErYaPezPaKqjY8bcbMQzCXEf-aUQ2D5qpU7_S7pP5xjO7ohWn1mxfX2Fq73uOVW2rmWakGx8LvVUjU039m2Z49VjG37vW6Xaf4iPlHo9Kuy6_aJsr91JmNIIGDpJ8MgBD5L_4xXaVwxRR3X65N3GO7tcE12VLarhC1EtYvShdUAr9gsKwI9fq4F2dHuj4qjoYBRoX6TStjSLFJG7sOwVhNcaaFe2hwCuNOvXHZgtTeYRn7B91hfbSlook1KBuO1XfFl-MsEGrTLaaRgkXPX3UkdqYvROE4_CRarv16MQ6uqTK5j2rMo3WR11hwFcciPF4tOPZEfKDJHL9CWby9HqOWgN$AES$SHA256';
        $_data = YopSignUtils::decrypt($source, YopConfig::CFCA_PRIVATE_KEY, YopConfig::PUBLIC_KEY);
        var_dump(json_decode($_data, true));
        die;
    }

    public function actionAutoCreate(){
        Yii::log(__CLASS__."   autoCreate run", 'info');

        try {
            //已分配待收购用户数据
            $sql = "SELECT  xau.user_id ,xau.assignee_user_id  from xf_assignee_user xau  
LEFT JOIN xf_purchase_assignee xpa on xpa.user_id=xau.assignee_user_id 
where xau.status=1 and xpa.status=2  and xau.purchase_status=0  and xau.auto_create=0 
GROUP BY xau.user_id ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            if(!$list){
                Yii::log(__CLASS__." autoCreate no data", 'info');
                return false;
            }

            foreach ($list as $key => $value) {
                Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} start", 'info');
                //普惠本金
                $d_sql = "SELECT  sum(wait_capital) as s_wait_capital,max(debt_status) as debt_status   from firstp2p_deal_load  where user_id={$value['user_id']} and status=1 and xf_status=0 and black_status=1 and wait_capital>0";
                $ph_load = Yii::app()->phdb->createCommand($d_sql)->queryRow() ;
                $phwait_capital = !empty($ph_load['s_wait_capital']) ? $ph_load['s_wait_capital'] : 0;
                if($ph_load['debt_status'] == 1){
                    Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} end debt_status=1", 'info');
                    continue;
                    /*
                    $d_esql = "update  firstp2p_deal_load set debt_status=0 where user_id={$value['user_id']} and status=1 and debt_status=1";
                    $ph_load_ret = Yii::app()->phdb->createCommand($d_esql)->execute() ;
                    if(!$ph_load_ret){
                        Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} end $d_esql error", 'info');
                        continue;
                    }
                    $d_dsql = "update  firstp2p_debt set status=3 where user_id={$value['user_id']} and status=1 ";
                    $ph_debt_ret = Yii::app()->phdb->createCommand($d_dsql)->execute() ;
                    if(!$ph_debt_ret){
                        Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} end $d_dsql error", 'info');
                        continue;
                    }*/
                }

                //智多新本金
                $zdx_sql = "SELECT  sum(wait_capital)  as s_wait_capital,max(debt_status) as debt_status  from offline_deal_load  where user_id={$value['user_id']} and status=1 and xf_status=0 and black_status=1 and wait_capital>0 and platform_id=4";
                $zdx_load = Yii::app()->offlinedb->createCommand($zdx_sql)->queryRow() ;
                $zdxwait_capital = !empty($zdx_load['s_wait_capital']) ? $zdx_load['s_wait_capital'] : 0;
                if($zdx_load['debt_status'] == 1){
                    Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} end debt_status=1", 'info');
                    continue;
                    /*
                    $d_esql = "update  offline_deal_load set debt_status=0 where user_id={$value['user_id']} and status=1 and debt_status=1  and platform_id=4";
                    $zdx_load_ret = Yii::app()->offlinedb->createCommand($d_esql)->execute() ;
                    if(!$zdx_load_ret){
                        Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} end $d_esql error", 'info');
                        continue;
                    }
                    $d_dsql = "update  offline_debt set status=3 where user_id={$value['user_id']} and status=1  and platform_id=4 ";
                    $zdx_debt_ret = Yii::app()->offlinedb->createCommand($d_dsql)->execute() ;
                    if(!$zdx_debt_ret){
                        Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} end $d_dsql error", 'info');
                        continue;
                    }*/
                }
                $value['wait_capital'] = bcadd($phwait_capital, $zdxwait_capital, 2);
                //重提差
                $u_sql = "SELECT  ph_increase_reduce  from xf_user_recharge_withdraw  where user_id={$value['user_id']}";
                $r_user = Yii::app()->fdb->createCommand($u_sql)->queryRow();

                $value['purchase_amount'] = round($r_user['ph_increase_reduce']*0.2, 2);


                $value['wait_capital'] = $value['wait_capital'] ?: 0;
                $value['purchase_amount'] = $value['purchase_amount'] > 0 ? $value['purchase_amount'] : 0;

                if($value['wait_capital'] <= 0){
                    Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} end wait_capital<=0", 'info');
                    continue;
                }

                if($value['purchase_amount'] <= 0){
                    Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} end purchase_amount<=0", 'info');
                    continue;
                }

                $params   = [
                    'user_id'     => $value['user_id'],
                    'purchase_amount'     => $value['purchase_amount'],
                    'user_chong_ti_cha'     =>$r_user['ph_increase_reduce'],
                    'discount'     => 2,
                    'purchase_type'     => 1,
                ];
                $params['buyer_user_id'] = $value['assignee_user_id'];
                var_dump($params);
                $res = $this->createPurchase($params);
                if ($res) {
                    Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} createPurchase end and success", 'info');
                }else{
                    Yii::log(__CLASS__." autoCreate user_id:{$value['user_id']} createPurchase end and error", 'info');
                }
            }

            Yii::log(__CLASS__." autoCreate end and success:", 'info');
        } catch (Exception $e) {
            Yii::log(__CLASS__." autoCreate end and Exception:". $e->getMessage(), 'info');
        }
    }

    public function createPurchase($params)
    {
        try {
            $user_id = $params['user_id'];
            if (empty($user_id)) {
                throw new Exception('出借人不能为空');
            }
            if (empty($params['buyer_user_id'])) {
                throw new Exception('受让人不能为空');
            }
            if (empty($params['purchase_amount'])) {
                throw new Exception('收购金额不能为空');
            }
            if (empty($params['purchase_type'])) {
                throw new Exception('收购类型不能为空');
            }
            if ($params['discount'] > 10 || $params['discount']< 0.01) {
                throw new Exception('折扣范围 0.01-10');
            }
            if (empty($params['user_chong_ti_cha'])) {
                throw new Exception('用户充提差不能为空');
            }

            $deal_load_info = ExclusivePurchaseService::getInstance()->getUserWaitDealLoad($user_id);
            //bug修复
            $deal_load_info['zdx_yr_deal_load_ids'] = $deal_load_info['zdx_deal_load_ids'];
            if ($deal_load_info['ph_deal_load_ids'] && $deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->phdb->beginTransaction();
                Yii::app()->offlinedb->beginTransaction();
            } elseif ($deal_load_info['ph_deal_load_ids']) {
                Yii::app()->phdb->beginTransaction();
            } elseif ($deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->offlinedb->beginTransaction();
            }


            $xf_assignee_user  = Yii::app()->phdb->createCommand("select * from xf_assignee_user where status=1 and user_id  = {$params['user_id']} and assignee_user_id = {$params['buyer_user_id']} for update")->queryRow();
           // var_dump($xf_assignee_user);die;
            if (!$xf_assignee_user) {
                throw new Exception('受让人与出借人未关联');
            }

            if ($xf_assignee_user['purchase_status'] == 1) {
                throw new Exception('出借人债权已被收购');
            }

            $user_info = ExclusivePurchaseService::getInstance()->getUserInfo($user_id);
            if (!FunctionUtil::float_equal($user_info['recharge_withdrawal_difference'], $params['user_chong_ti_cha'], 2)) {
                echo '出借人债权总额发生变化，请重新查询';
                throw new Exception('出借人债权总额发生变化，请重新查询');
            }
            //专属列表数据
            $xf_exclusive_purchase     = Yii::app()->phdb->createCommand("select * from xf_exclusive_purchase where user_id  = {$params['user_id']} and status <= 4 and purchase_user_id = {$params['buyer_user_id']}")->queryRow();
            //var_dump($xf_exclusive_purchase);die;
            if ($xf_exclusive_purchase) {
                throw new Exception('该出借人与受让人债转进行中');
            }

            $now = time();
            //受让人信息
            $xf_purchase_assignee     = Yii::app()->phdb->createCommand("select * from xf_purchase_assignee where user_id  = {$params['buyer_user_id']}")->queryRow();

            if (!$xf_purchase_assignee) {
                throw new Exception('该受让人不在范围内');
            }

            if ($xf_purchase_assignee ['status'] != 2) {
                throw new Exception('受让人状态不可用');
            }

            $transferability_limit =  $xf_purchase_assignee['transferability_limit'];//总受让额度
            $frozen_quota =  $xf_purchase_assignee['frozen_quota'];//冻结的
            $transferred_amount =  $xf_purchase_assignee['transferred_amount'];//已收购的
            //专属数据
            //受让人额度校验
            $check_amount = round($frozen_quota + $transferred_amount + $user_info['wait_capital'], 2);
            if ($check_amount > $transferability_limit) {
                echo '超过受让人受让额度';
                throw new Exception('超过受让人受让额度');
            }

            $queryData = [
                'traceid'=>$params['user_id'],
                'cardno'=>$user_info['bank_card'],
            ];
            $query_res = ExclusivePurchaseService::getInstance()->queryBankNameYop($queryData);
            if (!$query_res) {
                echo '请求易宝查询接口异常';
                throw new Exception('请求易宝查询接口异常');
            }
            if ($query_res['isvalid'] == 'INVALID') {
                echo '该银行卡状态不可用';
                throw new Exception('该银行卡状态不可用');
            }



            $model = new XfExclusivePurchase();
            $model->discount = $params['discount'];
            $model->purchase_amount = $params['purchase_amount'];
            $model->user_id = $params['user_id'];
            $model->purchase_user_id = $params['buyer_user_id'];
            $model->wait_capital = $user_info['wait_capital'];
            $model->recharge_withdrawal_difference = $user_info['recharge_withdrawal_difference'];
            $model->real_name = $user_info['real_name'];
            $model->mobile_phone = $user_info['mobile_phone'];
            $model->idno = $user_info['idno'];
            $model->bank_name = $query_res['bankname'];//开户行
            $model->bank_card = $user_info['bank_card'];
            $model->bankcode = $query_res['bankcode'];//银行编码
            $model->end_time = $now + ($xf_purchase_assignee['purchase_time']*3600);
            $model->start_time = $now;
            $model->add_time = $now;
            $model->order_no = FunctionUtil::getRequestNo("PAY");
            $model->add_ip = Yii::app()->request->userHostAddress;
            $model->add_user_id = 0;
            $model->status = 0;
            $model->add_user_name = '';



            if (!$model->save()) {
                throw new Exception('数据保存失败",请重试-1');
            }
            $id = $model->id;

            $sql = " UPDATE xf_purchase_assignee SET frozen_quota = frozen_quota + {$user_info['wait_capital']} WHERE user_id = {$params['buyer_user_id']}";
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                throw new Exception('数据保存失败",请重试-3');
            }
            if ($deal_load_info['ph_deal_load_ids']) {
                $sql = " UPDATE firstp2p_deal_load SET exclusive_purchase_id = $id WHERE user_id = {$user_id} and id in (".implode(',', $deal_load_info['ph_deal_load_ids']).")";
                $res = Yii::app()->phdb->createCommand($sql)->execute();
                if ($res === false) {
                    throw new Exception('数据保存失败",请重试-2');
                }
            }
            if ($deal_load_info['zdx_yr_deal_load_ids']) {
                $sql = " UPDATE offline_deal_load SET exclusive_purchase_id = $id WHERE platform_id=4 and user_id = {$user_id} and id in (".implode(',', $deal_load_info['zdx_yr_deal_load_ids']).")";
                $res = Yii::app()->offlinedb->createCommand($sql)->execute();
                if ($res === false) {
                    throw new Exception('数据保存失败",请重试-3');
                }
            }

            if ($deal_load_info['ph_deal_load_ids'] && $deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->phdb->commit();
                Yii::app()->offlinedb->commit();
            } elseif ($deal_load_info['ph_deal_load_ids']) {
                Yii::app()->phdb->commit();
            } elseif ($deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->offlinedb->commit();
            }

            //todo 临时不发
            return true;
            //发短信
            $smaClass                   = new XfSmsClass();
            $remind                     = array();
            $remind['sms_code']         = "create_exclusive_purchase";
            $remind['mobile']           = ConfUtil::get('xf_fdd_app_id') == '001542'?$user_info['mobile_phone']:'13716970622';
            $remind['data']['hour']     = $xf_purchase_assignee['purchase_time'];

            $send_ret_a = $smaClass->sendToUserByPhone($remind);

            if ($send_ret_a['code'] != 0) {
                Yii::log("SendSMS user_id:{$user_info['id']}; error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
            }
            return true;
        } catch (Exception $e) {
            if ($deal_load_info['ph_deal_load_ids'] && $deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->phdb->rollback();
                Yii::app()->offlinedb->rollback();
            } elseif ($deal_load_info['ph_deal_load_ids']) {
                Yii::app()->phdb->rollback();
            } elseif ($deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->offlinedb->rollback();
            }


            throw $e;
        }
    }



}

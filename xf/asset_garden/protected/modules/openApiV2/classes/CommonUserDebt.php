<?php

class CommonUserDebt
{
    public $user_id = 0;
    public $shop_app_id = 0;
    private static $cache_key='exchange_debt_area:shop:user:%s:%s';
    public function __construct($appid, $user_id)
    {
        $this->shop_app_id = $appid ? intval($appid):0;
        $this->user_id = $user_id ? intval($user_id):0;
    }

   

    public function debtOrderCommit($data)
    {
        $returnData = [
            'data' => [],
            'code' => 0,
            'info' => '',
        ];
        
        $tenderIds_PH = $tenderIds_ZDX = [];
        foreach ($data['select_debt'] as $key => $value) {
            if ($value['type'] == 2) {
                $tenderIds_PH[] = $value['id'];
            } elseif ($value['type'] == 4) {
                $tenderIds_ZDX[] = $value['id'];
            } else {
                $returnData['code'] = 100;
                $returnData['info'] = '债权信息有误';
                return   $returnData ;
            }
        }
        $notify_url = $data['notify_url'];
        $redirect_url = $data['redirect_url'];
        $appid = $data['appid'];
        $amount_init =  floatval($data['amount']) ? floatval($data['amount']) : 0;

        $aboutDebt = new AboutUserDebtV2($this->user_id, $this->shop_app_id, '');
        $aboutDebt->is_not_check_white_list = true;
        $result1 = $aboutDebt->getUserSumAccountAndTotalTenderPH();
        $offlineDebt4 = new AboutUserOfflineDebt($this->user_id, 4, $this->shop_app_id, '');
        $offlineDebt4->is_not_check_white_list = true;
        $result4 = $offlineDebt4->getUserSumAccountAndTotalTender();
        $total = $result1['total_account']+$result4['total_account'];
        if (bcsub($amount_init, $total, 2) != 0) {
            $returnData['code'] = 2057;
            $returnData['info'] = '债权需全部兑换,当前剩余 普惠债权:'.$result1['total_account'] .' 智多新债权:'.$result4['total_account'];
            return $returnData;
        }

        //校验兑换流水号是否存在信息
        $orderNum = strval($data['exchange_no']) ?: '';
       
        if ($tenderIds_PH) {
            $db_type = 1;
        }
        if ($tenderIds_ZDX) {
            $db_type = 2;
        }
        if ($tenderIds_ZDX && $tenderIds_PH) {
            $db_type = 3;
        }

        $buyer_uid = $total_capital = 0;
        $transaction_id = str_replace('.', '', uniqid('', true));
        //拼接合同内容
        $deal_load_content = $deal_load_content_01 = $deal_load_content_02 = $deal_load_content_03 = $deal_load_content_04 = $deal_load_content_05 = '';
        $deal_load_content_06 = $deal_load_content_07 = $deal_load_content_08 = $deal_load_content_09 = $deal_load_content_10 = $deal_load_content_11 = '';
        $end_time = time()-60*5;
        try {
            $this->getBeginTransaction($db_type);
            $n = 0;
            if ($tenderIds_PH) {
                $check_ret = PHDebtExchangeLog::model()->find("order_id='$orderNum' and user_id={$this->user_id} and (status!=9 or (status=9 and addtime>=$end_time)) ");
                if ($check_ret) {
                    $returnData['code'] = 2032;
                    return $returnData;
                }
                $tenderIds_PH = count($tenderIds_PH) > 1 ? implode(',', $tenderIds_PH) : current($tenderIds_PH);
           
                $condition = " AND t.id in  ($tenderIds_PH) AND t.black_status = 1 ";
                $aboutDebt->isCheckExchangeCommit = true;
                $canDebtTendersInfo = $aboutDebt->getUserCanDebtTendersPH($condition);
                //校验数据
                $tenderIdsArray = explode(',', $tenderIds_PH);
                //非特殊兑换才校验这个
                if (count($tenderIdsArray) !== count($canDebtTendersInfo)) {
                    $this->getRollback($db_type);
                    $returnData['code'] = 2016;
                    $returnData['info'] = '数据不一致 (-1) in:'.count($tenderIdsArray).' set:'.count($canDebtTendersInfo);
                    return $returnData;
                }
               
                $res = DebtExchangeService::getInstance()->makeDebtBuyer($canDebtTendersInfo);
                if ($res['code']) {
                    $this->getRollback($db_type);
                    return  $res;
                }
                $debt_exchange_str = '';
                foreach ($canDebtTendersInfo as $orderTender) {
                    $insert_change_data = array(
                        'user_id' => $this->user_id,
                        'tender_id' => $orderTender['id'],
                        'order_id' => $orderNum,
                        'debt_account' => $orderTender['account'],
                        'addtime' => time(),
                        'status' => 9,
                        'borrow_id' => $orderTender['borrow_id'],
                        'buyer_uid' => $orderTender['buyer_uid'],
                        'debt_src' => 1,
                        'platform_no' => $data['appid'],
                        'order_info' => $data['goodsInfo'],
                        'order_sn' => $data['goods_order_no'],
                        'contract_transaction_id'=>$transaction_id
                    );
                    $debt_exchange_str .= "( '".  implode("','", $insert_change_data) ."' ),";
                    $debt_exchange_key = array_keys($insert_change_data);

                    //合同信息
                    $total_capital = bcadd($total_capital, $orderTender['account'], 2);
                    $buyer_uid = $orderTender['buyer_uid'];
                    $platform_no = $data['appid'];
                    //债转合同编号根据规则拼接
                    $seller_contract_number = implode('-', [date('Ymd', $orderTender['addtime']), $orderTender['deal_type'], $orderTender['borrow_id'], $orderTender['id']]);
                    //普惠获取合同编号
                    if ($orderTender['debt_type'] == 1  ) {
                        //合同信息
                        $table_name = $orderTender['borrow_id'] % 128;
                        $contract_sql = "select number from contract_$table_name 
                             where deal_load_id = {$orderTender['id']} 
                             and user_id={$this->user_id} 
                             and deal_id={$orderTender['borrow_id']}
                             and type in (0,1) and status=1 and source_type=0  ";
                        $contract_info = Yii::app()->cdb->createCommand($contract_sql)->queryRow();
                        if (!$contract_info) {
                            Yii::log("saveDebtOrdersPHTmp tender_id[{$orderTender['id']}]  $contract_sql error  ", 'error');
                            return false;
                        }
                        $seller_contract_number = $contract_info['number'];
                    }

                    $n+=1;
                    if ($n<=25) {
                        $deal_load_content .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>25 && $n<= 57) {
                        $deal_load_content_01 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>57 && $n<= 89) {
                        $deal_load_content_02 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>89 && $n<= 121) {
                        $deal_load_content_03 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>121 && $n<= 153) {
                        $deal_load_content_04 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>153 && $n<= 185) {
                        $deal_load_content_05 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>185 && $n<= 217) {
                        $deal_load_content_06 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>217 && $n<= 249) {
                        $deal_load_content_07 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>249 && $n<= 281) {
                        $deal_load_content_08 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>281 && $n<= 313) {
                        $deal_load_content_09 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>313 && $n<= 345) {
                        $deal_load_content_10 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>345 && $n<= 377) {
                        $deal_load_content_11 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                }
                //批量写入
                $debt_exchange_str = rtrim($debt_exchange_str, ',');
                $debt_exchange_key = implode(",", $debt_exchange_key);
                $i_sql = "INSERT INTO firstp2p_debt_exchange_log (".$debt_exchange_key.") VALUES $debt_exchange_str";
                $result = Yii::app()->phdb->createCommand($i_sql)->execute();
                if (!$result) {
                    $this->getRollback($db_type);
                    $returnData['code'] = 100;
                    return $returnData;
                }
            }
            if ($tenderIds_ZDX) {
                //校验兑换流水号是否存在信息
                $check_ret = OfflineDebtExchangeLog::model()->find("order_id='$orderNum' and user_id={$this->user_id} and (status!=9 or (status=9 and addtime>={$end_time}))");
                if ($check_ret) {
                    $this->getRollback($db_type);
                    $returnData['code'] = 2032;
                    return $returnData;
                }
                //校验数据
                $tenderIds_ZDX = count($tenderIds_ZDX) > 1 ? implode(',', $tenderIds_ZDX) : current($tenderIds_ZDX);
                $condition = " AND t.id in  ($tenderIds_ZDX) AND t.black_status = 1 ";
                //获取tender对应信息
                $offlineDebt4->isCheckExchangeCommit = true;
                $canDebtTendersInfo = $offlineDebt4->getUserCanDebtTenders($condition);
                $tenderIdsArray = explode(',', $tenderIds_ZDX);
                //非特殊兑换才校验这个
                if (count($tenderIdsArray) !== count($canDebtTendersInfo)) {
                    $this->getRollback($db_type);
                    $returnData['code'] = 2016;
                    $returnData['info'] = '数据不一致 (-2) in:'.count($tenderIdsArray).' set:'.count($canDebtTendersInfo);

                    return $returnData;
                }

                $res = DebtExchangeService::getInstance()->makeDebtBuyer($canDebtTendersInfo);
                if ($res['code']) {
                    $this->getRollback($db_type);
                    return  $res;
                }
                $debt_exchange_str = '';
                foreach ($canDebtTendersInfo as $orderTender) {
                    $insert_change_data = array(
                        'user_id' =>$this->user_id,
                        'tender_id' => $orderTender['id'],
                        'order_id' => $orderNum,
                        'debt_account' => $orderTender['account'],
                        'addtime' => time(),
                        'status' => 9,
                        'borrow_id' => $orderTender['borrow_id'],
                        'buyer_uid' => $orderTender['buyer_uid'],
                        'debt_src' => 1,
                        'platform_no' => $data['appid'],
                        'order_info' => $data['goodsInfo'],
                        'order_sn' => $data['goods_order_no'],
                        'platform_id' => 4,
                        'contract_transaction_id'=>$transaction_id
                      
                    );
                    $debt_exchange_str .= "( '".  implode("','", $insert_change_data) ."' ),";
                    $debt_exchange_key = array_keys($insert_change_data);

                    //合同信息
                    $total_capital = bcadd($total_capital, $orderTender['account'], 2);
                    $buyer_uid = $orderTender['buyer_uid'];
                    $platform_no = $data['appid'];
                    //债转合同编号根据规则拼接
                    $seller_contract_number = implode('-', [date('Ymd', $orderTender['addtime']), $orderTender['deal_type'], $orderTender['borrow_id'], $orderTender['id']]);
                    //普惠获取合同编号
                    if ($orderTender['debt_type'] == 1  ) {
                        $contract_info = OfflineContractTask::model()->find("tender_id={$orderTender['id']} and contract_type=1 and type=1 and status=2");
                        if (!$contract_info) {
                            Yii::log("commonUserDebt  tender_id[{$orderTender['id']}] OfflineContractTask  error  ", 'error');
                            return false;
                        }
                        $seller_contract_number = $contract_info->contract_no;
                    }

                    $n+=1;
                    if ($n<=25) {
                        $deal_load_content .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>25 && $n<= 57) {
                        $deal_load_content_01 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>57 && $n<= 89) {
                        $deal_load_content_02 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>89 && $n<= 121) {
                        $deal_load_content_03 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>121 && $n<= 153) {
                        $deal_load_content_04 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>153 && $n<= 185) {
                        $deal_load_content_05 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>185 && $n<= 217) {
                        $deal_load_content_06 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>217 && $n<= 249) {
                        $deal_load_content_07 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>249 && $n<= 281) {
                        $deal_load_content_08 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>281 && $n<= 313) {
                        $deal_load_content_09 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>313 && $n<= 345) {
                        $deal_load_content_10 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                    if ($n>345 && $n<= 377) {
                        $deal_load_content_11 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$orderTender['name']}；转让债权本金：{$orderTender['account']}元整。\r\n";
                    }
                }
                //批量写入
                $debt_exchange_str = rtrim($debt_exchange_str, ',');
                $debt_exchange_key = implode(",", $debt_exchange_key);
                $i_sql = "INSERT INTO offline_debt_exchange_log (".$debt_exchange_key.") VALUES $debt_exchange_str";

                $result = Yii::app()->offlinedb->createCommand($i_sql)->execute();
                if (!$result) {
                    $this->getRollback($db_type);
                    $returnData['code'] = 100;
                    return $returnData;
                }


            }

            //受让人信息查询
            $buyer_uid = !empty($buyer_uid) ? $buyer_uid : DebtService::getInstance()->getBuyerUid($total_capital);
            $assignee = User::model()->findByPk($buyer_uid)->attributes;
            if (!$assignee) {
                Yii::log("debtOrderCommit   assignee error  $buyer_uid ", 'error');
                return false;
            }

            //拼接合同内容
            $assignee_idno = GibberishAESUtil::dec($assignee['idno'], Yii::app()->c->contract['idno_key']);
            $seller_user = User::model()->findByPk($this->user_id)->attributes;
            $seller_idno = GibberishAESUtil::dec($seller_user['idno'], Yii::app()->c->contract['idno_key']);
            $template_id = empty($deal_load_content_03) ? Yii::app()->c->contract[8]['template_id'] : Yii::app()->c->contract[9]['template_id'];
            $shop_name = DebtService::getInstance()->getShopName($platform_no);

            //合同生成
            $cvalue = [
                'title' => '债权转让协议',
                'params' => [
                    'contract_id' => implode('-', ['JFDH', date('Ymd', time()), $buyer_uid, $this->user_id]),
                    'debt_account_total' =>  $total_capital,
                    'A_user_name' => $seller_user['real_name'],
                    'A_card_id' => $seller_idno,
                    'B_user_name' => $assignee['real_name'],
                    'B_card_id' => $assignee_idno,
                    'sign_year' => date('Y'),
                    'sign_month' => date('m'),
                    'sign_day' => date('d'),
                    'company_name' =>   "北京东方联合投资管理有限公司",
                    'plan_name' =>   "网信普惠平台",
                    'shop_name' =>   $shop_name,
                    'web_address' =>   "www.firstp2p.com",
                    'deal_load_content' => $deal_load_content,//债权信息
                    'deal_load_content_one' => $deal_load_content_01,
                    'deal_load_content_two' => $deal_load_content_02,
                    'deal_load_content_three' => $deal_load_content_03,
                    'deal_load_content_four' => $deal_load_content_04,
                    'deal_load_content_five' => $deal_load_content_05,
                    'deal_load_content_8' => $deal_load_content_06,
                    'deal_load_content_9' => $deal_load_content_07,
                    'deal_load_content_10' => $deal_load_content_08,
                    'deal_load_content_11' => $deal_load_content_09,
                    'deal_load_content_12' => $deal_load_content_10,
                    'deal_load_content_13' => $deal_load_content_11,
                ],
                'sign' => [
                    'A盖签' => $seller_user['yj_fdd_customer_id'],
                    'B盖签' => '',
                ],
                'pwd' => '',
            ];

            //合同文档标题
            $doc_title = $cvalue['title'];
            //填充自定义参数
            $params = $cvalue['params'];
            //生成合同
            $result = XfFddService::getInstance()->invokeGenerateContract($template_id, $doc_title, $params, $cvalue['dynamic_tables']?:'');
            if (!$result || $result['code'] != 1000) {
                Yii::log("debtOrderCommit  order_id[{$orderNum}]  合同生成失败！\n" . print_r($result, true), 'error');
                return false;
            }
            //法大大合同ID
            $contract_id = $result['contract_id'];

            //加水印
            $text_name = mb_substr("{$seller_user['real_name']}  {$assignee['real_name']}", 0, 15, 'utf-8');
            $watermark_params = [
                'contract_id' => $contract_id,
                'stamp_type' => 1,
                'text_name' => $text_name,
                'font_size' => 12,
                'rotate' => 45,
                'concentration_factor' => 10,
                'opacity' => 0.2,
            ];
            $result = XfFddService::getInstance()->watermarkPdf($watermark_params);
            if (!$result || $result['code'] != 1) {
                Yii::log("debtOrderCommit  order_id[{$orderNum}]  的{$cvalue['title']}加水印失败！\n" . print_r($result, true), 'error');
                return false;
            }

            //卖方手动签署合同
            $sign_contract_url = XfFddService::getInstance()->invokeExtSign($seller_user['yj_fdd_customer_id'], $contract_id, $doc_title, 'A盖签', $transaction_id,1);
            if (!$sign_contract_url) {
                Yii::log("debtOrderCommit  order_id[{$orderNum}]  收购合同获取签署地址失败！\n" . print_r($result, true), 'error');
                return false;
            }

            $notice_data = [
                'amount'=>$amount_init,
                'appid'=>$appid,
                'user_id'=>$this->user_id,
                'order_id'=>$orderNum,
                'notify_url'=>$notify_url,
                'order_info'=>$data['goodsInfo'],
                'order_sn'=>$data['goods_order_no'],
            ];
            $notice_data = json_encode($notice_data);

            //合临时表数据写入
            $redirect_url = urldecode($redirect_url);
            $strc = substr_count($redirect_url, '?');
            $redirect_url = $strc>0 ? $redirect_url."&exchange_no={$orderNum}" : $redirect_url."?exchange_no={$orderNum}";
            $redirect_url = urlencode($redirect_url);

            $debt['user_id'] = $this->user_id;
            $debt['status'] = 0;
            $debt['contract_transaction_id'] = $transaction_id;
            $debt['add_time'] = time();
            $debt['contract_url'] = $sign_contract_url;
            $debt['contract_id'] = $contract_id;
            //$redirect_url = $redirect_url."?exchange_no={$orderNum}";
            $debt['return_url'] = $redirect_url;
            $debt['platform_id'] = 99;
            $debt['notice_data'] = $notice_data;
            $debt['buyer_uid'] = $buyer_uid;
            $ret = BaseCrudService::getInstance()->add('Firstp2pDebtContract', $debt);
            if(false == $ret){//添加失败
                Yii::log("debtOrderCommit  user_id[{$this->user_id}] , order_id[{$orderNum}]: add DebtContract error ", 'error');
                return false;
            }
            //合同签署地址
            $returnData['contract_url'] = $sign_contract_url;
           // $this->saveNotice($amount_init, $orderNum, $appid, $notify_url, $data['goodsInfo'], $data['goods_order_no']);
            $this->getCommit($db_type);
            return $returnData;
        } catch (Exception $e) {
            $this->getRollback($db_type);
            Yii::log('save error debtType:'.$data['debtType'].' : save tender  '.print_r($data, true) .' Exception:'.$e->getMessage(), 'error', __FUNCTION__);
        }
        Yii::log('save error debtType:'.$data['debtType'].' : save tender  '.print_r($data, true), 'error', __FUNCTION__);
        $returnData['code'] = 2001;
        return $returnData;
    }


    private function saveNotice($amount, $orderNum, $appid, $notify_url='', $goodsInfo='', $goods_order_no='')
    {
        $notice = new XfDebtExchangeNotice();
        $notice->amount = $amount;
        $notice->appid = $appid;
        $notice->user_id = $this->user_id;
        $notice->order_id = $orderNum;
        $notice->notify_url = $notify_url;
        $notice->order_info = $goodsInfo;
        $notice->order_sn = $goods_order_no;
        $notice->created_at = time();
        $notice->notice_time_1 = time();
        $notice->notice_time_2 = time()+30;
        $notice->notice_time_3 = time()+300;
        return  $notice->save();
    }

    private function getBeginTransaction($type)
    {
        if ($type == 1) {
            Yii::app()->phdb->beginTransaction();
        } elseif ($type == 2) {
            Yii::app()->offlinedb->beginTransaction();
        } elseif ($type == 3) {
            Yii::app()->phdb->beginTransaction();
            Yii::app()->offlinedb->beginTransaction();
        } else {
        }
    }
    private function getCommit($type)
    {
        if ($type == 1) {
            Yii::app()->phdb->commit();
        } elseif ($type == 2) {
            Yii::app()->offlinedb->commit();
        } elseif ($type == 3) {
            Yii::app()->phdb->commit();
            Yii::app()->offlinedb->commit();
        } else {
        }
    }

    private function getRollback($type)
    {
        if ($type == 1) {
            Yii::app()->phdb->rollback();
        } elseif ($type == 2) {
            Yii::app()->offlinedb->rollback();
        } elseif ($type == 3) {
            Yii::app()->phdb->rollback();
            Yii::app()->offlinedb->rollback();
        } else {
        }
    }



    public function getNeedAllExchangeUserDebtBalance()
    {
        $aboutDebt = new AboutUserDebtV2($this->user_id, $this->shop_app_id, '');
        $aboutDebt->is_not_check_white_list = true;
        $result2 = $aboutDebt->getUserSumAccountAndTotalTenderPH();
        $offlineDebt4 = new AboutUserOfflineDebt($this->user_id, 4, $this->shop_app_id, '');
        $offlineDebt4->is_not_check_white_list = true;
        $result4 = $offlineDebt4->getUserSumAccountAndTotalTender();
        return $result2['total_account']+$result4['total_account'];
    }

    public function checkUserIsNeedAllExchange()
    {
        $sql = "select user_id from xf_shop_xche_user  where user_id = {$this->user_id} and status = 1";
        $is_in = Yii::app()->db->createCommand($sql)->queryRow()?1:0;

        $re = $this->getNeedAllExchangeUserDebtBalance();

        if ($re > 0 && $is_in) {
            return $re;
        }
        return false;
    }

    public function getUserSpecialAreaListFromCache()
    {
        $res = RedisService::getInstance()->get(sprintf(self::$cache_key, $this->shop_app_id, $this->user_id));
        if ($res) {
            return $res;
        }
        $res = $this->getUserSpecialAreaList();
        RedisService::getInstance()->set(sprintf(self::$cache_key, $this->shop_app_id, $this->user_id), $res, 60);
        return  $res;
    }

    public function getUserSpecialAreaList()
    {
        //获取专区列表；
        $user_area_list = [];
        $area_id_arr = [];
        $special_area_list = $res = Yii::app()->db->createCommand("select id,name,code  from xf_debt_exchange_special_area where status = 1")->queryAll() ?: [];
        if (empty($special_area_list)) {
            return $user_area_list;
        }

        //查尊享的项目；
        // $sql = "select al.area_id,sum(dl.wait_capital) as amount  from firstp2p_deal_load as dl  left join xf_debt_exchange_deal_allow_list as al on dl.deal_id =al.deal_id where dl.user_id = {$this->user_id} and dl.wait_capital > 0 and dl.black_status = 1 and dl.status = 1 and  al.appid = {$this->shop_app_id} and al.area_id > 0 AND  al.status = 1 AND al.type = 1 GROUP BY al.area_id";
        // $res = Yii::app()->db->createCommand($sql)->queryAll();
        // if ($res) {
        //     $area_id_arr = array_merge($area_id_arr, $res);
        // }


        /*********普惠的项目************/
        $aboutUserDebt = new AboutUserDebtV2($this->user_id, $this->shop_app_id);
        $condition = '';
        $disableBorrow = array_merge($aboutUserDebt->getDisableBorrow(2), $aboutUserDebt->getZDXBorrow());
        if (!empty($disableBorrow)) {
            $condition = ' AND d.id  not in ('.implode(',', $disableBorrow).') ';
        }
       
        //部分还款中的债权
        if ($partialRepayTender = $aboutUserDebt->getUserPartialRepayTenderPH()) {
            $condition .= ' AND dl.id  not in ('.implode(',', $partialRepayTender).') ';
        }

        $sql = "select al.area_id,sum(dl.wait_capital) as amount  from firstp2p_deal_load as dl  left join firstp2p.xf_debt_exchange_deal_allow_list as al on dl.deal_id =al.deal_id left join firstp2p_deal as d on d.id = dl.deal_id where dl.user_id = {$this->user_id} and dl.wait_capital > 0 and dl.black_status = 1 and dl.status = 1 and  al.appid = {$this->shop_app_id} and al.area_id > 0 AND  al.status = 1 AND al.type = 2 and ( d.product_class_type = 223 OR ( d.advisory_id  in (153, 215, 397, 399) and d.product_class_type in (5,232,202,223,316) )) ". $condition." GROUP BY al.area_id";
        $res = Yii::app()->phdb->createCommand($sql)->queryAll();
        if ($res) {
            $area_id_arr = array_merge($area_id_arr, $res);
        }

        //查工厂的项目；
//        $sql = "select distinct (al.area_id) from offline_deal_load as dl  left join firstp2p.xf_debt_exchange_deal_allow_list as al on dl.deal_id =al.deal_id where dl.user_id = {$this->user_id} and dl.wait_capital > 0 and dl.black_status = 1 and dl.status = 1 and  al.appid = {$this->shop_app_id} and al.area_id > 0 AND  al.status = 1 AND al.type = 3 ";
//        $res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
//        if($res){
//            $area_id_arr = array_merge($area_id_arr,ItzArray::array_column($res,'area_id'));
//        }

        //查智多新的项目；
        // $sql = "select al.area_id,sum(dl.wait_capital) as amount  from offline_deal_load as dl  left join firstp2p.xf_debt_exchange_deal_allow_list as al on dl.deal_id =al.deal_id where dl.user_id = {$this->user_id} and dl.wait_capital > 0 and dl.black_status = 1 and dl.status = 1 and  al.appid = {$this->shop_app_id} and al.area_id > 0 AND  al.status = 1 AND al.type = 4 GROUP BY al.area_id";
        // $res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
        // if ($res) {
        //     $area_id_arr = array_merge($area_id_arr, $res);
        // }

        //查交易所的项目；
//        $sql = "select distinct (al.area_id)from offline_deal_load as dl  left join firstp2p.xf_debt_exchange_deal_allow_list as al on dl.deal_id =al.deal_id where dl.user_id = {$this->user_id} and dl.wait_capital > 0 and dl.black_status = 1 and dl.status = 1 and  al.appid = {$this->shop_app_id} and al.area_id > 0 AND  al.status = 1 AND al.type = 5 ";
//        $res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
//        if($res){
//            $area_id_arr = array_merge($area_id_arr,ItzArray::array_column($res,'area_id'));
//        }

        if (empty($area_id_arr)) {
            return  $area_id_arr;
        }
        $area_id_arr_amount =[];
        foreach ($area_id_arr as $val) {
            $area_id_arr_amount[$val['area_id']] += $val['amount'];
        }
        
        foreach ($special_area_list as $item) {
            if (array_key_exists($item['id'], $area_id_arr_amount)) {
                $_tmp['area_code'] = $item['code'];
                $_tmp['name'] = $item['name'];
                $_tmp['user_amount'] = $area_id_arr_amount[$item['id']];
                $user_area_list[] = $_tmp;
            }
        }
        return  $user_area_list;
    }

    public function checkUserPurchase()
    {
        $purchase_sql = "SELECT id,user_id,wait_capital,discount,purchase_amount,status FROM xf_exclusive_purchase WHERE user_id = {$this->user_id} and status in (0,1,2,3)";
        $result_data  = Yii::app()->phdb->createCommand($purchase_sql)->queryRow();
        return $result_data?true:false;
    }
}

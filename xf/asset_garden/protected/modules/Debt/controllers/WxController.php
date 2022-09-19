<?php
class WxController extends YoujieGardenDebtController
{
    /**
     * 最近N天债转平均折扣走势数据 张健
     * @param   days    int     查询最近N天债转平均折扣走势数据(正整数)
     * @return  json
     */
    public function actionIndex()
    {
        $errorcodeinfo = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3100 , $errorcodeinfo[3100]);
        }
        if (empty($_POST['days'])) {
            $this->echoJson(array() , 3102 , $errorcodeinfo[3102]);
        }
        if (!is_numeric($_POST['days']) || $_POST['days'] < 1) {
            $this->echoJson(array() , 3103 , $errorcodeinfo[3103]);
        }
        $days       = intval($_POST['days']);
        $days_time  = $days * 86400;
        $today_time = strtotime(date('Y-m-d' , time()));
        $start_time = $today_time - $days_time;
        $end_time   = $today_time - 1;

        $sql     = "SELECT discount , addtime FROM firstp2p_debt WHERE addtime >= {$start_time} AND addtime <= {$end_time} AND status = 2 AND debt_src = 2";
        $zx_data = Yii::app()->db->createCommand($sql)->queryAll();
        $ph_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        $data    = array_merge($zx_data , $ph_data);
        if (!$data) {
            $this->echoJson(array() , 3104 , $errorcodeinfo[3104]);
        }
        $res = array();
        for ($i = 0; $i < $days; $i++) { 
            $temp       = $start_time + ($i * 86400);
            $temp       = date('Y-m-d' , $temp);
            $res[$temp] = array('number' => 0 , 'total' => 0 , 'discount' => 0);
        }
        foreach ($data as $key => $value) {
            $k = date('Y-m-d' , $value['addtime']);
            $res[$k]['number'] ++;
            $res[$k]['total'] += $value['discount'];
            $res[$k]['discount'] = round(($res[$k]['total'] / $res[$k]['number']) , 2);
        }
        $result = array();
        foreach ($res as $key => $value) {
            $temp_a['date']     = $key;
            $temp_a['discount'] = $value['discount'];
            $result[]           = $temp_a;
        }
        $this->echoJson($result , 0 , '查询成功');
    }

    /**
     * 最近N笔债转折扣走势数据 张健
     * @param   limit   int     查询最近N笔债转折扣走势数据(正整数)
     * @return  json
     */
    public function actionRecentDebtData()
    {
        $errorcodeinfo = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3100 , $errorcodeinfo[3100]);
        }
        if (empty($_POST['limit'])) {
            $this->echoJson(array() , 3105 , $errorcodeinfo[3105]);
        }
        if (!is_numeric($_POST['limit']) || $_POST['limit'] < 1) {
            $this->echoJson(array() , 3106 , $errorcodeinfo[3106]);
        }
        $limit   = intval($_POST['limit']);
        $sql     = "SELECT discount , addtime FROM firstp2p_debt WHERE status = 2 AND debt_src = 2 ORDER BY id DESC LIMIT 0,{$limit} ";
        $zx_data = Yii::app()->db->createCommand($sql)->queryAll();
        $ph_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        $data    = array_merge($zx_data , $ph_data);
        if (!$data) {
            $this->echoJson(array() , 3107 , $errorcodeinfo[3107]);
        }
        foreach ($data as $key => $value) {
            $discount[$key] = $value['discount'];
            $addtime[$key]  = $value['addtime'];
        }
        asort($addtime);
        $result = array();
        $i = 0;
        foreach ($addtime as $key => $value) {
            if ($i < $limit) {
                $result[]['discount'] = $discount[$key];
            }
            $i++;
        }
        
        $this->echoJson($result , 0 , '查询成功');
    }

    /**
     * 用户银行卡信息
     */
    public function actionUserBankCard()
    {
        $errorcodeinfo = Yii::app()->c->errorcodeinfo;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 3101 , $errorcodeinfo[3101]);
        }
        $sql     = "SELECT bl.branch AS name , ubc.bankcard , ubc.card_name FROM firstp2p_user_bankcard AS ubc INNER JOIN firstp2p_banklist AS bl ON ubc.bankzone = bl.name AND ubc.user_id = {$user_id} AND ubc.verify_status = 1 AND bl.status = 1";
        $result  = Yii::app()->db->createCommand($sql)->queryAll();
        if (!$result) {
            $this->echoJson(array() , 3108 , $errorcodeinfo[3108]);
        }
        foreach ($result as $key => $value) {
            $result[$key]['bankcard'] = GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key);
        }

        $this->echoJson($result , 0 , '查询成功');
    }

    /**
     * 债权转让 - 列表
     */
    public function actionDebtList()
    {
        $result_data   = array('count' => 0 , 'page_count' => 0 ,'data' => array());
        $errorcodeinfo = Yii::app()->c->errorcodeinfo;
        $where         = '';
        if (empty($_POST)) {
            $this->echoJson($result_data , 3100 , $errorcodeinfo[3100]);
        }
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson($result_data , 3101 , $errorcodeinfo[3101]);
        }
        if (!in_array($_POST['products'] , array(1 , 2))) {
            $this->echoJson($result_data , 3109 , $errorcodeinfo[3109]);
        }
        if (empty($_POST['status']) || !in_array($_POST['status'] , array(1 , 2 , 3 , 5 , 6))) {
            $this->echoJson($result_data , 3110 , $errorcodeinfo[3110]);
        }
        switch ($_POST['status']) {
            case '1':
                $where .= " AND debt.status = 1 ";
                $where_dt = " AND dt.status = -1 ";
                break;
            case '2':
                $where .= " AND debt.status = 2 ";
                $where_dt = " AND dt.status = 2 ";
                break;
            case '3':
                $where .= " AND debt.status IN (3 , 4) ";
                $where_dt = " AND dt.status = -1 ";
                break;
            case '5':
                $where .= " AND debt.status = 5 ";
                $where_dt = " AND dt.status = 1 ";
                break;
            case '6':
                $where .= " AND debt.status = 6 ";
                $where_dt = " AND dt.status = 6 ";
                break;
        }
        if (!empty($_POST['order'])) {
            if (!in_array($_POST['order'] , array(1 , 2 ))) {
                $this->echoJson($result_data , 3111 , $errorcodeinfo[3111]);
            }
            if ($_POST['order'] == 1) {
                $order = " ORDER BY debt.addtime DESC ";
            } else if ($_POST['order'] == 2) {
                $order = " ORDER BY debt.addtime ASC ";
            }
        } else {
            $order = " ORDER BY debt.addtime DESC ";
        }
        if (!empty($_POST['limit'])) {
            if (!is_numeric($_POST['limit']) || $_POST['limit'] < 1 || $_POST['limit'] > 100) {
                $this->echoJson($result_data , 3112 , $errorcodeinfo[3112]);
            }
            $limit = intval($_POST['limit']);
        } else {
            $limit = 10;
        }
        if (!empty($_POST['page'])) {
            if (!is_numeric($_POST['page']) || $_POST['page'] < 1) {
                $this->echoJson($result_data , 3113 , $errorcodeinfo[3113]);
            }
            $page = intval($_POST['page']);
        } else {
            $page = 1;
        }
        $time = time();
        if ($_POST['products'] == 1) {
            $sql = "SELECT count(debt.id) AS count FROM firstp2p_debt AS debt 
                    LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN firstp2p_debt_tender AS dt ON debt.id = dt.debt_id {$where_dt}
                    LEFT JOIN ag_wx_debt_appeal AS da ON debt.id = da.debt_id AND da.status = 1 AND da.products = {$_POST['products']} WHERE debt.user_id = {$user_id} AND debt.debt_src = 2 {$where} ";
            $count      = Yii::app()->db->createCommand($sql)->queryScalar();
            $page_count = ceil($count / $limit);
            $pass       = ($page - 1) * $limit;
            $sql = "SELECT dt.new_tender_id, debt.id AS debt_id , deal.name , debt.status , debt.money , debt.discount , debt.buy_code , debt.endtime , dt.cancel_time , da.id AS da_id , debt.tender_id , dt.addtime , dt.submit_paytime
                    FROM firstp2p_debt AS debt 
                    LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN firstp2p_debt_tender AS dt ON debt.id = dt.debt_id {$where_dt}
                    LEFT JOIN ag_wx_debt_appeal AS da ON debt.id = da.debt_id AND da.status = 1 AND da.products = {$_POST['products']} WHERE debt.user_id = {$user_id} AND debt.debt_src = 2 {$where} {$order} LIMIT {$pass} , {$limit} ";
            $res = Yii::app()->db->createCommand($sql)->queryAll();
            if (!$res) {
                $this->echoJson($result_data , 3114 , $errorcodeinfo[3114]);
            }
            foreach ($res as $key => $value) {
                $res[$key]['products']   = 1;
                if ($value['status'] == 1) {
                    $res[$key]['cancel_time'] = 0;
                    $res[$key]['count_down']  = $value['endtime'] - $time;
                } else if ($value['status'] == 5) {
                    $res[$key]['count_down'] = strval($value['addtime'] + ConfUtil::get('youjie-undertake-endtime') - $time);
                } else if ($value['status'] == 6) {
                    $res[$key]['count_down'] = strval($value['submit_paytime'] + ConfUtil::get('youjie-payment-endtime') - $time);
                } else {
                    $res[$key]['cancel_time'] = 0;
                    $res[$key]['count_down']  = 0;
                }
                if (empty($value['da_id'])) {
                    $res[$key]['is_appeal'] = 0;
                } else {
                    $res[$key]['is_appeal'] = 1;
                }
                unset($res[$key]['da_id']);
                $res[$key]['is_again_ok'] = 0;
                $res[$key]['oss_download'] = '';
                $res[$key]['remark_status'] = '';

                $tender_id_arr[] = $value['tender_id'];
                $new_tender_id_arr[] = $value['new_tender_id'];
            }
            $remark_status = array(0=>'1', 1=>'3', 2=>'2', 3=>'3');
            if ($_POST['status'] == 2 && $new_tender_id_arr) {
                $new_tender_id_str = implode(',' , $new_tender_id_arr);
                $sql = "SELECT tender_id , oss_download , status FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                $contractInfo_res = Yii::app()->db->createCommand($sql)->queryAll();
                if ($contractInfo_res) {
                    foreach ($contractInfo_res as $key => $value) {
                        $contractInfo[$value['tender_id']] = $value;
                    }
                    foreach ($res as $key => $value) {
                        if (!empty($contractInfo[$value['new_tender_id']])) {
                            $res[$key]['oss_download'] = 'http://'.ConfUtil::get('OSS-ccs-yj-dashboard.bucket').".".ConfUtil::get('OSS-ccs-yj.endpoint').DIRECTORY_SEPARATOR.$contractInfo[$value['new_tender_id']]['oss_download'];
                            $res[$key]['remark_status'] = $remark_status[$contractInfo[$value['new_tender_id']]['status']];
                        }
                    }
                }
            }
            if ($_POST['status'] == 3 && $tender_id_arr) {
                $tender_id_str = implode(',' , $tender_id_arr);
                $sql = "SELECT tender_id FROM firstp2p_debt WHERE status IN (1 , 5 , 6) AND tender_id IN ({$tender_id_str})";
                $is_again_ok = Yii::app()->db->createCommand($sql)->queryColumn();
                if (!$is_again_ok) {
                    $is_again_ok = array();
                }
                if ($is_again_ok) {
                    foreach ($res as $key => $value) {
                        if (!in_array($value['tender_id'] , $is_again_ok)) {
                            $res[$key]['is_again_ok'] = 1;
                        }
                    }
                }
                $sql = "SELECT * FROM firstp2p_deal_load WHERE id IN ({$tender_id_str})";
                $deal_load_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($deal_load_res as $key => $value) {
                    $deal_load[$value['id']] = $value;
                }
                if ($deal_load) {
                    foreach ($res as $key => $value) {
                        if ($deal_load[$value['tender_id']]['wait_capital'] > 0 && $deal_load[$value['tender_id']]['debt_status'] == 0 && $deal_load[$value['tender_id']]['is_debt_confirm'] == 1 && $deal_load[$value['tender_id']]['black_status'] == 1) {
                            $res[$key]['is_again_ok'] = 1;
                        } else {
                            $res[$key]['is_again_ok'] = 0;
                        }
                    }
                }
            }
        } else if ($_POST['products'] == 2) {
            $sql = "SELECT count(debt.id) AS count FROM firstp2p_debt AS debt 
                        LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id
                    LEFT JOIN firstp2p_debt_tender AS dt ON debt.id = dt.debt_id {$where_dt} WHERE debt.user_id = {$user_id} AND debt.debt_src = 2 {$where} ";
            $count      = Yii::app()->phdb->createCommand($sql)->queryScalar();
            $page_count = ceil($count / $limit);
            $pass       = ($page - 1) * $limit;
            $sql = "SELECT dt.new_tender_id,debt.id AS debt_id , deal.name , debt.status , debt.money , debt.discount , debt.buy_code , debt.endtime , dt.cancel_time , debt.tender_id , dt.addtime , dt.submit_paytime
                    FROM firstp2p_debt AS debt 
                    LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN firstp2p_debt_tender AS dt ON debt.id = dt.debt_id {$where_dt} WHERE debt.user_id = {$user_id} AND debt.debt_src = 2 {$where} {$order} LIMIT {$pass} , {$limit} ";
            $res = Yii::app()->phdb->createCommand($sql)->queryAll();
            if (!$res) {
                $this->echoJson($result_data , 3114 , $errorcodeinfo[3114]);
            }
            foreach ($res as $key => $value) {
                $res[$key]['products']   = 2;
                if ($value['status'] == 1) {
                    $res[$key]['cancel_time'] = 0;
                    $res[$key]['count_down']  = $value['endtime'] - $time;
                } else if ($value['status'] == 5) {
                    $res[$key]['count_down'] = strval($value['addtime'] + ConfUtil::get('youjie-undertake-endtime') - $time);
                } else if ($value['status'] == 6) {
                    $res[$key]['count_down'] = strval($value['submit_paytime'] + ConfUtil::get('youjie-payment-endtime') - $time);
                } else {
                    $res[$key]['cancel_time'] = 0;
                    $res[$key]['count_down']  = 0;
                }
                $res[$key]['is_again_ok'] = 0;
                $res[$key]['oss_download'] = '';
                $res[$key]['remark_status'] = '';

                $debt_id_arr[] = $value['debt_id'];
                $tender_id_arr[] = $value['tender_id'];
                $new_tender_id_arr[] = $value['new_tender_id'];
            }
            if ($debt_id_arr) {
                $debt_id_str = implode(',' , $debt_id_arr);
                $sql         = "SELECT debt_id FROM ag_wx_debt_appeal WHERE debt_id IN ({$debt_id_str}) AND status = 1 AND products = {$_POST['products']}";
                $da_debt_id  = Yii::app()->db->createCommand($sql)->queryColumn();
                if (!$da_debt_id) {
                    $da_debt_id = array();
                }
            } else {
                $da_debt_id = array();
            }
            $remark_status = array(0=>'1', 1=>'3', 2=>'2', 3=>'3');
            if ($_POST['status'] == 2 && $new_tender_id_arr) {
                $new_tender_id_str = implode(',' , $new_tender_id_arr);
                $sql = "SELECT tender_id , oss_download , status FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                $contractInfo_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($contractInfo_res) {
                    foreach ($contractInfo_res as $key => $value) {
                        $contractInfo[$value['tender_id']] = $value;
                    }
                    foreach ($res as $key => $value) {
                        if (!empty($contractInfo[$value['new_tender_id']])) {
                            $res[$key]['oss_download'] = 'http://'.ConfUtil::get('OSS-ccs-yj-dashboard.bucket').".".ConfUtil::get('OSS-ccs-yj.endpoint').DIRECTORY_SEPARATOR.$contractInfo[$value['new_tender_id']]['oss_download'];
                            $res[$key]['remark_status'] = $remark_status[$contractInfo[$value['new_tender_id']]['status']];
                        }
                    }
                }
            }
            if ($_POST['status'] == 3 && $tender_id_arr) {
                $tender_id_str = implode(',' , $tender_id_arr);
                $sql = "SELECT tender_id FROM firstp2p_debt WHERE status IN (1 , 5 , 6) AND tender_id IN ({$tender_id_str})";
                $is_again_ok = Yii::app()->phdb->createCommand($sql)->queryColumn();
                if (!$is_again_ok) {
                    $is_again_ok = array();
                }
                $sql = "SELECT * FROM firstp2p_deal_load WHERE id IN ({$tender_id_str})";
                $deal_load_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                foreach ($deal_load_res as $key => $value) {
                    $deal_load[$value['id']] = $value;
                }
            } else {
                $is_again_ok = array();
                $deal_load = array();
            }
            foreach ($res as $key => $value) {
                if (in_array($value['debt_id'] , $da_debt_id)) {
                    $res[$key]['is_appeal'] = 1;
                } else {
                    $res[$key]['is_appeal'] = 0;
                }
                if (!in_array($value['tender_id'] , $is_again_ok)) {
                    $res[$key]['is_again_ok'] = 1;
                }
                if ($deal_load) {
                    if ($deal_load[$value['tender_id']]['wait_capital'] > 0 && $deal_load[$value['tender_id']]['debt_status'] == 0 && $deal_load[$value['tender_id']]['is_debt_confirm'] == 1 && $deal_load[$value['tender_id']]['black_status'] == 1) {
                        $res[$key]['is_again_ok'] = 1;
                    } else {
                        $res[$key]['is_again_ok'] = 0;
                    }
                }
            }
        }
        
        $result_data['count']      = $count;
        $result_data['page_count'] = $page_count;
        $result_data['data']       = $res;
        $this->echoJson($result_data , 0 , '查询成功');
    }

    /**
     * 债权转让 - 详情
     */
    public function actionDebtInfo()
    {
        $errorcodeinfo = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3100 , $errorcodeinfo[3100]);
        }
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 3101 , $errorcodeinfo[3101]);
        }
        if (!in_array($_POST['products'] , array(1 , 2))) {
            $this->echoJson(array() , 3109 , $errorcodeinfo[3109]);
        }
        if (empty($_POST['debt_id']) || !is_numeric($_POST['debt_id'])) {
            $this->echoJson(array() , 3115 , $errorcodeinfo[3115]);
        }
        if ($_POST['products'] == 1) {
            $model = Yii::app()->db;
        } else if ($_POST['products'] == 2) {
            $model = Yii::app()->phdb;
        }
        $debt_id = intval($_POST['debt_id']);
        $sql = "SELECT debt.tender_id,debt.id AS debt_id , deal.name , debt.status , debt.money , debt.discount , debt.buy_code , debt.endtime , debt.payee_name , debt.payee_bankzone , debt.payee_bankcard , debt.serial_number , debt.successtime , debt.arrival_amount
                FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id WHERE debt.user_id = {$user_id} AND debt.id = {$debt_id}";
        $result = $model->createCommand($sql)->queryRow();
        if (!$result) {
            $this->echoJson(array() , 3116 , $errorcodeinfo[3116]);
        }
        $sqlAdd = "";
        if($result['status'] == 2 || $result['status'] == 6){
            //交易成功、待付款、待卖方收款
            $sqlAdd = " and dt.status = {$result['status']}";
        }elseif($result['status'] == 3){
            //交易失败
            $sqlAdd = " and dt.status in(3,4,5)";
        }elseif($result['status'] == 5){
            //待付款
            $sqlAdd = " and dt.status = 1";
        }
        $tenderInfo = $model->createCommand("select * from firstp2p_debt_tender as dt where dt.debt_id = $debt_id {$sqlAdd}")->queryRow();
        $time = time();
        //1-转让中，2-交易成功，3-交易取消，4-已过期，5-待买方付款，6-待收款
        if ($result['status'] == 1) {
            //1-转让中
            $result['cancel_time'] = 0;
            $result['count_down']  = $result['endtime'] - $time;
        } else if ($result['status'] == 5) {
            //待付款
            $result['count_down'] = strval($tenderInfo['addtime'] + ConfUtil::get('youjie-undertake-endtime') - $time);
        } else if($result['status'] == 6){
            //待收款确认
            $result['count_down'] = strval($tenderInfo['submit_paytime'] + ConfUtil::get('youjie-payment-endtime') - $time);
        }else{
            $result['cancel_time'] = 0;
            $result['count_down']  = 0;
        }
        //转让中-折后价格
        $arrival_amount = $result['arrival_amount'] > 0 ? $result['arrival_amount'] : round($result['discount'] * $result['money']*0.1, 2);
        $result['products']        = $_POST['products'];
        $result['cancel_time']     = $tenderInfo['cancel_time'];
        $result['payer_name']      = $tenderInfo['payer_name'];
        $result['payer_bankzone']  = $tenderInfo['payer_bankzone'];
        $result['submit_paytime']  = $tenderInfo['submit_paytime'];
        $result['addtime']         = $tenderInfo['addtime'];
        $result['arrival_amount']  = $arrival_amount;
        $result['payment_voucher'] = explode(',' , $tenderInfo['payment_voucher']);
        $result['payee_bankcard']  = GibberishAESUtil::dec($result['payee_bankcard'], Yii::app()->c->idno_key);
        $result['payer_bankcard']  = GibberishAESUtil::dec($tenderInfo['payer_bankcard'], Yii::app()->c->idno_key);
        if ($tenderInfo['payment_voucher']) {
            foreach ($result['payment_voucher'] as $key => $value) {
                if ($tenderInfo['addtime'] <= 1578394800) {
                    $result['payment_voucher'][$key] = 'https://service.zichanhuayuan.com'.DIRECTORY_SEPARATOR.$value;
                } else {
                    $result['payment_voucher'][$key] = Yii::app()->c->itouzi['oss_preview_address'].DIRECTORY_SEPARATOR.$value;
                }
            }
        }
        if ($tenderInfo) {
            $sql = "SELECT * FROM ag_wx_debt_appeal WHERE debt_id = {$result['debt_id']} AND debt_tender_id = {$tenderInfo['id']} AND products = {$_POST['products']}";
            $appeal = Yii::app()->db->createCommand($sql)->queryRow();
        } else {
            $appeal = array();
        }
        if ($appeal) {
            $result['is_appeal']      = 1;
            $result['appeal_addtime'] = $appeal['addtime'];
        } else {
            $result['is_appeal']      = 0;
            $result['appeal_addtime'] = 0;
        }
        $remark_status = array(0=>'1', 1=>'3', 2=>'2', 3=>'3');
        if ($tenderInfo) {
            $contractInfo = $model->createCommand("select tender_id, oss_download, status from firstp2p_contract_task where tender_id = {$tenderInfo['new_tender_id']}")->queryRow();
        } else {
            $contractInfo = array();
        }
        if ($contractInfo) {
            $result['oss_download'] = 'http://'.ConfUtil::get('OSS-ccs-yj-dashboard.bucket').".".ConfUtil::get('OSS-ccs-yj.endpoint').DIRECTORY_SEPARATOR.$contractInfo['oss_download'];
            $result['remark_status'] = $remark_status[$contractInfo['status']];
        } else {
            $result['oss_download'] = '';
            $result['remark_status'] = '';
        }
        $this->echoJson($result , 0 , '查询成功');
    }

    /**
     * 债权转让 - 客服介入
     */
    public function actionDebtCustomerService()
    {
        $errorcodeinfo = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3100 , $errorcodeinfo[3100]);
        }
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 3101 , $errorcodeinfo[3101]);
        }
        if (!in_array($_POST['products'] , array(1 , 2))) {
            $this->echoJson(array() , 3109 , $errorcodeinfo[3109]);
        }
        if (empty($_POST['debt_id']) || !is_numeric($_POST['debt_id'])) {
            $this->echoJson(array() , 3115 , $errorcodeinfo[3115]);
        }
        if (empty($_POST['outaccount'])) {
            $this->echoJson(array() , 3127 , $errorcodeinfo[3127]);
        }
        $outaccount = trim($_POST['outaccount']);
        if ($_POST['products'] == 1) {
            $model = Yii::app()->db;
        } else if ($_POST['products'] == 2) {
            $model = Yii::app()->phdb;
        }
        $debt_id = intval($_POST['debt_id']);
        $sql     = "SELECT * FROM firstp2p_debt WHERE user_id = {$user_id} AND id = {$debt_id}";
        $result  = $model->createCommand($sql)->queryRow();
        if (!$result) {
            $this->echoJson(array() , 3116 , $errorcodeinfo[3116]);
        }
        if ($result['status'] != 6) {
            $this->echoJson(array() , 3117 , $errorcodeinfo[3117]);
        }
        $sql    = "SELECT * FROM ag_wx_debt_appeal WHERE debt_id = {$result['id']} AND status = 1 AND products = {$_POST['products']}";
        $appeal = Yii::app()->db->createCommand($sql)->queryRow();
        if ($appeal) {
            $this->echoJson(array() , 3118 , $errorcodeinfo[3118]);
        }
        $sql = "SELECT * FROM firstp2p_debt_tender WHERE debt_id = {$result['id']} AND status = 6";
        $debt_tender = $model->createCommand($sql)->queryRow();
        if (!$debt_tender) {
            $this->echoJson(array() , 3119 , $errorcodeinfo[3119]);
        }
        $time = time();
        $ip   = Yii::app()->request->userHostAddress;
        $sql  = "INSERT INTO ag_wx_debt_appeal (products , debt_id , debt_tender_id , type , status , addtime , addip , decision_outaccount) VALUES ({$_POST['products']} , {$result['id']} , {$debt_tender['id']} , 2 , 1 , {$time} , '{$ip}' , '{$outaccount}') ";
        $res  = Yii::app()->db->createCommand($sql)->execute();
        if (!$res) {
            $this->echoJson(array() , 3120 , $errorcodeinfo[3120]);
        }

        $this->echoJson(array() , 0 , '申请客服介入成功');
    }
}
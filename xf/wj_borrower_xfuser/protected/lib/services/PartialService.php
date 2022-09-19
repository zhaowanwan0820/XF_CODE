<?php
class PartialService extends ItzInstanceService
{
    private $amount_error = 2; // 误差金额
    private $insert_limit = 1000; // 添加数据限制

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 部分还款 添加
     */
    public function add_partial_repayment($pay_user , $pay_plan_time , $template_url , $proof_url , $data)
    {
        $result = array('code'=>1000 , 'info'=>'');
        $time   = time();
        $model  = Yii::app()->fdb;
        foreach ($data as $key => $value) {
            if (empty($value[0])) {
                $result['info'] = '第'.($key+1).'行缺少借款标题';
                return $result;
            }
            if (empty($value[1])) {
                $result['info'] = '第'.($key+1).'行缺少投资记录ID';
                return $result;
            }
            if (empty($value[2])) {
                $result['info'] = '第'.($key+1).'行缺少用户ID';
                return $result;
            }
            if (empty($value[3])) {
                $result['info'] = '第'.($key+1).'行缺少还款金额';
                return $result;
            }
            if (!is_numeric($value[1])) {
                $result['info'] = '第'.($key+1).'行投资记录ID格式错误';
                return $result;
            }
            if (!is_numeric($value[2])) {
                $result['info'] = '第'.($key+1).'行用户ID格式错误';
                return $result;
            }
            if (!is_numeric($value[3])) {
                $result['info'] = '第'.($key+1).'行还款金额格式错误';
                return $result;
            }
            if ($value[3] <= 0) {
                $result['info'] = '第'.($key+1).'行还款金额输入错误，应为正数';
                return $result;
            }
            $deal_name_arr[$key]    = trim($value[0]);
            $deal_load_id_arr[$key] = intval($value[1]);
            $user_id_arr[$key]      = intval($value[2]);
            $money_arr[$key]        = $value[3];
        }
        $deal_name_str    = "'".implode("','" , $deal_name_arr)."'";
        $deal_load_id_str = implode(',' , $deal_load_id_arr);
        $user_id_str      = implode(',' , $user_id_arr);

        // 查询借款项目
        $deal_info   = array();
        $deal_id_arr = array();
        $sql         = "SELECT id , name , is_effect , is_delete , deal_status FROM firstp2p_deal WHERE name IN ({$deal_name_str})";
        $deal_res    = $model->createCommand($sql)->queryAll();
        if (empty($deal_res)) {
            $result['info'] = '未查询到任何借款项目';
            return $result;
        }
        foreach ($deal_res as $key => $value) {
            if ($value['is_effect'] != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目无效';
            } else if ($value['is_delete'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目已被删除';
            } else if ($value['deal_status'] != 4) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目未处于还款中';
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';

                $deal_id_arr[] = $value['id'];
            }
            $deal_info[$value['name']] = $value;
        }
        $deal_id_str = implode(',' , $deal_id_arr);
        if (empty($deal_id_str)) {
            $result['info'] = '所有借款项目无效';
            return $result;
        }
        unset($deal_res);

        // 查询投资记录
        $deal_load_info = array();
        $sql            = "SELECT id , deal_id , user_id , wait_capital , is_repay , debt_status FROM firstp2p_deal_load WHERE id IN ({$deal_load_id_str})";
        $deal_load_res  = $model->createCommand($sql)->queryAll();
        if (empty($deal_load_res)) {
            $result['info'] = '未查询到任何投资记录';
            return $result;
        }
        // 查询进行中的债权兑换记录
        $sql = "SELECT tender_id FROM firstp2p_debt_exchange_log WHERE tender_id IN ({$deal_load_id_str}) AND status = 1";
        $debt_exchange_log = $model->createCommand($sql)->queryColumn();
        if (!$debt_exchange_log) {
            $debt_exchange_log = array();
        }
        // 查询进行中的线下还款记录
        $repayment_plan_info    = array();
        $loan_user_id_arr       = array();
        $repayment_plan_deal_id = array();
        $sql = "SELECT deal_loan_id , loan_user_id , repay_type , deal_id FROM ag_wx_repayment_plan WHERE deal_id IN ({$deal_id_str}) AND status IN (0 , 1 , 2)";
        $repayment_plan_res = $model->createCommand($sql)->queryAll();
        if ($repayment_plan_res) {
            foreach ($repayment_plan_res as $key => $value) {
                if ($value['repay_type'] == 1) {
                    // 常规还款
                    $repayment_plan_deal_id[] = $value['deal_id'];
                } else if ($value['repay_type'] == 2) {
                    // 特殊还款
                    if (!empty($value['deal_loan_id'])) {
                        $temp = explode(',' , $value['deal_loan_id']);
                        foreach ($temp as $k => $v) {
                            if ($v) {
                                $repayment_plan_info[$v] = $v;
                            }
                        }
                    }
                    if (!empty($value['loan_user_id'])) {
                        $temp_a = explode(',' , $value['loan_user_id']);
                        foreach ($temp_a as $i => $j) {
                            if ($j) {
                                $loan_user_id_arr[$j] = $j;
                            }
                        }
                    }
                }
            }
            // 特殊还款
            if (!empty($loan_user_id_arr)) {
                $loan_user_id_str = implode(',' , $loan_user_id_arr);
                $sql = "SELECT id FROM firstp2p_deal_load WHERE user_id IN ({$loan_user_id_str})";
                $loan_user_id_deal_loan_id = $model->createCommand($sql)->queryColumn();
                if ($loan_user_id_deal_loan_id) {
                    foreach ($loan_user_id_deal_loan_id as $key => $value) {
                        $repayment_plan_info[$value] = $value;
                    }
                }
            }
        }
        // 查询进行中的部分还款记录
        $sql = "SELECT prd.deal_loan_id FROM ag_wx_partial_repayment AS pr INNER JOIN ag_wx_partial_repay_detail AS prd ON pr.id = prd.partial_repay_id AND pr.status IN (1 , 2) AND prd.deal_loan_id IN ({$deal_load_id_str}) AND prd.status = 1";
        $partial_repayment_info = $model->createCommand($sql)->queryColumn();
        if (!$partial_repayment_info) {
            $partial_repayment_info = array();
        }
        $debt_tender_id = array();
        foreach ($deal_load_res as $key => $value) {
            if ($value['is_repay'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录已流标';
            } else if (in_array($value['id'] , $debt_exchange_log)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录处于债权兑换中';
            } else if (in_array($value['id'] , $repayment_plan_info)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录已经处于计划还款中或计划还款审核中';
            } else if (in_array($value['id'] , $partial_repayment_info)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录处于部分还款中';
            } else if (bccomp($value['wait_capital'] , 0 , 2) != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录的待还本金未大于0';
            } else if ($value['debt_status'] != 0) {
                if ($value['debt_status'] == 1) {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录正处于债转中';

                    $debt_tender_id[] = $value['id'];
                } else if ($value['debt_status'] == 15) {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录已全部债转';
                } else {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录债转状态错误';
                }
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';
            }
            $deal_load_info[$value['id']] = $value;
        }
        if ($debt_tender_id) {
            $debt_tender_id_str = implode(',' , $debt_tender_id);
            $sql    = "SELECT id , tender_id , user_id FROM firstp2p_debt WHERE tender_id IN ({$debt_tender_id_str}) AND status = 1";
            $debt_1 = $model->createCommand($sql)->queryAll();
            if ($debt_1) {
                $url = Yii::app()->c->wx_confirm_debt_api;
                foreach ($debt_1 as $key => $value) {
                    $params = array(
                        'debt_id'       => $value['id'],
                        'products'      => 1,
                        'checkuser'     => 2
                    );
                    $CancelDebt = $this->curlRequest($url.'/Launch/DebtGarden/CancelDebt' , 'POST' , $params);
                    if ($CancelDebt['code'] == 0) {
                        $deal_load_info[$value['tender_id']]['check_status'] = 1;
                        $deal_load_info[$value['tender_id']]['remark']       = '发布中的债转已被取消';
                    } else {
                        $deal_load_info[$value['tender_id']]['remark'] .= "发布中的债转取消失败，原因：{$CancelDebt['info']}";
                    }
                }
            }
        }
        unset($deal_load_res);

        // 查询用户
        $user_info = array();
        $sql       = "SELECT id , is_effect , is_delete FROM firstp2p_user WHERE id IN ({$user_id_str})";
        $user_res  = $model->createCommand($sql)->queryAll();
        if (empty($user_res)) {
            $result['info'] = '未查询到任何用户';
            return $result;
        }
        foreach ($user_res as $key => $value) {
            if ($value['is_effect'] != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此用户无效';
            } else if ($value['is_delete'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此用户已被删除';
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';
            }
            $user_info[$value['id']] = $value;
        }
        unset($user_res);

        // 查询还款计划
        $deal_loan_repay_info = array();
        $sql = "SELECT id , deal_id , deal_loan_id , loan_user_id , money , time FROM firstp2p_deal_loan_repay WHERE deal_loan_id IN ({$deal_load_id_str}) AND money > 0 AND type = 1 AND status = 0 ";
        $deal_loan_repay_res = $model->createCommand($sql)->queryAll();
        if (empty($deal_loan_repay_res)) {
            $result['info'] = '未查询到任何还款计划';
            return $result;
        }
        foreach ($deal_loan_repay_res as $key => $value) {
            if (!empty($deal_loan_repay_info[$value['deal_loan_id']])) {
                $deal_loan_repay_info[$value['deal_loan_id']]['total_money'] = bcadd($deal_loan_repay_info[$value['deal_loan_id']]['total_money'] , $value['money'] , 10);
                if ($value['time'] > $deal_loan_repay_info[$value['deal_loan_id']]['end_time']) {
                    $deal_loan_repay_info[$value['deal_loan_id']]['end_time'] = $value['time'];
                }
            } else {
                $deal_loan_repay_info[$value['deal_loan_id']] = array(
                    'id'           => $value['id'],
                    'deal_id'      => $value['deal_id'],
                    'deal_loan_id' => $value['deal_loan_id'],
                    'loan_user_id' => $value['loan_user_id'],
                    'total_money'  => $value['money'],
                    'end_time'     => $value['time']
                );
            }
        }
        unset($deal_loan_repay_res);

        // 查询咨询方的借款项目
        $adminUserInfo = \Yii::app()->user->getState('_user');
        if($adminUserInfo['user_type'] == 2){
            $deallist = $model->createCommand("SELECT firstp2p_deal.id deal_id FROM firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' AND firstp2p_deal_agency.is_effect = 1 AND firstp2p_deal.id > 0")->queryColumn();
            if(!empty($deallist)){
                $admin_deal_id_arr = $deallist;
            }else{
                $admin_deal_id_arr = array('no');
            }
        } else {
            $admin_deal_id_arr = array();
        }

        // 校验数据
        $total_repayment         = '0.00';
        $total_successful_amount = '0.00';
        $total_fail_amount       = '0.00';
        $success_number          = 0;
        $fail_number             = 0;
        $is_repeat               = array();
        foreach ($data as $key => $value) {
            $temp                     = array();
            $temp['partial_repay_id'] = 0;
            $temp['name']             = $value[0];
            $temp['end_time']         = 0;
            $temp['deal_loan_id']     = $value[1];
            $temp['user_id']          = $value[2];
            $temp['repay_money']      = $value[3];
            $temp['deal_id']          = 0;
            $temp['status']           = 0;
            $temp['remark']           = '';
            $temp['addtime']          = $time;
            if (empty($deal_info[$value[0]])) {
                $temp['status'] = 2;
                $temp['remark'] = '借款标题输入错误，未查询到借款项目';
            } else if (empty($deal_load_info[$value[1]])) {
                $temp['status'] = 2;
                $temp['remark'] = '投资记录ID输入错误，未查询到投资记录';
            } else if (empty($user_info[$value[2]])) {
                $temp['status'] = 2;
                $temp['remark'] = '用户ID输入错误，未查询到用户';
            } else if (empty($deal_loan_repay_info[$value[1]])) {
                $temp['status'] = 2;
                $temp['remark'] = '通过此投资记录ID未查询到有效还款计划';
            } else if ($deal_info[$value[0]]['id'] != $deal_load_info[$value[1]]['deal_id']) {
                $temp['status'] = 2;
                $temp['remark'] = '借款标题与投资记录ID不匹配';
            } else if ($deal_load_info[$value[1]]['user_id'] != $user_info[$value[2]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '投资记录ID与用户ID不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['deal_id'] != $deal_info[$value[0]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与借款标题不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['deal_loan_id'] != $deal_load_info[$value[1]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与投资记录ID不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['loan_user_id'] != $user_info[$value[2]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与用户ID不匹配';
            } else if ($deal_info[$value[0]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $deal_info[$value[0]]['remark'];
            } else if ($deal_load_info[$value[1]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $deal_load_info[$value[1]]['remark'];
            } else if ($user_info[$value[2]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $user_info[$value[2]]['remark'];
            } else if (in_array($deal_info[$value[0]]['id'] , $repayment_plan_deal_id)) {
                $temp['status'] = 2;
                $temp['remark'] = '此借款项目已经处于计划还款中或计划还款审核中';
            } else if (bccomp($deal_load_info[$value[1]]['wait_capital'] , $deal_loan_repay_info[$value[1]]['total_money'] , 2) != 0) {
                $temp['status'] = 2;
                $temp['remark'] = "此投资记录的待还本金(￥{$deal_load_info[$value[1]]['wait_capital']})与还款计划的总待还本金(￥{$deal_loan_repay_info[$value[1]]['total_money']})不一致";
            } else if (bccomp($value[3] , $deal_load_info[$value[1]]['wait_capital'] , 2) == 1) {
                $temp['status'] = 2;
                $temp['remark'] = '还款金额输入错误，不可大于此投资记录的待还本金';
            } else if (in_array($value[1] , $is_repeat)) {
                $temp['status'] = 2;
                $temp['remark'] = '此投资记录ID已在文件中存在';
            } else if (!empty($admin_deal_id_arr) && !in_array($deal_info[$value[0]]['id'] , $admin_deal_id_arr)) {
                $result['info'] = '录入人账号类型为咨询方类型，并且存在借款项目不属于此录入人';
                return $result;
            } else {
                $temp['status']   = 1;
                $temp['end_time'] = $deal_loan_repay_info[$value[1]]['end_time'];
            }
            if ($deal_load_info[$value[1]]['deal_id']) {
                $temp['deal_id']  = $deal_load_info[$value[1]]['deal_id'];
            }
            $total_repayment = bcadd($total_repayment , $value[3] , 10);
            if ($temp['status'] == 1) {
                $success_number++;
                $total_successful_amount = bcadd($total_successful_amount , $value[3] , 10);
            } else if ($temp['status'] == 2) {
                $fail_number++;
                $total_fail_amount = bcadd($total_fail_amount , $value[3] , 10);
            }
            $is_repeat[] = $value[1];
            $new_data[]  = $temp;
        }
        $admin_user_id = Yii::app()->user->id;
        $admin_user_id = $admin_user_id ? $admin_user_id : 0 ;
        $status        = 1;
        $remark        = '';
        $addtime       = $time;
        $updatetime    = $time;

        $model->beginTransaction();

        if (abs(bcsub($total_repayment - bcadd($total_successful_amount , $total_fail_amount , 10) , 10)) < $this->amount_error) {
            $total_repayment = bcadd($total_successful_amount , $total_fail_amount , 10);
        }

        $sql = "INSERT INTO ag_wx_partial_repayment (total_repayment , total_successful_amount , total_fail_amount , success_number , fail_number , admin_user_id , pay_user , pay_plan_time , status , remark , template_url , proof_url , addtime , updatetime) VALUES({$total_repayment} , {$total_successful_amount} , {$total_fail_amount} , {$success_number} , {$fail_number} , {$admin_user_id} , '{$pay_user}' , {$pay_plan_time} , {$status} , '{$remark}' , '{$template_url}' , '{$proof_url}' , {$addtime} , {$updatetime}) ";
        $add_partial_repayment = $model->createCommand($sql)->execute();
        $partial_repay_id      = $model->getLastInsertID();

        $add_data_arr = array();
        $i = 0;
        foreach ($new_data as $key => $value) {
            if (count($add_data_arr[$i]) >= $this->insert_limit) {
                $i++;
            }
            $add_data_arr[$i][] = "({$partial_repay_id} , '{$value['name']}' , {$value['end_time']} , {$value['deal_loan_id']} , {$value['user_id']} , {$value['repay_money']} , {$value['deal_id']} , {$value['status']} , '{$value['remark']}' , {$value['addtime']})";
        }
        $add_partial_repay_detail_status = true;
        foreach ($add_data_arr as $key => $value) {
            $add_data_str = implode(',' , $value);
            $sql = "INSERT INTO ag_wx_partial_repay_detail (partial_repay_id , name , end_time , deal_loan_id , user_id , repay_money , deal_id , status , remark , addtime) VALUES {$add_data_str}";
            $add_partial_repay_detail = $model->createCommand($sql)->execute();
            if (!$add_partial_repay_detail) {
                $add_partial_repay_detail_status = false;
            }
        }
        $sql = "SELECT sum(repay_money) AS repay_money , status FROM ag_wx_partial_repay_detail WHERE partial_repay_id = {$partial_repay_id} GROUP BY status";
        $check_res = $model->createCommand($sql)->queryAll();
        $check_data['1'] = 0;
        $check_data['2'] = 0;
        foreach ($check_res as $key => $value) {
            $check_data[$value['status']] = $value['repay_money'];
        }
        $check_total = $check_data['1'] + $check_data['2'];
        $sql = "SELECT total_repayment , total_successful_amount , total_fail_amount FROM ag_wx_partial_repayment WHERE id = {$partial_repay_id}";
        $check = $model->createCommand($sql)->queryRow();
        if ($check['total_repayment'] != $check_total || $check['total_successful_amount'] != $check_data['1'] || $check['total_fail_amount'] != $check_data['2']) {
            if (abs(bcsub($check['total_repayment'] , $check_total , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入总金额错误';
                return $result;
            }
            if (abs(bcsub($check['total_successful_amount'] , $check_data['1'] , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入成功金额错误';
                return $result;
            }
            if (abs(bcsub($check['total_fail_amount'] , $check_data['2'] , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入失败金额错误';
                return $result;
            }
            $sql = "UPDATE ag_wx_partial_repayment SET total_repayment = {$check_total} , total_successful_amount = {$check_data['1']} , total_fail_amount = {$check_data['2']} WHERE id = {$partial_repay_id}";
            $update = $model->createCommand($sql)->execute();
        }

        if (!$add_partial_repayment || !$add_partial_repay_detail_status) {
            $model->rollback();
            $result['info'] = '录入失败';
            return $result;
        }
        $model->commit();
        $result['code'] = 0;
        $result['info'] = '录入成功';
        return $result;
    }

    private function curlRequest($api, $method = 'GET', $params = array(), $headers = [], $json_decode = true)
    {
        $curl = curl_init();
        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($params)) {
                    $api .= (strpos($api, '?') ? '&' : '?') . http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                if(is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $api);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if(isset($_SERVER['HTTP_USER_AGENT'])){
            curl_setopt($curl,CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }

        $response = curl_exec($curl);
        if ($response === false) {
            curl_close($curl);
            return false;
        } else {
            // 解决windows 服务器 BOM 问题
            $response = trim($response, chr(239).chr(187).chr(191));
            if ($json_decode) {
                $response = json_decode($response, true);
            }
        }
        curl_close($curl);
        return $response;
    }

    /**
     * 部分还款 添加
     */
    public function add_PH_partial_repayment($pay_user , $pay_plan_time , $template_url , $proof_url , $data , $is_clear=1)
    {
        $result = array('code'=>1000 , 'info'=>'');
        $time   = time();
        $model  = Yii::app()->phdb;
        foreach ($data as $key => $value) {
            if (empty($value[0])) {
                $result['info'] = '第'.($key+1).'行缺少借款标题';
                return $result;
            }
            if (empty($value[1])) {
                $result['info'] = '第'.($key+1).'行缺少投资记录ID';
                return $result;
            }
            if (empty($value[2])) {
                $result['info'] = '第'.($key+1).'行缺少用户ID';
                return $result;
            }
            if (empty($value[3])) {
                $result['info'] = '第'.($key+1).'行缺少还款金额';
                return $result;
            }
            if (!is_numeric($value[1])) {
                $result['info'] = '第'.($key+1).'行投资记录ID格式错误';
                return $result;
            }
            if (!is_numeric($value[2])) {
                $result['info'] = '第'.($key+1).'行用户ID格式错误';
                return $result;
            }
            if (!is_numeric($value[3])) {
                $result['info'] = '第'.($key+1).'行还款金额格式错误';
                return $result;
            }
            if ($value[3] <= 0) {
                $result['info'] = '第'.($key+1).'行还款金额输入错误，应为正数';
                return $result;
            }
            $deal_name_arr[$key]    = trim($value[0]);
            $deal_load_id_arr[$key] = intval($value[1]);
            $user_id_arr[$key]      = intval($value[2]);
            $money_arr[$key]        = $value[3];
        }
        $deal_name_str    = "'".implode("','" , $deal_name_arr)."'";
        $deal_load_id_str = implode(',' , $deal_load_id_arr);
        $user_id_str      = implode(',' , $user_id_arr);

        // 查询借款项目
        $deal_info   = array();
        $deal_id_arr = array();
        $sql         = "SELECT id , name , is_effect , is_delete , deal_status FROM firstp2p_deal WHERE name IN ({$deal_name_str})";
        $deal_res    = $model->createCommand($sql)->queryAll();
        if (empty($deal_res)) {
            $result['info'] = '未查询到任何借款项目';
            return $result;
        }
        foreach ($deal_res as $key => $value) {
            if ($value['is_effect'] != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目无效';
            } else if ($value['is_delete'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目已被删除';
            } else if ($value['deal_status'] != 4) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目未处于还款中';
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';

                $deal_id_arr[] = $value['id'];
            }
            $deal_info[$value['name']] = $value;
        }
        $deal_id_str = implode(',' , $deal_id_arr);
        if (empty($deal_id_str)) {
            $result['info'] = '所有借款项目无效';
            return $result;
        }
        unset($deal_res);

        // 查询投资记录
        $deal_load_info = array();
        $sql            = "SELECT id , deal_id , user_id , wait_capital , is_repay , debt_status FROM firstp2p_deal_load WHERE id IN ({$deal_load_id_str})";
        $deal_load_res  = $model->createCommand($sql)->queryAll();
        if (empty($deal_load_res)) {
            $result['info'] = '未查询到任何投资记录';
            return $result;
        }
        // 查询进行中的债权兑换记录
        $sql = "SELECT tender_id FROM firstp2p_debt_exchange_log WHERE tender_id IN ({$deal_load_id_str}) AND status = 1";
        $debt_exchange_log = $model->createCommand($sql)->queryColumn();
        if (!$debt_exchange_log) {
            $debt_exchange_log = array();
        }
        // 查询进行中的线下还款记录
        $repayment_plan_info    = array();
        $loan_user_id_arr       = array();
        $repayment_plan_deal_id = array();
        $sql = "SELECT deal_loan_id , loan_user_id , repay_type , deal_id FROM ag_wx_repayment_plan WHERE deal_id IN ({$deal_id_str}) AND status IN (0 , 1 , 2)";
        $repayment_plan_res = $model->createCommand($sql)->queryAll();
        if ($repayment_plan_res) {
            foreach ($repayment_plan_res as $key => $value) {
                if ($value['repay_type'] == 1) {
                    // 常规还款
                    $repayment_plan_deal_id[] = $value['deal_id'];
                } else if ($value['repay_type'] == 2) {
                    // 特殊还款
                    if (!empty($value['deal_loan_id'])) {
                        $temp = explode(',' , $value['deal_loan_id']);
                        foreach ($temp as $k => $v) {
                            if ($v) {
                                $repayment_plan_info[$v] = $v;
                            }
                        }
                    }
                    if (!empty($value['loan_user_id'])) {
                        $temp_a = explode(',' , $value['loan_user_id']);
                        foreach ($temp_a as $i => $j) {
                            if ($j) {
                                $loan_user_id_arr[$j] = $j;
                            }
                        }
                    }
                }
            }
            // 特殊还款
            if (!empty($loan_user_id_arr)) {
                $loan_user_id_str = implode(',' , $loan_user_id_arr);
                $sql = "SELECT id FROM firstp2p_deal_load WHERE user_id IN ({$loan_user_id_str})";
                $loan_user_id_deal_loan_id = $model->createCommand($sql)->queryColumn();
                if ($loan_user_id_deal_loan_id) {
                    foreach ($loan_user_id_deal_loan_id as $key => $value) {
                        $repayment_plan_info[$value] = $value;
                    }
                }
            }
        }
        // 查询进行中的部分还款记录
        $sql = "SELECT prd.deal_loan_id FROM ag_wx_partial_repayment AS pr INNER JOIN ag_wx_partial_repay_detail AS prd ON pr.id = prd.partial_repay_id AND pr.status IN (1 , 2) AND prd.deal_loan_id IN ({$deal_load_id_str}) AND prd.status = 1";
        $partial_repayment_info = $model->createCommand($sql)->queryColumn();
        if (!$partial_repayment_info) {
            $partial_repayment_info = array();
        }
        $debt_tender_id = array();
        foreach ($deal_load_res as $key => $value) {
            if ($value['is_repay'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录已流标';
            } else if (in_array($value['id'] , $debt_exchange_log)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录处于债权兑换中';
            } else if (in_array($value['id'] , $repayment_plan_info)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录已经处于计划还款中或计划还款审核中';
            } else if (in_array($value['id'] , $partial_repayment_info)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录处于部分还款中';
            } else if (bccomp($value['wait_capital'] , 0 , 2) != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录的待还本金未大于0';
            } else if ($value['debt_status'] != 0) {
                if ($value['debt_status'] == 1) {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录正处于债转中';

                    $debt_tender_id[] = $value['id'];
                } else if ($value['debt_status'] == 15) {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录已全部债转';
                } else {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录债转状态错误';
                }
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';
            }
            $deal_load_info[$value['id']] = $value;
        }
        if ($debt_tender_id) {
            $debt_tender_id_str = implode(',' , $debt_tender_id);
            $sql    = "SELECT id , tender_id , user_id FROM firstp2p_debt WHERE tender_id IN ({$debt_tender_id_str}) AND status = 1";
            $debt_1 = $model->createCommand($sql)->queryAll();
            if ($debt_1) {
                $url = Yii::app()->c->wx_confirm_debt_api;
                foreach ($debt_1 as $key => $value) {
                    $params = array(
                        'debt_id'       => $value['id'],
                        'products'      => 2,
                        'checkuser'     => 2
                    );
                    $CancelDebt = $this->curlRequest($url.'/Launch/DebtGarden/CancelDebt' , 'POST' , $params);
                    if ($CancelDebt['code'] == 0) {
                        $deal_load_info[$value['tender_id']]['check_status'] = 1;
                        $deal_load_info[$value['tender_id']]['remark']       = '发布中的债转已被取消';
                    } else {
                        $deal_load_info[$value['tender_id']]['remark'] .= "发布中的债转取消失败，原因：{$CancelDebt['info']}";
                    }
                }
            }
        }
        unset($deal_load_res);

        // 查询用户
        $user_info = array();
        $sql       = "SELECT id , is_effect , is_delete FROM firstp2p_user WHERE id IN ({$user_id_str})";
        $user_res  = Yii::app()->fdb->createCommand($sql)->queryAll();
        if (empty($user_res)) {
            $result['info'] = '未查询到任何用户';
            return $result;
        }
        foreach ($user_res as $key => $value) {
            if ($value['is_effect'] != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此用户无效';
            } else if ($value['is_delete'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此用户已被删除';
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';
            }
            $user_info[$value['id']] = $value;
        }
        unset($user_res);

        // 查询还款计划
        $deal_loan_repay_info = array();
        $sql = "SELECT id , deal_id , deal_loan_id , loan_user_id , money , time FROM firstp2p_deal_loan_repay WHERE deal_loan_id IN ({$deal_load_id_str}) AND money > 0 AND type = 1 AND status = 0 ";
        $deal_loan_repay_res = $model->createCommand($sql)->queryAll();
        if (empty($deal_loan_repay_res)) {
            $result['info'] = '未查询到任何还款计划';
            return $result;
        }
        foreach ($deal_loan_repay_res as $key => $value) {
            if (!empty($deal_loan_repay_info[$value['deal_loan_id']])) {
                $deal_loan_repay_info[$value['deal_loan_id']]['total_money'] = bcadd($deal_loan_repay_info[$value['deal_loan_id']]['total_money'] , $value['money'] , 10);
                if ($value['time'] > $deal_loan_repay_info[$value['deal_loan_id']]['end_time']) {
                    $deal_loan_repay_info[$value['deal_loan_id']]['end_time'] = $value['time'];
                }
            } else {
                $deal_loan_repay_info[$value['deal_loan_id']] = array(
                    'id'           => $value['id'],
                    'deal_id'      => $value['deal_id'],
                    'deal_loan_id' => $value['deal_loan_id'],
                    'loan_user_id' => $value['loan_user_id'],
                    'total_money'  => $value['money'],
                    'end_time'     => $value['time']
                );
            }
        }
        unset($deal_loan_repay_res);

        // 查询咨询方的借款项目
        $adminUserInfo = \Yii::app()->user->getState('_user');
        if($adminUserInfo['user_type'] == 2){
            $deallist = Yii::app()->phdb->createCommand("SELECT firstp2p_deal.id deal_id FROM firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' AND firstp2p_deal_agency.is_effect = 1 AND firstp2p_deal.id > 0")->queryColumn();
            if(!empty($deallist)){
                $admin_deal_id_arr = $deallist;
            }else{
                $admin_deal_id_arr = array('no');
            }
        } else {
            $admin_deal_id_arr = array();
        }

        // 校验数据
        $total_repayment         = '0.00';
        $total_successful_amount = '0.00';
        $total_fail_amount       = '0.00';
        $success_number          = 0;
        $fail_number             = 0;
        $is_repeat               = array();
        foreach ($data as $key => $value) {
            $temp                     = array();
            $temp['partial_repay_id'] = 0;
            $temp['name']             = $value[0];
            $temp['end_time']         = 0;
            $temp['deal_loan_id']     = $value[1];
            $temp['user_id']          = $value[2];
            $temp['repay_money']      = $value[3];
            $temp['deal_id']          = 0;
            $temp['status']           = 0;
            $temp['remark']           = '';
            $temp['addtime']          = $time;
            if (empty($deal_info[$value[0]])) {
                $temp['status'] = 2;
                $temp['remark'] = '借款标题输入错误，未查询到借款项目';
            } else if (empty($deal_load_info[$value[1]])) {
                $temp['status'] = 2;
                $temp['remark'] = '投资记录ID输入错误，未查询到投资记录';
            } else if (empty($user_info[$value[2]])) {
                $temp['status'] = 2;
                $temp['remark'] = '用户ID输入错误，未查询到用户';
            } else if (empty($deal_loan_repay_info[$value[1]])) {
                $temp['status'] = 2;
                $temp['remark'] = '通过此投资记录ID未查询到有效还款计划';
            } else if ($deal_info[$value[0]]['id'] != $deal_load_info[$value[1]]['deal_id']) {
                $temp['status'] = 2;
                $temp['remark'] = '借款标题与投资记录ID不匹配';
            } else if ($deal_load_info[$value[1]]['user_id'] != $user_info[$value[2]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '投资记录ID与用户ID不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['deal_id'] != $deal_info[$value[0]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与借款标题不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['deal_loan_id'] != $deal_load_info[$value[1]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与投资记录ID不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['loan_user_id'] != $user_info[$value[2]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与用户ID不匹配';
            } else if ($deal_info[$value[0]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $deal_info[$value[0]]['remark'];
            } else if ($deal_load_info[$value[1]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $deal_load_info[$value[1]]['remark'];
            } else if ($user_info[$value[2]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $user_info[$value[2]]['remark'];
            } else if (in_array($deal_info[$value[0]]['id'] , $repayment_plan_deal_id)) {
                $temp['status'] = 2;
                $temp['remark'] = '此借款项目已经处于计划还款中或计划还款审核中';
            } else if (bccomp($deal_load_info[$value[1]]['wait_capital'] , $deal_loan_repay_info[$value[1]]['total_money'] , 2) != 0) {
                $temp['status'] = 2;
                $temp['remark'] = "此投资记录的待还本金(￥{$deal_load_info[$value[1]]['wait_capital']})与还款计划的总待还本金(￥{$deal_loan_repay_info[$value[1]]['total_money']})不一致";
            } else if (bccomp($value[3] , $deal_load_info[$value[1]]['wait_capital'] , 2) == 1 && $is_clear == 1) {
                $temp['status'] = 2;
                $temp['remark'] = '还款金额输入错误，不可大于此投资记录的待还本金';
            } else if (in_array($value[1] , $is_repeat)) {
                $temp['status'] = 2;
                $temp['remark'] = '此投资记录ID已在文件中存在';
            } else if (!empty($admin_deal_id_arr) && !in_array($deal_info[$value[0]]['id'] , $admin_deal_id_arr)) {
                $result['info'] = '录入人账号类型为咨询方类型，并且存在借款项目不属于此录入人';
                return $result;
            } else {
                $temp['status']   = 1;
                $temp['end_time'] = $deal_loan_repay_info[$value[1]]['end_time'];
            }
            if ($deal_load_info[$value[1]]['deal_id']) {
                $temp['deal_id']  = $deal_load_info[$value[1]]['deal_id'];
            }
            $total_repayment = bcadd($total_repayment , $value[3] , 10);
            if ($temp['status'] == 1) {
                $success_number++;
                $total_successful_amount = bcadd($total_successful_amount , $value[3] , 10);
            } else if ($temp['status'] == 2) {
                $fail_number++;
                $total_fail_amount = bcadd($total_fail_amount , $value[3] , 10);
            }
            $is_repeat[] = $value[1];
            $new_data[]  = $temp;
        }
        $admin_user_id = Yii::app()->user->id;
        $admin_user_id = $admin_user_id ? $admin_user_id : 0 ;
        $status        = 1;
        $remark        = '';
        $addtime       = $time;
        $updatetime    = $time;

        $model->beginTransaction();

        if (abs(bcsub($total_repayment - bcadd($total_successful_amount , $total_fail_amount , 10) , 10)) < $this->amount_error) {
            $total_repayment = bcadd($total_successful_amount , $total_fail_amount , 10);
        }

        $sql = "INSERT INTO ag_wx_partial_repayment (total_repayment , total_successful_amount , total_fail_amount , success_number , fail_number , admin_user_id , pay_user , pay_plan_time , status , remark , template_url , proof_url , addtime , updatetime) VALUES({$total_repayment} , {$total_successful_amount} , {$total_fail_amount} , {$success_number} , {$fail_number} , {$admin_user_id} , '{$pay_user}' , {$pay_plan_time} , {$status} , '{$remark}' , '{$template_url}' , '{$proof_url}' , {$addtime} , {$updatetime}) ";
        $add_partial_repayment = $model->createCommand($sql)->execute();
        $partial_repay_id      = $model->getLastInsertID();

        $add_data_arr = array();
        $i = 0;
        foreach ($new_data as $key => $value) {
            if (count($add_data_arr[$i]) >= $this->insert_limit) {
                $i++;
            }
            $add_data_arr[$i][] = "({$partial_repay_id} , '{$value['name']}' , {$value['end_time']} , {$value['deal_loan_id']} , {$value['user_id']} , {$value['repay_money']} , {$value['deal_id']} , {$value['status']} , '{$value['remark']}' , {$value['addtime']})";
        }
        $add_partial_repay_detail_status = true;
        foreach ($add_data_arr as $key => $value) {
            $add_data_str = implode(',' , $value);
            $sql = "INSERT INTO ag_wx_partial_repay_detail (partial_repay_id , name , end_time , deal_loan_id , user_id , repay_money , deal_id , status , remark , addtime) VALUES {$add_data_str}";
            $add_partial_repay_detail = $model->createCommand($sql)->execute();
            if (!$add_partial_repay_detail) {
                $add_partial_repay_detail_status = false;
            }
        }
        $sql = "SELECT sum(repay_money) AS repay_money , status FROM ag_wx_partial_repay_detail WHERE partial_repay_id = {$partial_repay_id} GROUP BY status";
        $check_res = $model->createCommand($sql)->queryAll();
        $check_data['1'] = 0;
        $check_data['2'] = 0;
        foreach ($check_res as $key => $value) {
            $check_data[$value['status']] = $value['repay_money'];
        }
        $check_total = $check_data['1'] + $check_data['2'];
        $sql = "SELECT total_repayment , total_successful_amount , total_fail_amount FROM ag_wx_partial_repayment WHERE id = {$partial_repay_id}";
        $check = $model->createCommand($sql)->queryRow();
        if ($check['total_repayment'] != $check_total || $check['total_successful_amount'] != $check_data['1'] || $check['total_fail_amount'] != $check_data['2']) {
            if (abs(bcsub($check['total_repayment'] , $check_total , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入总金额错误';
                return $result;
            }
            if (abs(bcsub($check['total_successful_amount'] , $check_data['1'] , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入成功金额错误';
                return $result;
            }
            if (abs(bcsub($check['total_fail_amount'] , $check_data['2'] , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入失败金额错误';
                return $result;
            }
            $sql = "UPDATE ag_wx_partial_repayment SET total_repayment = {$check_total} , total_successful_amount = {$check_data['1']} , total_fail_amount = {$check_data['2']} WHERE id = {$partial_repay_id}";
            $update = $model->createCommand($sql)->execute();
        }

        if (!$add_partial_repayment || !$add_partial_repay_detail_status) {
            $model->rollback();
            $result['info'] = '录入失败';
            return $result;
        }
        $model->commit();
        $result['code'] = 0;
        $result['info'] = '录入成功';
        return $result;
    }

    /**
     * 部分还款 添加
     */
    public function add_XF_partial_repayment($pay_user , $pay_plan_time , $template_url , $proof_url , $data , $is_clear=1 , $platform_id)
    {
        $result = array('code'=>1000 , 'info'=>'');
        $time   = time();
        $model  = Yii::app()->offlinedb;
        foreach ($data as $key => $value) {
            if (empty($value[0])) {
                $result['info'] = '第'.($key+1).'行缺少借款标题';
                return $result;
            }
            if (empty($value[1])) {
                $result['info'] = '第'.($key+1).'行缺少投资记录ID';
                return $result;
            }
            if (empty($value[2])) {
                $result['info'] = '第'.($key+1).'行缺少用户ID';
                return $result;
            }
            if (empty($value[3])) {
                $result['info'] = '第'.($key+1).'行缺少还款金额';
                return $result;
            }
            if (!is_numeric($value[1])) {
                $result['info'] = '第'.($key+1).'行投资记录ID格式错误';
                return $result;
            }
            if (!is_numeric($value[2])) {
                $result['info'] = '第'.($key+1).'行用户ID格式错误';
                return $result;
            }
            if (!is_numeric($value[3])) {
                $result['info'] = '第'.($key+1).'行还款金额格式错误';
                return $result;
            }
            if ($value[3] <= 0) {
                $result['info'] = '第'.($key+1).'行还款金额输入错误，应为正数';
                return $result;
            }
            $deal_name_arr[$key]    = trim($value[0]);
            $deal_load_id_arr[$key] = intval($value[1]);
            $user_id_arr[$key]      = intval($value[2]);
            $money_arr[$key]        = $value[3];
        }
        $deal_name_str    = "'".implode("','" , $deal_name_arr)."'";
        $deal_load_id_str = implode(',' , $deal_load_id_arr);
        $user_id_str      = implode(',' , $user_id_arr);

        // 查询借款项目
        $deal_info   = array();
        $deal_id_arr = array();
        $sql         = "SELECT id , name , is_effect , is_delete , deal_status FROM offline_deal WHERE name IN ({$deal_name_str}) AND platform_id = {$platform_id}";
        $deal_res    = $model->createCommand($sql)->queryAll();
        if (empty($deal_res)) {
            $result['info'] = '未查询到任何借款项目';
            return $result;
        }
        foreach ($deal_res as $key => $value) {
            if ($value['is_effect'] != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目无效';
            } else if ($value['is_delete'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目已被删除';
            } else if ($value['deal_status'] != 4) {
                $value['check_status'] = 0;
                $value['remark']       = '此借款项目未处于还款中';
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';

                $deal_id_arr[] = $value['id'];
            }
            $deal_info[$value['name']] = $value;
        }
        $deal_id_str = implode(',' , $deal_id_arr);
        if (empty($deal_id_str)) {
            $result['info'] = '所有借款项目无效';
            return $result;
        }
        unset($deal_res);

        // 查询投资记录
        $deal_load_info = array();
        $sql            = "SELECT id , deal_id , user_id , wait_capital , is_repay , debt_status FROM offline_deal_load WHERE id IN ({$deal_load_id_str})";
        $deal_load_res  = $model->createCommand($sql)->queryAll();
        if (empty($deal_load_res)) {
            $result['info'] = '未查询到任何投资记录';
            return $result;
        }
        // 查询进行中的债权兑换记录
        $sql = "SELECT tender_id FROM offline_debt_exchange_log WHERE tender_id IN ({$deal_load_id_str}) AND status = 1";
        $debt_exchange_log = $model->createCommand($sql)->queryColumn();
        if (!$debt_exchange_log) {
            $debt_exchange_log = array();
        }
        // 查询进行中的线下还款记录
        $repayment_plan_info    = array();
        $loan_user_id_arr       = array();
        $repayment_plan_deal_id = array();
        // $sql = "SELECT deal_loan_id , loan_user_id , repay_type , deal_id FROM ag_wx_repayment_plan WHERE deal_id IN ({$deal_id_str}) AND status IN (0 , 1 , 2)";
        // $repayment_plan_res = $model->createCommand($sql)->queryAll();
        // if ($repayment_plan_res) {
        //     foreach ($repayment_plan_res as $key => $value) {
        //         if ($value['repay_type'] == 1) {
        //             // 常规还款
        //             $repayment_plan_deal_id[] = $value['deal_id'];
        //         } else if ($value['repay_type'] == 2) {
        //             // 特殊还款
        //             if (!empty($value['deal_loan_id'])) {
        //                 $temp = explode(',' , $value['deal_loan_id']);
        //                 foreach ($temp as $k => $v) {
        //                     if ($v) {
        //                         $repayment_plan_info[$v] = $v;
        //                     }
        //                 }
        //             }
        //             if (!empty($value['loan_user_id'])) {
        //                 $temp_a = explode(',' , $value['loan_user_id']);
        //                 foreach ($temp_a as $i => $j) {
        //                     if ($j) {
        //                         $loan_user_id_arr[$j] = $j;
        //                     }
        //                 }
        //             }
        //         }
        //     }
        //     // 特殊还款
        //     if (!empty($loan_user_id_arr)) {
        //         $loan_user_id_str = implode(',' , $loan_user_id_arr);
        //         $sql = "SELECT id FROM firstp2p_deal_load WHERE user_id IN ({$loan_user_id_str})";
        //         $loan_user_id_deal_loan_id = $model->createCommand($sql)->queryColumn();
        //         if ($loan_user_id_deal_loan_id) {
        //             foreach ($loan_user_id_deal_loan_id as $key => $value) {
        //                 $repayment_plan_info[$value] = $value;
        //             }
        //         }
        //     }
        // }
        // 查询进行中的部分还款记录
        $sql = "SELECT prd.deal_loan_id FROM offline_partial_repay AS pr INNER JOIN offline_partial_repay_detail AS prd ON pr.id = prd.partial_repay_id AND pr.status IN (1 , 2) AND prd.deal_loan_id IN ({$deal_load_id_str}) AND prd.status = 1 AND pr.platform_id = {$platform_id}";
        $partial_repayment_info = $model->createCommand($sql)->queryColumn();
        if (!$partial_repayment_info) {
            $partial_repayment_info = array();
        }
        $debt_tender_id = array();
        foreach ($deal_load_res as $key => $value) {
            if ($value['is_repay'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录已流标';
            } else if (in_array($value['id'] , $debt_exchange_log)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录处于债权兑换中';
            } else if (in_array($value['id'] , $repayment_plan_info)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录已经处于计划还款中或计划还款审核中';
            } else if (in_array($value['id'] , $partial_repayment_info)) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录处于部分还款中';
            } else if (bccomp($value['wait_capital'] , 0 , 2) != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此投资记录的待还本金未大于0';
            } else if ($value['debt_status'] != 0) {
                if ($value['debt_status'] == 1) {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录正处于债转中';

                    $debt_tender_id[] = $value['id'];
                } else if ($value['debt_status'] == 15) {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录已全部债转';
                } else {
                    $value['check_status'] = 0;
                    $value['remark']       = '此投资记录债转状态错误';
                }
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';
            }
            $deal_load_info[$value['id']] = $value;
        }
        // if ($debt_tender_id) {
        //     $debt_tender_id_str = implode(',' , $debt_tender_id);
        //     $sql    = "SELECT id , tender_id , user_id FROM firstp2p_debt WHERE tender_id IN ({$debt_tender_id_str}) AND status = 1";
        //     $debt_1 = $model->createCommand($sql)->queryAll();
        //     if ($debt_1) {
        //         $url = Yii::app()->c->wx_confirm_debt_api;
        //         foreach ($debt_1 as $key => $value) {
        //             $params = array(
        //                 'debt_id'       => $value['id'],
        //                 'products'      => 2,
        //                 'checkuser'     => 2
        //             );
        //             $CancelDebt = $this->curlRequest($url.'/Launch/DebtGarden/CancelDebt' , 'POST' , $params);
        //             if ($CancelDebt['code'] == 0) {
        //                 $deal_load_info[$value['tender_id']]['check_status'] = 1;
        //                 $deal_load_info[$value['tender_id']]['remark']       = '发布中的债转已被取消';
        //             } else {
        //                 $deal_load_info[$value['tender_id']]['remark'] .= "发布中的债转取消失败，原因：{$CancelDebt['info']}";
        //             }
        //         }
        //     }
        // }
        unset($deal_load_res);

        // 查询用户
        $user_info = array();
        $sql       = "SELECT id , is_effect , is_delete FROM firstp2p_user WHERE id IN ({$user_id_str})";
        $user_res  = Yii::app()->fdb->createCommand($sql)->queryAll();
        if (empty($user_res)) {
            $result['info'] = '未查询到任何用户';
            return $result;
        }
        foreach ($user_res as $key => $value) {
            if ($value['is_effect'] != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此用户无效';
            } else if ($value['is_delete'] != 0) {
                $value['check_status'] = 0;
                $value['remark']       = '此用户已被删除';
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';
            }
            $user_info[$value['id']] = $value;
        }
        unset($user_res);

        // 查询还款计划
        $deal_loan_repay_info = array();
        $sql = "SELECT id , deal_id , deal_loan_id , loan_user_id , money , time FROM offline_deal_loan_repay WHERE deal_loan_id IN ({$deal_load_id_str}) AND money > 0 AND type = 1 AND status = 0 ";
        $deal_loan_repay_res = $model->createCommand($sql)->queryAll();
        if (empty($deal_loan_repay_res)) {
            $result['info'] = '未查询到任何还款计划';
            return $result;
        }
        foreach ($deal_loan_repay_res as $key => $value) {
            if (!empty($deal_loan_repay_info[$value['deal_loan_id']])) {
                $deal_loan_repay_info[$value['deal_loan_id']]['total_money'] = bcadd($deal_loan_repay_info[$value['deal_loan_id']]['total_money'] , $value['money'] , 10);
                if ($value['time'] > $deal_loan_repay_info[$value['deal_loan_id']]['end_time']) {
                    $deal_loan_repay_info[$value['deal_loan_id']]['end_time'] = $value['time'];
                }
            } else {
                $deal_loan_repay_info[$value['deal_loan_id']] = array(
                    'id'           => $value['id'],
                    'deal_id'      => $value['deal_id'],
                    'deal_loan_id' => $value['deal_loan_id'],
                    'loan_user_id' => $value['loan_user_id'],
                    'total_money'  => $value['money'],
                    'end_time'     => $value['time']
                );
            }
        }
        unset($deal_loan_repay_res);

        // 查询咨询方的借款项目
        $adminUserInfo = \Yii::app()->user->getState('_user');
        if($adminUserInfo['user_type'] == 2){
            $deallist = Yii::app()->offlinedb->createCommand("SELECT DISTINCT offline_deal.id AS deal_id FROM offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' AND offline_deal_agency.is_effect = 1")->queryColumn();
            if(!empty($deallist)){
                $admin_deal_id_arr = $deallist;
            }else{
                $admin_deal_id_arr = array('no');
            }
        } else {
            $admin_deal_id_arr = array();
        }

        // 校验数据
        $total_repayment         = '0.00';
        $total_successful_amount = '0.00';
        $total_fail_amount       = '0.00';
        $success_number          = 0;
        $fail_number             = 0;
        $is_repeat               = array();
        foreach ($data as $key => $value) {
            $temp                     = array();
            $temp['partial_repay_id'] = 0;
            $temp['name']             = $value[0];
            $temp['end_time']         = 0;
            $temp['deal_loan_id']     = $value[1];
            $temp['user_id']          = $value[2];
            $temp['repay_money']      = $value[3];
            $temp['deal_id']          = 0;
            $temp['status']           = 0;
            $temp['remark']           = '';
            $temp['addtime']          = $time;
            if (empty($deal_info[$value[0]])) {
                $temp['status'] = 2;
                $temp['remark'] = '借款标题输入错误，未查询到借款项目';
            } else if (empty($deal_load_info[$value[1]])) {
                $temp['status'] = 2;
                $temp['remark'] = '投资记录ID输入错误，未查询到投资记录';
            } else if (empty($user_info[$value[2]])) {
                $temp['status'] = 2;
                $temp['remark'] = '用户ID输入错误，未查询到用户';
            } else if (empty($deal_loan_repay_info[$value[1]])) {
                $temp['status'] = 2;
                $temp['remark'] = '通过此投资记录ID未查询到有效还款计划';
            } else if ($deal_info[$value[0]]['id'] != $deal_load_info[$value[1]]['deal_id']) {
                $temp['status'] = 2;
                $temp['remark'] = '借款标题与投资记录ID不匹配';
            } else if ($deal_load_info[$value[1]]['user_id'] != $user_info[$value[2]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '投资记录ID与用户ID不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['deal_id'] != $deal_info[$value[0]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与借款标题不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['deal_loan_id'] != $deal_load_info[$value[1]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与投资记录ID不匹配';
            } else if ($deal_loan_repay_info[$value[1]]['loan_user_id'] != $user_info[$value[2]]['id']) {
                $temp['status'] = 2;
                $temp['remark'] = '还款计划与用户ID不匹配';
            } else if ($deal_info[$value[0]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $deal_info[$value[0]]['remark'];
            } else if ($deal_load_info[$value[1]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $deal_load_info[$value[1]]['remark'];
            } else if ($user_info[$value[2]]['check_status'] === 0) {
                $temp['status'] = 2;
                $temp['remark'] = $user_info[$value[2]]['remark'];
            } else if (in_array($deal_info[$value[0]]['id'] , $repayment_plan_deal_id)) {
                $temp['status'] = 2;
                $temp['remark'] = '此借款项目已经处于计划还款中或计划还款审核中';
            } else if (bccomp($deal_load_info[$value[1]]['wait_capital'] , $deal_loan_repay_info[$value[1]]['total_money'] , 2) != 0) {
                $temp['status'] = 2;
                $temp['remark'] = "此投资记录的待还本金(￥{$deal_load_info[$value[1]]['wait_capital']})与还款计划的总待还本金(￥{$deal_loan_repay_info[$value[1]]['total_money']})不一致";
            } else if (bccomp($value[3] , $deal_load_info[$value[1]]['wait_capital'] , 2) == 1 && $is_clear == 1) {
                $temp['status'] = 2;
                $temp['remark'] = '还款金额输入错误，不可大于此投资记录的待还本金';
            } else if (in_array($value[1] , $is_repeat)) {
                $temp['status'] = 2;
                $temp['remark'] = '此投资记录ID已在文件中存在';
            } else if (!empty($admin_deal_id_arr) && !in_array($deal_info[$value[0]]['id'] , $admin_deal_id_arr)) {
                $result['info'] = '录入人账号类型为咨询方类型，并且存在借款项目不属于此录入人';
                return $result;
            } else {
                $temp['status']   = 1;
                $temp['end_time'] = $deal_loan_repay_info[$value[1]]['end_time'];
            }
            if ($deal_load_info[$value[1]]['deal_id']) {
                $temp['deal_id']  = $deal_load_info[$value[1]]['deal_id'];
            }
            $total_repayment = bcadd($total_repayment , $value[3] , 10);
            if ($temp['status'] == 1) {
                $success_number++;
                $total_successful_amount = bcadd($total_successful_amount , $value[3] , 10);
            } else if ($temp['status'] == 2) {
                $fail_number++;
                $total_fail_amount = bcadd($total_fail_amount , $value[3] , 10);
            }
            $is_repeat[] = $value[1];
            $new_data[]  = $temp;
        }
        $admin_user_id = Yii::app()->user->id;
        $admin_user_id = $admin_user_id ? $admin_user_id : 0 ;
        $status        = 1;
        $remark        = '';
        $addtime       = $time;
        $updatetime    = $time;

        $model->beginTransaction();

        if (abs(bcsub($total_repayment - bcadd($total_successful_amount , $total_fail_amount , 10) , 10)) < $this->amount_error) {
            $total_repayment = bcadd($total_successful_amount , $total_fail_amount , 10);
        }

        $sql = "INSERT INTO offline_partial_repay (platform_id , total_repayment , total_successful_amount , total_fail_amount , success_number , fail_number , admin_user_id , pay_user , pay_plan_time , status , remark , template_url , proof_url , addtime , updatetime) VALUES({$platform_id} , {$total_repayment} , {$total_successful_amount} , {$total_fail_amount} , {$success_number} , {$fail_number} , {$admin_user_id} , '{$pay_user}' , {$pay_plan_time} , {$status} , '{$remark}' , '{$template_url}' , '{$proof_url}' , {$addtime} , {$updatetime}) ";
        $add_partial_repayment = $model->createCommand($sql)->execute();
        $partial_repay_id      = $model->getLastInsertID();

        $add_data_arr = array();
        $i = 0;
        foreach ($new_data as $key => $value) {
            if (count($add_data_arr[$i]) >= $this->insert_limit) {
                $i++;
            }
            $add_data_arr[$i][] = "({$platform_id} , {$partial_repay_id} , '{$value['name']}' , {$value['end_time']} , {$value['deal_loan_id']} , {$value['user_id']} , {$value['repay_money']} , {$value['deal_id']} , {$value['status']} , '{$value['remark']}' , {$value['addtime']})";
        }
        $add_partial_repay_detail_status = true;
        foreach ($add_data_arr as $key => $value) {
            $add_data_str = implode(',' , $value);
            $sql = "INSERT INTO offline_partial_repay_detail (platform_id , partial_repay_id , name , end_time , deal_loan_id , user_id , repay_money , deal_id , status , remark , addtime) VALUES {$add_data_str}";
            $add_partial_repay_detail = $model->createCommand($sql)->execute();
            if (!$add_partial_repay_detail) {
                $add_partial_repay_detail_status = false;
            }
        }
        $sql = "SELECT sum(repay_money) AS repay_money , status FROM offline_partial_repay_detail WHERE partial_repay_id = {$partial_repay_id} GROUP BY status";
        $check_res = $model->createCommand($sql)->queryAll();
        $check_data['1'] = 0;
        $check_data['2'] = 0;
        foreach ($check_res as $key => $value) {
            $check_data[$value['status']] = $value['repay_money'];
        }
        $check_total = $check_data['1'] + $check_data['2'];
        $sql = "SELECT total_repayment , total_successful_amount , total_fail_amount FROM offline_partial_repay WHERE id = {$partial_repay_id}";
        $check = $model->createCommand($sql)->queryRow();
        if ($check['total_repayment'] != $check_total || $check['total_successful_amount'] != $check_data['1'] || $check['total_fail_amount'] != $check_data['2']) {
            if (abs(bcsub($check['total_repayment'] , $check_total , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入总金额错误';
                return $result;
            }
            if (abs(bcsub($check['total_successful_amount'] , $check_data['1'] , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入成功金额错误';
                return $result;
            }
            if (abs(bcsub($check['total_fail_amount'] , $check_data['2'] , 2)) >= $this->amount_error) {
                $model->rollback();
                $result['info'] = '录入失败金额错误';
                return $result;
            }
            $sql = "UPDATE offline_partial_repay SET total_repayment = {$check_total} , total_successful_amount = {$check_data['1']} , total_fail_amount = {$check_data['2']} WHERE id = {$partial_repay_id}";
            $update = $model->createCommand($sql)->execute();
        }

        if (!$add_partial_repayment || !$add_partial_repay_detail_status) {
            $model->rollback();
            $result['info'] = '录入失败';
            return $result;
        }
        $model->commit();
        $result['code'] = 0;
        $result['info'] = '录入成功';
        return $result;
    }


    public function add_borrower_distribution($post_data , $template_url , $data)
    {
        $result = array('code'=>1000 , 'info'=>'');

        //角色校验
        $op_user_id = \Yii::app()->user->id;
        /*
        $distribution_info = \Yii::app()->db->createCommand()
            ->select('item_id')
            ->from('itz_auth_assignment')
            ->where(" user_id=$op_user_id")
            ->queryRow();
        if(!$distribution_info ||  !in_array($distribution_info['item_id'], \Yii::app()->c->xf_config['borrower_distribution_itemid'])){
            $result['info'] = '请使用第三方公司登陆账号操作分配录入';
            return $result;
        }*/

        //第三方公司
        /*
        $user_info = \Yii::app()->db->createCommand()
            ->select('company_id, type')
            ->from('itz_user')
            ->where(" id=$op_user_id")
            ->queryRow();
        if(!$user_info || empty($user_info['company_id']) || $user_info['type'] != 1){
            $result['info'] = '账号所属公司数据异常';
            return $result;
        }*/

        //空数据过滤
        $user_id_arr = [];
        if($_POST['d_type'] == 1 ){
            // 查询借款项目
            $sql = "SELECT user_id  FROM firstp2p_deal WHERE agency_id={$post_data['agency_id']} and deal_status=4 and is_zdx=0 group by user_id";
            $deal_user_ids = Yii::app()->cmsdb->createCommand($sql)->queryColumn();
            if (empty($deal_user_ids)) {
                $result['info'] = '借款人未查询到任何在途的借款项目';
                return $result;
            }
            $user_id_arr = $deal_user_ids;
        }else{
            foreach ($data as $key => $value) {
                if (empty($value[0])) {
                    $result['info'] = '第'.($key+1).'行用户ID为空';
                    return $result;
                }
                if (!is_numeric($value[0])) {
                    $result['info'] = '第'.($key+1).'行用户ID格式错误';
                    return $result;
                }
                $user_id_arr[$key] = intval($value[0]);
            }
            $user_id_str = implode(',' , $user_id_arr);
            // 查询借款项目
            $sql = "SELECT user_id  FROM firstp2p_deal WHERE user_id IN ({$user_id_str}) and deal_status=4 and is_zdx=0 group by user_id";
            $deal_user_ids = Yii::app()->cmsdb->createCommand($sql)->queryColumn();
            if (empty($deal_user_ids)) {
                $result['info'] = '借款人未查询到任何在途的借款项目';
                return $result;
            }
        }


        Yii::app()->cmsdb->beginTransaction();

        //分配记录
        $data = [];
        $time = time();
        $data['company_id'] = $post_data['company_id'];//第三方公司ID
        $data['op_user_id'] = $op_user_id;//第三方公司后台用户ID
        $data['start_time'] = $post_data['start_time'];//开始时间
        $data['type'] = $post_data['d_type'];//开始时间
        $data['agency_id'] = $post_data['agency_id'] ?: 0;//开始时间
        $data['end_time'] = $post_data['end_time'];//结束时间
        $data['file_path'] = $template_url;//上传的用户ID文件地址
        $data['status'] = 0;//状态：0-待审核 1-审核通过 2-审核拒绝 3-已终止
        $data['addtime'] = $time;//导入时间
        $ret = BaseCrudService::getInstance()->add('Firstp2pBorrowerDistribution', $data);
        if(false == $ret){
            $result['info'] = '借款人分配记录添加失败';
            return $result;
        }

        //分配明细添加
        $distribution_id = $ret['id'];
        $f = 0;
        $add_data_arr = '';
        foreach ($user_id_arr as $key=>$value){
            $status = 1;
            $remark = '';
            if(!in_array($value, $deal_user_ids)){
                $status = 2;
                $remark = '用户未持有在途标的';
                $f++;
                $add_data_arr .= "({$distribution_id} , {$value} , {$status} ,{$post_data['company_id']}, '{$remark}' , {$time},$status ),";
                continue;
            }
            //校验是否已分配
            $sql = "SELECT a.id,b.company_id FROM firstp2p_borrower_distribution_detail a left join  firstp2p_borrower_distribution b on a.distribution_id = b.id WHERE a.user_id=$value  and a.status=1 and b.status in (0,1) and b.end_time>$time";
            $check_res = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if($check_res){
                $status = 2;
                $remark = "用户已分配 相关公司ID：{$check_res['company_id']}";
                $add_data_arr .= " ({$distribution_id} , {$value} , {$status} ,{$post_data['company_id']}, '{$remark}' , {$time},$status ),";
                $f++;
                continue;
            }
            $add_data_arr .= " ({$distribution_id} , {$value} , {$status} , {$post_data['company_id']},'{$remark}' , {$time} ,1),";
        }

        //添加明细
        $add_data_arr = rtrim($add_data_arr, ',');
        $sql = "INSERT INTO firstp2p_borrower_distribution_detail (distribution_id , user_id , status , company_id, remark , addtime,f_status) VALUES {$add_data_arr}";
        $add_distribution_detail = Yii::app()->cmsdb->createCommand($sql)->execute();
        if (!$add_distribution_detail) {
            Yii::app()->cmsdb->rollback();
            $result['info'] = '录入成功金额错误';
            return $result;
        }

        //更新
        $s = count($user_id_arr) - $f;
        $sql = "UPDATE firstp2p_borrower_distribution SET success_num = {$s} , fail_num = {$f} , update_time = {$time} WHERE id = {$distribution_id}";
        $update = Yii::app()->cmsdb->createCommand($sql)->execute();
        if($update == false){
            Yii::app()->cmsdb->rollback();
            $result['info'] = '录入失败';
            return $result;
        }

        Yii::app()->cmsdb->commit();
        $result['code'] = 0;
        $result['info'] = '录入成功';
        return $result;
    }

    public function add_records_uids($post_data , $template_url , $data)
    {
        $result = array('code'=>1000 , 'info'=>'');

        //角色校验
        $op_user_id = \Yii::app()->user->id;
        //空数据过滤
        $user_id_arr = [];
        foreach ($data as $key => $value) {
            if (empty($value[0])) {
                $result['info'] = '第'.($key+1).'行用户ID为空';
                return $result;
            }
            if (!is_numeric($value[0])) {
                $result['info'] = '第'.($key+1).'行用户ID格式错误';
                return $result;
            }
            $user_id_arr[$key] = intval($value[0]);
        }

        Yii::app()->cmsdb->beginTransaction();

        //通话记录
        $data = [];
        $time = time();
        $data['record_time'] = $post_data['record_time'];//第三方公司ID
        $data['op_user_id'] = $op_user_id;//第三方公司后台用户ID
        $data['addtime'] = $time;//导入时间
        $data['record_num'] = $post_data['record_num'];
        $data['op_user_name'] = \Yii::app()->user->name;
        $data['file_path'] = $template_url;
        $data['company_id'] = $post_data['company_id'];
        $data['company_name'] = $post_data['company_name'];
        $data['tax_number'] = $post_data['tax_number'] ?: 0;
        $ret = BaseCrudService::getInstance()->add('Firstp2pPhoneRecords', $data);
        if(false == $ret){
            $result['info'] = '电话录音录入失败';
            return $result;
        }

        //分配明细添加
        $r_id = $ret['id'];
        $f = $s = 0;
        $add_data_arr = '';
        foreach ($user_id_arr as $key=>$value){
            if(empty($value)){
                continue;
            }
            $s+=1;
            $add_data_arr .= " ({$r_id} , {$value} ),";
        }

        if(empty($add_data_arr)){
            Yii::app()->cmsdb->rollback();
            $result['info'] = '无有效数据';
            return $result;
        }
        //添加明细
        $add_data_arr = rtrim($add_data_arr, ',');
        $sql = "INSERT INTO firstp2p_phone_records_uids (r_id , user_id ) VALUES {$add_data_arr}";
        $records_uids = Yii::app()->cmsdb->createCommand($sql)->execute();
        if (!$records_uids) {
            Yii::app()->cmsdb->rollback();
            $result['info'] = '明细录入有误';
            return $result;
        }

        Yii::app()->cmsdb->commit();
        $result['code'] = 0;
        $result['info'] = '录入成功';
        return $result;
    }
}
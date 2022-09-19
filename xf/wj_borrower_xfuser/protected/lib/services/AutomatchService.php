<?php
class AutomatchService extends ItzInstanceService
{
    private $amount_error = 2; // 误差金额
    private $insert_limit = 1000; // 添加数据限制

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 匹配债权部分还款 新增
     */
    public function addPartialRepayment($param)
    {
        $result = array('code'=>1000 , 'info'=>'');
        $time   = time();
        if (!in_array($param['platform_id'] , [1, 2, 3, 4])) {
            $result['info'] = '平台ID输入错误';
            return $result;
        }
        if ($param['platform_id'] == 1) {
            $model                      = Yii::app()->fdb;
            $table_deal_load            = 'firstp2p_deal_load';
            $table_debt_exchange        = 'firstp2p_debt_exchange_log';
            $table_repayment_plan       = 'ag_wx_repayment_plan';
            $table_partial_repay        = 'ag_wx_partial_repayment';
            $table_partial_repay_detail = 'ag_wx_partial_repay_detail';
            $table_debt                 = 'firstp2p_debt';
            $table_deal_loan_repay      = 'firstp2p_deal_loan_repay';
            $table_deal_agency          = 'firstp2p_deal_agency';
            $table_deal                 = 'firstp2p_deal';
        } else if ($param['platform_id'] == 2) {
            $model                      = Yii::app()->phdb;
            $table_deal_load            = 'firstp2p_deal_load';
            $table_debt_exchange        = 'firstp2p_debt_exchange_log';
            $table_repayment_plan       = 'ag_wx_repayment_plan';
            $table_partial_repay        = 'ag_wx_partial_repayment';
            $table_partial_repay_detail = 'ag_wx_partial_repay_detail';
            $table_debt                 = 'firstp2p_debt';
            $table_deal_loan_repay      = 'firstp2p_deal_loan_repay';
            $table_deal_agency          = 'firstp2p_deal_agency';
            $table_deal                 = 'firstp2p_deal';
        } else if (in_array($param['platform_id'] , [3, 4])) {
            $model                      = Yii::app()->offlinedb;
            $table_deal_load            = 'offline_deal_load';
            $table_debt_exchange        = 'offline_debt_exchange_log';
            $table_repayment_plan       = 'offline_wx_repayment_plan';
            $table_partial_repay        = 'offline_partial_repay';
            $table_partial_repay_detail = 'offline_partial_repay_detail';
            $table_debt                 = 'offline_debt';
            $table_deal_loan_repay      = 'offline_deal_loan_repay';
            $table_deal_agency          = 'offline_deal_agency';
            $table_deal                 = 'offline_deal';
        }
        foreach ($param['data'] as $key => $value) {
            $user_id_arr[$key] = intval($value[0]);
            $money_arr[$key]   = $value[1];
        }
        $user_id_str = implode(',' , $user_id_arr);

        // 查询用户
        $user_info = array();
        $sql       = "SELECT id , is_effect , is_delete , is_online FROM firstp2p_user WHERE id IN ({$user_id_str})";
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
            } else if ($value['is_online'] != 1) {
                $value['check_status'] = 0;
                $value['remark']       = '此用户为非在途用户';
            } else {
                $value['check_status'] = 1;
                $value['remark']       = '';
            }
            $user_info[$value['id']] = $value;
        }
        unset($user_res);

        // 查询投资记录
        $deal_load_info   = array();
        $deal_load_max    = array();
        $deal_id_arr      = array();
        $deal_load_id_arr = array();
        if ($param['advisory_id']) {
            $advisory_id_sql = " AND deal.advisory_id = {$param['advisory_id']} ";
        } else {
            $advisory_id_sql = "";
        }
        $sql = "SELECT deal_load.* , deal.name AS deal_name FROM {$table_deal_load} AS deal_load INNER JOIN {$table_deal} AS deal ON deal_load.deal_id = deal.id WHERE deal_load.user_id IN ({$user_id_str}) AND deal_load.wait_capital > 0 AND deal_load.debt_status = 0 AND deal_load.status = 1 {$advisory_id_sql} ";
        $deal_load_res  = $model->createCommand($sql)->queryAll();
        if (empty($deal_load_res)) {
            $result['info'] = '未查询到任何有效投资记录';
            return $result;
        }
        foreach ($deal_load_res as $key => $value) {
            $deal_load_info[$value['user_id']][$value['deal_id']][$value['id']] = $value;
            if (!isset($deal_load_max[$value['user_id']])) {
                $deal_load_max[$value['user_id']] = 0;
            }
            $deal_load_max[$value['user_id']] = bcadd($deal_load_max[$value['user_id']] , $value['wait_capital'] , 10);
            $deal_id_arr[]                    = $value['deal_id'];
            $deal_load_id_arr[]               = $value['id'];
        }
        $deal_id_str      = implode(',' , $deal_id_arr);
        $deal_load_id_str = implode(',' , $deal_load_id_arr);
        unset($deal_load_res);

        // 查询进行中的债权兑换记录
        $sql = "SELECT tender_id FROM {$table_debt_exchange} WHERE user_id IN ({$user_id_str}) AND status = 1";
        $debt_exchange_log = $model->createCommand($sql)->queryColumn();
        if (!$debt_exchange_log) {
            $debt_exchange_log = array();
        }

        // 查询进行中的线下还款记录
        $repayment_plan_deal_id      = array();
        $repayment_plan_deal_loan_id = array();
        $repayment_plan_loan_user_id = array();
        $sql = "SELECT deal_loan_id , loan_user_id , repay_type , deal_id FROM {$table_repayment_plan} WHERE deal_id IN ({$deal_id_str}) AND status IN (0 , 1 , 2)";
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
                                $repayment_plan_deal_loan_id[] = $v;
                            }
                        }
                    }
                    if (!empty($value['loan_user_id'])) {
                        $temp_a = explode(',' , $value['loan_user_id']);
                        foreach ($temp_a as $i => $j) {
                            if ($j) {
                                $repayment_plan_loan_user_id[$j][] = $value['deal_id'];
                            }
                        }
                    }
                }
            }
        }
        unset($repayment_plan_res);

        // 查询进行中的部分还款记录
        $sql = "SELECT prd.deal_loan_id FROM {$table_partial_repay} AS pr INNER JOIN {$table_partial_repay_detail} AS prd ON pr.id = prd.partial_repay_id AND pr.status IN (1 , 2) AND prd.user_id IN ({$user_id_str}) AND prd.status = 1";
        $partial_repayment_info = $model->createCommand($sql)->queryColumn();
        if (!$partial_repayment_info) {
            $partial_repayment_info = array();
        }

        // 取消未被认购的债转
        $debt_info = array();
        $sql       = "SELECT id , tender_id , user_id FROM {$table_debt} WHERE tender_id IN ({$deal_load_id_str}) AND status = 1";
        $debt_res  = $model->createCommand($sql)->queryAll();
        if ($debt_res) {
            $url = Yii::app()->c->wx_confirm_debt_api;
            foreach ($debt_res as $key => $value) {
                $params = array(
                    'debt_id'       => $value['id'],
                    'products'      => $param['platform_id'],
                    'checkuser'     => 2
                );
                $CancelDebt = $this->curlRequest($url.'/Launch/XfDebtGarden/CancelDebt' , 'POST' , $params);
                if ($CancelDebt['code'] == 0) {
                    $debt_info[$value['tender_id']]['check_status'] = 1; 
                    $debt_info[$value['tender_id']]['remark']       = '发布中的债转已被取消';
                } else {
                    $debt_info[$value['tender_id']]['check_status'] = 0; 
                    $debt_info[$value['tender_id']]['remark']       = "发布中的债转取消失败，原因：{$CancelDebt['info']}";
                }
            }
        }
        unset($debt_res);

        // 查询还款计划
        $deal_loan_repay_info = array();
        $sql = "SELECT id , deal_id , deal_loan_id , loan_user_id , money , time FROM {$table_deal_loan_repay} WHERE deal_loan_id IN ({$deal_load_id_str}) AND money > 0 AND type = 1 AND status = 0 ";
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
        $admin_deal_id_arr = array();
        $adminUserInfo = \Yii::app()->user->getState('_user');
        if($adminUserInfo['user_type'] == 2){
            $admin_deal_id_arr = $model->createCommand("SELECT deal.id FROM {$table_deal_agency} AS agency INNER JOIN {$table_deal} AS deal ON deal.advisory_id = agency.id WHERE agency.name = '{$adminUserInfo['realname']}' AND agency.is_effect = 1 ")->queryColumn();
            if(!$admin_deal_id_arr){
                $admin_deal_id_arr = array();
            }
        }

        // 校验数据
        $total_repayment         = 0;
        $total_successful_amount = 0;
        $total_fail_amount       = 0;
        $success_number          = 0;
        $fail_number             = 0;
        $is_repeat               = array();
        $insert_data             = array();
        foreach ($param['data'] as $key => $value) {
            $total_repayment      = bcadd($total_repayment , $value[1] , 10);
            $temp                 = array();
            $temp['name']         = '';
            $temp['end_time']     = 0;
            $temp['deal_loan_id'] = 0;
            $temp['user_id']      = $value[0];
            $temp['repay_money']  = $value[1];
            $temp['deal_id']      = 0;
            $temp['status']       = 0;
            $temp['remark']       = '';
            $temp['addtime']      = $time;
            if (in_array($value[0] , $is_repeat)) { // 用户ID重复
                $temp['status'] = 2;
                $temp['remark'] = '此用户ID已经存在';
                $insert_data[]  = $temp;
                $fail_number++;
                $total_fail_amount = bcadd($total_fail_amount , $value[1] , 10);
            } else if (!isset($user_info[$value[0]])) { // 用户信息不存在
                $temp['status'] = 2;
                $temp['remark'] = '通过此用户ID未查询到相关用户信息';
                $insert_data[]  = $temp;
                $fail_number++;
                $total_fail_amount = bcadd($total_fail_amount , $value[1] , 10);
            } else if ($user_info[$value[0]]['check_status'] === 0) { // 用户信息错误
                $temp['status'] = 2;
                $temp['remark'] = $user_info[$value[0]]['remark'];
                $insert_data[]  = $temp;
                $fail_number++;
                $total_fail_amount = bcadd($total_fail_amount , $value[1] , 10);
            } else if (!isset($deal_load_info[$value[0]])) { // 未查询到投资记录
                $temp['status'] = 2;
                $temp['remark'] = '此用户未查询到在途投资记录';
                $insert_data[]  = $temp;
                $fail_number++;
                $total_fail_amount = bcadd($total_fail_amount , $value[1] , 10);
            } else if (bccomp($value[1] , $deal_load_max[$value[0]] , 2) === 1) { // 还款金额错误
                $temp['status'] = 2;
                $temp['remark'] = '此用户的还款金额大于在途本金总额';
                $insert_data[]  = $temp;
                $fail_number++;
                $total_fail_amount = bcadd($total_fail_amount , $value[1] , 10);
            } else {
                // 遍历校验借款项目
                foreach ($deal_load_info[$value[0]] as $a => $b) {
                    if (in_array($a , $repayment_plan_deal_id)) { // 常规还款中
                        unset($deal_load_info[$value[0]][$a]);
                    } else if (in_array($a , $repayment_plan_loan_user_id[$value[0]])) { // 特殊还款中
                        unset($deal_load_info[$value[0]][$a]);
                    } else if (!empty($admin_deal_id_arr) && !in_array($a , $admin_deal_id_arr)) { // 非咨询方的借款项目
                        unset($deal_load_info[$value[0]][$a]);
                    } else {
                        // 遍历校验投资记录
                        foreach ($b as $c => $d) {
                            if (in_array($c , $debt_exchange_log)) { // 进行中的债权兑换
                                unset($deal_load_info[$value[0]][$a][$c]);
                            } else if (in_array($c , $repayment_plan_deal_loan_id)) { // 特殊还款中
                                unset($deal_load_info[$value[0]][$a][$c]);
                            } else if (in_array($c , $partial_repayment_info)) { // 进行中的部分还款
                                unset($deal_load_info[$value[0]][$a][$c]);
                            } else if ($debt_info[$c]['check_status'] === 0) { // 取消未被认购的债转失败
                                unset($deal_load_info[$value[0]][$a][$c]);
                            } else if (!isset($deal_loan_repay_info[$c])) { // 未查询到还款计划
                                unset($deal_load_info[$value[0]][$a][$c]);
                            } else if (bccomp($deal_loan_repay_info[$c]['total_money'] , $d['wait_capital'] , 2) !== 0) { // 投资记录待还本金与还款计划总待还本金不一致
                                unset($deal_load_info[$value[0]][$a][$c]);
                            } else {
                                if (bccomp($value[1] , 0 , 2) === 1 && bccomp($value[1] , $d['wait_capital'] , 2) === 1) {
                                    // 还款金额 > 待还本金
                                    $temp['name']         = $d['deal_name'];
                                    $temp['end_time']     = $deal_loan_repay_info[$c]['end_time'];
                                    $temp['deal_loan_id'] = $d['id'];
                                    $temp['user_id']      = $value[0];
                                    $temp['repay_money']  = $d['wait_capital'];
                                    $temp['deal_id']      = $d['deal_id'];
                                    $temp['status']       = 1;
                                    $temp['remark']       = $debt_info[$c]['check_status'] === 1 ? $debt_info[$c]['remark'] : '';
                                    $insert_data[]        = $temp;
                                    $success_number++;
                                    $total_successful_amount = bcadd($total_successful_amount , $d['wait_capital'] , 10);
                                    $value[1] = bcsub($value[1] , $d['wait_capital'] , 2);
                                } else if (bccomp($value[1] , 0 , 2) === 1 && bccomp($value[1] , $d['wait_capital'] , 2) < 1) {
                                    // 还款金额 <= 待还本金
                                    $temp['name']         = $d['deal_name'];
                                    $temp['end_time']     = $deal_loan_repay_info[$c]['end_time'];
                                    $temp['deal_loan_id'] = $d['id'];
                                    $temp['user_id']      = $value[0];
                                    $temp['repay_money']  = $value[1];
                                    $temp['deal_id']      = $d['deal_id'];
                                    $temp['status']       = 1;
                                    $temp['remark']       = $debt_info[$c]['check_status'] === 1 ? $debt_info[$c]['remark'] : '';
                                    $insert_data[]        = $temp;
                                    $success_number++;
                                    $total_successful_amount = bcadd($total_successful_amount , $value[1] , 10);
                                    $value[1] = 0;
                                }
                            }
                        }
                    }
                }
                // 还款金额剩余
                if (bccomp($value[1] , 0 , 2) === 1) {
                    $temp['name']         = '';
                    $temp['end_time']     = 0;
                    $temp['deal_loan_id'] = 0;
                    $temp['user_id']      = $value[0];
                    $temp['repay_money']  = $value[1];
                    $temp['deal_id']      = 0;
                    $temp['status']       = 2;
                    $temp['remark']       = '剩余还款金额未匹配到有效投资记录';
                    $insert_data[]        = $temp;
                    $fail_number++;
                    $total_fail_amount = bcadd($total_fail_amount , $value[1] , 10);
                }
            }
            $is_repeat[] = $value[0];
        }
        $admin_user_id = Yii::app()->user->id;
        $admin_user_id = $admin_user_id ? $admin_user_id : 0 ;
        $addtime       = $time;
        $updatetime    = $time;

        $model->beginTransaction();

        if (in_array($param['platform_id'] , [1, 2])) {
            $sql = "INSERT INTO {$table_partial_repay} (total_repayment , total_successful_amount , total_fail_amount , success_number , fail_number , admin_user_id , pay_user , pay_plan_time , status , template_url , addtime , updatetime , type) VALUES({$total_repayment} , {$total_successful_amount} , {$total_fail_amount} , {$success_number} , {$fail_number} , {$admin_user_id} , '{$param['pay_user']}' , {$param['pay_plan_time']} , 1 , '{$param['template_url']}' , {$addtime} , {$updatetime} , 2) ";
        } else if (in_array($param['platform_id'] , [3, 4])) {
            $sql = "INSERT INTO {$table_partial_repay} (platform_id , total_repayment , total_successful_amount , total_fail_amount , success_number , fail_number , admin_user_id , pay_user , pay_plan_time , status , template_url , addtime , updatetime , type) VALUES({$param['platform_id']} , {$total_repayment} , {$total_successful_amount} , {$total_fail_amount} , {$success_number} , {$fail_number} , {$admin_user_id} , '{$param['pay_user']}' , {$param['pay_plan_time']} , 1 , '{$param['template_url']}' , {$addtime} , {$updatetime} , 2) ";
        }
        $add_partial_repayment = $model->createCommand($sql)->execute();
        $partial_repay_id      = $model->getLastInsertID();

        $add_data_arr = array();
        $i = 0;
        if (in_array($param['platform_id'] , [1, 2])) {
            foreach ($insert_data as $key => $value) {
                if (count($add_data_arr[$i]) >= $this->insert_limit) {
                    $i++;
                }
                $add_data_arr[$i][] = "({$partial_repay_id} , '{$value['name']}' , {$value['end_time']} , {$value['deal_loan_id']} , {$value['user_id']} , {$value['repay_money']} , {$value['deal_id']} , {$value['status']} , '{$value['remark']}' , {$value['addtime']})";
            }
        } else if (in_array($param['platform_id'] , [3, 4])) {
            foreach ($insert_data as $key => $value) {
                if (count($add_data_arr[$i]) >= $this->insert_limit) {
                    $i++;
                }
                $add_data_arr[$i][] = "({$param['platform_id']} , {$partial_repay_id} , '{$value['name']}' , {$value['end_time']} , {$value['deal_loan_id']} , {$value['user_id']} , {$value['repay_money']} , {$value['deal_id']} , {$value['status']} , '{$value['remark']}' , {$value['addtime']})";
            }
        }
        $add_partial_repay_detail_status = true;
        if (in_array($param['platform_id'] , [1, 2])) {
            foreach ($add_data_arr as $key => $value) {
                $add_data_str = implode(',' , $value);
                $sql = "INSERT INTO {$table_partial_repay_detail} (partial_repay_id , name , end_time , deal_loan_id , user_id , repay_money , deal_id , status , remark , addtime) VALUES {$add_data_str}";
                $add_partial_repay_detail = $model->createCommand($sql)->execute();
                if (!$add_partial_repay_detail) {
                    $add_partial_repay_detail_status = false;
                }
            }
        } else if (in_array($param['platform_id'] , [3, 4])) {
            foreach ($add_data_arr as $key => $value) {
                $add_data_str = implode(',' , $value);
                $sql = "INSERT INTO {$table_partial_repay_detail} (platform_id , partial_repay_id , name , end_time , deal_loan_id , user_id , repay_money , deal_id , status , remark , addtime) VALUES {$add_data_str}";
                $add_partial_repay_detail = $model->createCommand($sql)->execute();
                if (!$add_partial_repay_detail) {
                    $add_partial_repay_detail_status = false;
                }
            }
        }
        $sql = "SELECT SUM(repay_money) AS repay_money , status FROM {$table_partial_repay_detail} WHERE partial_repay_id = {$partial_repay_id} GROUP BY status ";
        $check_res = $model->createCommand($sql)->queryAll();
        $check_data['1'] = 0;
        $check_data['2'] = 0;
        foreach ($check_res as $key => $value) {
            $check_data[$value['status']] = $value['repay_money'];
        }
        $check_total = bcadd($check_data['1'] , $check_data['2'] , 2);
        $sql = "SELECT total_repayment , total_successful_amount , total_fail_amount FROM {$table_partial_repay} WHERE id = {$partial_repay_id}";
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
            $sql = "UPDATE {$table_partial_repay} SET total_repayment = {$check_total} , total_successful_amount = {$check_data['1']} , total_fail_amount = {$check_data['2']} WHERE id = {$partial_repay_id}";
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

    public function PartialRepaymentList($param)
    {
        $result = array('code' => 1000 , 'info' => '' , 'count' => 0 , 'data' => array());
        if (!in_array($param['platform_id'] , [1, 2, 3, 4])) {
            $result['info'] = '平台ID输入错误';
            return $result;
        }
        // 条件筛选
        $where = ' WHERE type = 2 AND status != 6 ';
        if ($param['platform_id'] == 1) {
            $model                      = Yii::app()->fdb;
            $table_partial_repay        = 'ag_wx_partial_repayment';
            $table_partial_repay_detail = 'ag_wx_partial_repay_detail';
            $edit_url                   = '/user/Automatch/EditZXPartialRepay';
            $pass_url                   = '/user/Automatch/AllowedZXPartialRepay';
            $refuse_url                 = '/user/Automatch/RefuseZXPartialRepay';
            $delete_url                 = '/user/Automatch/DeleteZXPartialRepay';
        } else if ($param['platform_id'] == 2) {
            $model                      = Yii::app()->phdb;
            $table_partial_repay        = 'ag_wx_partial_repayment';
            $table_partial_repay_detail = 'ag_wx_partial_repay_detail';
            $edit_url                   = '/user/Automatch/EditPHPartialRepay';
            $pass_url                   = '/user/Automatch/AllowedPHPartialRepay';
            $refuse_url                 = '/user/Automatch/RefusePHPartialRepay';
            $delete_url                 = '/user/Automatch/DeletePHPartialRepay';
        } else if ($param['platform_id'] == 3) {
            $model                      = Yii::app()->offlinedb;
            $table_partial_repay        = 'offline_partial_repay';
            $table_partial_repay_detail = 'offline_partial_repay_detail';
            $where                     .= " AND platform_id = {$param['platform_id']} ";
            $edit_url                   = '/user/Automatch/EditGCWJPartialRepay';
            $pass_url                   = '/user/Automatch/AllowedGCWJPartialRepay';
            $refuse_url                 = '/user/Automatch/RefuseGCWJPartialRepay';
            $delete_url                 = '/user/Automatch/DeleteGCWJPartialRepay';
        } else if ($param['platform_id'] == 4) {
            $model                      = Yii::app()->offlinedb;
            $table_partial_repay        = 'offline_partial_repay';
            $table_partial_repay_detail = 'offline_partial_repay_detail';
            $where                     .= " AND platform_id = {$param['platform_id']} ";
            $edit_url                   = '/user/Automatch/EditZDXPartialRepay';
            $pass_url                   = '/user/Automatch/AllowedZDXPartialRepay';
            $refuse_url                 = '/user/Automatch/RefuseZDXPartialRepay';
            $delete_url                 = '/user/Automatch/DeleteZDXPartialRepay';
        }
        // 序号
        if (!empty($param['id'])) {
            $where .= " AND id = {$param['id']} ";
        }
        // 状态
        if (!empty($param['status'])) {
            $where .= " AND status = {$param['status']} ";
        }
        // 时间
        if (!empty($param['start'])) {
            $where .= " AND pay_plan_time >= {$param['start']} ";
        }
        if (!empty($param['end'])) {
            $where .= " AND pay_plan_time <= {$param['end']} ";
        }
        $sql   = "SELECT count(id) AS count FROM {$table_partial_repay} {$where} ";
        $count = $model->createCommand($sql)->queryScalar();
        if ($count == 0) {
            $result['code'] = 0;
            return $result;
        }
        // 查询数据
        $sql = "SELECT * FROM {$table_partial_repay} {$where} ORDER BY id DESC ";
        $page_count = ceil($count / $param['limit']);
        if ($param['page'] > $page_count) {
            $param['page'] = $page_count;
        }
        $pass = ($param['page'] - 1) * $param['limit'];
        $sql .= " LIMIT {$pass} , {$param['limit']} ";
        $list = $model->createCommand($sql)->queryAll();
        // 获取当前账号所有子权限
        $authList      = \Yii::app()->user->getState('_auth');
        $edit_status   = 0;
        $pass_status   = 0;
        $refuse_status = 0;
        $delete_status = 0;
        if (!empty($authList) && strstr($authList , $edit_url) || empty($authList)) {
            $edit_status = 1;
        }
        if (!empty($authList) && strstr($authList , $pass_url) || empty($authList)) {
            $pass_status = 1;
        }
        if (!empty($authList) && strstr($authList , $refuse_url) || empty($authList)) {
            $refuse_status = 1;
        }
        if (!empty($authList) && strstr($authList , $delete_url) || empty($authList)) {
            $delete_status = 1;
        }
        $status = array(1 => '待审核' , 2 => '审核已通过' , 3 => '审核未通过' , 4 => '还款成功' , 5 => '还款失败');
        $user_id_arr = array();
        foreach ($list as $key => $value) {
            $value['total_repayment']         = number_format($value['total_repayment'], 2, '.', ',');
            $value['total_successful_amount'] = number_format($value['total_successful_amount'], 2, '.', ',');
            $value['total_fail_amount']       = number_format($value['total_fail_amount'], 2, '.', ',');
            $value['addtime']                 = date('Y-m-d H:i:s' , $value['addtime']);
            $value['pay_plan_time']           = date('Y-m-d' , $value['pay_plan_time']);
            $value['status_name']             = $status[$value['status']];
            if ($value['task_success_time'] > 0) {
                $value['task_success_time']   = date('Y-m-d H:i:s' , $value['task_success_time']);
            } else {
                $value['task_success_time']   = '——';
            }
            if ($value['proof_url']) {
                $oss_preview_address = Yii::app()->c->oss_preview_address;
                $value['proof_url'] = "<a href='{$oss_preview_address}/{$value['proof_url']}' target='_blank'><button class='layui-btn layui-btn-primary'>下载</button></a>";
            } else {
                $value['proof_url'] = '——';
            }
            if ($value['status'] == 1) {
                $value['edit_status']   = $edit_status;
                $value['pass_status']   = $pass_status;
                $value['refuse_status'] = $refuse_status;
                $value['delete_status'] = $delete_status;
            } else if (in_array($value['status'] , array(2 , 4 , 5))) {
                $value['edit_status']   = 0;
                $value['pass_status']   = 0;
                $value['refuse_status'] = 0;
                $value['delete_status'] = 0;
            } else if ($value['status'] == 3) {
                $value['edit_status']   = 0;
                $value['pass_status']   = 0;
                $value['refuse_status'] = 0;
                $value['delete_status'] = $delete_status;
            }
            $user_id_arr[] = $value['admin_user_id'];
            
            $listInfo[] = $value;
        }
        if ($user_id_arr) {
            $user_id_str = implode(',' , $user_id_arr);
            $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
            $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
            foreach ($user_infos_res as $key => $value) {
                $user_infos[$value['id']] = $value['realname'];
            }
            foreach ($listInfo as $key => $value) {
                $listInfo[$key]['admin_user'] = $user_infos[$value['admin_user_id']];
            }
        }
        $result['code']  = 0;
        $result['count'] = $count;
        $result['data']  = $listInfo;
        return $result;
    }

    public function updatePartialRepayment($param)
    {
        $return_result = array(
            'code' => 1000, 'info' => '', 'data' => array()
        );
        $time          = time();
        $admin_user_id = Yii::app()->user->id;
        $admin_user_id = $admin_user_id ? $admin_user_id : 0;
        $id            = $param['id'];
        $status        = $param['status'];
        $remark        = $param['remark'];
        $where         = " WHERE id = {$id} ";
        if ($param['platform_id'] == 1) {
            $model                      = Yii::app()->fdb;
            $table_partial_repay        = 'ag_wx_partial_repayment';
            $table_partial_repay_detail = 'ag_wx_partial_repay_detail';
        } else if ($param['platform_id'] == 2) {
            $model                      = Yii::app()->phdb;
            $table_partial_repay        = 'ag_wx_partial_repayment';
            $table_partial_repay_detail = 'ag_wx_partial_repay_detail';
        } else if ($param['platform_id'] == 3) {
            $model                      = Yii::app()->offlinedb;
            $table_partial_repay        = 'offline_partial_repay';
            $table_partial_repay_detail = 'offline_partial_repay_detail';
            $where                     .= ' AND platform_id = 3 ';
        } else if ($param['platform_id'] == 4) {
            $model                      = Yii::app()->offlinedb;
            $table_partial_repay        = 'offline_partial_repay';
            $table_partial_repay_detail = 'offline_partial_repay_detail';
            $where                     .= ' AND platform_id = 4 ';
        }
        $partialepayment = $model->createCommand("SELECT * FROM {$table_partial_repay} {$where} ")->queryRow();
        if(!$partialepayment) {
            $return_result['info'] = '请正确输入ID';
            return $return_result;
        }
        if ($status == 6) { // 移除

            if (!in_array($partialepayment['status'] , [1, 3])) {
                $return_result['info'] = '状态错误，无法继续操作！';
                return $return_result;
            }
            $model->beginTransaction();
            $sql   = "UPDATE {$table_partial_repay} SET status = 6 , updatetime = {$time} WHERE id = {$id} ";
            $res   = $model->createCommand($sql)->execute();
            $sql   = "UPDATE {$table_partial_repay_detail} SET status = 3 , repay_status = 2 WHERE partial_repay_id = {$id} ";
            $res_a = $model->createCommand($sql)->execute();
            if (!$res || !$res_a) {
                $model->rollback();
                $return_result['info'] = "操作失败";
                return $return_result;
            }
            $model->commit();
            $return_result['code'] = 0;
            return $return_result;

        } else if ($status == 3) { // 拒绝

            if ($partialepayment['status'] != 1) {
                $return_result['info'] = "状态错误，无法继续操作！";
                return $return_result;
            }
            if (empty($remark)) {
                $return_result['info'] = "请输入拒绝原因";
                return $return_result;
            }
            $sql = "UPDATE {$table_partial_repay} SET status = 3 , remark = '{$remark}' , updatetime = {$time} WHERE id = {$id} ";
            $res = $model->createCommand($sql)->execute();
            if (!$res) {
                $return_result['info'] = "操作失败";
                return $return_result;
            }
            $return_result['code'] = 0;
            return $return_result;

        } else if ($status == 2) {

            if($partialepayment['status'] != 1){
                $return_result['info'] = "状态错误，无法继续操作！";
                return $return_result;
            }
            // 校验计划还款时间
            $today_midnight = strtotime("midnight");
            if ($partialepayment['pay_plan_time'] < $today_midnight) {
                $return_result['info'] = "计划还款时间必须大于等于今日凌晨";
                return $return_result;
            }
            // 校验还款凭证是否存在
            if (empty($partialepayment['proof_url'])) {
                $return_result['info'] = "请上传还款凭证";
                return $return_result;
            }
            $partialepayDetail = $model->createCommand("SELECT SUM(repay_money) as repay_money , status , count(*) as number FROM {$table_partial_repay_detail} WHERE partial_repay_id = {$id} GROUP BY status")->queryAll();
            if(!$partialepayDetail){
                $return_result['info'] = "详情信息不存在";
                return $return_result;
            }
            // 校验还款总额
            $total_repayment = array_sum(ItzUtil::array_column($partialepayDetail , "repay_money"));
            if($total_repayment != $partialepayment['total_repayment']){
                $return_result['info'] = "还款总额不一致";
                return $return_result;
            }
            foreach($partialepayDetail as $key => $val){
                if($val['status'] == 1){
                    // 校验成功金额合计
                    if($val['repay_money'] != $partialepayment['total_successful_amount']){
                        $return_result['info'] = "成功金额合计不一致";
                        return $return_result;
                    }
                    // 校验导入成功条数
                    if($val['number'] != $partialepayment['success_number']){
                        $return_result['info'] = "导入成功条数不一致";
                        return $return_result;
                    }
                }
                if($val['status'] == 2){
                    // 校验失败金额合计
                    if($val['repay_money'] != $partialepayment['total_fail_amount']){
                        $return_result['info'] = "失败金额合计不一致";
                        return $return_result;
                    }
                    // 校验导入失败条数
                    if($val['number'] != $partialepayment['fail_number']){
                        $return_result['info'] = "导入失败条数不一致";
                        return $return_result;
                    }
                }
            }
            $sql = "UPDATE {$table_partial_repay} SET status = 2 , examine_user_id = {$admin_user_id} WHERE id = {$id} ";
            $res = $model->createCommand($sql)->execute();
            if (!$res) {
                $return_result['info'] = "操作失败";
                return $return_result;
            }
            $return_result['code'] = 0;
            return $return_result;
        }
    }

    public function PartialRepaymentDetail($param)
    {
        $result       = array('code' => 1000 , 'info' => '' , 'count' => 0 , 'data' => array());
        $id           = $param['id'];
        $name         = $param['name'];
        $deal_loan_id = $param['deal_loan_id'];
        $user_id      = $param['user_id'];
        $status       = $param['status'];
        $repay_status = $param['repay_status'];
        // 条件筛选
        $where = " WHERE prd.partial_repay_id = {$id} ";
        if ($param['platform_id'] == 1) {
            $model                      = Yii::app()->fdb;
            $table_partial_repay        = 'ag_wx_partial_repayment';
            $table_partial_repay_detail = 'ag_wx_partial_repay_detail';
        } else if ($param['platform_id'] == 2) {
            $model                      = Yii::app()->phdb;
            $table_partial_repay        = 'ag_wx_partial_repayment';
            $table_partial_repay_detail = 'ag_wx_partial_repay_detail';
        } else if ($param['platform_id'] == 3) {
            $model                      = Yii::app()->offlinedb;
            $table_partial_repay        = 'offline_partial_repay';
            $table_partial_repay_detail = 'offline_partial_repay_detail';
            $where                     .= ' AND pr.platform_id = 3 ';
        } else if ($param['platform_id'] == 4) {
            $model                      = Yii::app()->offlinedb;
            $table_partial_repay        = 'offline_partial_repay';
            $table_partial_repay_detail = 'offline_partial_repay_detail';
            $where                     .= ' AND pr.platform_id = 4 ';
        }
        // 借款标题
        if (!empty($name)) {
            $where .= " AND prd.name = '{$name}' ";
        }
        // 投资记录ID
        if (!empty($deal_loan_id)) {
            $where .= " AND prd.deal_loan_id = {$deal_loan_id} ";
        }
        // 用户ID
        if (!empty($user_id)) {
            $where .= " AND prd.user_id = {$user_id} ";
        }
        // 导入状态
        if (!empty($status)) {
            $where .= " AND prd.status = {$status} ";
        }
        // 还款状态
        if (!empty($repay_status)) {
            $where .= " AND prd.repay_status = {$repay_status} ";
        }
        $sql = "SELECT count(prd.id) AS count FROM {$table_partial_repay_detail} AS prd INNER JOIN {$table_partial_repay} AS pr ON pr.id = prd.partial_repay_id {$where} ";
        $count = $model->createCommand($sql)->queryScalar();
        if ($count == 0) {
            $result['code'] = 0;
            return $result;
        }
        // 查询数据
        $sql = "SELECT prd.* FROM {$table_partial_repay_detail} AS prd INNER JOIN {$table_partial_repay} AS pr ON pr.id = prd.partial_repay_id {$where} ";
        if (empty($param['download'])) {
            $page_count = ceil($count / $param['limit']);
            if ($param['page'] > $page_count) {
                $param['page'] = $page_count;
            }
            $pass = ($param['page'] - 1) * $param['limit'];
            $sql .= " LIMIT {$pass} , {$param['limit']} ";
        }
        $list = $model->createCommand($sql)->queryAll();

        $status_name       = array(1 => '成功' , 2 => '失败');
        $repay_status_name = array(0 => '待还' , 1 => '已还');
        foreach ($list as $key => $value) {
            $value['repay_money']  = number_format($value['repay_money'], 2, '.', ',');
            $value['end_time']     = date('Y-m-d H:i:s' , $value['end_time']);
            $value['status']       = $status_name[$value['status']];
            $value['repay_status'] = $repay_status_name[$value['repay_status']];
            if ($value['repay_yestime'] > 0) {
                $value['repay_yestime'] = date('Y-m-d H:i:s' , $value['repay_yestime']);
            } else {
                $value['repay_yestime'] = '——';
            }
            
            $listInfo[] = $value;
        }
        $result['code']  = 0;
        $result['count'] = $count;
        $result['data']  = $listInfo;
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
}
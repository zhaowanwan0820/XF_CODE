<?php
use iauth\models\AuthAssignment;

class CallBackController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'BorrowerInfo' , 'UserCallBackLogList',
            'GetShiJiRenShuData',
            'GetQuanGuoFenBuData',
            'GetJieKuanJineData',
            'GetQuestionData',
            'GetAgeData',
            'GetSexData',
            'GetZongLanData',
            'UserCallBackLogList'
        );
    }

    public function actionBorrowerInfo()
    {
        if (!empty($_POST)) {
            if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
                $this->echoJson(array(), 1, '请正确输入用户ID');
            }
            $user_id  = intval($_POST['user_id']);
            $sql      = "SELECT * FROM xf_yr_borrower WHERE user_id = {$user_id}";
            $borrower = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$borrower) {
                $this->echoJson(array(), 2, '此用户ID不在借款人名单中');
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = {$user_id} ";
            $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 3, '用户信息不存在');
            }
            if ($user_info['is_effect'] != 1) {
                $this->echoJson(array(), 4, '此用户的帐户状态无效');
            }
            if ($user_info['is_delete'] != 0) {
                $this->echoJson(array(), 5, '此用户的帐户已被删除');
            }
            // firstp2p_deal
            $sql = "SELECT id , name , borrow_amount , user_id , loantype , repay_time , success_time FROM firstp2p_deal WHERE user_id = {$user_id} AND is_effect = 1 AND is_delete = 0 AND deal_status = 4 ";
            $deal_info = Yii::app()->phdb->createCommand($sql)->queryALL();
            if (!$deal_info) {
                $this->echoJson(array(), 6, '此用户ID未查询到在途项目');
            }
            foreach ($deal_info as $key => $value) {
                $deal_id_arr[] = $value['id'];

                $deal_repay[$value['id']]['id']            = $value['id'];
                $deal_repay[$value['id']]['name']          = $value['name'];
                $deal_repay[$value['id']]['user_id']       = $value['user_id'];
                $deal_repay[$value['id']]['borrow_amount'] = number_format($value['borrow_amount'], 2, '.', ',');
                $deal_repay[$value['id']]['success_time']  = date("Y-m-d H:i:s", $value['success_time']);

                if ($value['loantype'] == 5) {
                    $deal_repay[$value['id']]['repay_time'] = $value['repay_time'].'天';
                } else {
                    $deal_repay[$value['id']]['repay_time'] = $value['repay_time'].'个月';
                }
            }
            $deal_id_str = implode(',', $deal_id_arr);
            // firstp2p_deal_loan_repay
            $sql = "SELECT 
                    SUM(CASE WHEN type = 1 AND status = 0 THEN money - repaid_amount ELSE 0 END) AS wait_capital , 
                    SUM(CASE WHEN type = 2 AND status = 0 THEN money - repaid_amount ELSE 0 END) AS wait_interest , 
                    SUM(CASE WHEN (type = 1 AND status = 1) OR (type = 3 AND status = 1) OR (type = 1 AND status = 0 AND repaid_amount > 0) THEN money - repaid_amount ELSE 0 END) AS yes_capital , 
                    SUM(CASE WHEN (type = 2 AND status = 1) OR (type = 7 AND status = 1) OR (type = 2 AND status = 0 AND repaid_amount > 0) THEN money - repaid_amount ELSE 0 END) AS yes_interest , 
                    deal_id , MAX(time) AS max_repay_time , MIN(time) AS min_repay_time 
                    FROM firstp2p_deal_loan_repay WHERE deal_id IN ({$deal_id_str}) GROUP BY deal_id ";
            $repay_info = Yii::app()->phdb->createCommand($sql)->queryALL();
            $time = time();
            foreach ($repay_info as $key => $value) {
                $deal_repay[$value['deal_id']]['wait_capital']   = number_format($value['wait_capital'], 2, '.', ',');
                $deal_repay[$value['deal_id']]['wait_interest']  = number_format($value['wait_interest'], 2, '.', ',');
                $deal_repay[$value['deal_id']]['yes_capital']    = number_format($value['yes_capital'], 2, '.', ',');
                $deal_repay[$value['deal_id']]['yes_interest']   = number_format($value['yes_interest'], 2, '.', ',');
                $deal_repay[$value['deal_id']]['max_repay_time'] = date("Y-m-d", ($value['max_repay_time'] + 28800));
                $deal_repay[$value['deal_id']]['overdue_day']    = round(($time - $value['min_repay_time']) / 86400);
            }
            foreach ($deal_repay as $key => $value) {
                $deal_repay_data[] = $value;
            }

            $sql = "SELECT * FROM xf_borrower_call_back_log WHERE user_id = {$user_id} ORDER BY id ";
            $log = Yii::app()->phdb->createCommand($sql)->queryRow();
            
            $result_data['real_name'] = $user_info['real_name'];
            $result_data['idno']      = $this->strEncrypt(GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key), 6, 8);
            $result_data['mobile']    = $this->strEncrypt(GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key), 3, 4);
            $result_data['deal_info'] = $deal_repay_data;
            $result_data['type']      = $borrower['type'];
            $result_data['type_else'] = $borrower['type_else'];
            if ($log) {
                $result_data['question_6']             = $log['question_6'];
                $result_data['question_7']             = $log['question_7'];
                $result_data['question_7_status']      = $log['question_7_status'];
                $result_data['question_7_status_else'] = $log['question_7_status_else'];
                $result_data['question_8']             = $log['question_8'];
                $result_data['question_8_else']        = $log['question_8_else'];
            } else {
                $result_data['question_6']             = 0;
                $result_data['question_7']             = '';
                $result_data['question_7_status']      = 0;
                $result_data['question_7_status_else'] = '';
                $result_data['question_8']             = 0;
                $result_data['question_8_else']        = '';
            }
            $this->echoJson($result_data, 0, '查询成功');
        }
    }

    public function actionAddCallBackLog()
    {
        if (!empty($_POST)) {
            if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
                $this->echoJson(array(), 1, "请正确输入用户ID");
            } elseif (empty($_POST['question_1_1']) || !in_array($_POST['question_1_1'], [1,2])) {
                $this->echoJson(array(), 1, "请正确选择号码状态");
            } elseif ($_POST['question_1_1'] == 2 && (empty($_POST['question_1_2']) || !in_array($_POST['question_1_2'], [2,3,4,5,6,7,8,9]))) {
                $this->echoJson(array(), 1, "请正确选择号码状态");
            } elseif ($_POST['question_1_1'] == 1 && (empty($_POST['question_2']) || !in_array($_POST['question_2'], [1,2]))) {
                $this->echoJson(array(), 1, "请正确选择接听人是否本人");
            } elseif ($_POST['question_1_1'] == 1 && $_POST['question_2'] == 2 && $_POST['question_2_else_1'] == '') {
                $this->echoJson(array(), 1, "请输入接听人");
            } elseif ($_POST['question_1_1'] == 1 && $_POST['question_2'] == 2 && $_POST['question_2_else_2'] == '') {
                $this->echoJson(array(), 1, "请输入接听人与本人关系");
            } elseif (empty($_POST['question_3']) || !in_array($_POST['question_3'], [1,2,3,4,5,6])) {
                $this->echoJson(array(), 1, "请正确选择拨打工具");
            } elseif ($_POST['question_3'] == 6 && $_POST['question_3_else'] == '') {
                $this->echoJson(array(), 1, "请输入拨打工具");
            } elseif (empty($_POST['question_4']) || !in_array($_POST['question_4'], [1,2,3,4,5,6])) {
                $this->echoJson(array(), 1, "请正确选择客户状态");
            } elseif ($_POST['question_4'] == 6 && $_POST['question_4_else'] == '') {
                $this->echoJson(array(), 1, "请输入客户状态");
            } elseif (empty($_POST['question_5']) || !in_array($_POST['question_5'], [1,2,3,4])) {
                $this->echoJson(array(), 1, "请正确选择客户标签");
            } elseif ($_POST['question_1_1'] == 1 && (empty($_POST['question_6']) || !in_array($_POST['question_6'], [1,2,3,4]))) {
                $this->echoJson(array(), 1, "请正确选择是否添加微信");
            } elseif ($_POST['question_1_1'] == 1 && $_POST['question_7'] == '') {
                $this->echoJson(array(), 1, "请输入关联公司");
            } elseif ($_POST['question_1_1'] == 1 && (empty($_POST['question_7_status']) || !in_array($_POST['question_7_status'], [1,2,3]))) {
                $this->echoJson(array(), 1, "请正确选择公司是否存续");
            } elseif ($_POST['question_1_1'] == 1 && $_POST['question_7_status'] == 3 && $_POST['question_7_status_else'] == '') {
                $this->echoJson(array(), 1, "请输入公司是否存续的其他");
            } elseif ($_POST['question_1_1'] == 1 && (empty($_POST['question_8']) || !in_array($_POST['question_8'], [1,2,3,4]))) {
                $this->echoJson(array(), 1, "请正确选择支付宝认证");
            } elseif ($_POST['question_1_1'] == 1 && $_POST['question_8'] == 4 && $_POST['question_8_else'] == '') {
                $this->echoJson(array(), 1, "请输入支付宝认证的其他");
            } elseif ($_POST['question_1_1'] == 1 && $_POST['remark'] == '') {
                $this->echoJson(array(), 1, "请输入跟进记录");
            } elseif ($_POST['question_1_1'] == 1 && (empty($_POST['type']) || !in_array($_POST['type'], [1,2,3,4,5,6,7,8,9]))) {
                $this->echoJson(array(), 1, "请正确选择问题类型");
            } elseif ($_POST['question_1_1'] == 1 && $_POST['type'] == 9 && $_POST['type_else'] == '') {
                $this->echoJson(array(), 1, "请输入问题类型");
            }
            $user_id = intval($_POST['user_id']);
            if ($_POST['question_1_1'] == 1) {
                $question_1 = 1;
            } elseif ($_POST['question_1_1'] == 2) {
                $question_1 = intval($_POST['question_1_2']);
            }
            $question_2             = intval($_POST['question_2']);
            $question_2_else        = '接听人：'.trim($_POST['question_2_else_1']).'；接听人与本人关系：'.trim($_POST['question_2_else_2']).'。';
            $question_3             = intval($_POST['question_3']);
            $question_3_else        = trim($_POST['question_3_else']);
            $question_4             = intval($_POST['question_4']);
            $question_4_else        = trim($_POST['question_4_else']);
            $question_5             = intval($_POST['question_5']);
            $question_6             = intval($_POST['question_6']);
            $question_7             = trim($_POST['question_7']);
            $question_7_status      = intval($_POST['question_7_status']);
            $question_7_status_else = trim($_POST['question_7_status_else']);
            $question_8             = intval($_POST['question_8']);
            $question_8_else        = trim($_POST['question_8_else']);
            $remark                 = trim($_POST['remark']);
            $type                   = intval($_POST['type']);
            $type_else              = trim($_POST['type_else']);
            $contact_status         = $question_1;

            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $sql = "SELECT * FROM firstp2p_user WHERE id = {$_POST['user_id']} AND is_effect = 1 AND is_delete = 0";
            $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 1, "用户信息不存在");
            }
            Yii::app()->phdb->beginTransaction();
            $sql = "INSERT INTO xf_borrower_call_back_log (user_id , mobile , add_time , add_user_id , remark , type , type_else , question_1 , question_2 , question_2_else , question_3 , question_3_else , question_4 , question_4_else , question_5 , question_6 , question_7 , question_7_status , question_7_status_else , question_8 , question_8_else) VALUES ('{$user_info['id']}' , '{$user_info['mobile']}' , {$time} , {$add_user_id} , '{$remark}' , '{$type}' , '{$type_else}' , '{$question_1}' , '{$question_2}' , '{$question_2_else}' , '{$question_3}' , '{$question_3_else}' , '{$question_4}' , '{$question_4_else}' , '{$question_5}' , '{$question_6}' , '{$question_7}' , '{$question_7_status}' , '{$question_7_status_else}' , '{$question_8}' , '{$question_8_else}') ";
            $add_log = Yii::app()->phdb->createCommand($sql)->execute();

            $sql = "UPDATE xf_yr_borrower SET call_times = call_times + 1 , last_call_time = {$time} , type = '{$type}' , type_else = '{$type_else}' , contact_status = '{$contact_status}' WHERE user_id = {$user_info['id']} ";
            $update = Yii::app()->phdb->createCommand($sql)->execute();
            if ($add_log && $update) {
                Yii::app()->phdb->commit();
                $this->echoJson(array(), 0, "录入成功");
            } else {
                Yii::app()->phdb->rollback();
                $this->echoJson(array(), 1, "录入失败");
            }
        }

        return $this->renderPartial('AddCallBackLog', array());
    }

    public function actionUserCallBackLogList()
    {
        if (!empty($_POST['user_id'])) {
            $model   = Yii::app()->phdb;
            $user_id = intval($_POST['user_id']);
            // 条件筛选
            $where = " WHERE user_id = {$user_id} ";
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(*) AS count FROM xf_borrower_call_back_log {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_borrower_call_back_log {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            $info_status = 0;
            if (!empty($authList) && strstr($authList, '/user/CallBack/CallBackLogInfo') || empty($authList)) {
                $info_status = 1;
            }
            $type = array(0 => '——' , 1 => '结清类' , 2 => '还款纠纷类' , 3 => '借款核实类' , 4 => '还款渠道身份类' , 5 => '负面影响类' , 6 => '拒绝还款类' , 7 => '减免类' , 8 => '死亡类' , 9 => '其他');
            $question_1 = array(0 => '——' , 1 => '可联' , 2 => '空号' , 3 => '停机' , 4 => '关机' , 5 => '无法接通' , 6 => '占线' , 7 => '挂断' , 8 => '无人接听' , 9 => '暂停服务');
            $question_2 = array(0 => '——' , 1 => '是' , 2 => '否');
            $question_3 = array(0 => '——' , 1 => '度言' , 2 => '属地' , 3 => '私人' , 4 => '微信' , 5 => '云客' , 6 => '其他');
            $question_4 = array(0 => '——' , 1 => '本人失联，无法代偿' , 2 => '恶意拖欠/质疑合同、金额等' , 3 => '工资拖欠' , 4 => '有意偿还，积极筹措资金' , 5 => '资金短缺，敷衍跳票' , 6 => '其他');
            $question_5 = array(0 => '——' , 1 => '可联' , 2 => '失联' , 3 => '不可联可修复' , 4 => '拒绝还款');
            $question_6 = array(0 => '——' , 1 => '已添加' , 2 => '未添加' , 3 => '等待验证' , 4 => '搜索不到');
            $question_7_status = array(0 => '——' , 1 => '是' , 2 => '否' , 3 => '其他');
            $question_8 = array(0 => '——' , 1 => '支付宝认证是本人' , 2 => '支付宝认证非本人' , 3 => '支付宝搜不到' , 4 => '其他');
            foreach ($list as $key => $value) {
                $value['add_time']          = date('Y-m-d H:i:s', $value['add_time']);
                $value['add_user_name']     = '';
                $value['info_status']       = $info_status;
                $value['type']              = $type[$value['type']];
                $value['question_1']        = $question_1[$value['question_1']];
                $value['question_2']        = $question_2[$value['question_2']];
                $value['question_3']        = $question_3[$value['question_3']];
                if ($value['question_4'] != 6) {
                    $value['question_4']    = $question_4[$value['question_4']];
                } else {
                    $value['question_4']    = $question_4[$value['question_4']].'：'.$value['question_4_else'];
                }
                $value['question_5']        = $question_5[$value['question_5']];
                $value['question_6']        = $question_6[$value['question_6']];
                $value['question_7_status'] = $question_7_status[$value['question_7_status']];
                $value['question_8']        = $question_8[$value['question_8']];

                $listInfo[] = $value;
                $user_id_arr[] = $value['add_user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',', $user_id_arr);
                $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
                $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['id']] = $value['realname'];
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['add_user_name'] = $user_infos[$value['add_user_id']];
                }
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        } else {
            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = array();
            $result_data['count'] = 0;
            $result_data['code']  = 1;
            $result_data['info']  = '请输入用户ID';
            echo exit(json_encode($result_data));
        }
    }

    public function actionCallBackUserList()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->phdb;
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            if (!empty($_POST['user_id'])) {
                $u      = intval($_POST['user_id']);
                $where .= " AND user_id = '{$u}' ";
            }
            if (!empty($_POST['real_name'])) {
                $r      = trim($_POST['real_name']);
                $where .= " AND real_name = '{$r}' ";
            }
            if (!empty($_POST['idno'])) {
                $i      = trim($_POST['idno']);
                $i      = GibberishAESUtil::enc($i, Yii::app()->c->idno_key);
                $where .= " AND idno = '{$i}' ";
            }
            if (!empty($_POST['mobile'])) {
                $m      = trim($_POST['mobile']);
                $m      = GibberishAESUtil::enc($m, Yii::app()->c->idno_key);
                $where .= " AND mobile = '{$m}' ";
            }
            if ($_POST['sex'] === '0' || $_POST['sex'] === '1') {
                $where .= " AND sex = '{$_POST['sex']}' ";
            }
            if (is_numeric($_POST['age_min']) && $_POST['age_min'] >= 1) {
                $age_min = date('Y', time()) - intval($_POST['age_min']);
                $where  .= " AND byear <= '{$age_min}' AND byear != 0 ";
            }
            if (is_numeric($_POST['age_max']) && $_POST['age_max'] <= 150) {
                $age_max = date('Y', time()) - intval($_POST['age_max']);
                $where  .= " AND byear >= '{$age_max}' ";
            }
            if (!empty($_POST['province'])) {
                $province = trim($_POST['province']);
                $where   .= " AND province = '{$province}' ";
            }
            if (!empty($_POST['city'])) {
                $city   = trim($_POST['city']);
                $where .= " AND city = '{$city}' ";
            }
            if (!empty($_POST['region'])) {
                $region = trim($_POST['region']);
                $where .= " AND region = '{$region}' ";
            }
            if (is_numeric($_POST['call_times']) && $_POST['call_times'] >= 0) {
                $call_times = intval($_POST['call_times']);
                $where     .= " AND call_times = '{$call_times}' ";
            }
            if (!empty($_POST['type'])) {
                $t      = intval($_POST['type']);
                $where .= " AND type = '{$t}' ";
            }
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND last_call_time >= '{$start}' ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND last_call_time <= '{$end}' AND last_call_time != 0 ";
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(*) AS count FROM xf_yr_borrower {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_yr_borrower {$where} ORDER BY last_call_time DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList    = \Yii::app()->user->getState('_auth');
            $info_status = 0;
            if (!empty($authList) && strstr($authList, '/user/CallBack/CallBackLogList') || empty($authList)) {
                $info_status = 1;
            }
            $sex  = array(-1 => '——' , 0 => '女' , 1 => '男');
            $type = array(0 => '——' , 1 => '结清类' , 2 => '还款纠纷类' , 3 => '借款核实类' , 4 => '还款渠道身份类' , 5 => '负面影响类' , 6 => '拒绝还款类' , 7 => '减免类' , 8 => '死亡类' , 9 => '其他');
            foreach ($list as $key => $value) {
                if ($value['idno']) {
                    $value['idno'] = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                    $value['idno'] = $this->strEncrypt($value['idno'], 6, 8);
                } else {
                    $value['idno'] = '——';
                }
                if ($value['mobile']) {
                    $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                    $value['mobile'] = $this->strEncrypt($value['mobile'], 3, 4);
                } else {
                    $value['mobile'] = '——';
                }
                $value['sex'] = $sex[$value['sex']];
                $value['age'] = date('Y', time()) - $value['byear'];
                if ($value['age'] > 150) {
                    $value['age'] = '——';
                }
                if ($value['type'] == 0) {
                    $value['type_name'] = '——';
                } elseif ($value['type'] != 9) {
                    $value['type_name'] = $type[$value['type']];
                } elseif ($value['type'] == 9) {
                    $value['type_name'] = $type[$value['type']].'：'.$value['type_else'];
                }
                if ($value['last_call_time'] > 0) {
                    $value['last_call_time'] = date('Y-m-d H:i:s', $value['last_call_time']);
                } else {
                    $value['last_call_time'] = '——';
                }
                $value['info_status'] = $info_status;

                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $CallBackUserList2Excel = 0;
        if (!empty($authList) && strstr($authList, '/user/CallBack/CallBackUserList2Excel') || empty($authList)) {
            $CallBackUserList2Excel = 1;
        }
        return $this->renderPartial('CallBackUserList', array('CallBackUserList2Excel' => $CallBackUserList2Excel));
    }

    public function actionCallBackUserList2Excel()
    {
        if (!empty($_GET)) {
            $model = Yii::app()->phdb;
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            if (!empty($_GET['user_id'])) {
                $u      = intval($_GET['user_id']);
                $where .= " AND user_id = '{$u}' ";
            }
            if (!empty($_GET['real_name'])) {
                $r      = trim($_GET['real_name']);
                $where .= " AND real_name = '{$r}' ";
            }
            if (!empty($_GET['idno'])) {
                $i      = trim($_GET['idno']);
                $i      = GibberishAESUtil::enc($i, Yii::app()->c->idno_key);
                $where .= " AND idno = '{$i}' ";
            }
            if (!empty($_GET['mobile'])) {
                $m      = trim($_GET['mobile']);
                $m      = GibberishAESUtil::enc($m, Yii::app()->c->idno_key);
                $where .= " AND mobile = '{$m}' ";
            }
            if ($_GET['sex'] === '0' || $_GET['sex'] === '1') {
                $where .= " AND sex = '{$_GET['sex']}' ";
            }
            if (is_numeric($_GET['age_min']) && $_GET['age_min'] >= 1) {
                $age_min = date('Y', time()) - intval($_GET['age_min']);
                $where  .= " AND byear <= '{$age_min}' AND byear != 0 ";
            }
            if (is_numeric($_GET['age_max']) && $_GET['age_max'] <= 150) {
                $age_max = date('Y', time()) - intval($_GET['age_max']);
                $where  .= " AND byear >= '{$age_max}' ";
            }
            if (!empty($_GET['province'])) {
                $province = trim($_GET['province']);
                $where   .= " AND province = '{$province}' ";
            }
            if (!empty($_GET['city'])) {
                $city   = trim($_GET['city']);
                $where .= " AND city = '{$city}' ";
            }
            if (!empty($_GET['region'])) {
                $region = trim($_GET['region']);
                $where .= " AND region = '{$region}' ";
            }
            if (is_numeric($_GET['call_times']) && $_GET['call_times'] >= 0) {
                $call_times = intval($_GET['call_times']);
                $where     .= " AND call_times = '{$call_times}' ";
            }
            if (!empty($_GET['type'])) {
                $t      = intval($_GET['type']);
                $where .= " AND type = '{$t}' ";
            }
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start']);
                $where .= " AND last_call_time >= '{$start}' ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end']);
                $where .= " AND last_call_time <= '{$end}' AND last_call_time != 0 ";
            }
            // 查询数据
            $sql  = "SELECT * FROM xf_yr_borrower {$where} ORDER BY last_call_time DESC ";
            $list = $model->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            // 获取当前账号所有子权限
            $authList    = \Yii::app()->user->getState('_auth');
            $info_status = 0;
            if (!empty($authList) && strstr($authList, '/user/CallBack/CallBackLogList') || empty($authList)) {
                $info_status = 1;
            }
            $sex  = array(-1 => '——' , 0 => '女' , 1 => '男');
            $type = array(0 => '——' , 1 => '结清类' , 2 => '还款纠纷类' , 3 => '借款核实类' , 4 => '还款渠道身份类' , 5 => '负面影响类' , 6 => '拒绝还款类' , 7 => '减免类' , 8 => '死亡类' , 9 => '其他');
            foreach ($list as $key => $value) {
                if ($value['idno']) {
                    $value['idno'] = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                } else {
                    $value['idno'] = '——';
                }
                if ($value['mobile']) {
                    $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                } else {
                    $value['mobile'] = '——';
                }
                $value['sex'] = $sex[$value['sex']];
                $value['age'] = date('Y', time()) - $value['byear'];
                if ($value['age'] > 150) {
                    $value['age'] = '——';
                }
                if ($value['type'] == 0) {
                    $value['type_name'] = '——';
                } elseif ($value['type'] != 9) {
                    $value['type_name'] = $type[$value['type']];
                } elseif ($value['type'] == 9) {
                    $value['type_name'] = $type[$value['type']].'：'.$value['type_else'];
                }
                if ($value['last_call_time'] > 0) {
                    $value['last_call_time'] = date('Y-m-d H:i:s', $value['last_call_time']);
                } else {
                    $value['last_call_time'] = '——';
                }
                $value['info_status'] = $info_status;

                $listInfo[] = $value;
            }
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel2007.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '用户姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '用户手机号');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '用户证件号');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '性别');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '年龄');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '省');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '市');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '区');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '进件编号');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '拨打次数');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '问题类型');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', '最后一次拨打时间');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['real_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['mobile']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['idno']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['sex']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['age']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['province']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['city']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['region']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $value['number']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['call_times']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $value['type_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2), $value['last_call_time']);
            }

            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '借款人呼叫管理借款人查询 '.date("Y年m月d日 H时i分s秒", time());

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xlsx"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
        }
    }

    public function actionCallBackLogList()
    {
        if (!empty($_POST)) {
            $model   = Yii::app()->phdb;
            $user_id = intval($_POST['user_id']);
            // 条件筛选
            $where = " WHERE user_id = {$user_id} ";
            if (!empty($_POST['add_user_name'])) {
                $n   = trim($_POST['add_user_name']);
                $sql = "SELECT id FROM itz_user WHERE realname = '{$n}' ";
                $add_user_id = Yii::app()->db->createCommand($sql)->queryScalar();
                if ($add_user_id) {
                    $where .= " AND add_user_id = {$add_user_id} ";
                } else {
                    $where .= " AND add_user_id IS NULL ";
                }
            }
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND add_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND add_time <= {$end} ";
            }
            if (!empty($_POST['question_1'])) {
                $q1     = intval($_POST['question_1']);
                $where .= " AND question_1 = {$q1} ";
            }
            if (!empty($_POST['question_4'])) {
                $q4     = intval($_POST['question_4']);
                $where .= " AND question_4 = {$q4} ";
            }
            if (!empty($_POST['question_5'])) {
                $q5     = intval($_POST['question_5']);
                $where .= " AND question_5 = {$q5} ";
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(*) AS count FROM xf_borrower_call_back_log {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_borrower_call_back_log {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList    = \Yii::app()->user->getState('_auth');
            $info_status = 0;
            if (!empty($authList) && strstr($authList, '/user/CallBack/CallBackLogInfo') || empty($authList)) {
                $info_status = 1;
            }
            $type = array(0 => '——' , 1 => '结清类' , 2 => '还款纠纷类' , 3 => '借款核实类' , 4 => '还款渠道身份类' , 5 => '负面影响类' , 6 => '拒绝还款类' , 7 => '减免类' , 8 => '死亡类' , 9 => '其他');
            $question_1 = array(0 => '——' , 1 => '可联' , 2 => '空号' , 3 => '停机' , 4 => '关机' , 5 => '无法接通' , 6 => '占线' , 7 => '挂断' , 8 => '无人接听' , 9 => '暂停服务');
            $question_2 = array(0 => '——' , 1 => '是' , 2 => '否');
            $question_3 = array(0 => '——' , 1 => '度言' , 2 => '属地' , 3 => '私人' , 4 => '微信' , 5 => '云客' , 6 => '其他');
            $question_4 = array(0 => '——' , 1 => '本人失联，无法代偿' , 2 => '恶意拖欠/质疑合同、金额等' , 3 => '工资拖欠' , 4 => '有意偿还，积极筹措资金' , 5 => '资金短缺，敷衍跳票' , 6 => '其他');
            $question_5 = array(0 => '——' , 1 => '可联' , 2 => '失联' , 3 => '不可联可修复' , 4 => '拒绝还款');
            $question_6 = array(0 => '——' , 1 => '已添加' , 2 => '未添加' , 3 => '等待验证' , 4 => '搜索不到');
            $question_7_status = array(0 => '——' , 1 => '是' , 2 => '否' , 3 => '其他');
            $question_8 = array(0 => '——' , 1 => '支付宝认证是本人' , 2 => '支付宝认证非本人' , 3 => '支付宝搜不到' , 4 => '其他');
            foreach ($list as $key => $value) {
                $value['add_time']          = date('Y-m-d H:i:s', $value['add_time']);
                $value['info_status']       = $info_status;
                $value['type']              = $type[$value['type']];
                $value['question_1']        = $question_1[$value['question_1']];
                $value['question_2']        = $question_2[$value['question_2']];
                $value['question_3']        = $question_3[$value['question_3']];
                if ($value['question_4'] != 6) {
                    $value['question_4']    = $question_4[$value['question_4']];
                } else {
                    $value['question_4']    = $question_4[$value['question_4']].'：'.$value['question_4_else'];
                }
                $value['question_5']        = $question_5[$value['question_5']];
                $value['question_6']        = $question_6[$value['question_6']];
                $value['question_7_status'] = $question_7_status[$value['question_7_status']];
                $value['question_8']        = $question_8[$value['question_8']];

                $listInfo[] = $value;
                $user_id_arr[] = $value['add_user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',', $user_id_arr);
                $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
                $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['id']] = $value['realname'];
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['add_user_name'] = $user_infos[$value['add_user_id']];
                }
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('CallBackLogList', array());
    }

    public function actionCallBackLogInfo()
    {
        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误', 5);
            }
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_borrower_call_back_log WHERE id = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID', 5);
            }
            $type = array(0 => '——' , 1 => '结清类' , 2 => '还款纠纷类' , 3 => '借款核实类' , 4 => '还款渠道身份类' , 5 => '负面影响类' , 6 => '拒绝还款类' , 7 => '减免类' , 8 => '死亡类' , 9 => '其他');
            $question_1 = array(0 => '——' , 1 => '可联' , 2 => '空号' , 3 => '停机' , 4 => '关机' , 5 => '无法接通' , 6 => '占线' , 7 => '挂断' , 8 => '无人接听' , 9 => '暂停服务');
            $question_2 = array(0 => '——' , 1 => '是' , 2 => '否');
            $question_3 = array(0 => '——' , 1 => '度言' , 2 => '属地' , 3 => '私人' , 4 => '微信' , 5 => '云客' , 6 => '其他');
            $question_4 = array(0 => '——' , 1 => '本人失联，无法代偿' , 2 => '恶意拖欠/质疑合同、金额等' , 3 => '工资拖欠' , 4 => '有意偿还，积极筹措资金' , 5 => '资金短缺，敷衍跳票' , 6 => '其他');
            $question_5 = array(0 => '——' , 1 => '可联' , 2 => '失联' , 3 => '不可联可修复' , 4 => '拒绝还款');
            $question_6 = array(0 => '——' , 1 => '已添加' , 2 => '未添加' , 3 => '等待验证' , 4 => '搜索不到');
            $question_7_status = array(0 => '——' , 1 => '是' , 2 => '否' , 3 => '其他');
            $question_8 = array(0 => '——' , 1 => '支付宝认证是本人' , 2 => '支付宝认证非本人' , 3 => '支付宝搜不到' , 4 => '其他');
            if ($res['remark'] == '') {
                $res['remark'] = '——';
            }
            if ($res['type'] != 9) {
                $res['type'] = $type[$res['type']];
            } else {
                $res['type'] = $type[$res['type']].'：'.$res['type_else'];
            }
            $res['question_1'] = $question_1[$res['question_1']];
            if ($res['question_2'] != 2) {
                $res['question_2'] = $question_2[$res['question_2']];
            } else {
                $res['question_2'] = $question_2[$res['question_2']].'。'.$res['question_2_else'];
            }
            if ($res['question_3'] != 6) {
                $res['question_3'] = $question_3[$res['question_3']];
            } else {
                $res['question_3'] = $question_3[$res['question_3']].'：'.$res['question_3_else'];
            }
            if ($res['question_4'] != 6) {
                $res['question_4'] = $question_4[$res['question_4']];
            } else {
                $res['question_4'] = $question_4[$res['question_4']].'：'.$res['question_4_else'];
            }
            $res['question_5'] = $question_5[$res['question_5']];
            $res['question_6'] = $question_6[$res['question_6']];
            if ($res['question_7'] == '') {
                $res['question_7'] = '——';
            }
            if ($res['question_7_status'] != 3) {
                $res['question_7_status'] = $question_7_status[$res['question_7_status']];
            } else {
                $res['question_7_status'] = $question_7_status[$res['question_7_status']].'：'.$res['question_7_status_else'];
            }
            if ($res['question_8'] != 4) {
                $res['question_8'] = $question_8[$res['question_8']];
            } else {
                $res['question_8'] = $question_8[$res['question_8']].'：'.$res['question_8_else'];
            }

            return $this->renderPartial('CallBackLogInfo', array('res' => $res));
        }
    }

    public function actionRepayProof()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->phdb;
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            // 用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND user.id = '{$user_id}' ";
            }
            // 用户姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND user.real_name = '{$real_name}' ";
            }
            // 用户证件号
            if (!empty($_POST['idno'])) {
                $idno   = GibberishAESUtil::enc(trim($_POST['idno']), Yii::app()->c->idno_key);
                $where .= " AND user.idno = '{$idno}' ";
            }
            // 手机号
            if (!empty($_POST['mobile'])) {
                $mobile = GibberishAESUtil::enc(trim($_POST['mobile']), Yii::app()->c->idno_key);
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql   = "SELECT count(*) AS count FROM xf_borrower_repay_proof AS proof INNER JOIN firstp2p_user AS user ON proof.user_id = user.id {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT proof.* , user.real_name , user.idno , user.mobile FROM xf_borrower_repay_proof AS proof INNER JOIN firstp2p_user AS user ON proof.user_id = user.id ORDER BY user.id ASC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();
            // 获取当前账号所有子权限
            $authList     = \Yii::app()->user->getState('_auth');
            $info_status  = 0;
            if (!empty($authList) && strstr($authList, '/user/CallBack/RepayProofInfo') || empty($authList)) {
                $info_status = 1;
            }
            foreach ($list as $key => $value) {
                $value['add_time']    = date('Y-m-d H:i:s', $value['add_time']);
                $value['idno']        = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                $value['mobile']      = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                $value['info_status'] = $info_status;

                $listInfo[]    = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('RepayProof', array());
    }

    public function actionRepayProofInfo()
    {
        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT proof.* , user.real_name , user.idno , user.mobile FROM xf_borrower_repay_proof AS proof INNER JOIN firstp2p_user AS user ON proof.user_id = user.id WHERE proof.id = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            $res['add_time'] = date('Y-m-d H:i:s', $res['add_time']);
            $res['idno']     = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            $res['mobile']   = GibberishAESUtil::dec($res['mobile'], Yii::app()->c->idno_key);
            $temp = json_decode($res['proof_photograph'], true);
            foreach ($temp as $key => $value) {
                $res['proof'][] = Yii::app()->c->oss_preview_address.$value;
            }

            return $this->renderPartial('RepayProofInfo', array('res' => $res));
        }
    }
    /**
     * 出借人统计页
     *
     * @return void
     */
    public function actionStatistics()
    {
        return $this->renderPartial('Statistics', array('res' => []));
    }

    /**
     * 总览数据
     * @return void
     *
     */
    public function actionGetZongLanData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];

        //联系状态分组查询
        $sql = "SELECT contact_status, count(1) AS count FROM xf_yr_borrower  group by contact_status ";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

        $contact_name = Yii::app()->c->xf_config['contact_name'];
        $data1 = $data2 = ['total' => 0, 'detail' => []];
        foreach ($count_data as $key=>$value) {
            //累计人数统计
            $data1['total'] +=  $value['count'];
            if (in_array($value['contact_status'], [0, 1])) {
                $data1['detail'][] = [
                    'name' => $contact_name[$value['contact_status']],
                    'value' => $value['count']
                ];
            } else {
                //失联
                $lost['name'] = '失联人数';
                $lost['value'] += $value['count'];
        
            
                //失联明细
                $data2['detail'][] = [
                    'name' => $contact_name[$value['contact_status']],
                    'value' => $value['count']
                ];
            }
        }
        $data1['detail'][]= $lost;
        $data2['total'] =  $lost['value'];
        $result_data['data']['data1'] = $data1;
        $result_data['data']['data2'] = $data2;
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }

    /**
     * 男女人数
     *
     * @return void
     */
    public function actionGetSexData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];

        //查询
        $sql = "SELECT sex, count(1) AS count FROM xf_yr_borrower  group by sex ";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

        $sex_name = Yii::app()->c->xf_config['sex_name'];
        $data1 = ['total' => 0, 'detail' => []];
        foreach ($count_data as $key=>$value) {
            $data1['total'] +=  $value['count'];
            $data1['detail'][$value['sex']] = [
                'name' => $sex_name[$value['sex']],
                'value' => $value['count']
            ];
        }

        $result_data['data'] = $data1;
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }
    /**
     * 年龄分布
     *
     * @return void
     */
    public function actionGetAgeData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];

        //查询
        $sql = "SELECT byear, count(1) AS count FROM xf_yr_borrower  group by byear ";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

        $age_group = Yii::app()->c->xf_config['age_group'];
        $data['name_list'] = array_keys($age_group);
        $value_list = array_fill_keys($data['name_list'], "0");
        foreach ($count_data as $key=>$value) {
            $age = date('Y', time()) - $value['byear'];
            foreach ($age_group as $k => $v) {
                if ($age > $v['max'] || $v < $v['min']) {
                    continue;
                }
                $value_list[$k] += $value['count'];
                break;
            }
        }
        $data['value_list'] = array_values($value_list);
        $result_data['data'] = $data;
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }

    
    /**
     * 问题类型分类统计
     *
     * @return void
     */
    public function actionGetQuestionData()
    {
        header("Content-type:application/json; charset=utf-8");

        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];

        //查询
        $sql = "SELECT type, count(1) AS count FROM xf_yr_borrower  group by type ";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

        $question_type = Yii::app()->c->xf_config['question_type'];
        $name_list = array_values($question_type);
        $value_list = array_fill_keys($name_list, "0");
       
        $total = 0;
        foreach ($count_data as $key=>$value) {
            $value_list[$question_type[$value['type']]] += $value['count'];
            $total+=$value['count'];
        }
        $wei_gui_lei = $value_list['未归类'];
        unset($value_list['未归类']);

        asort($value_list);
        $data['name_list'] = array_keys($value_list);
        $data['value_list'] = array_values($value_list);
        $result_data['data'] = $data;
        $result_data['data']['weiguilei'] =  number_format($wei_gui_lei);
        $result_data['data']['total'] =  number_format($total);

        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }

    /**
     * 各借款金额区间人数统计
     *
     * @return void
     */
    public function actionGetJieKuanJineData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];

        //查询
        $sql = "SELECT wait_capital, count(1) AS count FROM xf_yr_borrower  group by wait_capital ";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

        $wait_capital_group = Yii::app()->c->xf_config['wait_capital_group'];
        $data['name_list'] = array_keys($wait_capital_group);
        $value_list = array_fill_keys($data['name_list'], "0");
        foreach ($count_data as $key=>$value) {
            foreach ($wait_capital_group as $k => $v) {
                if ($value['wait_capital'] >= $v['max'] || $value['wait_capital'] < $v['min']) {
                    continue;
                }
                $value_list[$k] += $value['count'];
                break;
            }
        }
        $data['value_list'] = array_values($value_list);
        $result_data['data'] = $data;
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }
    /**
     * 省级地域人数统计
     *
     * @return void
     */
    public function actionGetQuanGuoFenBuData()
    {
        header("Content-type:application/json; charset=utf-8");

        $pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $page = \Yii::app()->request->getParam('page') ?: 1;
        $offset = ($page - 1) * $pageSize;
        $from = \Yii::app()->request->getParam('from');

        $result_data = ['code'=>0, 'info'=>''];
        $sql = "SELECT province, count(1) AS count FROM xf_yr_borrower  group by province order by count desc  ";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

        $data = [];
        foreach ($count_data as $key => $value) {
            $data[] = [
                'order' =>$key+1,
                'name' => $value['province'],
                'value' => $value['count'],
            ];
        }
        if ($from != 'list') {
            $result_data['data'] = $data;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }
        $list = array_splice($data, $offset, $pageSize);
        $result_data['countNum'] = count($count_data);
        $result_data['list'] = $list;
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }
    
    /**
     * 市级地域人数统计
     *
     * @return void
     */
    public function actionGetShiJiRenShuData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];

        $from = \Yii::app()->request->getParam('from');
    
        if ($from == 'list') {
            $pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $page = \Yii::app()->request->getParam('page') ?: 1;
            $offset = ($page - 1) * $pageSize;


            $sql = "SELECT count(distinct(city)) AS count FROM xf_yr_borrower ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();


            $sql = "SELECT city, count(1) AS count FROM xf_yr_borrower  group by city order by count desc ,city asc limit {$offset}, {$pageSize} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();

            foreach ($list as $key=>&$value) {
                $value['order'] = $key +1;
                $value['city'] = $value['city']?:'未知';
            }
            
            $result_data['countNum'] =  $count;
            $result_data['list'] = $list;
        
            echo exit(json_encode($result_data));
        } else {
            $sql = "SELECT city, count(1) AS count FROM xf_yr_borrower  group by city order by count desc ,city asc limit 20 ";
            $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
            if (!$count_data) {
                $result_data['info']  = '暂无数据';
                echo exit(json_encode($result_data));
            }
        }

        $data = [];
        foreach ($count_data as $key=>$value) {
            $data[$value['city']] = $value['count'];
        }
        $result_data['data']['name_list'] = array_keys($data);
        $result_data['data']['value_list'] = array_values($data);
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }
}

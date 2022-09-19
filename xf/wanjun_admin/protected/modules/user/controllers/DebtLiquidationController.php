<?php
use iauth\models\AuthAssignment;

class DebtLiquidationController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'UserCallBackLogList',
            'GetShiJiRenShuData',
            'GetQuanGuoFenBuData',
            'GetGiftData',
            'GetAgeData',
            'GetSexData',
            'GetZongLanData'
        );
    }

    /**
     * 用户债权查询
     */
    public function actionUserDebt()
    {
        if (!empty($_POST)) {
            if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
                $this->echoJson(array(), 1, '请正确输入用户ID');
            }
            $user_id   = intval($_POST['user_id']);
            $sql       = "SELECT user.* FROM firstp2p_user AS user INNER JOIN xf_debt_exchange_user_allow_list AS list ON user.id = list.user_id WHERE user.id = {$user_id} AND list.status = 1 ";
            $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 2, '用户ID不在积分兑换白名单中');
            }
            if ($user_info['is_effect'] != 1) {
                $this->echoJson(array(), 3, '此用户的帐户状态无效');
            }
            if ($user_info['is_delete'] != 0) {
                $this->echoJson(array(), 4, '此用户的帐户已被删除');
            }
            // 用户投资项目
            $sql         = "SELECT DISTINCT deal_id FROM firstp2p_deal_load WHERE user_id = {$user_id} ";
            $ph_deal_id  = Yii::app()->phdb->createCommand($sql)->queryColumn();
            $sql         = "SELECT DISTINCT deal_id FROM offline_deal_load WHERE user_id = {$user_id} AND platform_id = 4 ";
            $zdx_deal_id = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
            if ($ph_deal_id) {
                $ph_deal_id_str = implode(',', $ph_deal_id);
            } else {
                $ph_deal_id_str = '';
            }
            if ($zdx_deal_id) {
                $zdx_deal_id_str = implode(',', $zdx_deal_id);
            } else {
                $zdx_deal_id_str = '';
            }

            // 普惠在途本金
            $ph_sql = "SELECT SUM(wait_capital) AS deal_load_wait_capital FROM firstp2p_deal_load WHERE user_id = {$user_id} AND debt_status = 0 AND black_status = 1 AND clear_status = 0 AND status = 1 ";
            // 排除普惠常规还款
            if ($ph_deal_id_str) {
                $ph_repayment_plan_deal_id      = array();
                $ph_repayment_plan_deal_loan_id = array();
                $sql = "SELECT deal_loan_id , loan_user_id , repay_type , deal_id FROM ag_wx_repayment_plan WHERE deal_id IN ({$ph_deal_id_str}) AND status IN (0 , 1 , 2) ";
                $ph_repayment_plan_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($ph_repayment_plan_res) {
                    foreach ($ph_repayment_plan_res as $key => $value) {
                        if ($value['repay_type'] == 1) {
                            // 常规还款
                            $ph_repayment_plan_deal_id[] = $value['deal_id'];
                        } elseif ($value['repay_type'] == 2) {
                            // 特殊还款
                            if (!empty($value['deal_loan_id'])) {
                                $temp = explode(',', $value['deal_loan_id']);
                                foreach ($temp as $k => $v) {
                                    if ($v) {
                                        $ph_repayment_plan_deal_loan_id[] = $v;
                                    }
                                }
                            }
                            if (!empty($value['loan_user_id'])) {
                                $temp_a = explode(',', $value['loan_user_id']);
                                foreach ($temp_a as $i => $j) {
                                    if ($j && $j == $user_id) {
                                        $ph_repayment_plan_deal_id[] = $value['deal_id'];
                                    }
                                }
                            }
                        }
                    }
                }
                if ($ph_repayment_plan_deal_id) {
                    $ph_repayment_plan_deal_id_str = implode(',', $ph_repayment_plan_deal_id);
                    $ph_sql .= " AND deal_id NOT IN ({$ph_repayment_plan_deal_id_str}) ";
                }
                if ($ph_repayment_plan_deal_loan_id) {
                    $ph_repayment_plan_deal_loan_id_str = implode(',', $ph_repayment_plan_deal_loan_id);
                    $ph_sql .= " AND id NOT IN ({$ph_repayment_plan_deal_loan_id_str}) ";
                }
            }
            // 排除普惠部分还款
            if ($ph_deal_id_str) {
                $sql = "SELECT prd.deal_loan_id FROM ag_wx_partial_repayment AS pr INNER JOIN ag_wx_partial_repay_detail AS prd ON pr.id = prd.partial_repay_id AND pr.status IN (1 , 2) AND prd.user_id = {$user_id} AND prd.status = 1 AND prd.deal_id IN ({$ph_deal_id_str}) ";
                $ph_partial_repayment = Yii::app()->phdb->createCommand($sql)->queryColumn();
                if ($ph_partial_repayment) {
                    $ph_partial_repayment_str = implode(',', $ph_partial_repayment);
                    $ph_sql .= " AND id NOT IN ({$ph_partial_repayment_str}) ";
                }
            }
            // 排除普惠黑名单
            if ($ph_deal_id_str) {
                $sql = "SELECT deal_id FROM ag_wx_debt_black_list WHERE type = 2 AND status = 1 AND deal_id IN ({$ph_deal_id_str}) ";
                $ph_black_list = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($ph_black_list) {
                    $ph_black_list_str = implode(',', $ph_black_list);
                    $ph_sql .= " AND deal_id NOT IN ({$ph_black_list_str}) ";
                }
            }
            $ph_deal_load_wait_capital = Yii::app()->phdb->createCommand($ph_sql)->queryScalar();
            
            // 智多新在途本金
            $zdx_sql = "SELECT SUM(wait_capital) AS deal_load_wait_capital FROM offline_deal_load WHERE user_id = {$user_id} AND debt_status = 0 AND black_status = 1 AND clear_status = 0 AND status = 1 AND platform_id = 4 ";
            // 排除智多新常规还款
            if ($zdx_deal_id_str) {
                $zdx_repayment_plan_deal_id      = array();
                $zdx_repayment_plan_deal_loan_id = array();
                $sql = "SELECT deal_loan_id , loan_user_id , repay_type , deal_id FROM offline_wx_repayment_plan WHERE deal_id IN ({$zdx_deal_id_str}) AND status IN (0 , 1 , 2) ";
                $zdx_repayment_plan_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                if ($zdx_repayment_plan_res) {
                    foreach ($zdx_repayment_plan_res as $key => $value) {
                        if ($value['repay_type'] == 1) {
                            // 常规还款
                            $zdx_repayment_plan_deal_id[] = $value['deal_id'];
                        } elseif ($value['repay_type'] == 2) {
                            // 特殊还款
                            if (!empty($value['deal_loan_id'])) {
                                $temp = explode(',', $value['deal_loan_id']);
                                foreach ($temp as $k => $v) {
                                    if ($v) {
                                        $zdx_repayment_plan_deal_loan_id[] = $v;
                                    }
                                }
                            }
                            if (!empty($value['loan_user_id'])) {
                                $temp_a = explode(',', $value['loan_user_id']);
                                foreach ($temp_a as $i => $j) {
                                    if ($j && $j == $user_id) {
                                        $zdx_repayment_plan_deal_id[] = $value['deal_id'];
                                    }
                                }
                            }
                        }
                    }
                }
                if ($zdx_repayment_plan_deal_id) {
                    $zdx_repayment_plan_deal_id_str = implode(',', $zdx_repayment_plan_deal_id);
                    $zdx_sql .= " AND deal_id NOT IN ({$zdx_repayment_plan_deal_id_str}) ";
                }
                if ($zdx_repayment_plan_deal_loan_id) {
                    $zdx_repayment_plan_deal_loan_id_str = implode(',', $zdx_repayment_plan_deal_loan_id);
                    $zdx_sql .= " AND id NOT IN ({$zdx_repayment_plan_deal_loan_id_str}) ";
                }
            }
            // 排除智多新部分还款
            if ($zdx_deal_id_str) {
                $sql = "SELECT prd.deal_loan_id FROM offline_partial_repay AS pr INNER JOIN offline_partial_repay_detail AS prd ON pr.id = prd.partial_repay_id AND pr.status IN (1 , 2) AND prd.user_id = {$user_id} AND prd.status = 1 AND prd.deal_id IN ({$zdx_deal_id_str}) ";
                $zdx_partial_repayment = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if ($zdx_partial_repayment) {
                    $zdx_partial_repayment_str = implode(',', $zdx_partial_repayment);
                    $zdx_sql .= " AND id NOT IN ({$zdx_partial_repayment_str}) ";
                }
            }
            // 排除智多新黑名单
            if ($zdx_deal_id_str) {
                $sql = "SELECT deal_id FROM ag_wx_debt_black_list WHERE type = 4 AND status = 1 AND deal_id IN ({$zdx_deal_id_str}) ";
                $zdx_black_list = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($zdx_black_list) {
                    $zdx_black_list_str = implode(',', $zdx_black_list);
                    $zdx_sql .= " AND deal_id NOT IN ({$zdx_black_list_str}) ";
                }
            }
            $zdx_deal_load_wait_capital = Yii::app()->offlinedb->createCommand($zdx_sql)->queryScalar();

            $deal_load_wait_capital = $ph_deal_load_wait_capital + $zdx_deal_load_wait_capital;
            if ($deal_load_wait_capital == 0) {
                $this->echoJson(array(), 5, '此用户的在途本金为零');
            }

            $sql = "SELECT * FROM xf_gift_exchange WHERE exchange_min <= {$deal_load_wait_capital} AND exchange_max >= {$deal_load_wait_capital} ";
            $gift_exchange = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$gift_exchange) {
                $this->echoJson(array(), 6, "此用户的在途本金为：{$deal_load_wait_capital}，未匹配到礼包档位");
            }

            $result_data['user_real_name']         = $user_info['real_name'];
            $result_data['user_mobile'] = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key);
            $result_data['deal_load_wait_capital'] = number_format($deal_load_wait_capital, 2, '.', ',');
            $result_data['gift_name']              = $gift_exchange['name'];
            $result_data['gift_exchange_user']     = $gift_exchange['liquidation_user'];
            $result_data['gift_min_max']           = number_format($gift_exchange['exchange_min'], 2, '.', ',').' - '.number_format($gift_exchange['exchange_max'], 2, '.', ',');

            $goods_url = $this->curlRequest("https://shop.xfuser.com/api/product/xf/url?amount={$deal_load_wait_capital}");
            if ($goods_url['status'] == 200) {
                $result_data['goods_url'] = $goods_url['data']['url'];
            } else {
                $result_data['goods_url'] = '';
            }

            $sql = "SELECT remark FROM xf_debt_liquidation_user_details WHERE user_id = {$user_id} ";
            $remark = Yii::app()->phdb->createCommand($sql)->queryScalar();
            if ($remark) {
                $result_data['remark'] = $remark;
            } else {
                $result_data['remark'] = '';
            }
            $this->echoJson($result_data, 0, '查询成功');
        }

        return $this->renderPartial('UserDebt');
    }

    /**
     * 礼包兑换统计
     */
    public function actionGiftExchange()
    {
        if (!empty($_POST)) {
            set_time_limit(0);
            // 条件筛选
            $model = Yii::app()->phdb;
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
            $sql = "SELECT count(*) AS count FROM xf_gift_exchange ";
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
            $sql = "SELECT * FROM xf_gift_exchange ";
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
            if (!$list) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            foreach ($list as $key => $value) {
                $value['exchange_min']          = number_format($value['exchange_min'], 2, '.', ',');
                $value['exchange_max']          = number_format($value['exchange_max'], 2, '.', ',');
                $value['exchange_min_max']      = "{$value['exchange_min']} - {$value['exchange_max']}";
                $value['plan_yr_debt_total']    = number_format($value['plan_yr_debt_total'], 2, '.', ',');
                $value['yr_debt_total']         = number_format($value['yr_debt_total'], 2, '.', ',');
                $value['plan_debt_total']       = number_format($value['plan_debt_total'], 2, '.', ',');
                $value['debt_total']            = number_format($value['debt_total'], 2, '.', ',');
                $value['plan_liquidation_cost'] = number_format($value['plan_liquidation_cost'], 2, '.', ',');
                $value['liquidation_cost']      = number_format($value['liquidation_cost'], 2, '.', ',');
                $value['avg_proportion']        = number_format($value['avg_proportion'], 2, '.', ',');
                $value['avg_debt']              = number_format($value['avg_debt'], 2, '.', ',');
                $value['avg_liquidation_cost']  = number_format($value['avg_liquidation_cost'], 2, '.', ',');
                $value['kpi_1_min']             = number_format($value['kpi_1_min'], 2, '.', ',');
                $value['kpi_1_max']             = number_format($value['kpi_1_max'], 2, '.', ',');
                $value['kpi_2_min']             = number_format($value['kpi_2_min'], 2, '.', ',');
                $value['kpi_2_max']             = number_format($value['kpi_2_max'], 2, '.', ',');
                $value['kpi_3_min']             = number_format($value['kpi_3_min'], 2, '.', ',');
                $value['kpi_3_max']             = number_format($value['kpi_3_max'], 2, '.', ',');
                $value['kpi_1_min_max']         = "{$value['kpi_1_min']} - {$value['kpi_1_max']}";
                $value['kpi_2_min_max']         = "{$value['kpi_2_min']} - {$value['kpi_2_max']}";
                $value['kpi_3_min_max']         = "{$value['kpi_3_min']} - {$value['kpi_3_max']}";
                
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('GiftExchange');
    }

    /**
     * 下车用户明细 列表
     */
    public function actionUserDetails()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " details.status = 2 ";
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND details.user_id = {$user_id} ";
            }
            // 校验用户姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where  .= " AND user.real_name = '{$real_name}' ";
            }
            // 校验用户手机号
            if (!empty($_POST['mobile'])) {
                $mobile = GibberishAESUtil::enc(trim($_POST['mobile']), Yii::app()->c->idno_key);
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验礼包名称
            if (!empty($_POST['name'])) {
                $name   = trim($_POST['name']);
                $where .= " AND gift.name = '{$name}' ";
            }
            // 下车状态
            // if (!empty($_POST['status'])) {
            //     $sta    = intval($_POST['status']);
            //     $where .= " AND details.status = {$sta} ";
            // }
            // 校验下车时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start'].' 00:00:00');
                $where .= " AND details.liquidation_time > 0 AND details.liquidation_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end'].' 23:59:59');
                $where .= " AND details.liquidation_time > 0 AND details.liquidation_time <= {$end} ";
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
            // 查询数据总量
            $sql = "SELECT count(*) AS count 
                    FROM xf_debt_liquidation_user_details AS details 
                    LEFT JOIN firstp2p_user AS user ON details.user_id = user.id 
                    LEFT JOIN xf_gift_exchange AS gift ON details.gift_id = gift.id 
                    LEFT JOIN xf_gift_exchange AS initial ON details.initial_gift_id = initial.id 
                    WHERE {$where} ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT details.* , user.real_name , user.mobile , gift.name , gift.avg_debt , gift.avg_liquidation_cost , initial.name AS initial_name , initial.avg_debt AS initial_avg_debt , initial.avg_liquidation_cost AS initial_avg_liquidation_cost
                    FROM xf_debt_liquidation_user_details AS details 
                    LEFT JOIN firstp2p_user AS user ON details.user_id = user.id 
                    LEFT JOIN xf_gift_exchange AS gift ON details.gift_id = gift.id 
                    LEFT JOIN xf_gift_exchange AS initial ON details.initial_gift_id = initial.id 
                    WHERE {$where} ORDER BY details.liquidation_time DESC , details.id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();

            $status = array(1 => '待下车' , 2 => '已下车');
            foreach ($list as $key => $value) {
                $value['mobile']                       = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                $value['user_yr_cost']                 = $value['yr_debt_total'] * 0.25;
                $value['user_cost']                    = $value['user_yr_cost'] - $value['initial_avg_liquidation_cost'];
                $value['real_user_yr_cost']            = $value['real_yr_debt_total'] * 0.25;
                $value['real_user_cost']               = $value['real_user_yr_cost'] - $value['avg_liquidation_cost'];
                $value['status']                       = $status[$value['status']];
                $value['debt_total']                   = number_format($value['debt_total'], 2, '.', ',');
                $value['yr_debt_total']                = number_format($value['yr_debt_total'], 2, '.', ',');
                $value['real_debt_total']              = number_format($value['real_debt_total'], 2, '.', ',');
                $value['real_yr_debt_total']           = number_format($value['real_yr_debt_total'], 2, '.', ',');
                $value['avg_debt']                     = number_format($value['avg_debt'], 2, '.', ',');
                $value['avg_liquidation_cost']         = number_format($value['avg_liquidation_cost'], 2, '.', ',');
                $value['initial_avg_debt']             = number_format($value['initial_avg_debt'], 2, '.', ',');
                $value['initial_avg_liquidation_cost'] = number_format($value['initial_avg_liquidation_cost'], 2, '.', ',');
                $value['user_yr_cost']                 = number_format($value['user_yr_cost'], 2, '.', ',');
                $value['user_cost']                    = number_format($value['user_cost'], 2, '.', ',');
                $value['real_user_yr_cost']            = number_format($value['real_user_yr_cost'], 2, '.', ',');
                $value['real_user_cost']               = number_format($value['real_user_cost'], 2, '.', ',');
                if ($value['liquidation_time'] > 0) {
                    $value['liquidation_time'] = date('Y-m-d H:i:s', $value['liquidation_time']);
                } else {
                    $value['liquidation_time'] = '——';
                }
                
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $UserDetails2Excel = 0;
        if (!empty($authList) && strstr($authList, '/user/DebtLiquidation/UserDetails2Excel') || empty($authList)) {
            $UserDetails2Excel = 1;
        }
        return $this->renderPartial('UserDetails', array('UserDetails2Excel' => $UserDetails2Excel));
    }

    /**
     * 下车用户明细 导出
     */
    public function actionUserDetails2Excel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " details.status = 2 ";
            if (empty($_GET['user_id']) && empty($_GET['real_name']) && empty($_GET['mobile']) && empty($_GET['name']) && empty($_GET['status']) && empty($_GET['start']) && empty($_GET['end'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = intval($_GET['user_id']);
                $where  .= " AND details.user_id = {$user_id} ";
            }
            // 校验用户姓名
            if (!empty($_GET['real_name'])) {
                $real_name = trim($_GET['real_name']);
                $where  .= " AND user.real_name = '{$real_name}' ";
            }
            // 校验用户手机号
            if (!empty($_GET['mobile'])) {
                $mobile = GibberishAESUtil::enc(trim($_GET['mobile']), Yii::app()->c->idno_key);
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验礼包名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND gift.name = '{$name}' ";
            }
            // 下车状态
            // if (!empty($_GET['status'])) {
            //     $sta    = intval($_GET['status']);
            //     $where .= " AND details.status = {$sta} ";
            // }
            // 校验下车时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND details.liquidation_time > 0 AND details.liquidation_time >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND details.liquidation_time > 0 AND details.liquidation_time <= {$end} ";
            }
            // 查询数据
            $sql = "SELECT details.* , user.real_name , user.mobile , gift.name , gift.avg_debt , gift.avg_liquidation_cost , initial.name AS initial_name , initial.avg_debt AS initial_avg_debt , initial.avg_liquidation_cost AS initial_avg_liquidation_cost
                    FROM xf_debt_liquidation_user_details AS details 
                    LEFT JOIN firstp2p_user AS user ON details.user_id = user.id 
                    LEFT JOIN xf_gift_exchange AS gift ON details.gift_id = gift.id 
                    LEFT JOIN xf_gift_exchange AS initial ON details.initial_gift_id = initial.id 
                    WHERE {$where} ORDER BY details.id DESC ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $status = array(1 => '待下车' , 2 => '已下车');
            foreach ($list as $key => $value) {
                $value['mobile']                       = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                $value['user_yr_cost']                 = $value['yr_debt_total'] * 0.25;
                $value['user_cost']                    = $value['user_yr_cost'] - $value['initial_avg_liquidation_cost'];
                $value['real_user_yr_cost']            = $value['real_yr_debt_total'] * 0.25;
                $value['real_user_cost']               = $value['real_user_yr_cost'] - $value['avg_liquidation_cost'];
                $value['status']                       = $status[$value['status']];
                $value['debt_total']                   = number_format($value['debt_total'], 2, '.', ',');
                $value['yr_debt_total']                = number_format($value['yr_debt_total'], 2, '.', ',');
                $value['real_debt_total']              = number_format($value['real_debt_total'], 2, '.', ',');
                $value['real_yr_debt_total']           = number_format($value['real_yr_debt_total'], 2, '.', ',');
                $value['avg_debt']                     = number_format($value['avg_debt'], 2, '.', ',');
                $value['avg_liquidation_cost']         = number_format($value['avg_liquidation_cost'], 2, '.', ',');
                $value['initial_avg_debt']             = number_format($value['initial_avg_debt'], 2, '.', ',');
                $value['initial_avg_liquidation_cost'] = number_format($value['initial_avg_liquidation_cost'], 2, '.', ',');
                $value['user_yr_cost']                 = number_format($value['user_yr_cost'], 2, '.', ',');
                $value['user_cost']                    = number_format($value['user_cost'], 2, '.', ',');
                $value['real_user_yr_cost']            = number_format($value['real_user_yr_cost'], 2, '.', ',');
                $value['real_user_cost']               = number_format($value['real_user_cost'], 2, '.', ',');
                if ($value['liquidation_time'] > 0) {
                    $value['liquidation_time'] = date('Y-m-d H:i:s', $value['liquidation_time']);
                } else {
                    $value['liquidation_time'] = '——';
                }
                
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '用户姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '用户手机号');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '初始用户总债权');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '初始用户悠融债权');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '初始归属礼包段');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '初始礼包段内平均债权');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '初始礼包成本');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '初始用户实际化债可回收成本');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '初始用户化债成本增减');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '实际用户总债权');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '实际用户悠融债权');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', '实际归属礼包段');
            $objPHPExcel->getActiveSheet()->setCellValue('N1', '实际礼包段内平均债权');
            $objPHPExcel->getActiveSheet()->setCellValue('O1', '实际礼包成本');
            $objPHPExcel->getActiveSheet()->setCellValue('P1', '实际用户实际化债可回收成本');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1', '实际用户化债成本增减');
            $objPHPExcel->getActiveSheet()->setCellValue('R1', '下车状态');
            $objPHPExcel->getActiveSheet()->setCellValue('S1', '下车时间');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['real_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['mobile']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['debt_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['yr_debt_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['initial_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['initial_avg_debt']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['initial_avg_liquidation_cost']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['user_yr_cost']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $value['user_cost']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['real_debt_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $value['real_yr_debt_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2), $value['name']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2), $value['avg_debt']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2), $value['avg_liquidation_cost']);
                $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2), $value['real_user_yr_cost']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2), $value['real_user_cost']);
                $objPHPExcel->getActiveSheet()->setCellValue('R' . ($key + 2), $value['status']);
                $objPHPExcel->getActiveSheet()->setCellValue('S' . ($key + 2), $value['liquidation_time']);
            }

            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '下车用户明细 '.date("Y年m月d日 H时i分s秒", time());

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

    /**
     * 下车用户综合统计(日统计) 列表
     */
    public function actionDebtLiquidationStatistics()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " 1 = 1 ";
            // 校验统计时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start'].' 00:00:00');
                $where .= " AND sta.add_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end'].' 23:59:59');
                $where .= " AND sta.add_time <= {$end} ";
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
            // 查询数据总量
            $sql = "SELECT count(*) AS count 
                    FROM xf_debt_liquidation_statistics AS sta 
                    LEFT JOIN xf_gift_exchange AS gift ON sta.gift_id = gift.id 
                    WHERE {$where} ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT sta.* , gift.exchange_min, gift.exchange_max, gift.name , gift.plan_liquidation_user , gift.plan_yr_debt_total , gift.plan_debt_total , gift.plan_liquidation_cost 
                    FROM xf_debt_liquidation_statistics AS sta 
                    LEFT JOIN xf_gift_exchange AS gift ON sta.gift_id = gift.id 
                    WHERE {$where} ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();

            foreach ($list as $key => $value) {
                $value['num']                          = $key + 1;
                $value['add_time']                     = date('Y-m-d', $value['add_time']);
                $value['liquidation_user_day_percent'] = $value['liquidation_user_day'] / $value['plan_liquidation_user'] * 100;
                $value['liquidation_user_percent']     = $value['liquidation_user'] / $value['plan_liquidation_user'] * 100;
                $value['debt_total_day_percent']       = $value['debt_total_day'] / $value['plan_debt_total'] * 100;
                $value['debt_total_percent']           = $value['debt_total'] / $value['plan_debt_total'] * 100;
                $value['yr_debt_total_day_percent']    = $value['yr_debt_total_day'] / $value['plan_yr_debt_total'] * 100;
                $value['yr_debt_total_percent']        = $value['yr_debt_total'] / $value['plan_yr_debt_total'] * 100;
                $value['liquidation_cost_day_percent'] = $value['liquidation_cost_day'] / $value['plan_liquidation_cost'] * 100;
                $value['liquidation_cost_percent']     = $value['liquidation_cost'] / $value['plan_liquidation_cost'] * 100;

                $value['exchange_min_max']             = "{$value['exchange_min']} - {$value['exchange_max']}";
                $value['liquidation_user_day_percent'] = number_format($value['liquidation_user_day_percent'], 2, '.', ',').'%';
                $value['liquidation_user_percent']     = number_format($value['liquidation_user_percent'], 2, '.', ',').'%';
                $value['debt_total_day']               = number_format($value['debt_total_day'], 2, '.', ',');
                $value['debt_total']                   = number_format($value['debt_total'], 2, '.', ',');
                $value['debt_total_day_percent']       = number_format($value['debt_total_day_percent'], 2, '.', ',').'%';
                $value['debt_total_percent']           = number_format($value['debt_total_percent'], 2, '.', ',').'%';
                $value['yr_debt_total_day']            = number_format($value['yr_debt_total_day'], 2, '.', ',');
                $value['yr_debt_total']                = number_format($value['yr_debt_total'], 2, '.', ',');
                $value['yr_debt_total_day_percent']    = number_format($value['yr_debt_total_day_percent'], 2, '.', ',').'%';
                $value['yr_debt_total_percent']        = number_format($value['yr_debt_total_percent'], 2, '.', ',').'%';
                $value['liquidation_cost_day']         = number_format($value['liquidation_cost_day'], 2, '.', ',');
                $value['liquidation_cost']             = number_format($value['liquidation_cost'], 2, '.', ',');
                $value['liquidation_cost_day_percent'] = number_format($value['liquidation_cost_day_percent'], 2, '.', ',').'%';
                $value['liquidation_cost_percent']     = number_format($value['liquidation_cost_percent'], 2, '.', ',').'%';
                $value['plan_yr_debt_total']           = number_format($value['plan_yr_debt_total'], 2, '.', ',');
                $value['plan_debt_total']              = number_format($value['plan_debt_total'], 2, '.', ',');
                $value['plan_liquidation_cost']        = number_format($value['plan_liquidation_cost'], 2, '.', ',');
                
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        $now_time = date('Y-m-d', (time()-86400));
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $DebtLiquidationStatistics2Excel = 0;
        if (!empty($authList) && strstr($authList, '/user/DebtLiquidation/DebtLiquidationStatistics2Excel') || empty($authList)) {
            $DebtLiquidationStatistics2Excel = 1;
        }
        return $this->renderPartial('DebtLiquidationStatistics', array('DebtLiquidationStatistics2Excel' => $DebtLiquidationStatistics2Excel , 'now_time' => $now_time));
    }

    /**
     * 下车用户综合统计(日统计) 导出
     */
    public function actionDebtLiquidationStatistics2Excel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " 1 = 1 ";
            if (empty($_GET['start']) && empty($_GET['end'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            // 校验统计时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND sta.add_time >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND sta.add_time <= {$end} ";
            }
            // 查询数据
            $sql = "SELECT sta.* , gift.exchange_min, gift.exchange_max, gift.name , gift.plan_liquidation_user , gift.plan_yr_debt_total , gift.plan_debt_total , gift.plan_liquidation_cost 
                    FROM xf_debt_liquidation_statistics AS sta 
                    LEFT JOIN xf_gift_exchange AS gift ON sta.gift_id = gift.id 
                    WHERE {$where} ORDER BY sta.id DESC ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }

            foreach ($list as $key => $value) {
                $value['num']                          = $key + 1;
                $value['add_time']                     = date('Y-m-d', $value['add_time']);
                $value['liquidation_user_day_percent'] = $value['liquidation_user_day'] / $value['plan_liquidation_user'] * 100;
                $value['liquidation_user_percent']     = $value['liquidation_user'] / $value['plan_liquidation_user'] * 100;
                $value['debt_total_day_percent']       = $value['debt_total_day'] / $value['plan_debt_total'] * 100;
                $value['debt_total_percent']           = $value['debt_total'] / $value['plan_debt_total'] * 100;
                $value['yr_debt_total_day_percent']    = $value['yr_debt_total_day'] / $value['plan_yr_debt_total'] * 100;
                $value['yr_debt_total_percent']        = $value['yr_debt_total'] / $value['plan_yr_debt_total'] * 100;
                $value['liquidation_cost_day_percent'] = $value['liquidation_cost_day'] / $value['plan_liquidation_cost'] * 100;
                $value['liquidation_cost_percent']     = $value['liquidation_cost'] / $value['plan_liquidation_cost'] * 100;

                $value['exchange_min_max']             = "{$value['exchange_min']} - {$value['exchange_max']}";
                $value['liquidation_user_day_percent'] = number_format($value['liquidation_user_day_percent'], 2, '.', ',').'%';
                $value['liquidation_user_percent']     = number_format($value['liquidation_user_percent'], 2, '.', ',').'%';
                $value['debt_total_day']               = number_format($value['debt_total_day'], 2, '.', ',');
                $value['debt_total']                   = number_format($value['debt_total'], 2, '.', ',');
                $value['debt_total_day_percent']       = number_format($value['debt_total_day_percent'], 2, '.', ',').'%';
                $value['debt_total_percent']           = number_format($value['debt_total_percent'], 2, '.', ',').'%';
                $value['yr_debt_total_day']            = number_format($value['yr_debt_total_day'], 2, '.', ',');
                $value['yr_debt_total']                = number_format($value['yr_debt_total'], 2, '.', ',');
                $value['yr_debt_total_day_percent']    = number_format($value['yr_debt_total_day_percent'], 2, '.', ',').'%';
                $value['yr_debt_total_percent']        = number_format($value['yr_debt_total_percent'], 2, '.', ',').'%';
                $value['liquidation_cost_day']         = number_format($value['liquidation_cost_day'], 2, '.', ',');
                $value['liquidation_cost']             = number_format($value['liquidation_cost'], 2, '.', ',');
                $value['liquidation_cost_day_percent'] = number_format($value['liquidation_cost_day_percent'], 2, '.', ',').'%';
                $value['liquidation_cost_percent']     = number_format($value['liquidation_cost_percent'], 2, '.', ',').'%';
                $value['plan_yr_debt_total']           = number_format($value['plan_yr_debt_total'], 2, '.', ',');
                $value['plan_debt_total']              = number_format($value['plan_debt_total'], 2, '.', ',');
                $value['plan_liquidation_cost']        = number_format($value['plan_liquidation_cost'], 2, '.', ',');
                
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('X')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Y')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Z')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '统计时间');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '礼包名称');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '兑换区间');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '计划下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '当日下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '累计下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '当日下车人数占比');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '累计下车人数占比');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '计划回收总债权');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '当日回收总债权');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '累计回收总债权');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', '当日回收总债权占比');
            $objPHPExcel->getActiveSheet()->setCellValue('N1', '累计回收总债权占比');
            $objPHPExcel->getActiveSheet()->setCellValue('O1', '计划回收悠融债权');
            $objPHPExcel->getActiveSheet()->setCellValue('P1', '当日回收悠融债权');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1', '累计回收悠融债权');
            $objPHPExcel->getActiveSheet()->setCellValue('R1', '当日回收悠融债权占比');
            $objPHPExcel->getActiveSheet()->setCellValue('S1', '累计回收悠融债权占比');
            $objPHPExcel->getActiveSheet()->setCellValue('T1', '计划化债成本');
            $objPHPExcel->getActiveSheet()->setCellValue('U1', '当日化债成本');
            $objPHPExcel->getActiveSheet()->setCellValue('V1', '累计化债成本');
            $objPHPExcel->getActiveSheet()->setCellValue('W1', '当日化债成本占比');
            $objPHPExcel->getActiveSheet()->setCellValue('X1', '累计化债成本占比');
            $objPHPExcel->getActiveSheet()->setCellValue('Y1', '当日化债成本增减');
            $objPHPExcel->getActiveSheet()->setCellValue('Z1', '累计化债成本增减');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['num']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['exchange_min_max']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['plan_liquidation_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['liquidation_user_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['liquidation_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['liquidation_user_day_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['liquidation_user_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $value['plan_debt_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['debt_total_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $value['debt_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2), $value['debt_total_day_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2), $value['debt_total_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2), $value['plan_yr_debt_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2), $value['yr_debt_total_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2), $value['yr_debt_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('R' . ($key + 2), $value['yr_debt_total_day_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('S' . ($key + 2), $value['yr_debt_total_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('T' . ($key + 2), $value['plan_liquidation_cost']);
                $objPHPExcel->getActiveSheet()->setCellValue('U' . ($key + 2), $value['liquidation_cost_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('V' . ($key + 2), $value['liquidation_cost']);
                $objPHPExcel->getActiveSheet()->setCellValue('W' . ($key + 2), $value['liquidation_cost_day_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('X' . ($key + 2), $value['liquidation_cost_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('Y' . ($key + 2), $value['liquidation_cost_fluctuation_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('Z' . ($key + 2), $value['liquidation_cost_fluctuation']);
            }

            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '下车用户综合统计(日统计) '.date("Y年m月d日 H时i分s秒", time());

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

    /**
     * 下车人数分段(电销) 列表
     */
    public function actionDebtLiquidationUserStatistics()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " 1 = 1 ";
            // 校验统计时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start'].' 00:00:00');
                $where .= " AND sta.add_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end'].' 23:59:59');
                $where .= " AND sta.add_time <= {$end} ";
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
            // 查询数据总量
            $sql = "SELECT count(*) AS count 
                    FROM xf_debt_liquidation_user_statistics AS sta 
                    LEFT JOIN xf_gift_exchange AS gift ON sta.gift_id = gift.id 
                    WHERE {$where} ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT sta.* , gift.exchange_min, gift.exchange_max, gift.name , gift.plan_liquidation_user , gift.kpi_1_plan_user , gift.kpi_2_plan_user , gift.kpi_3_plan_user 
                    FROM xf_debt_liquidation_user_statistics AS sta 
                    LEFT JOIN xf_gift_exchange AS gift ON sta.gift_id = gift.id 
                    WHERE {$where} ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();

            foreach ($list as $key => $value) {
                $value['num']                      = $key + 1;
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['liquidation_user_percent'] = $value['liquidation_user'] / $value['plan_liquidation_user'] * 100;
                $value['kpi_1_user_percent']       = $value['kpi_1_user'] / $value['kpi_1_plan_user'] * 100;
                $value['kpi_2_user_percent']       = $value['kpi_2_user'] / $value['kpi_2_plan_user'] * 100;
                $value['kpi_3_user_percent']       = $value['kpi_3_user'] / $value['kpi_3_plan_user'] * 100;
                $value['exchange_min_max']         = "{$value['exchange_min']} - {$value['exchange_max']}";
                $value['liquidation_user_percent'] = number_format($value['liquidation_user_percent'], 2, '.', ',').'%';
                $value['kpi_1_user_percent']       = number_format($value['kpi_1_user_percent'], 2, '.', ',').'%';
                $value['kpi_2_user_percent']       = number_format($value['kpi_2_user_percent'], 2, '.', ',').'%';
                $value['kpi_3_user_percent']       = number_format($value['kpi_3_user_percent'], 2, '.', ',').'%';

                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        $now_time = date('Y-m-d', (time()-86400));
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $DebtLiquidationUserStatistics2Excel = 0;
        if (!empty($authList) && strstr($authList, '/user/DebtLiquidation/DebtLiquidationUserStatistics2Excel') || empty($authList)) {
            $DebtLiquidationUserStatistics2Excel = 1;
        }
        return $this->renderPartial('DebtLiquidationUserStatistics', array('DebtLiquidationUserStatistics2Excel' => $DebtLiquidationUserStatistics2Excel , 'now_time' => $now_time));
    }

    /**
     * 下车人数分段(电销) 导出
     */
    public function actionDebtLiquidationUserStatistics2Excel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " 1 = 1 ";
            if (empty($_GET['start']) && empty($_GET['end'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            // 校验统计时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND sta.add_time >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND sta.add_time <= {$end} ";
            }
            // 查询数据
            $sql = "SELECT sta.* , gift.exchange_min, gift.exchange_max, gift.name , gift.plan_liquidation_user , gift.kpi_1_plan_user , gift.kpi_2_plan_user , gift.kpi_3_plan_user 
                    FROM xf_debt_liquidation_user_statistics AS sta 
                    LEFT JOIN xf_gift_exchange AS gift ON sta.gift_id = gift.id 
                    WHERE {$where} ORDER BY sta.id DESC ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }

            foreach ($list as $key => $value) {
                $value['num']                      = $key + 1;
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['liquidation_user_percent'] = $value['liquidation_user'] / $value['plan_liquidation_user'] * 100;
                $value['kpi_1_user_percent']       = $value['kpi_1_user'] / $value['kpi_1_plan_user'] * 100;
                $value['kpi_2_user_percent']       = $value['kpi_2_user'] / $value['kpi_2_plan_user'] * 100;
                $value['kpi_3_user_percent']       = $value['kpi_3_user'] / $value['kpi_3_plan_user'] * 100;
                $value['exchange_min_max']         = "{$value['exchange_min']} - {$value['exchange_max']}";
                $value['liquidation_user_percent'] = number_format($value['liquidation_user_percent'], 2, '.', ',').'%';
                $value['kpi_1_user_percent']       = number_format($value['kpi_1_user_percent'], 2, '.', ',').'%';
                $value['kpi_2_user_percent']       = number_format($value['kpi_2_user_percent'], 2, '.', ',').'%';
                $value['kpi_3_user_percent']       = number_format($value['kpi_3_user_percent'], 2, '.', ',').'%';
                
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '统计时间');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '礼包名称');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '兑换区间');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '计划下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '当日下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '累计下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '累计下车人数占比');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', 'KPI记数分区1计划下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', 'KPI记数分区1当日下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', 'KPI记数分区1累计下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', 'KPI记数分区1累计完成比');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', 'KPI记数分区2计划下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('N1', 'KPI记数分区2当日下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('O1', 'KPI记数分区2累计下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('P1', 'KPI记数分区2累计完成比');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1', 'KPI记数分区3计划下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('R1', 'KPI记数分区3当日下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('S1', 'KPI记数分区3累计下车人数');
            $objPHPExcel->getActiveSheet()->setCellValue('T1', 'KPI记数分区3累计完成比');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['num']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['exchange_min_max']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['plan_liquidation_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['liquidation_user_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['liquidation_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['liquidation_user_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['kpi_1_plan_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $value['kpi_1_user_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['kpi_1_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $value['kpi_1_user_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2), $value['kpi_2_plan_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2), $value['kpi_2_user_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2), $value['kpi_2_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2), $value['kpi_2_user_percent']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2), $value['kpi_3_plan_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('R' . ($key + 2), $value['kpi_3_user_day']);
                $objPHPExcel->getActiveSheet()->setCellValue('S' . ($key + 2), $value['kpi_3_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('T' . ($key + 2), $value['kpi_3_user_percent']);
            }

            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '下车人数分段(电销) '.date("Y年m月d日 H时i分s秒", time());

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
                if (is_array($params)) {
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
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
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
     * 短信管理 发送短信
     */
    public function actionSendSMS()
    {
        if (!empty($_POST)) {
            if (empty($_POST['mobile'])) {
                $this->echoJson([], 1, '请输入手机号');
            }
            if (empty($_POST['realname'])) {
                $this->echoJson([], 1, '请输入用户姓名');
            }
            if (empty($_POST['email'])) {
                $this->echoJson([], 1, '请输入邮箱');
            }
            $mobile     = trim($_POST['mobile']);
            $realname   = trim($_POST['realname']);
            $email      = trim($_POST['email']);
            $mobile_str = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key);

            $sql       = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile_str}' AND is_effect = 1 AND is_delete = 0 ";
            $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson([], 1, '此手机号非网信用户所有');
            }

            $SmsSenderUtil = new SmsSenderUtil();

            $smaClass                   = new XfSmsClass();
            $remind                     = array();
            $remind['sms_code']         = "manual_trigger";
            $remind['mobile']           = $mobile;
            $remind['data']['realname'] = $realname;
            $remind['data']['email']    = $email;
            
            $messageConf = $SmsSenderUtil->getMessageConfByCode($remind['sms_code']);
            $content     = $SmsSenderUtil->replaceCode2Name($remind, $messageConf['sms']);

            $send_ret_a = $smaClass->sendToUserByPhone($remind);
            if ($send_ret_a['code'] != 0) {
                Yii::log("SendSMS user_id:{$user_info['id']}; error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                $this->echoJson([], 1, '短信发送失败');
            }

            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $sql = "INSERT INTO xf_user_sms_log (type , user_id , send_mobile , content , add_time , add_user_id) VALUES (1 , {$user_info['id']} , '{$mobile}' , '{$content}' , {$time} , {$add_user_id}) ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson([], 1, '操作失败');
            }
            $this->echoJson([], 0, '操作成功');
        }

        return $this->renderPartial('SendSMS');
    }

    /**
     * 短信管理 短信记录
     */
    public function actionSMSLogList()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " WHERE 1 = 1 ";
            // 手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $where .= " AND send_mobile = '{$mobile}' ";
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
            // 查询数据总量
            $sql = "SELECT count(*) AS count FROM xf_user_sms_log {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM xf_user_sms_log {$where} ORDER BY id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            $type   = array(1 => '送至' , 2 => '来自');
            $status = array(1 => '未读' , 2 => '已读');
            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            $info_status = 0;
            if (!empty($authList) && strstr($authList, '/user/DebtLiquidation/SMSLogInfo') || empty($authList)) {
                $info_status = 1;
            }
            foreach ($list as $key => $value) {
                $value['add_time']  = date('Y-m-d H:i:s', $value['add_time']);
                $value['type_name'] = $type[$value['type']];
                if ($value['type'] == 1) {
                    $value['status_name']    = '——';
                    $value['receive_mobile'] = '——';
                } elseif ($value['type'] == 2) {
                    $value['status_name'] = $status[$value['status']];
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

        return $this->renderPartial('SMSLogList', array());
    }

    /**
     * 短信管理 短信记录详情
     */
    public function actionSMSLogInfo()
    {
        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误', 5);
            }
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_user_sms_log WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID', 5);
            }
            $type   = array(1 => '送至' , 2 => '来自');
            $status = array(1 => '未读' , 2 => '已读');
            $res['add_time']  = date('Y-m-d H:i:s', $res['add_time']);
            $res['type_name'] = $type[$res['type']];
            if ($res['type'] == 1) {
                $res['status_name']    = '——';
                $res['receive_mobile'] = '——';
            } elseif ($res['type'] == 2) {
                $res['status_name'] = $status[$res['status']];
            }
            if ($res['type'] == 2 && $res['status'] == 1) {
                $sql    = "UPDATE xf_user_sms_log SET status = 2 WHERE id = {$id} ";
                $update = Yii::app()->fdb->createCommand($sql)->execute();
            }

            return $this->renderPartial('SMSLogInfo', array('res' => $res));
        }
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
            $sql   = "SELECT count(*) AS count FROM xf_lender_call_back_log {$where} ";
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
            $sql = "SELECT * FROM xf_lender_call_back_log {$where} ORDER BY id DESC ";
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
            if (!empty($authList) && strstr($authList, '/user/DebtLiquidation/CallBackLogInfo') || empty($authList)) {
                $info_status = 1;
            }
            $question_1 = array(0 => '——' , 1 => '可联' , 2 => '空号' , 3 => '停机' , 4 => '关机' , 5 => '无法接通' , 6 => '占线' , 7 => '挂断' , 8 => '无人接听' , 9 => '暂停服务');
            $question_2 = array(0 => '——' , 1 => '是' , 2 => '否');
            
            $question_3 = array(0 => '——' , 1 => '不要了，就当钱没了' , 2 => '可以接收商品兑付' , 3 => '要求现金兑付，坚持不要商品兑付' , 4 => '等立案，目前不选择任何兑付方式' , 5 => '其他');
            $question_4 = array(0 => '——' , 1 => '愿意接受换购商品，觉得商品性价比可以' , 2 => '不愿意接受换购商品，觉得商品性价比不高' , 3 => '不愿意接受换购商品，没有满意的商品' , 4 => '其他');
            $question_5 = array(0 => '——' , 1 => '生活日用品类' , 2 => '家具电器类' , 3 => '服装服饰类' , 4 => '其他');
            foreach ($list as $key => $value) {
                $value['add_time']          = date('Y-m-d H:i:s', $value['add_time']);
                $value['add_user_name']     = '';
                $value['info_status']       = $info_status;
                

                $value['question_1'] = $question_1[$value['question_1']];
                $value['question_2'] = $value['question_2']==1?$question_2[$value['question_2']]:$value['question_2_else'];
    
                
                if ($value['question_3'] == 5) {
                    $value['question_3']    = $question_3[$value['question_3']].'：'.$value['question_3_else'];
                } else {
                    $value['question_3']    = $question_3[$value['question_3']];
                }
                if ($value['question_4'] == 4) {
                    $value['question_4']    = $question_4[$value['question_4']].'：'.$value['question_4_else'];
                } else {
                    $value['question_4']    = $question_4[$value['question_4']];
                }
                if ($value['question_5'] == 4) {
                    $value['question_5']    = $question_5[$value['question_5']].'：'.$value['question_5_else'];
                } else {
                    $value['question_5']    = $question_5[$value['question_5']];
                }

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
            } elseif ($_POST['question_2'] == 5 && empty($_POST['question_2_else'])) {
                $this->echoJson(array(), 1, "请输入可联系状态其他");
            }
            $user_id         = intval($_POST['user_id']);
            if ($_POST['question_1_1'] == 1) {
                $question_1 = 1;
            } elseif ($_POST['question_1_1'] == 2) {
                $question_1 = intval($_POST['question_1_2']);
            }
            $question_2             = intval($_POST['question_2']);
            $question_2_else        = !empty($_POST['question_2_else_1'])?'否-接听人：'.trim($_POST['question_2_else_1']).'；接听人与本人关系：'.trim($_POST['question_2_else_2']).'。':'——';
            
            $question_3      = intval($_POST['question_3']);
            $question_3_else = trim($_POST['question_3_else']);
            $question_4      = intval($_POST['question_4']);
            $question_4_else = trim($_POST['question_4_else']);
            $question_5      = intval($_POST['question_5']);
            $question_5_else = trim($_POST['question_5_else']);
            $remark          = trim($_POST['remark']);
            $contact_status = $question_1?:0;
            $time        = time();
            $add_user_id = Yii::app()->user->id;
            $add_user_id = $add_user_id ? $add_user_id : 0 ;
            $sql = "SELECT * FROM firstp2p_user WHERE id = {$_POST['user_id']} AND is_effect = 1 AND is_delete = 0";
            $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 1, "用户信息不存在");
            }
            Yii::app()->phdb->beginTransaction();
            $sql = "INSERT INTO xf_lender_call_back_log (user_id , mobile , add_time , add_user_id , question_1  , question_2 , question_2_else , question_3 , question_3_else , question_4 , question_4_else , question_5 , question_5_else) VALUES ('{$user_info['id']}' , '{$user_info['mobile']}' , {$time} , {$add_user_id} , '{$question_1}'  , '{$question_2}' , '{$question_2_else}' , '{$question_3}' , '{$question_3_else}' , '{$question_4}' , '{$question_4_else}' , '{$question_5}' , '{$question_5_else}') ";
            $add_log = Yii::app()->phdb->createCommand($sql)->execute();

            $sql = "UPDATE xf_debt_liquidation_user_details SET call_times = call_times + 1 , last_call_time = {$time} , remark = '{$remark}', contact_status = '{$contact_status}' WHERE user_id = {$user_info['id']} ";
            $update = Yii::app()->phdb->createCommand($sql)->execute();
            if ($add_log && $update) {
                Yii::app()->phdb->commit();
                $this->echoJson(array(), 0, "录入成功");
            } else {
                Yii::app()->phdb->rollback();
                $this->echoJson(array(), 1, "录入失败");
            }
        }
    }

    public function actionCallBackLogInfo()
    {
        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('ID格式错误', 5);
            }
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM xf_lender_call_back_log WHERE id = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('请输入正确的ID', 5);
            }
            $question_1 = array(0 => '——' , 1 => '可联' , 2 => '空号' , 3 => '停机' , 4 => '关机' , 5 => '无法接通' , 6 => '占线' , 7 => '挂断' , 8 => '无人接听' , 9 => '暂停服务');
            $question_2 = array(0 => '——' , 1 => '是' , 2 => '否');
            
            $question_3 = array(0 => '——' , 1 => '不要了，就当钱没了' , 2 => '可以接收商品兑付' , 3 => '要求现金兑付，坚持不要商品兑付' , 4 => '等立案，目前不选择任何兑付方式' , 5 => '其他');
            $question_4 = array(0 => '——' , 1 => '愿意接受换购商品，觉得商品性价比可以' , 2 => '不愿意接受换购商品，觉得商品性价比不高' , 3 => '不愿意接受换购商品，没有满意的商品' , 4 => '其他');
            $question_5 = array(0 => '——' , 1 => '生活日用品类' , 2 => '家具电器类' , 3 => '服装服饰类' , 4 => '其他');
            
            $res['question_1'] = $question_1[$res['question_1']];
            $res['question_2'] = $res['question_2']==1?$question_2[$res['question_2']]:$res['question_2_else'];

            
            if ($res['question_3'] == 5) {
                $res['question_3'] = $question_3[$res['question_3']].'：'.$res['question_3_else'];
            } else {
                $res['question_3'] = $question_3[$res['question_3']];
            }
            if ($res['question_4'] == 4) {
                $res['question_4'] = $question_4[$res['question_4']].'：'.$res['question_4_else'];
            } else {
                $res['question_4'] = $question_4[$res['question_4']];
            }
            if ($res['question_5'] == 4) {
                $res['question_5'] = $question_5[$res['question_5']].'：'.$res['question_5_else'];
            } else {
                $res['question_5'] = $question_5[$res['question_5']];
            }

            return $this->renderPartial('CallBackLogInfo', array('res' => $res));
        }
    }

    public function actionCallBackUserList()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->phdb;
            // 条件筛选
            $where = " WHERE call_times > 0 ";
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
            $sql   = "SELECT count(*) AS count FROM xf_debt_liquidation_user_details {$where} ";
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
            $sql = "SELECT * FROM xf_debt_liquidation_user_details {$where} ";
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
            if (!empty($authList) && strstr($authList, '/user/DebtLiquidation/CallBackLogList') || empty($authList)) {
                $info_status = 1;
            }
            $sex  = array(-1 => '——' , 0 => '女' , 1 => '男');
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

        return $this->renderPartial('CallBackUserList', array());
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
            if (!empty($_POST['question_2'])) {
                $q2     = intval($_POST['question_2']);
                $where .= " AND question_2 = {$q2} ";
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
            $sql   = "SELECT count(*) AS count FROM xf_lender_call_back_log {$where} ";
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
            $sql = "SELECT * FROM xf_lender_call_back_log {$where} ORDER BY id DESC ";
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
            if (!empty($authList) && strstr($authList, '/user/DebtLiquidation/CallBackLogInfo') || empty($authList)) {
                $info_status = 1;
            }
            $question_1 = array(0 => '——' , 1 => '可联' , 2 => '空号' , 3 => '停机' , 4 => '关机' , 5 => '无法接通' , 6 => '占线' , 7 => '挂断' , 8 => '无人接听' , 9 => '暂停服务');
            $question_2 = array(0 => '——' , 1 => '是' , 2 => '否');
            $question_3 = array(0 => '——' , 1 => '不要了，就当钱没了' , 2 => '可以接收商品兑付' , 3 => '要求现金兑付，坚持不要商品兑付' , 4 => '等立案，目前不选择任何兑付方式' , 5 => '其他');
            $question_4 = array(0 => '——' , 1 => '愿意接受换购商品，觉得商品性价比可以' , 2 => '不愿意接受换购商品，觉得商品性价比不高' , 3 => '不愿意接受换购商品，没有满意的商品' , 4 => '其他');
            $question_5 = array(0 => '——' , 1 => '生活日用品类' , 2 => '家具电器类' , 3 => '服装服饰类' , 4 => '其他');
            foreach ($list as $key => $value) {
                $value['add_time']          = date('Y-m-d H:i:s', $value['add_time']);
                $value['info_status']       = $info_status;
                $value['question_1']        = $question_1[$value['question_1']];
                $value['question_2']        = $value['question_2']==1?$question_2[$value['question_2']]:$value['question_2_else'];
                if ($value['question_3'] == 5) {
                    $value['question_3']    = $question_3[$value['question_3']].'：'.$value['question_3_else'];
                } else {
                    $value['question_3']    = $question_3[$value['question_3']];
                }
                if ($value['question_4'] == 4) {
                    $value['question_4']    = $question_4[$value['question_4']].'：'.$value['question_4_else'];
                } else {
                    $value['question_4']    = $question_4[$value['question_4']];
                }
                if ($value['question_5'] == 4) {
                    $value['question_5']    = $question_5[$value['question_5']].'：'.$value['question_5_else'];
                } else {
                    $value['question_5']    = $question_5[$value['question_5']];
                }

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

    /**
    * 数据统计页
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
        $sql = "SELECT contact_status, count(1) AS count FROM xf_debt_liquidation_user_details  group by contact_status ";
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
        $sql = "SELECT sex, count(1) AS count FROM xf_debt_liquidation_user_details  group by sex ";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

        $sex_name = Yii::app()->c->xf_config['sex_name'];
        $data1 = ['total' => 0, 'detail' => []];
        foreach ($count_data as $key=>$value) {
            $data1['total'] +=  $value['count'];
            $data1['detail'][] = [
                'name' => $sex_name[$value['sex']]?:'未知',
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
        $sql = "SELECT byear, count(1) AS count FROM xf_debt_liquidation_user_details  group by byear ";
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
     * 各借款金额区间人数统计
     *
     * @return void
     */
    public function actionGetGiftData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];
        // 查询礼包数据
        $sql = "SELECT id,exchange_min,exchange_max FROM xf_gift_exchange order by id asc ";

        $gift_data = Yii::app()->phdb->createCommand($sql)->queryAll();

        $gift_id_name = [];
        foreach ($gift_data as $value) {
            $gift_id_name[$value['id']]=$value['exchange_min'].'-'.$value['exchange_max'];
        }

        //查询
        $sql = "SELECT initial_gift_id, count(1) AS count FROM xf_debt_liquidation_user_details  group by initial_gift_id  order by initial_gift_id asc";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

     
        $data['name_list'] = array_values($gift_id_name);
        $value_list = array_fill_keys($data['name_list'], "0");
        foreach ($count_data as $key=>$value) {
            $value_list[$gift_id_name[$value['initial_gift_id']]] = $value['count'];
        }
        $data['value_list'] = array_values($value_list);
       
        $result_data['data'] = $data;
        $result_data['data']['total'] = array_sum($data['value_list']);
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
        $sql = "SELECT province, count(1) AS count FROM xf_debt_liquidation_user_details  group by province order by count desc  ";
        $count_data = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$count_data) {
            $result_data['info']  = '暂无数据';
            echo exit(json_encode($result_data));
        }

        $data = [];
        foreach ($count_data as $key => $value) {
            $data[] = [
                'order' =>$key+1,
                'name' => $value['province']?:'未知',
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

            $sql = "SELECT count(distinct(city)) AS count FROM xf_debt_liquidation_user_details ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();


            $sql = "SELECT city, count(1) AS count FROM xf_debt_liquidation_user_details  group by city order by count desc ,city asc limit {$offset}, {$pageSize} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();

            foreach ($list as $key=>&$value) {
                $value['order'] = $key +1;
                $value['city'] = $value['city']?:'未知';
            }
            
            $result_data['countNum'] =  $count;
            $result_data['list'] = $list;
            echo exit(json_encode($result_data));
        } else {
            $sql = "SELECT city, count(1) AS count FROM xf_debt_liquidation_user_details  group by city order by count desc ,city asc limit 20 ";
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

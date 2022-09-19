<?php
class ItouziService extends ItzInstanceService
{
    /**
     * 爱投资 省心计划 债转记录
     */
    public function ShengXinDebt($order = 1 , $limit = 10 , $page = 1 , $status = 0 , $user_id = 0 , $type = 0 , $real_name = '' , $project = '')
    {
        if ($status == 1) {
            $where = ' AND ade.status = 1 '; // 发布中
        } else if ($status == 2) {
            $where = ' AND ade.status = 3 '; // 已成交
        } else if ($status == 3) {
            $where = ' AND ade.status = 5 '; // 已取消
        } else if ($status == 4) {
            $where = ' AND ade.status = 6 '; // 已过期
        } else {
            $where = '';
        }
        if (!empty($user_id)) {
            $where .= " AND ade.user_id = {$user_id} ";
        }
        if (!empty($type)) {
            $where .= " AND ade.borrow_type = {$type} ";
        }
        if (!empty($real_name)) {
            $where .= " AND u.realname = '{$real_name}' ";
        }
        if (!empty($project)) {
            $where .= " AND b.name = '{$project}' ";
        }
        $sql = "SELECT count(ade.id) AS count FROM ((itz_ag_debt_exchange AS ade INNER JOIN dw_borrow AS b ON ade.borrow_id = b.id) INNER JOIN dw_borrow_tender AS bt ON ade.tender_id = bt.id) INNER JOIN dw_user AS u ON ade.user_id = u.user_id WHERE ade.borrow_type <= 402 {$where} ";
        Yii::log("{$sql}", "info");
        $count = Yii::app()->yiidb->createCommand($sql)->queryScalar();

        if (!$count) {
            return array();
        }
        $result          = array();
        $result['count'] = $count;
        if ($order == 1) {
            $where .= " ORDER BY ade.create_debt_time DESC ";
        } else if ($order == 2) {
            $where .= " ORDER BY ade.create_debt_time ASC ";
        } else if ($order == 3) {
            $where .= " ORDER BY ade.discount DESC ";
        } else if ($order == 4) {
            $where .= " ORDER BY ade.discount ASC ";
        } else if ($order == 5) {
            $where .= " ORDER BY ade.debt_account DESC ";
        } else if ($order == 6) {
            $where .= " ORDER BY ade.debt_account ASC ";
        }
        $page_count = ceil($count / $limit); // 总页数
        $pass       = ($page - 1) * $limit;  // 跳过数据量
        $result['page_count'] = $page_count;
        $sql = "SELECT
                ade.id AS debt_id,
                ade.debt_account AS amount,
                ade.discount,
                ade.debt_serial_number AS serial_number,
                ade.create_debt_time AS addtime,
                ade.effect_days,
                ade.borrow_type AS type,
                ade.successtime AS success_time,
                ade.borrow_id,
                ade.tender_id,
                ade.debt_src,
                b.name,
                b.apr,
                u.realname AS real_name,
                bt.addtime AS bt_addtime
                FROM ((itz_ag_debt_exchange AS ade INNER JOIN dw_borrow AS b ON ade.borrow_id = b.id)
                INNER JOIN dw_borrow_tender AS bt ON ade.tender_id = bt.id)
                INNER JOIN dw_user AS u ON ade.user_id = u.user_id
                WHERE ade.borrow_type <= 402 {$where} LIMIT {$pass} , {$limit} ";
        $res = Yii::app()->yiidb->createCommand($sql)->queryAll();

        $itouzi = Yii::app()->c->itouzi;
        $type   = $itouzi['itouzi']['type'];
        $time   = time();
        foreach ($res as $key => $value) {
            $temp                  = array();
            $temp['debt_id']       = $value['debt_id'];
            $temp['amount']        = $value['amount'];
            $temp['discount']      = $value['discount'].'折';
            $temp['serial_number'] = $value['serial_number'];
            $temp['addtime_int']   = $value['addtime'];
            $temp['addtime']       = date('Y-m-d H:i:s' , $value['addtime']);
            $temp['end_time_int']  = $value['addtime'] + ($value['effect_days'] * 86400);
            $value['end_time']     = $temp['end_time_int'];
            $temp['end_time']      = date('Y-m-d H:i:s' , $value['end_time']);
            $temp['status']        = $status;
            $temp['platform_name'] = '爱投资';
            $temp['type']          = $value['type'];
            $temp['type_name']     = $type[$value['type']];
            $temp['name']          = $value['name'];
            $temp['apr']           = $value['apr'].'%';
            $temp['bond_no']       = implode('-', array(
                                            date('Ymd', $value['bt_addtime']),
                                            $value['type'],
                                            $value['borrow_id'],
                                            $value['tender_id']
                                        )
                                    );
            $temp['real_name']     = $value['real_name'];
            $temp['debt_src']      = $value['debt_src'];
            $temp['money']         = round(bcmul($value['amount'] , ($value['discount'] / 10) , 3) , 2);
            $temp['success_time']  = date('Y-m-d H:i:s' , $value['success_time']);
            if ($value['end_time'] > $time) {
                $remaining_time = $value['end_time'] - $time;
                if ($remaining_time >= 86400) {
                    $remaining_day   = floor($remaining_time / 86400);
                    $remaining_time -= $remaining_day * 86400;
                    $remaining_day  .= '日';
                } else {
                    $remaining_day = '';
                }
                if ($remaining_time >= 3600) {
                    $remaining_hour  = floor($remaining_time / 3600);
                    $remaining_time -= $remaining_hour * 3600;
                    $remaining_hour .= '小时';
                } else {
                    $remaining_hour = '';
                }
                if ($remaining_time >= 60) {
                    $remaining_minute  = floor($remaining_time / 60);
                    $remaining_time   -= $remaining_minute * 60;
                    $remaining_minute .= '分钟';
                } else {
                    $remaining_minute = $remaining_time . '秒';
                }
                $temp['remaining_time'] = $remaining_day . $remaining_hour . $remaining_minute;
            } else {
                $temp['remaining_time'] = '';
            }

            $result['data'][] = $temp;
        }
        return $result;
    }

    /**
     * 爱投资 查询债转记录 C1
     */
    public function Index($data)
    {
        $result['count']      = 0;
        $result['page_count'] = 0;
        $result['data']       = array();
        $result['code']       = -1;

        // 校验排序方式
        if (!empty($data['order'])) {
            if (!is_numeric($data['order']) || !in_array($data['order'] , array(1 , 2 , 3 , 4 , 5 , 6))) {
                $result['code'] = 3010;
                return $result;
            }
            $order = intval($_POST['order']);
        } else {
            $order = 1;
        }
        // 校验每页显示数据量
        if (!empty($data['limit'])) {
            if (!is_numeric($data['limit']) || $data['limit'] < 1 || $data['limit'] > 100) {
                $result['code'] = 3011;
                return $result;
            }
            $limit = intval($data['limit']);
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($data['page'])) {
            if (!is_numeric($data['page']) || $data['page'] < 1) {
                $result['code'] = 3012;
                return $result;
            }
            $page = intval($data['page']);
        } else {
            $page = 1;
        }
        $status         = intval($data['status']);
        $platformUserId = intval($data['platformUserId']);
        $list           = $this->ShengXinDebt($order , $limit , $page , $status , $platformUserId);
        if (!$list) {
            $result['count']      = 0;
            $result['page_count'] = 0;
            $result['data']       = array();
            $result['code']       = 3029;
            return $result;
        }
        $result['count']      = $list['count'];
        $result['page_count'] = $list['page_count'];
        $result['data']       = $list['data'];
        $result['code']       = 0;
        return $result;
    }

    /**
     * 爱投资 查询债转记录 平台
     */
    public function Platform($data)
    {
        $result['count']      = 0;
        $result['page_count'] = 0;
        $result['data']       = array();
        $result['code']       = -1;

        // 校验排序方式
        if (!empty($data['order'])) {
            if (!is_numeric($data['order']) || !in_array($data['order'] , array(1 , 2 , 3 , 4 , 5 , 6))) {
                $result['code'] = 3010;
                return $result;
            }
            $order = intval($_POST['order']);
        } else {
            $order = 1;
        }
        // 校验每页显示数据量
        if (!empty($data['limit'])) {
            if (!is_numeric($data['limit']) || $data['limit'] < 1 || $data['limit'] > 100) {
                $result['code'] = 3011;
                return $result;
            }
            $limit = intval($data['limit']);
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($data['page'])) {
            if (!is_numeric($data['page']) || $data['page'] < 1) {
                $result['code'] = 3012;
                return $result;
            }
            $page = intval($data['page']);
        } else {
            $page = 1;
        }
        $status    = intval($data['status']);
        $type      = intval($data['type']);
        $real_name = trim($data['real_name']);
        $list      = $this->ShengXinDebt($order , $limit , $page , $status , 0 , $type , $real_name);
        if (!$list) {
            $result['count']      = 0;
            $result['page_count'] = 0;
            $result['data']       = array();
            $result['code']       = 3029;
            return $result;
        }
        $result['count']      = $list['count'];
        $result['page_count'] = $list['page_count'];
        $result['data']       = $list['data'];
        $result['code']       = 0;
        return $result;
    }

    /**
     * 爱投资 查询债转记录 资方
     */
    public function Management($data)
    {
        $result['count']      = 0;
        $result['page_count'] = 0;
        $result['data']       = array();
        $result['code']       = -1;

        // 校验排序方式
        if (!empty($data['order'])) {
            if (!is_numeric($data['order']) || !in_array($data['order'] , array(1 , 2 , 3 , 4 , 5 , 6))) {
                $result['code'] = 3010;
                return $result;
            }
            $order = intval($_POST['order']);
        } else {
            $order = 1;
        }
        // 校验每页显示数据量
        if (!empty($data['limit'])) {
            if (!is_numeric($data['limit']) || $data['limit'] < 1 || $data['limit'] > 100) {
                $result['code'] = 3011;
                return $result;
            }
            $limit = intval($data['limit']);
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($data['page'])) {
            if (!is_numeric($data['page']) || $data['page'] < 1) {
                $result['code'] = 3012;
                return $result;
            }
            $page = intval($data['page']);
        } else {
            $page = 1;
        }
        $status    = 1;
        $type      = intval($data['type']);
        $real_name = trim($data['real_name']);
        $project   = trim($data['project']);
        $list      = $this->ShengXinDebt($order , $limit , $page , $status , 0 , $type , $real_name , $project);
        if (!$list) {
            $result['count']      = 0;
            $result['page_count'] = 0;
            $result['data']       = array();
            $result['code']       = 3029;
            return $result;
        }
        $result['count']      = $list['count'];
        $result['page_count'] = $list['page_count'];
        $result['data']       = $list['data'];
        $result['code']       = 0;
        return $result;
    }

    /**
     * 爱投资 省心计划 可债转投资记录 
     */
    public function ShengXinTender($user_id = 0 , $type = 0 , $order = 1 , $limit = 10 , $page = 1)
    {
        if (!empty($type)) {
            $where = " AND b.type = '{$type}' ";
        } else {
            $where = 'AND b.type <= 402';
        }
        $sql = "SELECT count(t.id) AS count
                FROM dw_borrow_tender AS t INNER JOIN dw_borrow AS b ON t.borrow_id = b.id
                WHERE t.user_id = {$user_id} AND t.status = 1 AND t.debt_status IN (0 , 1 , 14) AND t.is_debt_confirm = 1 {$where} ";
        $count = Yii::app()->yiidb->createCommand($sql)->queryScalar();
        Yii::log("{$sql}", "info");
        if (!$count) {
            return array();
        }
        $result               = array();
        $result['count']      = $count;
        $page_count           = ceil($count / $limit); // 总页数
        $pass                 = ($page - 1) * $limit;  // 跳过数据量
        $result['page_count'] = $page_count;
        if ($order == 1) {
            $where .= " ORDER BY t.debt_status IN (0,14) DESC , t.addtime DESC ";
        } else if ($order == 2) {
            $where .= " ORDER BY t.debt_status IN (0,14) DESC , t.addtime ASC ";
        }
        $sql = "SELECT t.id , b.type , b.name , b.apr , t.wait_account , t.wait_interest , t.debt_status , t.addtime , t.borrow_id
                FROM dw_borrow_tender AS t INNER JOIN dw_borrow AS b ON t.borrow_id = b.id
                WHERE t.user_id = {$user_id} AND t.status = 1 AND t.debt_status IN (0 , 1 , 14) AND t.is_debt_confirm = 1 {$where} LIMIT {$pass} , {$limit} ";
        $data   = Yii::app()->yiidb->createCommand($sql)->queryAll();
        $itouzi = Yii::app()->c->itouzi;
        $type   = $itouzi['itouzi']['type'];
        foreach ($data as $key => $value) {
            $temp = array();
            $temp['id']           = $value['id'];
            $temp['type']         = $value['type'];
            $temp['type_name']    = $type[$value['type']];
            $temp['name']         = $value['name'];
            $temp['bond_no']      = implode('-', array(
                                            date('Ymd', $value['addtime']),
                                            $value['type'],
                                            $value['borrow_id'],
                                            $value['id']
                                        )
                                    );
            $temp['apr']          = $value['apr'];
            $temp['debt_status']  = $value['debt_status'];
            $temp['wait_capital'] = bcsub($value['wait_account'] , $value['wait_interest'] , 2);

            $result['data'][] = $temp;

            $tender_id_arr[] = $value['id'];
        }
        $tender_id_str = implode(',' , $tender_id_arr);
        $sql           = "SELECT tender_id FROM itz_ag_debt_exchange WHERE tender_id IN ($tender_id_str) AND user_id = {$user_id} AND status IN (1,2)";
        $status_1      = Yii::app()->yiidb->createCommand($sql)->queryColumn();
        foreach ($result['data'] as $key => $value) {
            if (in_array($value['debt_status'] , array(0 , 14)) && in_array($value['id'] , $status_1)) {
                $result['data'][$key]['debt_status'] = 1;
            }
        }

        return $result;
    }

    /**
     * 爱投资 可债转投资记录 C1
     */
    public function ProjectList($data)
    {
        $result['count']      = 0;
        $result['page_count'] = 0;
        $result['data']       = array();
        $result['code']       = -1;

        // 校验排序方式
        if (!empty($data['order'])) {
            if (!is_numeric($data['order']) || !in_array($data['order'] , array(1 , 2))) {
                $result['code'] = 3010;
                return $result;
            }
            $order = intval($_POST['order']);
        } else {
            $order = 1;
        }
        // 校验每页显示数据量
        if (!empty($data['limit'])) {
            if (!is_numeric($data['limit']) || $data['limit'] < 1 || $data['limit'] > 100) {
                $result['code'] = 3011;
                return $result;
            }
            $limit = intval($data['limit']);
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($data['page'])) {
            if (!is_numeric($data['page']) || $data['page'] < 1) {
                $result['code'] = 3012;
                return $result;
            }
            $page = intval($data['page']);
        } else {
            $page = 1;
        }
        $user_id = intval($data['platformUserId']);
        $type    = intval($data['type']);
        $list    = $this->ShengXinTender($user_id , $type , $order , $limit , $page);
        if (!$list) {
            $result['count']      = 0;
            $result['page_count'] = 0;
            $result['data']       = array();
            $result['code']       = 3013;
            return $result;
        }
        $result['count']      = $list['count'];
        $result['page_count'] = $list['page_count'];
        $result['data']       = $list['data'];
        $result['code']       = 0;
        return $result;
    }
}
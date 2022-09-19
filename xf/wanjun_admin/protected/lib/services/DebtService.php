<?php
class DebtService extends ItzInstanceService
{

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 债转记录 列表 张健
     * 提供查询字段：
     * @param data['user_id']       int     用户ID
     * @param data['borrow_id']     int     项目ID
     * @param data['tender_id']     int     投资记录ID
     * @param data['name']          string  项目名称
     * @param data['status']        int     转让状态 1-新建转让中，2-转让成功，3-取消转让，4-过期
     * @param data['mobile']        string  用户手机号
     * @param data['debt_src']      int     债转类型 1-权益兑换、2-债转交易、3债权划扣
     * @param data['deal_type']     int     项目类型[1-尊享 2-普惠供应链]
     * @param data['limit']         int     每页数据显示量 默认10
     * @param data['page']          int     当前页数 默认1
     * @param limit                 int     每页数据显示量 默认10
     * @param page                  int     当前页数 默认1
     * @return json
     */
    public function getDebtList($data = array(), $limit = 10, $page = 1)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
        );
        if (empty($data['deal_type'])) {
            $returnResult['code'] = 1001;
            $returnResult['info'] = '请输入项目类型';
            return $returnResult;
        }
        if (!is_numeric($data['deal_type']) || !in_array($data['deal_type'] , array(1 , 2))) {
            $returnResult['code'] = 1002;
            $returnResult['info'] = '项目类型输入错误';
            return $returnResult;
        }
        // 尊享
        if ($data['deal_type'] == 1) {

            // 条件筛选
            $where = "";
            // 校验用户ID
            if (!empty($data['user_id'])) {
                if (!is_numeric($data['user_id'])) {
                    $returnResult['code'] = 1003;
                    $returnResult['info'] = '用户ID输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.user_id = {$data['user_id']} ";
            }
            // 校验项目ID
            if (!empty($data['borrow_id'])) {
                if (!is_numeric($data['borrow_id'])) {
                    $returnResult['code'] = 1004;
                    $returnResult['info'] = '项目ID输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.borrow_id = {$data['borrow_id']} ";
            }
            // 校验投资记录ID
            if (!empty($data['tender_id'])) {
                if (!is_numeric($data['tender_id'])) {
                    $returnResult['code'] = 1005;
                    $returnResult['info'] = '投资记录ID输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.tender_id = {$data['tender_id']} ";
            }
            // 校验转让状态
            if (!empty($data['status'])) {
                if (!is_numeric($data['status']) || !in_array($data['status'], array(1, 2, 3, 4))) {
                    $returnResult['code'] = 1006;
                    $returnResult['info'] = '转让状态输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.status = {$data['status']} ";
            }
            // 校验项目名称
            if (!empty($data['name'])) {
                $name   = trim($data['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验用户手机号
            if (!empty($data['mobile'])) {
                $mobile = trim($data['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验债转类型
            if (!empty($data['debt_src'])) {
                if (!is_numeric($data['debt_src']) || !in_array($data['debt_src'], array(1, 2, 3))) {
                    $returnResult['code'] = 1007;
                    $returnResult['info'] = '转让状态输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.debt_src = {$data['debt_src']} ";
            }
            // 校验每页数据显示量
            if (!empty($data['limit'])) {
                if (!is_numeric($data['limit']) || !in_array($data['limit'], array(10, 20, 30, 40, 50))) {
                    $returnResult['code'] = 1008;
                    $returnResult['info'] = '每页数据显示量输入错误';
                    return $returnResult;
                }
                $limit = $data['limit'];
            }
            // 校验当前页数
            if (!empty($data['page'])) {
                if (!is_numeric($data['page']) || $data['page'] < 1) {
                    $returnResult['code'] = 1009;
                    $returnResult['info'] = '当前页数输入错误';
                    return $returnResult;
                }
                $page = $data['page'];
            }
            // 查询数据总量
            $sql = "SELECT count(deal.id) AS count FROM
                    (firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id)
                    INNER JOIN firstp2p_user AS user ON debt.user_id = user.id {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                $returnResult['code'] = 1010;
                $returnResult['info'] = '暂无更多数据';
                return $returnResult;
            }
            $returnResult['data']['listTotal'] = $count;
            // 查询数据
            $sql = "SELECT
                    debt.id, debt.user_id, debt.tender_id AS borrow_id, debt.borrow_id AS tender_id, debt.money, debt.sold_money, debt.discount_money, debt.addtime, debt.successtime, debt.status, debt.debt_src,
                    deal.name,
                    user.real_name, user.mobile FROM
                    (firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id)
                    INNER JOIN firstp2p_user AS user ON debt.user_id = user.id {$where} ORDER BY debt.id DESC ";
            $offsets = ($page - 1) * $limit;
            $sql    .= " LIMIT {$offsets},{$limit} ";
            $list    = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value){
                $value['mobile']      = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                $value['addtime']     = date('Y-m-d H:i:s', $value['addtime']);
                if ($value['successtime'] != 0) {
                    $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                } else {
                    $value['successtime'] = '';
                }
                $listInfo[]           = $value;
            }
            $returnResult['code']             = 0;
            $returnResult['info']             = '查询成功';
            $returnResult['data']['listInfo'] = $listInfo;
            return $returnResult;

        // 普惠供应链
        } else if ($data['deal_type'] == 2) {

            // 条件筛选
            $where = "";
            // 校验用户ID
            if (!empty($data['user_id'])) {
                if (!is_numeric($data['user_id'])) {
                    $returnResult['code'] = 1003;
                    $returnResult['info'] = '用户ID输入错误';
                    return $returnResult;
                }
                
            }
            // 校验用户手机号
            if (!empty($data['mobile'])) {
                $mobile      = trim($data['mobile']);
                $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND debt.user_id = {$user_id} ";
                } else {
                    $where .= " AND debt.user_id is NULL ";
                }
            }
            // 校验项目ID
            if (!empty($data['borrow_id'])) {
                if (!is_numeric($data['borrow_id'])) {
                    $returnResult['code'] = 1004;
                    $returnResult['info'] = '项目ID输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.borrow_id = {$data['borrow_id']} ";
            }
            // 校验投资记录ID
            if (!empty($data['tender_id'])) {
                if (!is_numeric($data['tender_id'])) {
                    $returnResult['code'] = 1005;
                    $returnResult['info'] = '投资记录ID输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.tender_id = {$data['tender_id']} ";
            }
            // 校验转让状态
            if (!empty($data['status'])) {
                if (!is_numeric($data['status']) || !in_array($data['status'], array(1, 2, 3, 4))) {
                    $returnResult['code'] = 1006;
                    $returnResult['info'] = '转让状态输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.status = {$data['status']} ";
            }
            // 校验项目名称
            if (!empty($data['name'])) {
                $name   = trim($data['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验债转类型
            if (!empty($data['debt_src'])) {
                if (!is_numeric($data['debt_src']) || !in_array($data['debt_src'], array(1, 2, 3))) {
                    $returnResult['code'] = 1007;
                    $returnResult['info'] = '转让状态输入错误';
                    return $returnResult;
                }
                $where .= " AND debt.debt_src = {$data['debt_src']} ";
            }
            // 校验每页数据显示量
            if (!empty($data['limit'])) {
                if (!is_numeric($data['limit']) || !in_array($data['limit'], array(10, 20, 30, 40, 50))) {
                    $returnResult['code'] = 1008;
                    $returnResult['info'] = '每页数据显示量输入错误';
                    return $returnResult;
                }
                $limit = $data['limit'];
            }
            // 校验当前页数
            if (!empty($data['page'])) {
                if (!is_numeric($data['page']) || $data['page'] < 1) {
                    $returnResult['code'] = 1009;
                    $returnResult['info'] = '当前页数输入错误';
                    return $returnResult;
                }
                $page = $data['page'];
            }
            // 查询数据总量
            $sql   = "SELECT count(deal.id) AS count FROM firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id {$where} ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                $returnResult['code'] = 1010;
                $returnResult['info'] = '暂无更多数据';
                return $returnResult;
            }
            $returnResult['data']['listTotal'] = $count;
            // 查询数据
            $sql = "SELECT
                    debt.id, debt.user_id, debt.tender_id AS borrow_id, debt.borrow_id AS tender_id, debt.money, debt.sold_money, debt.discount_money, debt.addtime, debt.successtime, debt.status, debt.debt_src,
                    deal.name
                    FROM firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id {$where} ORDER BY debt.id DESC ";
            $offsets = ($page - 1) * $limit;
            $sql    .= " LIMIT {$offsets},{$limit} ";
            $list    = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value){
                $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                if ($value['successtime'] != 0) {
                    $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                } else {
                    $value['successtime'] = '';
                }
                $listInfo[]    = $value;
                $user_id_arr[] = $value['user_id'];
            }
            $user_id_str = implode(',' , $user_id_arr);
            $sql         = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
            $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($user_id_res as $key => $value) {
                $temp['real_name'] = $value['real_name'];
                $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密

                $user_id_data[$value['id']] = $temp;
            }
            foreach ($listInfo as $key => $value) {
                $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                $listInfo[$key]['mobile']    = $user_id_data[$value['user_id']]['mobile'];
            }
            $returnResult['code']             = 0;
            $returnResult['info']             = '查询成功';
            $returnResult['data']['listInfo'] = $listInfo;
            return $returnResult;
        }
    }

    /**
     * 用户管理 列表 张健
     * 提供查询字段：
     * @param data['id']                int     用户ID
     * @param data['fdd_customer_id']   string  法大大ID
     * @param data['user_name']         string  用户名
     * @param data['real_name']         string  真实姓名
     * @param data['sex']               int     性别 1-男，2-女
     * @param data['idno']              string  证件号码
     * @param data['mobile']            string  手机号码
     * @param data['limit']             int     每页数据显示量 默认10
     * @param data['page']              int     当前页数 默认1
     * @param limit                     int     每页数据显示量 默认10
     * @param page                      int     当前页数 默认1
     * @return json
     */
    public function getUserList($data = array(), $limit = 10, $page = 1)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
        );
        // 条件筛选
        $where = "";
        // 校验用户ID
        if (!empty($data['id'])) {
            if (!is_numeric($data['id'])) {
                $returnResult['code'] = 1001;
                $returnResult['info'] = '用户ID输入错误';
                return $returnResult;
            }
            $where .= " AND id = {$data['id']} ";
        }
        // 校验法大大ID
        if (!empty($data['fdd_customer_id'])) {
            $fdd_customer_id = trim($data['fdd_customer_id']);
            $where .= " AND fdd_customer_id = '{$fdd_customer_id}' ";
        }
        // 校验性别
        if (!empty($data['sex'])) {
            if (!is_numeric($data['sex']) || !in_array($data['sex'], array(1, 2))) {
                $returnResult['code'] = 1003;
                $returnResult['info'] = '性别输入错误';
                return $returnResult;
            }
            $where .= " AND sex = {$data['sex']} ";
        }
        // 校验用户名
        if (!empty($data['user_name'])) {
            $user_name = trim($data['user_name']);
            $where    .= " AND user_name = '{$user_name}' ";
        }
        // 校验真实姓名
        if (!empty($data['real_name'])) {
            $real_name = trim($data['real_name']);
            $where    .= " AND real_name = '{$real_name}' ";
        }
        // 校验证件号码
        if (!empty($data['idno'])) {
            $idno   = trim($data['idno']);
            $idno   = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key); // 证件号码加密
            $where .= " AND idno = '{$idno}' ";
        }
        // 校验手机号码
        if (!empty($data['mobile'])) {
            $mobile = trim($data['mobile']);
            $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
            $where .= " AND mobile = '{$mobile}' ";
        }
        // 校验每页数据显示量
        if (!empty($data['limit'])) {
            if (!is_numeric($data['limit']) || !in_array($data['limit'], array(10, 20, 30, 40, 50))) {
                $returnResult['code'] = 1004;
                $returnResult['info'] = '每页数据显示量输入错误';
                return $returnResult;
            }
            $limit = $data['limit'];
        }
        // 校验当前页数
        if (!empty($data['page'])) {
            if (!is_numeric($data['page']) || $data['page'] < 1) {
                $returnResult['code'] = 1005;
                $returnResult['info'] = '当前页数输入错误';
                return $returnResult;
            }
            $page = $data['page'];
        }
        // 查询数据总量
        $sql = "SELECT count(id) AS count FROM firstp2p_user WHERE 1 = 1 {$where} ";
        $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
        if ($count == 0) {
            $returnResult['code'] = 1006;
            $returnResult['info'] = '暂无更多数据';
            return $returnResult;
        }
        $returnResult['data']['listTotal'] = $count;
        // 查询数据
        $sql = "SELECT id, user_name, real_name, sex, idno, mobile, create_time, is_effect, is_delete, email, fdd_customer_id FROM firstp2p_user WHERE 1 = 1 {$where} ORDER BY id DESC ";
        $offsets        = ($page - 1) * $limit;
        $sql           .= " LIMIT {$offsets},{$limit} ";
        $list           = Yii::app()->fdb->createCommand($sql)->queryAll();
        foreach ($list as $key => $value){
            $value['idno']            = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key); // 证件号码解密
            $value['mobile']          = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
            $value['create_time']     = date('Y-m-d H:i:s', $value['create_time']);
            $value['fdd_customer_id'] = $value['fdd_customer_id'] ? $value['fdd_customer_id'] : '';
            $listInfo[]               = $value;
        }
        $returnResult['code']             = 0;
        $returnResult['info']             = '查询成功';
        $returnResult['data']['listInfo'] = $listInfo;
        return $returnResult;
    }

    /**
     * 用户管理 详情 张健
     * @param data['id']    int     用户ID
     * @return json
     */
    public function getUserInfo($data)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array()
        );
        // 校验用户ID
        if (empty($data['id']) || !is_numeric($data['id'])) {
            $returnResult['code'] = 1001;
            $returnResult['info'] = '用户ID输入错误';
            return $returnResult;
        }
        $sql  = "SELECT id, user_name, create_time, update_time, login_ip, group_id, coupon_level_id, coupon_level_valid_end, is_effect, is_delete, email, idno, real_name, mobile, score, money, quota, lock_money, user_type, sex, level_id, point, creditpassed, fdd_customer_id FROM firstp2p_user WHERE id = {$data['id']}";
        $info = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$info) {
            $returnResult['code'] = 1002;
            $returnResult['info'] = '用户信息不存在';
            return $returnResult;
        }
        $sql             = "SELECT SUM(wait_capital) FROM firstp2p_deal_load WHERE user_id = {$info['id']} AND debt_status in (0,1) AND wait_capital > 0";
        $zx_wait_capital = Yii::app()->fdb->createCommand($sql)->queryScalar();
        $sql             = "SELECT SUM(wait_capital) FROM firstp2p_deal_load WHERE user_id = {$info['id']} AND debt_status in (0,1) AND wait_capital > 0";
        $ph_wait_capital = Yii::app()->phdb->createCommand($sql)->queryScalar();

        $info['create_time']            = date('Y-m-d H:i:s', $info['create_time']);
        $info['update_time']            = date('Y-m-d H:i:s', $info['update_time']);
        $info['coupon_level_valid_end'] = date('Y-m-d H:i:s', $info['coupon_level_valid_end']);
        $info['idno']                   = GibberishAESUtil::dec($info['idno'], Yii::app()->c->idno_key); // 证件号码解密
        $info['mobile']                 = GibberishAESUtil::dec($info['mobile'], Yii::app()->c->idno_key); // 手机号解密
        $info['fdd_customer_id']        = $info['fdd_customer_id'] ? $info['fdd_customer_id'] : '';
        $info['zx_wait_capital']        = 0;
        $info['ph_wait_capital']        = 0;
        if ($zx_wait_capital) {
            $info['zx_wait_capital']    = $zx_wait_capital;
        }
        if ($ph_wait_capital) {
            $info['ph_wait_capital']    = $ph_wait_capital;
        }

        $returnResult['code'] = 0;
        $returnResult['info'] = '查询成功';
        $returnResult['data'] = $info;
        return $returnResult;
    }

    /**
     * 校验用户ID
     * @param id    int     用户ID
     * @return array
     */
    private function checkUserID($id)
    {
        $sql    = "SELECT * FROM firstp2p_user WHERE id = {$id} ";
        $result = Yii::app()->fdb->createCommand($sql)->queryRow();
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 校验投资记录ID
     * @param tender_id     int     用户ID
     * @param deal_type     int     所属平台 1尊享 2普惠
     * @return array
     */
    private function checkTenderID($deal_type , $tender_id)
    {
        if ($deal_type == 1) {
            $model = Yii::app()->fdb;
        } else if ($deal_type == 2) {
            $model = Yii::app()->phdb;
        }
        $sql    = "SELECT * FROM firstp2p_deal_load WHERE id = {$tender_id} ";
        $result = $model->createCommand($sql)->queryRow();
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 校验项目ID
     * @param deal_id       int     用户ID
     * @param deal_type     int     所属平台 1尊享 2普惠
     * @return array
     */
    private function checkDealID($deal_type , $deal_id)
    {
        if ($deal_type == 1) {
            $model = Yii::app()->fdb;
        } else if ($deal_type == 2) {
            $model = Yii::app()->phdb;
        }
        $sql    = "SELECT * FROM firstp2p_deal WHERE id = {$deal_id} ";
        $result = $model->createCommand($sql)->queryRow();
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 校验项目名称
     * @param deal_name     int     用户ID
     * @param deal_type     int     所属平台 1尊享 2普惠
     * @return array
     */
    private function checkDealName($deal_type , $deal_name)
    {
        if ($deal_type == 1) {
            $model = Yii::app()->fdb;
        } else if ($deal_type == 2) {
            $model = Yii::app()->phdb;
        }
        $sql    = "SELECT * FROM firstp2p_deal WHERE name = '{$deal_name}' ";
        $result = $model->createCommand($sql)->queryRow();
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 债权扣除记录 添加 张健
     * @param data['user_id']           int     用户ID
     * @param data['tender_id']         int     投资记录ID
     * @param data['deal_id']           int     项目ID(非必传,与项目名称二选一)
     * @param data['deal_name']         string  项目名称(非必传,与项目ID二选一)
     * @param data['buyback_user_id']   int     回购用户ID(非必传,默认12131543)
     * @param data['debt_account']      float   债权划扣金额
     * @param data['deal_type']         int     所属平台 1尊享 2普惠
     * @param data['agreement_pic']     file    授权债转协议图片
     * @return json
     */
    public function addDebtDeduct($data)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array()
        );
        // 校验用户ID
        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            $returnResult['code'] = 1001;
            $returnResult['info'] = '请正确输入用户ID';
            return $returnResult;
        }
        $user_id_info = $this->checkUserID($data['user_id']);
        if (!$user_id_info) {
            $returnResult['code'] = 1002;
            $returnResult['info'] = '用户ID输入错误';
            return $returnResult;
        }
        // 校验所属平台
        if (empty($data['deal_type']) || !is_numeric($data['deal_type']) || !in_array($data['deal_type'], array(1, 2))) {
            $returnResult['code'] = 1003;
            $returnResult['info'] = '请正确输入所属平台';
            return $returnResult;
        }
        // 校验投资记录ID
        if (empty($data['tender_id']) || !is_numeric($data['tender_id'])) {
            $returnResult['code'] = 1004;
            $returnResult['info'] = '请正确输入投资记录ID';
            return $returnResult;
        }
        $tender_id_info = $this->checkTenderID($data['deal_type'] , $data['tender_id']);
        if (!$tender_id_info) {
            $returnResult['code'] = 1005;
            $returnResult['info'] = '投资记录ID输入错误';
            return $returnResult;
        }
        // 校验项目ID和项目名称至少有一个
        if (empty($data['deal_id']) && empty($data['deal_name'])) {
            $returnResult['code'] = 1006;
            $returnResult['info'] = '请输入项目ID或项目名称其中至少一项';
            return $returnResult;
        }
        // 校验项目ID
        $deal_id_info = array();
        if (!empty($data['deal_id'])) {
            if (!is_numeric($data['deal_id'])) {
                $returnResult['code'] = 1007;
                $returnResult['info'] = '请正确输入项目ID';
                return $returnResult;
            }
            $deal_id_info = $this->checkDealID($data['deal_type'] , $data['deal_id']);
            if (!$deal_id_info) {
                $returnResult['code'] = 1008;
                $returnResult['info'] = '项目ID输入错误';
                return $returnResult;
            }
        }
        // 校验项目名称
        $deal_name_info = array();
        if (!empty($data['deal_name'])) {
            $deal_name      = trim($data['deal_name']);
            $deal_name_info = $this->checkDealName($data['deal_type'] , $deal_name);
            if (!$deal_name_info) {
                $returnResult['code'] = 1009;
                $returnResult['info'] = '项目名称输入错误';
                return $returnResult;
            }
            
        }
        // 校验项目ID与项目名称是否匹配
        if ($deal_id_info && $deal_name_info) {
            if ($deal_id_info['id'] != $deal_name_info['id']) {
                $returnResult['code'] = 1010;
                $returnResult['info'] = '项目ID与项目名称不匹配';
                return $returnResult;
            }
        }
        if ($deal_id_info) {
            $deal_info = $deal_id_info;
        }
        if ($deal_name_info) {
            $deal_info = $deal_name_info;
        }
        // 校验投资记录与项目是否匹配
        if ($tender_id_info['deal_id'] != $deal_info['id']) {
            $returnResult['code'] = 1011;
            $returnResult['info'] = '投资记录与项目不匹配';
            return $returnResult;
        }
        // 校验投资记录与用户是否匹配
        if ($tender_id_info['user_id'] != $user_id_info['id']) {
            $returnResult['code'] = 1012;
            $returnResult['info'] = '投资记录与用户不匹配';
            return $returnResult;
        }
        // 校验回购用户ID
        if (!empty($data['buyback_user_id'])) {
            if (!is_numeric($data['buyback_user_id'])) {
                $returnResult['code'] = 1013;
                $returnResult['info'] = '请正确输入回购用户ID';
                return $returnResult;
            }
            $buyback_user_id_info = $this->checkUserID($data['buyback_user_id']);
            if (!$buyback_user_id_info) {
                $returnResult['code'] = 1014;
                $returnResult['info'] = '回购用户ID输入错误';
                return $returnResult;
            }
        } else {
            $buyback_user_id_info['id'] = 12131543;
        }
        // 校验用户ID与回购用户ID是否相同
        if ($user_id_info['id'] == $buyback_user_id_info['id']) {
            $returnResult['code'] = 1015;
            $returnResult['info'] = '用户ID与回购用户ID不能相同';
            return $returnResult;
        }
        // 校验债权划扣金额
        if (empty($data['debt_account']) || !is_numeric($data['debt_account'])) {
            $returnResult['code'] = 1016;
            $returnResult['info'] = '请正确输入债权划扣金额';
            return $returnResult;
        }
        if (bccomp($data['debt_account'] , $tender_id_info['wait_capital'] , 2) == 1) {
            $returnResult['code'] = 1017;
            $returnResult['info'] = '债权划扣金额不能大于待还本金';
            return $returnResult;
        }
        // 上传图片
        $agreement_pic = $this->upload_base64($data['agreement_pic']);
        if (!$agreement_pic) {
            $returnResult['code'] = 1018;
            $returnResult['info'] = '图片上传失败';
            return $returnResult;
        }
        // 添加数据
        $op_user_id = Yii::app()->user->id;
        $time       = time();
        $ip         = Yii::app()->request->userHostAddress;
        $sql        = "INSERT INTO firstp2p_debt_deduct_log (user_id , tender_id , deal_id , deal_type , buyback_user_id , debt_account , op_user_id , addtime , addip , deal_name , agreement_pic) VALUES({$user_id_info['id']} , {$tender_id_info['id']} , {$deal_info['id']} , {$data['deal_type']} , {$buyback_user_id_info['id']} , {$data['debt_account']} , {$op_user_id} , {$time} , '{$ip}' , '{$deal_info['name']}' , '{$agreement_pic}') ";
        $result     = Yii::app()->fdb->createCommand($sql)->execute();
        if (!$result) {
            $returnResult['code'] = 1019;
            $returnResult['info'] = '添加债权扣除记录失败';
            return $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '添加债权扣除记录成功';
        return $returnResult;
    }

    /**
     * 债权扣除记录 列表 张健
     * 提供查询字段：
     * @param data['user_id']       int     用户ID
     * @param data['deal_type']     int     所属平台 1尊享 2普惠
     * @param data['limit']         int     每页数据显示量 默认10
     * @param data['page']          int     当前页数 默认1
     * @return json
     */
    public function debtDeductList($data = array(), $limit = 10, $page = 1)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
        );
        // 条件筛选
        $where = "";
        // 校验用户ID
        if (!empty($data['user_id'])) {
            if (!is_numeric($data['user_id'])) {
                $returnResult['code'] = 1001;
                $returnResult['info'] = '用户ID输入错误';
                return $returnResult;
            }
            $where .= " AND user_id = {$data['user_id']} ";
        }
        // 校验所属平台
        if (!empty($data['deal_type'])) {
            if (!is_numeric($data['deal_type']) || !in_array($data['deal_type'], array(1, 2))) {
                $returnResult['code'] = 1002;
                $returnResult['info'] = '所属平台输入错误';
                return $returnResult;
            }
            $where .= " AND deal_type = {$data['deal_type']} ";
        }
        // 校验每页数据显示量
        if (!empty($data['limit'])) {
            if (!is_numeric($data['limit']) || !in_array($data['limit'], array(10, 20, 30, 40, 50))) {
                $returnResult['code'] = 1003;
                $returnResult['info'] = '每页数据显示量输入错误';
                return $returnResult;
            }
            $limit = $data['limit'];
        }
        // 校验当前页数
        if (!empty($data['page'])) {
            if (!is_numeric($data['page']) || $data['page'] < 1) {
                $returnResult['code'] = 1004;
                $returnResult['info'] = '当前页数输入错误';
                return $returnResult;
            }
            $page = $data['page'];
        }
        $sql   = "SELECT count(id) AS count FROM firstp2p_debt_deduct_log WHERE 1 = 1 {$where} ";
        $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
        if ($count == 0) {
            $returnResult['code'] = 1005;
            $returnResult['info'] = '暂无更多数据';
            return $returnResult;
        }
        $returnResult['data']['listTotal'] = $count;
        // 查询数据
        $sql     = "SELECT * FROM firstp2p_debt_deduct_log WHERE 1 = 1 {$where} ORDER BY id DESC ";
        $offsets = ($page - 1) * $limit;
        $sql    .= " LIMIT {$offsets},{$limit} ";
        $list    = Yii::app()->fdb->createCommand($sql)->queryAll();
        foreach ($list as $key => $value) {
            $value['addtime'] = date('Y-m-d H:i:s' , $value['addtime']);
            if ($value['start_time'] != 0) {
                $value['start_time'] = date('Y-m-d H:i:s' , $value['start_time']);
            } else {
                $value['start_time'] = '';
            }
            if ($value['successtime'] != 0) {
                $value['successtime'] = date('Y-m-d H:i:s' , $value['successtime']);
            } else {
                $value['successtime'] = '';
            }
            $listInfo[] = $value;
        }

        $returnResult['code']             = 0;
        $returnResult['info']             = '查询成功';
        $returnResult['data']['listInfo'] = $listInfo;
        return $returnResult;
    }

    /**
     * 债权扣除记录 详情 张健
     * @param id    int     记录ID
     * @return json
     */
    public function debtDeductInfo($data)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array()
        );
        // 校验记录ID
        if (empty($data['id']) || !is_numeric($data['id'])) {
            $returnResult['code'] = 1001;
            $returnResult['info'] = '请正确输入记录ID';
            return $returnResult;
        }
        $sql = "SELECT * FROM firstp2p_debt_deduct_log WHERE id = {$data['id']} ";
        $old = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$old) {
            $returnResult['code'] = 1002;
            $returnResult['info'] = '记录ID输入错误';
            return $returnResult;
        }
        $old['addtime'] = date('Y-m-d H:i:s' , $old['addtime']);
        if ($old['start_time'] != 0) {
            $old['start_time'] = date('Y-m-d H:i:s' , $old['start_time']);
        } else {
            $old['start_time'] = '';
        }
        if ($old['successtime'] != 0) {
            $old['successtime'] = date('Y-m-d H:i:s' , $old['successtime']);
        } else {
            $old['successtime'] = '';
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '查询成功';
        $returnResult['data'] = $old;
        return $returnResult;
    }

    /**
     * 债权扣除记录 编辑 张健
     * @param data['id']                int     记录ID
     * @param data['tender_id']         int     投资记录ID
     * @param data['deal_id']           int     项目ID(非必传,与项目名称二选一)
     * @param data['deal_name']         string  项目名称(非必传,与项目ID二选一)
     * @param data['buyback_user_id']   int     回购用户ID(非必传,默认12131543)
     * @param data['debt_account']      float   债权划扣金额
     * @param data['deal_type']         int     所属平台 1尊享 2普惠
     * @return json
     */
    public function editDebtDeduct($data)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array()
        );
        // 校验记录ID
        if (empty($data['id']) || !is_numeric($data['id'])) {
            $returnResult['code'] = 1001;
            $returnResult['info'] = '请正确输入记录ID';
            return $returnResult;
        }
        $sql = "SELECT * FROM firstp2p_debt_deduct_log WHERE id = {$data['id']} ";
        $old = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$old) {
            $returnResult['code'] = 1002;
            $returnResult['info'] = '记录ID输入错误';
            return $returnResult;
        }
        // 校验所属平台
        if (empty($data['deal_type']) || !is_numeric($data['deal_type']) || !in_array($data['deal_type'], array(1, 2))) {
            $returnResult['code'] = 1003;
            $returnResult['info'] = '请正确输入所属平台';
            return $returnResult;
        }
        // 校验投资记录ID
        if (empty($data['tender_id']) || !is_numeric($data['tender_id'])) {
            $returnResult['code'] = 1004;
            $returnResult['info'] = '请正确输入投资记录ID';
            return $returnResult;
        }
        $tender_id_info = $this->checkTenderID($data['deal_type'] , $data['tender_id']);
        if (!$tender_id_info) {
            $returnResult['code'] = 1005;
            $returnResult['info'] = '投资记录ID输入错误';
            return $returnResult;
        }
        // 校验项目ID和项目名称至少有一个
        if (empty($data['deal_id']) && empty($data['deal_name'])) {
            $returnResult['code'] = 1006;
            $returnResult['info'] = '请输入项目ID或项目名称其中至少一项';
            return $returnResult;
        }
        // 校验项目ID
        $deal_id_info = array();
        if (!empty($data['deal_id'])) {
            if (!is_numeric($data['deal_id'])) {
                $returnResult['code'] = 1007;
                $returnResult['info'] = '请正确输入项目ID';
                return $returnResult;
            }
            $deal_id_info = $this->checkDealID($data['deal_type'] , $data['deal_id']);
            if (!$deal_id_info) {
                $returnResult['code'] = 1008;
                $returnResult['info'] = '项目ID输入错误';
                return $returnResult;
            }
        }
        // 校验项目名称
        $deal_name_info = array();
        if (!empty($data['deal_name'])) {
            $deal_name      = trim($data['deal_name']);
            $deal_name_info = $this->checkDealName($data['deal_type'] , $deal_name);
            if (!$deal_name_info) {
                $returnResult['code'] = 1009;
                $returnResult['info'] = '项目名称输入错误';
                return $returnResult;
            }
            
        }
        // 校验项目ID与项目名称是否匹配
        if ($deal_id_info && $deal_name_info) {
            if ($deal_id_info['id'] != $deal_name_info['id']) {
                $returnResult['code'] = 1010;
                $returnResult['info'] = '项目ID与项目名称不匹配';
                return $returnResult;
            }
        }
        if ($deal_id_info) {
            $deal_info = $deal_id_info;
        }
        if ($deal_name_info) {
            $deal_info = $deal_name_info;
        }
        // 校验投资记录与项目是否匹配
        if ($tender_id_info['deal_id'] != $deal_info['id']) {
            $returnResult['code'] = 1011;
            $returnResult['info'] = '投资记录与项目不匹配';
            return $returnResult;
        }
        // 校验投资记录与用户是否匹配
        if ($tender_id_info['user_id'] != $old['user_id']) {
            $returnResult['code'] = 1012;
            $returnResult['info'] = '投资记录与用户不匹配';
            return $returnResult;
        }
        // 校验回购用户ID
        if (!empty($data['buyback_user_id'])) {
            if (!is_numeric($data['buyback_user_id'])) {
                $returnResult['code'] = 1013;
                $returnResult['info'] = '请正确输入回购用户ID';
                return $returnResult;
            }
            $buyback_user_id_info = $this->checkUserID($data['buyback_user_id']);
            if (!$buyback_user_id_info) {
                $returnResult['code'] = 1014;
                $returnResult['info'] = '回购用户ID输入错误';
                return $returnResult;
            }
        } else {
            $buyback_user_id_info['id'] = 12131543;
        }
        // 校验用户ID与回购用户ID是否相同
        if ($old['user_id'] == $buyback_user_id_info['id']) {
            $returnResult['code'] = 1015;
            $returnResult['info'] = '用户ID与回购用户ID不能相同';
            return $returnResult;
        }
        // 校验债权划扣金额
        if (empty($data['debt_account']) || !is_numeric($data['debt_account'])) {
            $returnResult['code'] = 1016;
            $returnResult['info'] = '请正确输入债权划扣金额';
            return $returnResult;
        }
        if (bccomp($data['debt_account'] , $tender_id_info['wait_capital'] , 2) == 1) {
            $returnResult['code'] = 1017;
            $returnResult['info'] = '债权划扣金额不能大于待还本金';
            return $returnResult;
        }
        // 上传图片
        $agreement_pic = $this->upload_base64($data['agreement_pic']);
        if ($agreement_pic) {
            $pic = " , agreement_pic = '{$agreement_pic}' ";
        } else {
            $pic = '';
        }
        // 修改数据
        $op_user_id = Yii::app()->user->id;
        $ip         = Yii::app()->request->userHostAddress;
        $sql        = "UPDATE firstp2p_debt_deduct_log SET tender_id = {$tender_id_info['id']} , deal_id = {$deal_info['id']} , buyback_user_id = {$buyback_user_id_info['id']} , deal_type = {$data['deal_type']} , debt_account = {$data['debt_account']} , op_user_id = {$op_user_id} , addip = '{$ip}' , deal_name = '{$deal_info['name']}' {$pic} WHERE id = {$old['id']} ";
        $result     = Yii::app()->fdb->createCommand($sql)->execute();
        if (!$result) {
            $returnResult['code'] = 1018;
            $returnResult['info'] = '保存债权扣除记录失败';
            return $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '保存债权扣除记录成功';
        return $returnResult;
    }

    /**
     * 债权扣除记录 启动 张健
     * @param data['id']    int     记录ID
     * @return json
     */
    public function StartDebtDeduct($data)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array()
        );
        // 校验记录ID
        if (empty($data['id']) || !is_numeric($data['id'])) {
            $returnResult['code'] = 1001;
            $returnResult['info'] = '请正确输入记录ID';
            return $returnResult;
        }
        $sql = "SELECT * FROM firstp2p_debt_deduct_log WHERE id = {$data['id']} ";
        $old = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$old) {
            $returnResult['code'] = 1002;
            $returnResult['info'] = '记录ID输入错误';
            return $returnResult;
        }
        // 校验记录状态
        if ($old['status'] != 0) {
            $returnResult['code'] = 1003;
            $returnResult['info'] = '此记录不能重复启动';
            return $returnResult;
        }
        $time   = time();
        $sql    = "UPDATE firstp2p_debt_deduct_log SET status = 1 , start_time = {$time} WHERE id = {$old['id']} ";
        $result = Yii::app()->fdb->createCommand($sql)->execute();
        if (!$result) {
            $returnResult['code'] = 1003;
            $returnResult['info'] = '启动失败';
            return $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '启动成功';
        return $returnResult;
    }

    /**
     * 上传图片 张健 
     * @param name  string  图片名称
     * @return string
     */
    private function upload($name)
    {
        $file  = $_FILES[$name];
        $types = array('image/jpg' , 'image/jpeg' , 'image/png' , 'image/pjpeg' , 'image/gif' , 'image/bmp' , 'image/x-png');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的图片超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的图片超过了脚本显示' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => '图片只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有图片被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => '图片写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => '图片上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $file_type = $file['type'];
        if(!in_array($file_type, $types)){
            return array('code' => 2007 , 'info' => '图片类型不匹配' , 'data' => '');
        }
        $new_name = date('His' . rand(1000,9999));
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir , 0777 , true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建图片目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.jpg';
        $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存图片成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存图片失败' , 'data' => '');
        }
    }

    /**
     * 上传图片 张健 
     * @param content   string  图片的base64内容
     * @return string
     */
    private function upload_base64($content)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/' , $content , $result)) {
            $pic_type    = $result[2]; // 匹配出图片后缀名
            $dir_name    = date('Ymd');
            $dir_address = "upload/" . $dir_name . '/';
            if (!file_exists($dir_address)) {
                mkdir($dir_address, 0777, true);
            }
            $pic_name    = time() . rand(10000 , 99999) . ".{$pic_type}";
            $pic_address = $dir_address . $pic_name;
            if (file_put_contents($pic_address , base64_decode(str_replace($result[1] , '' , $content)))) {

                return $pic_address;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 还款管理 列表 张健
     * @param data['deal_type']     int     项目类型[1-尊享 2-普惠供应链]
     * @param data['deal_name']     string  项目名称
     * @param data['user_name']     string  借款方名称
     * @param data['type']          int     资金类型[1-本金 2-利息]
     * @param data['status']        int     还款状态[1未还 2已还]
     * @return json
     */
    public function loanRepayList($data = array(), $limit = 10, $page = 1)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
        );
        if (empty($data['deal_type'])) {
            $returnResult['code'] = 1001;
            $returnResult['info'] = '请输入项目类型';
            return $returnResult;
        }
        if (!is_numeric($data['deal_type']) || !in_array($data['deal_type'] , array(1 , 2))) {
            $returnResult['code'] = 1002;
            $returnResult['info'] = '项目类型输入错误';
            return $returnResult;
        }
        // 尊享
        if ($data['deal_type'] == 1) {

            // 条件筛选
            $where = "";
            // 校验项目名称
            if (!empty($data['deal_name'])) {
                $deal_name = trim($data['deal_name']);
                $where    .= " AND d.name = {$deal_name} ";
            }
            // 校验借款方名称
            if (!empty($data['user_name'])) {
                $user_name = trim($data['user_name']);
                $where    .= " AND u.real_name = {$user_name} ";
            }
            // 校验资金类型
            if (!empty($data['type'])) {
                if (!is_numeric($data['type']) || !in_array($data['type'], array(1, 2))) {
                    $returnResult['code'] = 1003;
                    $returnResult['info'] = '资金类型输入错误';
                    return $returnResult;
                }
                $where .= " AND dlr.type = {$data['type']} ";
            }
            // 校验还款状态
            if (!empty($data['status'])) {
                if (!is_numeric($data['status']) || !in_array($data['status'], array(1, 2))) {
                    $returnResult['code'] = 1004;
                    $returnResult['info'] = '还款状态输入错误';
                    return $returnResult;
                }
                $status = $data['status'] - 1;
                $where .= " AND dlr.status = {$status} ";
            }
            // 校验每页数据显示量
            if (!empty($data['limit'])) {
                if (!is_numeric($data['limit']) || !in_array($data['limit'], array(10, 20, 30, 40, 50))) {
                    $returnResult['code'] = 1005;
                    $returnResult['info'] = '每页数据显示量输入错误';
                    return $returnResult;
                }
                $limit = $data['limit'];
            }
            // 校验当前页数
            if (!empty($data['page'])) {
                if (!is_numeric($data['page']) || $data['page'] < 1) {
                    $returnResult['code'] = 1006;
                    $returnResult['info'] = '当前页数输入错误';
                    return $returnResult;
                }
                $page = $data['page'];
            }
            // 查询数据总量
            $sql = "SELECT count(DISTINCT dlr.deal_id , dlr.time , dlr.type) AS count FROM
                    (firstp2p_deal_loan_repay AS dlr INNER JOIN firstp2p_deal AS d ON dlr.deal_id = d.id)
                    INNER JOIN firstp2p_user AS u ON dlr.borrow_user_id = u.id {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                $returnResult['code'] = 1007;
                $returnResult['info'] = '暂无更多数据';
                return $returnResult;
            }
            $returnResult['data']['listTotal'] = $count;
            // 查询数据
            $sql = "SELECT dlr.deal_id , d.name , u.real_name , dlr.time , dlr.type , sum(dlr.money) AS money , dlr.status FROM
                    (firstp2p_deal_loan_repay AS dlr INNER JOIN firstp2p_deal AS d ON dlr.deal_id = d.id)
                    INNER JOIN firstp2p_user AS u ON dlr.borrow_user_id = u.id {$where} GROUP BY dlr.deal_id , dlr.time , dlr.type ORDER BY dlr.id DESC ";
            $offsets = ($page - 1) * $limit;
            $sql    .= " LIMIT {$offsets},{$limit} ";
            $list    = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value){
                $value['time']      = date('Y-m-d H:i:s', $value['time']);
                $value['deal_type'] = 1;
                $listInfo[]         = $value;
            }
            $returnResult['code']             = 0;
            $returnResult['info']             = '查询成功';
            $returnResult['data']['listInfo'] = $listInfo;
            return $returnResult;

        // 普惠供应链
        } else if ($data['deal_type'] == 2) {

            // 条件筛选
            $where = "";
            // 校验项目名称
            if (!empty($data['deal_name'])) {
                $deal_name = trim($data['deal_name']);
                $where    .= " AND d.name = {$deal_name} ";
            }
            // 校验借款方名称
            if (!empty($data['user_name'])) {
                $user_name = trim($data['user_name']);
                $sql       = "SELECT id FROM firstp2p_user WHERE real_name = '{$user_name}' ";
                $user_id   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND dlr.borrow_user_id = {$user_id} ";
                } else {
                    $where .= " AND dlr.borrow_user_id is NULL ";
                }
            }
            // 校验资金类型
            if (!empty($data['type'])) {
                if (!is_numeric($data['type']) || !in_array($data['type'], array(1, 2))) {
                    $returnResult['code'] = 1003;
                    $returnResult['info'] = '资金类型输入错误';
                    return $returnResult;
                }
                $where .= " AND dlr.type = {$data['type']} ";
            }
            // 校验还款状态
            if (!empty($data['status'])) {
                if (!is_numeric($data['status']) || !in_array($data['status'], array(1, 2))) {
                    $returnResult['code'] = 1004;
                    $returnResult['info'] = '还款状态输入错误';
                    return $returnResult;
                }
                $status = $data['status'] - 1;
                $where .= " AND dlr.status = {$status} ";
            }
            // 校验每页数据显示量
            if (!empty($data['limit'])) {
                if (!is_numeric($data['limit']) || !in_array($data['limit'], array(10, 20, 30, 40, 50))) {
                    $returnResult['code'] = 1005;
                    $returnResult['info'] = '每页数据显示量输入错误';
                    return $returnResult;
                }
                $limit = $data['limit'];
            }
            // 校验当前页数
            if (!empty($data['page'])) {
                if (!is_numeric($data['page']) || $data['page'] < 1) {
                    $returnResult['code'] = 1006;
                    $returnResult['info'] = '当前页数输入错误';
                    return $returnResult;
                }
                $page = $data['page'];
            }
            // 查询数据总量
            $sql = "SELECT count(DISTINCT dlr.deal_id , dlr.time , dlr.type) AS count FROM firstp2p_deal_loan_repay AS dlr INNER JOIN firstp2p_deal AS d ON dlr.deal_id = d.id {$where} ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                $returnResult['code'] = 1007;
                $returnResult['info'] = '暂无更多数据';
                return $returnResult;
            }
            $returnResult['data']['listTotal'] = $count;
            // 查询数据
            $sql = "SELECT dlr.deal_id , d.name , dlr.time , dlr.type , sum(dlr.money) AS money , dlr.status , dlr.borrow_user_id FROM
                    firstp2p_deal_loan_repay AS dlr INNER JOIN firstp2p_deal AS d ON dlr.deal_id = d.id {$where} GROUP BY dlr.deal_id , dlr.time , dlr.type ORDER BY dlr.id DESC ";
            $offsets = ($page - 1) * $limit;
            $sql    .= " LIMIT {$offsets},{$limit} ";
            $list    = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value){
                $value['time']      = date('Y-m-d H:i:s', $value['time']);
                $value['deal_type'] = 2;
                $user_id_arr[]      = $value['borrow_user_id'];
                unset($value['borrow_user_id']);
                $listInfo[]         = $value;
            }
            $user_id_str = implode(',' , $user_id_arr);
            $sql         = "SELECT id , real_name FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
            $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($user_id_res as $key => $value) {
                $temp['real_name'] = $value['real_name'];

                $user_id_data[$value['id']] = $temp;
            }
            foreach ($listInfo as $key => $value) {
                $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
            }
            $returnResult['code']             = 0;
            $returnResult['info']             = '查询成功';
            $returnResult['data']['listInfo'] = $listInfo;
            return $returnResult;
        }
    }

    /**
     * 还款管理 启动 张健
     * @param deal_type     int     项目类型[1-尊享 2-普惠供应链]
     * @param dlr_id        int     还款记录ID
     * @return json
     */
    public function startLoanRepay($data)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1000, 'info' => '', 'data' => array()
        );
        // 校验项目类型
        if (empty($data['deal_type']) || !is_numeric($data['deal_type']) || !in_array($data['deal_type'] , array(1 , 2))) {
            $returnResult['code'] = 1001;
            $returnResult['info'] = '请正确输入项目类型';
            return $returnResult;
        }
        if ($data['deal_type'] == 1) {
            $model = Yii::app()->fdb;
        } else if ($data['deal_type'] == 2) {
            $model = Yii::app()->phdb;
        }
        if (empty($data['dlr_id']) || !is_numeric($data['dlr_id'])) {
            $returnResult['code'] = 1002;
            $returnResult['info'] = '请正确输入还款记录ID';
            return $returnResult;
        }
        $sql = "SELECT * FROM firstp2p_deal_loan_repay WHERE id = {$data['dlr_id']} ";
        $res = $model->createCommand($sql)->queryRow();
        if (!$res) {
            $returnResult['code'] = 1003;
            $returnResult['info'] = '还款记录ID输入错误';
            return $returnResult;
        }
        // 校验记录状态
        if ($res['status'] != 0) {
            $returnResult['code'] = 1004;
            $returnResult['info'] = '此还款记录不能重复启动';
            return $returnResult;
        }
        exit;

        $sql        = "INSERT INTO ag_inner_repayment_plan (deal_type , tender_id , deal_id , deal_type , buyback_user_id , debt_account , op_user_id , addtime , addip , deal_name , agreement_pic) VALUES({$user_id_info['id']} , {$tender_id_info['id']} , {$deal_info['id']} , {$data['deal_type']} , {$buyback_user_id_info['id']} , {$data['debt_account']} , {$op_user_id} , {$time} , '{$ip}' , '{$deal_info['name']}' , '{$agreement_pic}') ";
        $result     = Yii::app()->fdb->createCommand($sql)->execute();
        $time   = time();
        $sql    = "UPDATE firstp2p_debt_deduct_log SET status = 1 , start_time = {$time} WHERE id = {$old['id']} ";
        $result = Yii::app()->fdb->createCommand($sql)->execute();
        if (!$result) {
            $returnResult['code'] = 1003;
            $returnResult['info'] = '启动失败';
            return $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '启动成功';
        return $returnResult;
    }

    public function add_frozen_loan($post_data , $template_url , $data)
    {
        $result = array('code'=>1000 , 'info'=>'');

        //角色校验
        $op_user_id = \Yii::app()->user->id;
        $op_user_name = \Yii::app()->user->name;
        $m_name = 'firstp2p_deal_load';
        if ($post_data['platform_id'] == 1) {
            $model = Yii::app()->fdb;
        } else if ($post_data['platform_id'] == 2) {
            $model = Yii::app()->phdb;
        }else if ($post_data['platform_id'] == 4) {
            $model = Yii::app()->offlinedb;
            $m_name = 'offline_deal_load';
        }

        //空数据过滤
        $deal_load_id_arr = [];
        $excel_arr = [];
        foreach ($data as $key => $value) {
            if (empty($value[0])) {
                $result['info'] = '第'.($key+1).'行投资记录ID为空';
                return $result;
            }
            if (!is_numeric($value[0])) {
                $result['info'] = '第'.($key+1).'行投资记录ID格式错误';
                return $result;
            }
            $deal_load_id_arr[$key] = intval($value[0]);
            $excel_arr[$value[0]] = $value[1];
        }

        $deal_load_id_str = implode(',' , $deal_load_id_arr);

        // 查询借款项目
        $sql = "SELECT id  FROM $m_name WHERE id IN ({$deal_load_id_str}) and status=1 and xf_status=0 ";
        $deal_load_ids = $model->createCommand($sql)->queryColumn();
        if (empty($deal_load_ids)) {
            $result['info'] = '未查询到任何在途且未冻结的投资记录,无需冻结';
            return $result;
        }

        $model->beginTransaction();
        $f = 0;
        $error_str = '';
        $load_sql_values = '';
        foreach ($deal_load_id_arr as $key=>$value){
            if(!in_array($value, $deal_load_ids)){
                $f++;
                $error_str .= " $value; ";
                continue;
            }
            $edit_load_data = [];
            $edit_load_data['id'] = $value;
            $edit_load_data['xf_status'] = 1;
            $edit_load_data['frozen_batch_number'] = " '{$post_data['frozen_batch_number']}' ";
            $edit_load_data['frozen_remark'] = " '{$excel_arr[$value]}' " ?: " " ;
            $edit_load_data['frozen_time'] = time();
            $edit_load_data['frozen_op_uid'] = $op_user_id;
            $edit_load_data['frozen_op_name'] = " '{$op_user_name}' ";
            $load_sql_values .= "(".implode(",", $edit_load_data)."),";
        }

        //更新投资记录
        $load_sql_values = rtrim($load_sql_values, ",");
        if(empty($load_sql_values)) {
            $model->rollback();
            $result['info'] = '无有效在途投资记录';
            return $result;
        }
        $sql = "INSERT INTO firstp2p_deal_load (id, xf_status, frozen_batch_number,frozen_remark, frozen_time,frozen_op_uid ,frozen_op_name) VALUES $load_sql_values ON DUPLICATE KEY".
            " UPDATE  xf_status=VALUES(xf_status),frozen_batch_number=VALUES(frozen_batch_number),frozen_remark=VALUES(frozen_remark), frozen_time=VALUES(frozen_time), frozen_op_uid=VALUES(frozen_op_uid) ,frozen_op_name=VALUES(frozen_op_name)";
        $command = $model->createCommand($sql)->execute();
        if(false === $command) {
            $model->rollback();
            $result['info'] = '投资记录更新失败';
            return $result;
        }
        $return_info = $error_str ? "部分冻结操作成功, 失败投资记录ID:$error_str" : "冻结操作成功";
        $model->commit();
        $result['code'] = 0;
        $result['info'] = $return_info;
        return $result;
    }


    public function add_xche_user($post_data , $template_url , $data)
    {
        $result = array('code'=>1000 , 'info'=>'');

        //角色校验
        $op_user_id = \Yii::app()->user->id;
        $op_user_name = \Yii::app()->user->name;
        $model = Yii::app()->fdb;


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

        $user_id_str = implode(',' , $user_id_arr);
        $sql = "SELECT id  FROM firstp2p_user WHERE id IN ({$user_id_str}) and is_online=1 ";
        $user_ids = $model->createCommand($sql)->queryColumn();
        if (empty($user_ids)) {
            $result['info'] = '表格中用户数据，未查询到任何在途用户';
            return $result;
        }

        //已经录入
        $user_sql = "SELECT user_id  FROM xf_shop_xche_user WHERE user_id IN ({$user_id_str}) and status=1 ";
        $shop_user_ids = $model->createCommand($user_sql)->queryColumn();

        $model->beginTransaction();
        $f = 0;
        $error_str = '';
        $user_sql_values = '';
        foreach ($user_id_arr as $key=>$value){
            if(!in_array($value, $user_ids)){
                $f++;
                $error_str .= " 非在途用户：$value; ";
                continue;
            }

            if(in_array($value, $shop_user_ids)){
                $f++;
                $error_str .= " 重复录入：$value; ";
                continue;
            }

            //查询是否录入
            $add_data = [];
            $add_data['user_id'] = $value;
            $add_data['add_time'] = time();
            $add_data['add_batch_number'] = " '{$post_data['add_batch_number']}' ";
            $add_data['add_admin_id'] = $op_user_id;
            $add_data['add_user_name'] = " '{$op_user_name}' ";
            $user_sql_values .= "(".implode(",", $add_data)."),";
        }

        //更新投资记录
        $user_sql_values = rtrim($user_sql_values, ",");
        if(empty($user_sql_values)) {
            $model->rollback();
            $result['info'] = '无有效用户可录入用户：'.$error_str;
            return $result;
        }
        $add_sql = "INSERT INTO xf_shop_xche_user (user_id, add_time,add_batch_number, add_admin_id,add_user_name  ) VALUES $user_sql_values ";
        $command = $model->createCommand($add_sql)->execute();
        if(false === $command) {
            $model->rollback();
            $result['info'] = '导入用户录入失败';
            return $result;
        }
        $return_info = $error_str ? "部分导入操作成功, 失败信息:$error_str" : "导入操作成功";
        $model->commit();
        $result['code'] = 0;
        $result['info'] = $return_info;
        return $result;
    }

}
<?php

class UserController extends CommonController
{
    public function filters()
    {
        return array(
            'UserLogin + Index ',

        );
    }
    /**
     * 首页
     */
    public function actionindex(){

        echo '欢迎使用';
        exit;
    }

    /**
     * 债权转让列表
     * @param   user_id     int     用户ID
     * @param   type        int     查询类型 1-无债权 2-新创建转让中 3-全部转让成功
     * @param   page        int     当前页数 （大于等于1，默认为1）
     * @param   limit       int     每页数据量 （大于等于1，默认为10）
     * @return  json
     */
    public function actionDebtList()
    {
        if (empty($_POST)) {
            $this->echoJson(array() , 1000 , "请用POST方式访问");
        }
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array() , 1001 , "未登录，请重新登录");
        }
        if (empty($_POST['type'])) {
            $this->echoJson(array() , 1017 , "请输入查询类型");
        }
        if (!is_int($user_id)) {
            $this->echoJson(array() , 1005 , "用户ID错误");
        }
        if (!is_int($_POST['type']) || !in_array($_POST['type'] , array(1 , 2 , 3))) {
            $this->echoJson(array() , 1018 , "查询类型错误");
        }
        if (!isset($_POST['page'])) {
            $page = 1;
        } else {
            if (!is_int($_POST['page']) || $_POST['page'] < 1) {
                $this->echoJson(array() , 1019 , '当前页数输入错误');
            } else {
                $page = $_POST['page'];
            }
        }
        if (!isset($_POST['limit'])) {
            $limit = 10;
        } else {
            if (!is_int($_POST['limit']) || $_POST['limit'] < 1) {
                $this->echoJson(array() , 1020 , '每页数据量输入错误');
            } else {
                $limit = $_POST['limit'];
            }
        }
        if ($_POST['type'] == 1 || $_POST['type'] == 3) {
            if ($_POST['type'] == 1) {
                $status = 0;
            } else if ($_POST['type'] == 3) {
                $status = 15;
            }
            $model      = Yii::app()->db;
            $count_sql  =  "SELECT
                            COUNT(id) AS count
                            FROM
                            firstp2p_deal_load
                            WHERE
                            user_id = '{$user_id}'
                            AND
                            debt_status = {$status}";
            $count      = $model->createCommand($count_sql)->queryScalar();
            $page_count = ceil($count / $limit); // 总页数
            $pass       = ($page - 1) * $limit;  // 跳过数据量
            $data_limit = "{$pass},{$limit}";
            $sql        =  "SELECT
                            *
                            FROM
                            firstp2p_deal_load
                            WHERE
                            user_id = '{$user_id}'
                            AND
                            debt_status = {$status}
                            LIMIT {$data_limit}";
            $result     = $model->createCommand($sql)->queryAll();
            if (!$result) {
                $this->echoJson(array() , 1021 , '暂无更多数据');
            }

            $this->echoJson($result , 0 , '查询成功');
        }
        else if ($_POST['type'] == 2) {
            $model      = Yii::app()->db;
            $count_sql  =  "SELECT
                            COUNT(id) AS count
                            FROM
                            firstp2p_debt
                            WHERE
                            user_id = '{$user_id}'
                            AND
                            status = 1";
            $count      = $model->createCommand($count_sql)->queryScalar();
            $page_count = ceil($count / $limit); // 总页数
            $pass       = ($page - 1) * $limit;  // 跳过数据量
            $data_limit = "{$pass},{$limit}";
            $sql        =  "SELECT
                            *
                            FROM
                            firstp2p_debt
                            WHERE
                            user_id = '{$user_id}'
                            AND
                            status = 1
                            LIMIT {$data_limit}";
            $result     = $model->createCommand($sql)->queryAll();
            if (!$result) {
                $this->echoJson(array() , 1021 , '暂无更多数据');
            }

            $this->echoJson($result , 0 , '查询成功');
        }
    }

    /**
     * 发起债权转让 张健
     * @param   user_id         int     用户ID
     * @param   tender_id       int     投标ID
     * @param   money           float   债转金额
     * @param   discount_money  float   折让金额
     * @return  json
     */
    public function actionSellDebt()
    {
        if (empty($_POST)) {
            $this->echoJson(array() , 1000 , "请用POST方式访问");
        }
        $data['user_id'] = $this->user_id;
        if (empty($data['user_id'])) {
            $this->echoJson(array() , 1001 , "未登录，请重新登录");
        }
        if (empty($_POST['tender_id'])) {
            $this->echoJson(array() , 1002 , "请输入投标ID");
        }
        if (empty($_POST['money'])) {
            $this->echoJson(array() , 1003 , "请输入债转金额");
        }
        $_POST['discount_money'] = 0; // 折让金额写死为0
        if (!isset($_POST['discount_money'])) {
            $this->echoJson(array() , 1004 , "请输入折让金额");
        }
        if (!is_int($data['user_id'])) {
            $this->echoJson(array() , 1005 , "用户ID错误");
        }
        $data['tender_id'] = $_POST['tender_id'];
        if (!is_int($data['tender_id'])) {
            $this->echoJson(array() , 1006 , "投标ID错误");
        }
        if (!is_numeric($_POST['money'])) {
            $this->echoJson(array() , 1007 , "债转金额格式错误，请正确填写数字金额");
        }
        $data['money'] = round($_POST['money'] , 2);
        if (bccomp($data['money'] , 0 , 2) <= 0) {
            $this->echoJson(array() , 1008 , "债转金额数值错误，金额请填写正数");
        }
        if (!is_numeric($_POST['discount_money'])) {
            $this->echoJson(array() , 1009 , "折让金额格式错误，请正确填写数字金额");
        }
        $data['discount_money'] = round($_POST['discount_money'] , 2);
        if (bccomp($data['discount_money'] , 0 , 2) === -1) {
            $this->echoJson(array() , 1010 , "折让金额数值错误，金额请填写非负数");
        }
        if (bccomp($data['discount_money'] , $data['money'] , 2) === 1) {
            $this->echoJson(array() , 1011 , "折让金额不可大于债转金额");
        }
        // 发起债权转让
        $result          = DebtService::getInstance()->createDebt($data);
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if ($result['code'] !== 0) {
            $this->echoJson(array() , $result['code'] , $error_code_info[$result['code']]);
        }

        $return_result['debt_id'] = $result['data']['debt_id'];
        $this->echoJson($return_result , 0 , "发起债权转让成功");
    }

    /**
     * 认购债权 张健
     * @param   user_id     int     用户ID
     * @param   debt_id     int     债权ID
     * @param   money       float   认购金额
     * @return  json
     */
    public function actionBuyDebt()
    {
        if (empty($_POST)) {
            $this->echoJson(array() , 1000 , "请用POST方式访问");
        }
        $data['user_id'] = $this->user_id;
        if (empty($data['user_id'])) {
            $this->echoJson(array() , 1001 , "未登录，请重新登录");
        }
        if (empty($_POST['debt_id'])) {
            $this->echoJson(array() , 1012 , "请输入债权ID");
        }
        if (empty($_POST['money'])) {
            $this->echoJson(array() , 1013 , "请输入认购金额");
        }
        if (!is_int($data['user_id'])) {
            $this->echoJson(array() , 1005 , "用户ID错误");
        }
        $data['debt_id'] = $_POST['debt_id'];
        if (!is_int($data['debt_id'])) {
            $this->echoJson(array() , 1014 , "债权ID错误");
        }
        if (!is_numeric($_POST['money'])) {
            $this->echoJson(array() , 1015 , "认购金额格式错误，请正确填写数字金额");
        }
        $data['money'] = round($_POST['money'] , 2);
        if (bccomp($data['money'] , 0 , 2) <= 0) {
            $this->echoJson(array() , 1016 , "认购金额数值错误，金额请填写正数");
        }
        // 认购债权
        $result          = DebtService::getInstance()->debtPreTransaction($data);
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if ($result['code'] !== 0) {
            $this->echoJson(array() , $result['code'] , $error_code_info[$result['code']]);
        }

        $this->echoJson(array() , 0 , "认购债权成功");
    }

}

<?php
class UserController extends CommonController
{
    public function __get($name)
    {
        //风险等级验证
        if($name == "checkUserRisk"){
            if(!empty($this->user_id)){
                $riskInfo = QuestionService::getInstance()->checkUserRisk($this->user_id);
                return $riskInfo['code'];
            }
        }
    }

    /**
     * 查询债转记录 张健
     * @param   type                int     查询类型：1-C1，2-平台，3-资方
     * @param   status              int     查询状态：0-全部，1-发布中，2-已成交，3-已取消，4-已过期
     * @param   platform_id         int     平台ID(默认为空)
     * @param   project_type_id     int     项目类型ID(默认为空)
     * @param   project_id          int     项目ID(默认为空)
     * @param   project_name        string  项目名称(默认为空)
     * @param   real_name           string  真实姓名(默认为空)
     * @param   order               int     排序方式(默认为1)：1-发布时间降序，2-发布时间升序，3-折扣降序，4-折扣升序，5-转让金额降序，6-转让金额升序
     * @param   limit               int     每页显示数据量(默认为10,取值范围1至100的正整数)
     * @param   page                int     当前页数(默认为1,正整数)
     * @return  json
     */
    public function actionIndex()
    {
        $result_data = array('count' => 0 , 'page_count' => 0 ,'data' => array());
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson($result_data , 3000 , $error_code_info[3000]);
        }
        // 初始查询条件
        $where = ' 1 = 1 ';
        // 检验查询类型
        if (empty($_POST['type'])) {
            $this->echoJson($result_data , 3022 , $error_code_info[3022]);
        }
        if (!is_numeric($_POST['type']) || !in_array($_POST['type'] , array(1 , 2 , 3))) {
            $this->echoJson($result_data , 3023 , $error_code_info[3023]);
        }
        if ($_POST['type'] == 1) {

            // C1 查询条件
            // 校验查询状态
            if (!is_numeric($_POST['status']) || !in_array($_POST['status'] , array(0, 1 , 2 , 3 , 4))) {
                $this->echoJson($result_data , 3025 , $error_code_info[3025]);
            }
            $status = intval($_POST['status']);
            if ($status != 0) {
                $where = " d.status = {$status} ";
            }
            if (empty($_POST['platform_id'])) {
                $this->echoJson($result_data , 3043 , $error_code_info[3043]);
            }
            if (!is_numeric($_POST['platform_id'])) {
                $this->echoJson($result_data , 3044 , $error_code_info[3044]);
            }
            $user_info['platform_id'] = $_POST['platform_id'];
            $user_info['id']          = $this->user_id;
            if (!$user_info || !$user_info['id'] || !$user_info['platform_id']) {
                $this->echoJson($result_data , 3007 , $error_code_info[3007]);
            }
            $where .= " AND u.id = {$user_info['id']} AND pf.id = {$user_info['platform_id']} ";
            // 校验是否为爱投资的平台ID
            $itouzi = Yii::app()->c->itouzi;
            if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {

                // 爱投资
                //验证资产花园用户是否绑定平台
                $transformationInfo = PurchService::getInstance()->getformationUserid($user_info['id'] , $user_info['platform_id']);
                if($transformationInfo['code'] != 0){
                    $this->echoJson(array(), $transformationInfo['code'], Yii::app()->c->errorcodeinfo[$transformationInfo['code']]);
                }
                $user_info['platformUserId'] = $transformationInfo['data']['platform_user_id'];
                if (empty($user_info['platformUserId'])) {
                    $this->echoJson($result_data , 3007 , $error_code_info[3007]);
                }
                $data['platformUserId'] = $user_info['platformUserId'];
                $data['status']         = $status;
                $data['order']          = $_POST['order'];
                $data['limit']          = $_POST['limit'];
                $data['page']           = $_POST['page'];

                $result                    = ItouziService::getInstance()->Index($data);
                $result_data['count']      = $result['count'];
                $result_data['page_count'] = $result['page_count'];
                $result_data['data']       = $result['data'];
                $this->echoJson($result_data , $result['code'] , $result['info']);
            }

        } else if ($_POST['type'] == 2) {

            // 平台 查询条件
            // 校验查询状态
            if (!empty($_POST['status'])) {
                if (!is_numeric($_POST['status']) || !in_array($_POST['status'] , array(0, 1 , 2 , 3 , 4))) {
                    $this->echoJson($result_data , 3025 , $error_code_info[3025]);
                }
                $status = intval($_POST['status']);
                if ($status != 0) {
                    $where = " d.status = {$status} ";
                }
            }
            // 校验平台ID
            if (empty($_POST['platform_id']) || !is_numeric($_POST['platform_id'])) {
                $this->echoJson($result_data , 3026 , $error_code_info[3026]);
            }
            $platform_id = intval($_POST['platform_id']);
            $where      .= " AND pf.id = {$platform_id} ";
            // 校验是否为爱投资的平台ID
            $itouzi = Yii::app()->c->itouzi;
            if ($platform_id == $itouzi['itouzi']['platform_id']) {

                // 爱投资
                $data['type']      = $_POST['project_type_id'];
                $data['real_name'] = $_POST['real_name'];
                $data['status']    = $status;
                $data['order']     = $_POST['order'];
                $data['limit']     = $_POST['limit'];
                $data['page']      = $_POST['page'];

                $result                    = ItouziService::getInstance()->Platform($data);
                $result_data['count']      = $result['count'];
                $result_data['page_count'] = $result['page_count'];
                $result_data['data']       = $result['data'];
                $this->echoJson($result_data , $result['code'] , $result['info']);
            }

        } else if ($_POST['type'] == 3) {

            // 资方 查询条件
            $where = " d.status = 1 ";
            // 校验平台ID
            if (!empty($_POST['platform_id'])) {
                if (!is_numeric($_POST['platform_id'])) {
                    $this->echoJson($result_data , 3026 , $error_code_info[3026]);
                }
                $platform_id = intval($_POST['platform_id']);
                $where      .= " AND pf.id = {$platform_id} ";
                // 校验是否为爱投资的平台ID
                $itouzi = Yii::app()->c->itouzi;
                if ($platform_id == $itouzi['itouzi']['platform_id']) {

                    // 爱投资
                    $data['type']      = $_POST['project_type_id'];
                    $data['real_name'] = $_POST['real_name'];
                    $data['project']   = $_POST['project_name'];
                    $data['order']     = $_POST['order'];
                    $data['limit']     = $_POST['limit'];
                    $data['page']      = $_POST['page'];

                    $result                    = ItouziService::getInstance()->Management($data);
                    $result_data['count']      = $result['count'];
                    $result_data['page_count'] = $result['page_count'];
                    $result_data['data']       = $result['data'];
                    $this->echoJson($result_data , $result['code'] , $result['info']);
                }
            }
        }
        // 校验项目类型ID
        if (!empty($_POST['project_type_id'])) {
            if (!is_numeric($_POST['project_type_id'])) {
                $this->echoJson($result_data , 3027 , $error_code_info[3027]);
            }
            $project_type_id = intval($_POST['project_type_id']);
            $where          .= " AND pt.id = {$project_type_id} ";
        }
        // 校验项目ID
        if (!empty($_POST['project_id'])) {
            if (!is_numeric($_POST['project_id'])) {
                $this->echoJson($result_data , 3028 , $error_code_info[3028]);
            }
            $project_id = intval($_POST['project_id']);
            $where     .= " AND p.id = {$project_id} ";
        }
        // 校验项目名称
        if (!empty($_POST['project_name'])) {
            $project_name = trim($_POST['project_name']);
            $where       .= " AND p.name = '{$project_name}' ";
        }
        // 校验真实姓名
        if (!empty($_POST['real_name'])) {
            $real_name = trim($_POST['real_name']);
            $where    .= " AND u.real_name = '{$real_name}' ";
        }
        // 校验排序方式
        if (!empty($_POST['order'])) {
            if (!is_numeric($_POST['order']) || !in_array($_POST['order'] , array(1 , 2 , 3 , 4 , 5 , 6))) {
                $this->echoJson($result_data , 3010 , $error_code_info[3010]);
            }
            if ($_POST['order'] == 1) {
                $where .= " ORDER BY d.addtime DESC ";
            } else if ($_POST['order'] == 2) {
                $where .= " ORDER BY d.addtime ASC ";
            } else if ($_POST['order'] == 3) {
                $where .= " ORDER BY d.discount DESC ";
            } else if ($_POST['order'] == 4) {
                $where .= " ORDER BY d.discount ASC ";
            } else if ($_POST['order'] == 5) {
                $where .= " ORDER BY d.amount DESC ";
            } else if ($_POST['order'] == 6) {
                $where .= " ORDER BY d.amount ASC ";
            }
        } else {
            $where .= " ORDER BY d.addtime DESC ";
        }
        // 校验每页显示数据量
        if (!empty($_POST['limit'])) {
            if (!is_numeric($_POST['limit']) || $_POST['limit'] < 1 || $_POST['limit'] > 100) {
                $this->echoJson($result_data , 3011 , $error_code_info[3011]);
            }
            $limit = intval($_POST['limit']);
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($_POST['page'])) {
            if (!is_numeric($_POST['page']) || $_POST['page'] < 1) {
                $this->echoJson($result_data , 3012 , $error_code_info[3012]);
            }
            $page = intval($_POST['page']);
        } else {
            $page = 1;
        }
        $sql = "SELECT
                count(d.id) AS count
                FROM ((((ag_debt AS d
                INNER JOIN ag_platform AS pf ON d.platform_id = pf.id)
                INNER JOIN ag_project_type AS pt ON d.project_type_id = pt.id)
                INNER JOIN ag_project AS p ON d.project_id = p.id)
                INNER JOIN ag_tender AS t ON d.tender_id = t.id)
                INNER JOIN ag_user AS u ON d.user_id = u.id
                WHERE {$where} ";
        $count = Yii::app()->agdb->createCommand($sql)->queryScalar();
        $page_count = ceil($count / $limit); // 总页数
        $pass       = ($page - 1) * $limit;  // 跳过数据量
        $sql = "SELECT
                d.id,
                d.amount,
                d.discount,
                d.serial_number,
                d.addtime,
                d.end_time,
                d.success_time,
                d.status,
                d.project_type_id,
                d.debt_src,
                pf.name AS platform_name,
                pt.type_name,
                p.name,
                p.apr,
                t.bond_no,
                u.real_name
                FROM ((((ag_debt AS d
                INNER JOIN ag_platform AS pf ON d.platform_id = pf.id)
                INNER JOIN ag_project_type AS pt ON d.project_type_id = pt.id)
                INNER JOIN ag_project AS p ON d.project_id = p.id)
                INNER JOIN ag_tender AS t ON d.tender_id = t.id)
                INNER JOIN ag_user AS u ON d.user_id = u.id
                WHERE {$where} LIMIT {$pass} , {$limit} ";
        $res  = Yii::app()->agdb->createCommand($sql)->queryAll();
        if (!$res) {
            $this->echoJson($result_data , 3029 , $error_code_info[3029]);
        }
        $time = time();
        $result_data['count']      = $count;
        $result_data['page_count'] = $page_count;
        foreach ($res as $key => $value) {
            $temp['debt_id']       = $value['id'];
            $temp['amount']        = $value['amount'];
            $temp['discount']      = $value['discount'] . '折';
            $temp['serial_number'] = $value['serial_number'];
            $temp['addtime_int']   = $value['addtime'];
            $temp['end_time_int']  = $value['end_time'];
            $temp['addtime']       = date('Y-m-d H:i:s' , $value['addtime']);
            $temp['end_time']      = date('Y-m-d H:i:s' , $value['end_time']);
            $temp['status']        = $value['status'];
            $temp['platform_name'] = $value['platform_name'];
            $temp['type']          = $value['project_type_id'];
            $temp['type_name']     = $value['type_name'];
            $temp['name']          = $value['name'];
            $temp['apr']           = $value['apr'] . '%';
            $temp['bond_no']       = $value['bond_no'];
            $temp['real_name']     = $value['real_name'];
            $temp['debt_src']      = $value['debt_src'];
            $temp['money']         = round($value['amount'] * ($value['discount'] / 10) , 2);
            if ($value['success_time'] != 0) {
                $temp['success_time'] = date('Y-m-d H:i:s' , $value['success_time']);
            } else {
                $temp['success_time'] = '';
            }
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
            $result_data['data'][] = $temp;
        }
        $this->echoJson($result_data , 0 , '查询成功');
    }

    /**
     * 我的债转 - 取消 张健
     * @param   debt_id     int     债转记录ID
     */
    public function actionCancel()
    {
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3000 , $error_code_info[3000]);
        }
        if (empty($_POST['platform_id'])) {
            $this->echoJson(array() , 3043 , $error_code_info[3043]);
        }
        if (!is_numeric($_POST['platform_id'])) {
            $this->echoJson(array() , 3044 , $error_code_info[3044]);
        }
        $user_info['platform_id'] = $_POST['platform_id'];
        $user_info['id']          = $this->user_id;
        if (!$user_info || !$user_info['id'] || !$user_info['platform_id']) {
            $this->echoJson(array() , 3007 , $error_code_info[3007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        // 校验债转记录ID
        if (empty($_POST['debt_id'])) {
            $this->echoJson(array() , 3030 , $error_code_info[3030]);
        }
        if (!is_numeric($_POST['debt_id'])) {
            $this->echoJson(array() , 3031 , $error_code_info[3031]);
        }
        $debt_id = intval($_POST['debt_id']);
        $status  = 3;
        $user_id = $user_info['id'];
        $itouzi  = Yii::app()->c->itouzi;
        // 校验是否为爱投资的平台ID
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {
            //验证资产花园用户是否绑定平台
            $transformationInfo = PurchService::getInstance()->getformationUserid($user_info['id'] , $user_info['platform_id']);
            if($transformationInfo['code'] != 0){
                $this->echoJson(array(), $transformationInfo['code'], Yii::app()->c->errorcodeinfo[$transformationInfo['code']]);
            }
            $user_info['platformUserId'] = $transformationInfo['data']['platform_user_id'];
            Yii::app()->yiidb->beginTransaction();
            $user_id = $user_info['platformUserId'];
            $result  = AgDebtitouziService::getInstance()->CancelDebt($debt_id, $status, $user_id);
            if ($result['code'] !== 0) {
                $this->echoJson(array() , $result['code'] , $error_code_info[$result['code']]);
                Yii::app()->yiidb->rollback();
            }
            Yii::app()->yiidb->commit();
            $this->echoJson(array() , 0 , '取消成功');
        }else{
            Yii::app()->agdb->beginTransaction();
            $result  = AgDebtService::getInstance()->CancelDebt($debt_id, $status, $user_id);
            if ($result['code'] !== 0) {
                $this->echoJson(array() , $result['code'] , $error_code_info[$result['code']]);
                Yii::app()->agdb->rollback();
            }
            Yii::app()->agdb->commit();
            $this->echoJson(array() , 0 , '取消成功');
        }
    }

    /**
     * 我的债转 - 重新发布 张健
     * @param   debt_id         int     债转记录ID
     * @param   payPassword     string  支付密码
     * @param   type            int     dw_borrow表中type或者itz_ag_debt_exchange中borrow_type（爱投资平台必填）
     */
    public function actionAgain()
    {
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3000 , $error_code_info[3000]);
        }
        if (empty($_POST['platform_id'])) {
            $this->echoJson(array() , 3043 , $error_code_info[3043]);
        }
        if (!is_numeric($_POST['platform_id'])) {
            $this->echoJson(array() , 3044 , $error_code_info[3044]);
        }
        $user_info['platform_id'] = $_POST['platform_id'];
        $user_info['id']          = $this->user_id;
        if (!$user_info || !$user_info['id'] || !$user_info['platform_id']) {
            $this->echoJson(array() , 3007 , $error_code_info[3007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        // 校验债转记录ID
        if (empty($_POST['debt_id'])) {
            $this->echoJson(array() , 3030 , $error_code_info[3030]);
        }
        if (!is_numeric($_POST['debt_id'])) {
            $this->echoJson(array() , 3031 , $error_code_info[3031]);
        }
        // if (empty($_POST['payPassword'])) {
        //     $this->echoJson(array() , 3020 , "请输入支付密码");
        // }
        // $agPassInfo      = AgDebtitouziService::getInstance()->checkAgPassWord($user_info['id'],$_POST['payPassword']);
        // if($agPassInfo['code'] != 0){
        //     $this->echoJson(array() , $agPassInfo['code'] , $error_code_info[$agPassInfo['code']]);
        // }
        $debt_id = intval($_POST['debt_id']);
        $itouzi  = Yii::app()->c->itouzi;
        // 校验是否为爱投资的平台ID
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {
            // 爱投资
            //验证资产花园用户是否绑定平台
            $transformationInfo = PurchService::getInstance()->getformationUserid($user_info['id'] , $user_info['platform_id']);
            if($transformationInfo['code'] != 0){
                $this->echoJson(array(), $transformationInfo['code'], Yii::app()->c->errorcodeinfo[$transformationInfo['code']]);
            }
            $user_info['platformUserId'] = $transformationInfo['data']['platform_user_id'];
            $sql = "SELECT * FROM itz_ag_debt_exchange WHERE id = {$debt_id} ";
            $res = Yii::app()->yiidb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 3032 , $error_code_info[3032]);
            }
            if ($res['user_id'] != $user_info['platformUserId']) {
                $this->echoJson(array() , 3033 , $error_code_info[3033]);
            }
            if ($res['platform_id'] != $user_info['platform_id']) {
                $this->echoJson(array() , 3034 , $error_code_info[3034]);
            }
            if ($res['status'] != 6) {
                $this->echoJson(array() , 3035 , $error_code_info[3035]);
            }
            Yii::app()->yiidb->beginTransaction();
            $data['debt_src']       = 2;
            $data['user_id']        = $user_info['id'];
            $data['tender_id']      = $res['tender_id'];
            $data['money']          = $res['debt_account'];
            $data['discount']       = $res['discount'];
            $data['effect_days']    = $res['effect_days'];
            $data['payPassword']    = $_POST['payPassword'];
            $data['user_id']        = $user_info['id'];
            $data['platformId']     = $res['platform_id'];
            $data['platformUserId'] = $user_info['platformUserId'];
            $result                 = AgDebtitouziService::getInstance()->createDebtExchange($data);
            $error_code_info        = Yii::app()->c->errorcodeinfo;
            if ($result['code'] !== 0) {
                $this->echoJson(array() , $result['code'] , $error_code_info[$result['code']]);
                Yii::app()->yiidb->rollback();
            }
            Yii::app()->yiidb->commit();
            $sql           = "SELECT debt_serial_number FROM itz_ag_debt_exchange WHERE id = {$result['data']['debt_id']} ";
            $serial_number = Yii::app()->yiidb->createCommand($sql)->queryScalar();
            $this->echoJson(array('serial_number' => $serial_number) , 0 , '发布成功');

        } else {

            // 其他平台
            $sql = "SELECT * FROM ag_debt WHERE id = {$debt_id} ";
            $res = Yii::app()->agdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 3032 , $error_code_info[3032]);
            }
            if ($res['user_id'] != $user_info['id']) {
                $this->echoJson(array() , 3033 , $error_code_info[3033]);
            }
            if ($res['platform_id'] != $user_info['platform_id']) {
                $this->echoJson(array() , 3034 , $error_code_info[3034]);
            }
            if ($res['status'] != 4) {
                $this->echoJson(array() , 3035 , $error_code_info[3035]);
            }
            Yii::app()->agdb->beginTransaction();
            $data['debt_src']    = 2;
            $data['user_id']     = $res['user_id'];
            $data['tender_id']   = $res['tender_id'];
            $data['money']       = $res['amount'];
            $data['discount']    = $res['discount'];
            $data['effect_days'] = round(($res['end_time'] - $res['start_time']) / 86400);
            $result              = AgDebtService::getInstance()->createDebt($data);
            if ($result['code'] !== 0) {
                $this->echoJson(array() , $result['code'] , $error_code_info[$result['code']]);
                Yii::app()->agdb->rollback();
            }
            Yii::app()->agdb->commit();
            $sql           = "SELECT serial_number FROM ag_debt WHERE id = {$result['data']['debt_id']} ";
            $serial_number = Yii::app()->agdb->createCommand($sql)->queryScalar();
            $this->echoJson(array('serial_number' => $serial_number) , 0 , '发布成功');
        }
    }

    /**
     * 我的债转 - 查看合同 张健
     * @param   debt_id     int     债转记录ID
     */
    public function actionContract()
    {
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3000 , $error_code_info[3000]);
        }
        if (empty($_POST['platform_id'])) {
            $this->echoJson(array() , 3043 , $error_code_info[3043]);
        }
        if (!is_numeric($_POST['platform_id'])) {
            $this->echoJson(array() , 3044 , $error_code_info[3044]);
        }
        $user_info['platform_id'] = $_POST['platform_id'];
        $user_info['id']          = $this->user_id;
        if (!$user_info || !$user_info['id'] || !$user_info['platform_id']) {
            $this->echoJson(array() , 3007 , $error_code_info[3007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        // 校验债转记录ID
        if (empty($_POST['debt_id'])) {
            $this->echoJson(array() , 3030 , $error_code_info[3030]);
        }
        if (!is_numeric($_POST['debt_id'])) {
            $this->echoJson(array() , 3031 , $error_code_info[3031]);
        }
        $debt_id = intval($_POST['debt_id']);
        // 校验是否为爱投资的平台ID
        $itouzi = Yii::app()->c->itouzi;
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {

            // 爱投资
            //验证资产花园用户是否绑定平台
            $transformationInfo = PurchService::getInstance()->getformationUserid($user_info['id'] , $user_info['platform_id']);
            if($transformationInfo['code'] != 0){
                $this->echoJson(array(), $transformationInfo['code'], Yii::app()->c->errorcodeinfo[$transformationInfo['code']]);
            }
            $user_info['platformUserId'] = $transformationInfo['data']['platform_user_id'];
            $sql = "SELECT * FROM itz_ag_debt_exchange WHERE id = {$debt_id} ";
            $res = Yii::app()->yiidb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 3032 , $error_code_info[3032]);
            }
            if ($res['user_id'] != $user_info['platformUserId']) {
                $this->echoJson(array() , 3033 , $error_code_info[3033]);
            }
            if ($res['status'] != 3) {
                $this->echoJson(array() , 3036 , $error_code_info[3036]);
            }
            $result = array(
                'bond_no'        => '',
                'c_download_url' => '',
                'c_viewpdf_url'  => ''
            );
            if (!$result) {
                $this->echoJson(array() , 3037 , $error_code_info[3037]);
            }
            $this->echoJson($result , 0 , '查询成功');

        } else {

            // 其他平台
            $sql = "SELECT * FROM ag_debt WHERE id = {$debt_id} ";
            $res = Yii::app()->agdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 3032 , $error_code_info[3032]);
            }
            if ($res['user_id'] != $user_info['id']) {
                $this->echoJson(array() , 3033 , $error_code_info[3033]);
            }
            if ($res['platform_id'] != $user_info['platform_id']) {
                $this->echoJson(array() , 3034 , $error_code_info[3034]);
            }
            if ($res['status'] != 2) {
                $this->echoJson(array() , 3036 , $error_code_info[3036]);
            }
            $sql    = "SELECT bond_no , c_download_url , c_viewpdf_url FROM ag_debt_tender WHERE debt_id = {$debt_id}";
            $result = Yii::app()->agdb->createCommand($sql)->queryRow();
            if (!$result) {
                $this->echoJson(array() , 3037 , $error_code_info[3037]);
            }
            $this->echoJson($result , 0 , '查询成功');
        }
    }

    /**
     * 全部债权 - 债权详情 张健
     * @param   debt_id     int     债转记录ID
     */
    public function actionDebtInfo()
    {
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3000 , $error_code_info[3000]);
        }
        if (empty($_POST['platform_id'])) {
            $this->echoJson(array() , 3043 , $error_code_info[3043]);
        }
        if (!is_numeric($_POST['platform_id'])) {
            $this->echoJson(array() , 3044 , $error_code_info[3044]);
        }
        $user_info['platform_id'] = $_POST['platform_id'];
        $user_info['id']          = $this->user_id;
        if (!$user_info || !$user_info['id'] || !$user_info['platform_id']) {
            $this->echoJson(array() , 3007 , $error_code_info[3007]);
        }
        // 校验债转记录ID
        if (empty($_POST['debt_id'])) {
            $this->echoJson(array() , 3030 , $error_code_info[3030]);
        }
        if (!is_numeric($_POST['debt_id'])) {
            $this->echoJson(array() , 3031 , $error_code_info[3031]);
        }
        $debt_id = intval($_POST['debt_id']);
        // 校验是否为爱投资的平台ID
        $itouzi = Yii::app()->c->itouzi;
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {

            // 爱投资
            $sql = "SELECT ade.id , ade.status , ade.debt_account , ade.discount , ade.create_debt_time , ade.effect_days , b.name , b.apr , b.repayment_time , b.style , gn.name AS guarantor FROM (itz_ag_debt_exchange AS ade INNER JOIN dw_borrow AS b ON ade.borrow_id = b.id) INNER JOIN dw_guarantor_new AS gn ON b.guarantors = gn.gid AND ade.id = {$debt_id} ";
            $res = Yii::app()->yiidb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 3032 , $error_code_info[3032]);
            }
            if ($res['status'] != 1) {
                $this->echoJson(array() , 3039 , $error_code_info[3039]);
            }
            $style[0] = '按日计息 按月付息 到期还本';
            $style[1] = '按日计息 到期还本息';
            $style[2] = '按日计息 月底付息 到期还本息';
            $style[3] = '按日计息 按季度付息 到期还本';
            $style[4] = '等额本金 按月付款';
            $style[5] = '等额本息 按月付款';

            $result['debt_id']          = $res['id'];
            $result['project_name']     = $res['name'];
            $result['apr']              = $res['apr'];
            $result['amount']           = $res['debt_account'];
            $result['discount']         = $res['discount'];
            $result['money']            = round($res['debt_account'] * ($res['discount'] / 10) , 2);
            $result['project_end_time'] = date('Y-m-d' , $res['repayment_time']);
            $result['style']            = $style[$res['style']];
            $result['guarantor']        = $res['guarantor'];

            $end_time = $res['create_debt_time'] + ($res['effect_days'] * 86400);
            $time     = time();
            $result['remaining_time'] = $end_time - $time;

            $this->echoJson($result , 0 , '查询成功');

        } else {

            // 其他平台
            $sql = "SELECT d.id , d.platform_id , d.status , d.amount , d.discount , d.end_time , p.name , p.apr, p.due_date , p.style FROM ag_debt AS d INNER JOIN ag_project AS p ON d.project_id = p.id AND d.id = {$debt_id} ";
            $res = Yii::app()->agdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 3032 , $error_code_info[3032]);
            }
            if ($res['platform_id'] != $user_info['platform_id']) {
                $this->echoJson(array() , 3034 , $error_code_info[3034]);
            }
            if ($res['status'] != 1) {
                $this->echoJson(array() , 3039 , $error_code_info[3039]);
            }
            $style[0] = '按日计息 按月付息 到期还本';
            $style[1] = '按日计息 到期还本息';
            $style[2] = '按日计息 月底付息 到期还本息';
            $style[3] = '按日计息 按季度付息 到期还本';
            $style[4] = '等额本金 按月付款';
            $style[5] = '等额本息 按月付款';

            $result['debt_id']          = $res['id'];
            $result['project_name']     = $res['name'];
            $result['apr']              = $res['apr'];
            $result['amount']           = $res['amount'];
            $result['discount']         = $res['discount'];
            $result['money']            = round($res['amount'] * ($res['discount'] / 10) , 2);
            $result['project_end_time'] = date('Y-m-d' , $res['due_date']);
            $result['style']            = $style[$res['style']];
            $result['guarantor']        = '';
            $time = time();
            $result['remaining_time'] = $res['end_time'] - $time;

            $this->echoJson($result , 0 , '查询成功');
        }
    }

    /**
     * 我的 上传的债转 列表
     */
    public function actionDebtDeclareRecord()
    {
        $error_code_info = Yii::app()->c->errorcodeinfo;
        $result_data = array('count' => 0 , 'page_count' => 0 ,'data' => array());
        if (empty($_POST)) {
            $this->echoJson($result_data , 3000 , $error_code_info[3000]);
        }
        if (empty($_POST['type'])) {
            $this->echoJson($result_data , 3055 , $error_code_info[3055]);
        }
        if (!is_numeric($_POST['type']) || !in_array($_POST['type'] , array(1 , 2))) {
            $this->echoJson($result_data , 3056 , $error_code_info[3056]);
        }
        $where = '';
        if ($_POST['type'] == 1) {
            // 校验用户信息
            $user_info['id'] = $this->user_id;
            if (!$user_info || !$user_info['id']) {
                $this->echoJson($result_data , 3007 , $error_code_info[3007]);
            }
            $where .= " AND ddr.user_id = {$user_info['id']} ";
        } else if ($_POST['type'] == 2) {
            if (!empty($_POST['platform_id'])) {
                if (!is_numeric($_POST['platform_id'])) {
                    $this->echoJson($result_data , 3044 , $error_code_info[3044]);
                }
                $p_id   = intval($_POST['platform_id']);
                $where .= " AND p.id = {$p_id} ";
            }
            if (!empty($_POST['user_info'])) {
                $user_info = trim($_POST['user_info']);
                $where    .= " AND (ddr.name = '{$user_info}' or u.phone = '{$user_info}') ";
            }
            if (!empty($_POST['intention'])) {
                if (!is_numeric($_POST['intention']) || !in_array($_POST['intention'] , array(1 , 2 , 3))) {
                    $this->echoJson($result_data , 3049 , $error_code_info[3049]);
                }
                $intention = intval($_POST['intention']);
                $where    .= " AND ddr.debt_intention = {$intention} ";
            }
        }
        // 校验每页显示数据量
        if (!empty($_POST['limit'])) {
            if (!is_numeric($_POST['limit']) || $_POST['limit'] < 1 || $_POST['limit'] > 100) {
                $this->echoJson($result_data , 3011 , $error_code_info[3011]);
            }
            $limit = intval($_POST['limit']);
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($_POST['page'])) {
            if (!is_numeric($_POST['page']) || $_POST['page'] < 1) {
                $this->echoJson($result_data , 3012 , $error_code_info[3012]);
            }
            $page = intval($_POST['page']);
        } else {
            $page = 1;
        }
        $sql = "SELECT count(ddr.id) 
                FROM (ag_debt_declare_record AS ddr INNER JOIN ag_user AS u ON ddr.user_id = u.id)
                INNER JOIN ag_platform AS p ON ddr.platform_id = p.id {$where} ";
        $count = Yii::app()->agdb->createCommand($sql)->queryScalar();
        if ($_POST['type'] == 1) {
            if (!$count) {
                $this->echoJson($result_data , 3057 , $error_code_info[3057]);
            }
        } else if ($_POST['type'] == 2) {
            if (!$count) {
                $this->echoJson($result_data , 3058 , $error_code_info[3058]);
            }
        }
        $page_count = ceil($count / $limit); // 总页数
        $pass       = ($page - 1) * $limit;  // 跳过数据量
        $sql = "SELECT ddr.id , ddr.name , ddr.money , ddr.debt_number , ddr.debt_intention AS intention , ddr.debt_attestation , ddr.addtime , p.name AS platform_name , u.phone , ddr.id_type , ddr.id_no
                FROM (ag_debt_declare_record AS ddr INNER JOIN ag_user AS u ON ddr.user_id = u.id)
                INNER JOIN ag_platform AS p ON ddr.platform_id = p.id {$where} ORDER BY ddr.id DESC LIMIT {$pass} , {$limit} ";
        $result = Yii::app()->agdb->createCommand($sql)->queryAll();
        if (!$result) {
            $this->echoJson($result_data , 3058 , $error_code_info[3058]);
        }
        $debt_intention[1] = "债权转让";
        $debt_intention[2] = "债权购物";
        $debt_intention[3] = "债权再投资";

        $type[1] = '身份证';
        $type[2] = '军官证';
        $type[3] = '港澳台通行证';
        $type[4] = '护照';
        $type[5] = '营业执照';
        $type[6] = '外国人永久居留证';

        $url = Yii::app()->c->oss_preview_address;
        foreach ($result as $key => $value) {
            $result[$key]['intention'] = $debt_intention[$value['intention']];
            $result[$key]['debt']      = explode(',' , $value['debt_attestation']);
            foreach ($result[$key]['debt'] as $k => $v) {
                $result[$key]['debt'][$k] = $url.DIRECTORY_SEPARATOR.$v;
            }
            $result[$key]['addtime']   = date('Y-m-d H:i:s' , $value['addtime']);
            $result[$key]['id_type']   = $type[$value['id_type']];

            unset($result[$key]['debt_attestation']);
        }
        $result_data['count']      = $count;
        $result_data['page_count'] = $page_count;
        $result_data['data']       = $result;
        $this->echoJson($result_data , 0 , "查询成功");
    }

    /**
     * 我的 上传的债转 详情
     */
    public function actionDebtDeclareRecordInfo()
    {
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson(array() , 3000 , $error_code_info[3000]);
        }
        if (empty($_POST['type'])) {
            $this->echoJson(array() , 3055 , $error_code_info[3055]);
        }
        if (!is_numeric($_POST['type']) || !in_array($_POST['type'] , array(1 , 2))) {
            $this->echoJson(array() , 3056 , $error_code_info[3056]);
        }
        if (empty($_POST['id'])) {
            $this->echoJson(array() , 3059 , $error_code_info[3059]);
        }
        if (!is_numeric($_POST['id'])) {
            $this->echoJson(array() , 3060 , $error_code_info[3060]);
        }
        $id    = intval($_POST['id']);
        $where = " AND ddr.id = {$id} ";
        if ($_POST['type'] == 1) {
            // 校验用户信息
            $user_info['id'] = $this->user_id;
            if (!$user_info || !$user_info['id']) {
                $this->echoJson(array() , 3007 , $error_code_info[3007]);
            }
            $where .= " AND ddr.user_id = {$user_info['id']} ";
        }
        $sql = "SELECT ddr.id , ddr.name , ddr.money , ddr.debt_number , ddr.debt_intention AS intention , ddr.debt_attestation , ddr.addtime , p.name AS platform_name , u.name AS user_name , u.phone , ddr.id_type , ddr.id_no
                FROM (ag_debt_declare_record AS ddr INNER JOIN ag_user AS u ON ddr.user_id = u.id)
                INNER JOIN ag_platform AS p ON ddr.platform_id = p.id {$where} ";
        $result = Yii::app()->agdb->createCommand($sql)->queryRow();
        if (!$result) {
            $this->echoJson(array() , 3061 , $error_code_info[3061]);
        }
        $debt_intention[1] = "债权转让";
        $debt_intention[2] = "债权购物";
        $debt_intention[3] = "债权再投资";

        $type[1] = '身份证';
        $type[2] = '军官证';
        $type[3] = '港澳台通行证';
        $type[4] = '护照';
        $type[5] = '营业执照';
        $type[6] = '外国人永久居留证';

        $url = Yii::app()->c->oss_preview_address;
        $result['intention'] = $debt_intention[$result['intention']];
        $result['debt']      = explode(',' , $result['debt_attestation']);
        foreach ($result['debt'] as $k => $v) {
            $result['debt'][$k] = $url.DIRECTORY_SEPARATOR.$v;
        }
        $result['addtime']   = date('Y-m-d H:i:s' , $result['addtime']);
        $result['id_type']   = $type[$result['id_type']];

        unset($result['debt_attestation']);

        $this->echoJson($result , 0 , "查询成功");
    }
}
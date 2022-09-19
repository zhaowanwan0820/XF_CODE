<?php
class IndexController extends CommonController
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
     * 最近N天债转平均折扣走势数据 张健
     * @param   days    int     查询最近N天债转平均折扣走势数据(正整数)
     * @return  json
     */
    public function actionIndex()
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
        if (empty($_POST['days'])) {
            $this->echoJson(array() , 3001 , $error_code_info[3001]);
        }
        if (!is_numeric($_POST['days']) || $_POST['days'] < 1) {
            $this->echoJson(array() , 3002 , $error_code_info[3002]);
        }
        $days       = intval($_POST['days']);
        $days_time  = $days * 86400;
        $today_time = strtotime(date('Y-m-d' , time()));
        $start_time = $today_time - $days_time;
        $end_time   = $today_time - 1;
        // 校验是否为的平台ID
        $itouzi = Yii::app()->c->itouzi;
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {
            $sql  = "SELECT discount,successtime AS addtime FROM itz_ag_debt_exchange WHERE status = 3 AND successtime >= {$start_time} AND successtime <= {$end_time} ";
            $data = Yii::app()->yiidb->createCommand($sql)->queryAll();

        } else {

            // 其他平台
            $sql         = "SELECT id FROM ag_debt WHERE platform_id = {$user_info['platform_id']} AND status = 2";
            $debt_id_arr = Yii::app()->agdb->createCommand($sql)->queryColumn();
            if ($debt_id_arr) {
                $debt_id_str = implode(',' , $debt_id_arr);
            } else {
                $debt_id_str = -1;
            }
            $sql  = "SELECT discount,addtime FROM ag_debt_tender WHERE addtime >= {$start_time} AND addtime <= {$end_time} AND debt_id IN ($debt_id_str)";
            $data = Yii::app()->agdb->createCommand($sql)->queryAll();
        }
        if (!$data) {
            $this->echoJson(array() , 3003 , $error_code_info[3003]);
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
        if (empty($_POST['limit'])) {
            $this->echoJson(array() , 3004 , $error_code_info[3004]);
        }
        if (!is_numeric($_POST['limit']) || $_POST['limit'] < 1) {
            $this->echoJson(array() , 3005 , $error_code_info[3005]);
        }
        $limit  = intval($_POST['limit']);
        // 校验是否为的平台ID
        $itouzi = Yii::app()->c->itouzi;
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {
            $sql = "SELECT discount FROM itz_ag_debt_exchange WHERE status = 3 ORDER BY id DESC LIMIT 0,{$limit} ";
            $res = Yii::app()->yiidb->createCommand($sql)->queryAll();

        } else {

            // 其他平台
            $sql         = "SELECT id FROM ag_debt WHERE platform_id = {$user_info['platform_id']} AND status = 2";
            $debt_id_arr = Yii::app()->agdb->createCommand($sql)->queryColumn();
            if ($debt_id_arr) {
                $debt_id_str = implode(',' , $debt_id_arr);
            } else {
                $debt_id_str = -1;
            }
            $sql = "SELECT discount FROM ag_debt_tender WHERE debt_id IN ($debt_id_str) ORDER BY id DESC LIMIT 0,{$limit} ";
            $res = Yii::app()->agdb->createCommand($sql)->queryAll();
        }
        if (!$res) {
            $this->echoJson(array() , 3006 , $error_code_info[3006]);
        }
        $result = array();
        for ($i = (count($res) - 1); $i >= 0; $i--) {
            $result[] = $res[$i];
        }
        $this->echoJson($result , 0 , '查询成功');
    }

    /**
     * 发布债转 - 项目类型 张健
     * @return  json
     */
    public function actionProjectType()
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
        // 校验是否为的平台ID
        $itouzi = Yii::app()->c->itouzi;
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {
            $data   = $itouzi['itouzi']['type'];
            $result = array();
            foreach ($data as $key => $value) {
                $temp              = array();
                $temp['id']        = $key;
                $temp['type_name'] = $value;

                $result[] = $temp;
            }

        } else {

            // 其他平台
            $sql    = "SELECT id,type_name FROM ag_project_type WHERE platform_id = {$user_info['platform_id']} ";
            $result = Yii::app()->agdb->createCommand($sql)->queryAll();
        }
        if (!$result) {
            $this->echoJson(array() , 3008 , $error_code_info[3008]);
        }
        $this->echoJson($result , 0 , '查询成功');
    }

    /**
     * 发布债转 - 项目列表 张健
     * @param   project_type_id     int     项目类型ID(默认为空)
     * @param   order               int     排序方式(默认为1)：1-投资时间降序，2-投资时间升序
     * @param   limit               int     每页显示数据量(默认为10,取值范围1至100的正整数)
     * @param   page                int     当前页数(默认为1,正整数)
     * @return  json
     */
    public function actionProjectList()
    {
        $result_data     = array('count' => 0 , 'page_count' => 0 ,'data' => array());
        $error_code_info = Yii::app()->c->errorcodeinfo;
        if (empty($_POST)) {
            $this->echoJson($result_data , 3000 , $error_code_info[3000]);
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
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        // 校验是否为的平台ID
        $itouzi = Yii::app()->c->itouzi;
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {
            // 校验项目类型ID
            if (!empty($_POST['project_type_id'])) {
                if (!is_numeric($_POST['project_type_id'])) {
                    $this->echoJson($result_data , 3009 , $error_code_info[3009]);
                }
                $project_type_id = intval($_POST['project_type_id']);
            } else {
                $project_type_id = 0;
            }
            //验证用户是否绑定平台
            $transformationInfo = PurchService::getInstance()->getformationUserid($user_info['id'] , $user_info['platform_id']);
            if($transformationInfo['code'] != 0){
                $this->echoJson(array(), $transformationInfo['code'], Yii::app()->c->errorcodeinfo[$transformationInfo['code']]);
            }
            $user_info['platformUserId'] = $transformationInfo['data']['platform_user_id'];
            $data['platformUserId']      = $user_info['platformUserId'];
            $data['type']                = $project_type_id;
            $data['order']               = $_POST['order'];
            $data['limit']               = $_POST['limit'];
            $data['page']                = $_POST['page'];
            $result                      = ItouziService::getInstance()->ProjectList($data);
            $result_data['count']        = $result['count'];
            $result_data['page_count']   = $result['page_count'];
            $result_data['data']         = $result['data'];
            $this->echoJson($result_data , $result['code'] , $error_code_info[$result['code']]);

        } else {

            // 其他平台
            $where = '';
            // 校验项目类型ID
            if (!empty($_POST['project_type_id'])) {
                if (!is_numeric($_POST['project_type_id'])) {
                    $this->echoJson($result_data , 3009 , $error_code_info[3009]);
                }
                $project_type_id = intval($_POST['project_type_id']);
                $where          .= " AND pt.id = {$project_type_id} ";
            }
            // 校验排序方式
            if (!empty($_POST['order'])) {
                if (!is_numeric($_POST['order']) || !in_array($_POST['order'] , array(1 , 2))) {
                    $this->echoJson($result_data , 3010 , $error_code_info[3010]);
                }
                if ($_POST['order'] == 1) {
                    $where .= " ORDER BY t.debt_status ASC , t.addtime DESC ";
                } else if ($_POST['order'] == 2) {
                    $where .= " ORDER BY t.debt_status ASC , t.addtime ASC ";
                }
            } else {
                $where .= " ORDER BY t.debt_status ASC , t.addtime DESC ";
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
            $sql = "SELECT count(t.id) AS count
                    FROM (ag_tender AS t INNER JOIN ag_project AS p ON t.project_id = p.id) INNER JOIN ag_project_type AS pt ON p.type_id = pt.id
                    WHERE t.user_id = {$user_info['id']} AND t.debt_status IN (0 , 1) AND t.is_debt_confirm = 1 AND t.platform_id = {$user_info['platform_id']} {$where} ";
            $count = Yii::app()->agdb->createCommand($sql)->queryScalar();
            $page_count = ceil($count / $limit); // 总页数
            $pass       = ($page - 1) * $limit;  // 跳过数据量
            $sql = "SELECT t.id , pt.type_name , p.name , t.bond_no , p.apr , t.wait_capital , t.debt_status
                    FROM (ag_tender AS t INNER JOIN ag_project AS p ON t.project_id = p.id) INNER JOIN ag_project_type AS pt ON p.type_id = pt.id
                    WHERE t.user_id = {$user_info['id']} AND t.debt_status IN (0 , 1) AND t.is_debt_confirm = 1 AND t.platform_id = {$user_info['platform_id']} {$where} LIMIT {$pass} , {$limit} ";
            $result = Yii::app()->agdb->createCommand($sql)->queryAll();
            if (!$result) {
                $this->echoJson($result_data , 3013 , $error_code_info[3013]);
            }
            $result_data['count']      = $count;
            $result_data['page_count'] = $page_count;
            $result_data['data']       = $result;
            $this->echoJson($result_data , 0 , '查询成功');
        }
    }

    /**
     * 发布债转 - 项目详情 张健
     * @param   tender_id   int     投资记录ID
     * @return  json
     */
    public function actionProjectInfo()
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
        if (empty($_POST['tender_id'])) {
            $this->echoJson(array() , 3014 , $error_code_info[3014]);
        }
        if (!is_numeric($_POST['tender_id'])) {
            $this->echoJson(array() , 3015 , $error_code_info[3015]);
        }
        $tender_id = intval($_POST['tender_id']);
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        // 校验是否为的平台ID
        $itouzi    = Yii::app()->c->itouzi;
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {
            //验证用户是否绑定平台
            $transformationInfo = PurchService::getInstance()->getformationUserid($user_info['id'] , $user_info['platform_id']);
            if($transformationInfo['code'] != 0){
                $this->echoJson(array(), $transformationInfo['code'], Yii::app()->c->errorcodeinfo[$transformationInfo['code']]);
            }
            $user_info['platformUserId'] = $transformationInfo['data']['platform_user_id'];
            $sql = "SELECT t.id , t.user_id , b.type , b.name , b.apr , t.wait_account , t.wait_interest , t.debt_status , t.addtime , t.borrow_id
                    FROM dw_borrow_tender AS t INNER JOIN dw_borrow AS b ON t.borrow_id = b.id
                    WHERE t.status = 1 AND t.id = {$tender_id} ";
            $result = Yii::app()->yiidb->createCommand($sql)->queryRow();
            if (!$result) {
                $this->echoJson(array() , 3016 , $error_code_info[3016]);
            }
            if ($result['user_id'] != $user_info['platformUserId']) {
                $this->echoJson(array() , 3017 , $error_code_info[3017]);
            }
            if ($result['debt_status'] != 0 && $result['debt_status'] != 14) {
                $this->echoJson(array() , 3018 , $error_code_info[3018]);
            }
            $type                   = $itouzi['itouzi']['type'];
            $result['type_name']    = $type[$result['type']];
            $result['wait_capital'] = bcsub($result['wait_account'] , $result['wait_interest'] , 2);
            $result['bond_no']      = implode('-', array(
                                            date('Ymd', $result['addtime']),
                                            $result['type'],
                                            $result['borrow_id'],
                                            $result['id']
                                        )
                                    );
            unset($result['user_id']);
            unset($result['wait_account']);
            unset($result['wait_interest']);
            unset($result['addtime']);
            unset($result['borrow_id']);
            $this->echoJson($result , 0 , '查询成功');

        } else {

            // 其他平台
            $sql = "SELECT t.id , t.user_id , pt.type_name , p.name , t.bond_no , p.apr , t.wait_capital , t.debt_status , p.type_id AS type
                    FROM (ag_tender AS t INNER JOIN ag_project AS p ON t.project_id = p.id) INNER JOIN ag_project_type AS pt ON p.type_id = pt.id
                    WHERE t.id = {$tender_id} ";
            $result = Yii::app()->agdb->createCommand($sql)->queryRow();
            if (!$result) {
                $this->echoJson(array() , 3016 , $error_code_info[3016]);
            }
            if ($result['user_id'] != $user_info['id']) {
                $this->echoJson(array() , 3017 , $error_code_info[3017]);
            }
            if ($result['debt_status'] != 0) {
                $this->echoJson(array() , 3018 , $error_code_info[3018]);
            }
            unset($result['user_id']);
            $this->echoJson($result , 0 , '查询成功');
        }
    }

    /**
     * 发布债转 - 提交发布 张健
     * @param   tender_id       int         投资记录ID
     * @param   money           float       转让金额
     * @param   discount        int         转让折扣(取值范围0.01至10)
     * @param   effect_days     int         有效期(10，20，30)
     * @param   type            int         dw_borrow表中type或者itz_ag_debt_exchange中borrow_type
     * @param   payPassword     string      支付密码
     * @return  json
     */
    public function actionProjectTransfer()
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
        if (empty($_POST['tender_id'])) {
            $this->echoJson(array() , 3014 , $error_code_info[3014]);
        }
        if (!is_numeric($_POST['tender_id'])) {
            $this->echoJson(array() , 3015 , $error_code_info[3015]);
        }
        if (empty($_POST['money'])) {
            $this->echoJson(array() , 3016 , "请输入转让金额");
        }
        if (empty($_POST['discount'])) {
            $this->echoJson(array() , 3018 , "请输入转让折扣");
        }
        if (!is_numeric($_POST['discount']) || $_POST['discount'] < 0.01 || $_POST['discount'] > 10) {
            $this->echoJson(array() , 3019 , "转让折扣输入错误");
        }
        if (empty($_POST['effect_days'])) {
            $this->echoJson(array() , 3020 , "请输入有效期");
        }
        if (!is_numeric($_POST['effect_days']) || !in_array($_POST['effect_days'] , array(10 , 20 , 30))) {
            $this->echoJson(array() , 3021 , "有效期输入错误");
        }
        if (empty($_POST['payPassword'])) {
            $this->echoJson(array() , 3020 , "请输入支付密码");
        }
        $agPassInfo = AgDebtitouziService::getInstance()->checkAgPassWord($user_info['id'],$_POST['payPassword']);
        if($agPassInfo['code'] != 0){
            $this->echoJson(array() , $agPassInfo['code'] , $error_code_info[$agPassInfo['code']]);
        }
        // 校验是否为的平台ID
        $itouzi = Yii::app()->c->itouzi;
        if ($user_info['platform_id'] == $itouzi['itouzi']['platform_id']) {
            //验证用户是否绑定平台
            $transformationInfo = PurchService::getInstance()->getformationUserid($user_info['id'] , $user_info['platform_id']);
            if($transformationInfo['code'] != 0){
                $this->echoJson(array(), $transformationInfo['code'], Yii::app()->c->errorcodeinfo[$transformationInfo['code']]);
            }
            $user_info['platformUserId'] = $transformationInfo['data']['platform_user_id'];
            Yii::app()->yiidb->beginTransaction();
            $data['debt_src']       = 2;
            $data['user_id']        = $user_info['id'];
            $data['tender_id']      = $_POST['tender_id'];
            $data['money']          = $_POST['money'];
            $data['discount']       = $_POST['discount'];
            $data['effect_days']    = $_POST['effect_days'];
            $data['payPassword']    = $_POST['payPassword'];
            $data['user_id']        = $user_info['id'];
            $data['platformId']     = $user_info['platform_id'];
            $data['platformUserId'] = $user_info['platformUserId'];

            $result = AgDebtitouziService::getInstance()->createDebtExchange($data);
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
            Yii::app()->agdb->beginTransaction();
            $data['debt_src']    = 2;
            $data['user_id']     = $user_info['id'];
            $data['tender_id']   = $_POST['tender_id'];
            $data['money']       = $_POST['money'];
            $data['discount']    = $_POST['discount'];
            $data['effect_days'] = $_POST['effect_days'];
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
     * 获取所有平台信息 张健
     */
    public function actionGetAllPlatform()
    {
        $error_code_info = Yii::app()->c->errorcodeinfo;
        $sql  = "SELECT id , name , platform_icon FROM ag_platform WHERE status = 1 AND type = 1";
        $data = Yii::app()->agdb->createCommand($sql)->queryAll();
        if (!$data) {
            $this->echoJson(array() , 3042 , $error_code_info[3042]);
        }
        $this->echoJson($data , 0 , '查询成功');
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
            $dir_name    = 'debt_declare_record';
            $dir_address = "uploads/" . $dir_name . '/';
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
     * 债转市场 - 上传债权 张健
     */
    public function actionAddDebtDeclareRecord()
    {
        $error_code_info = Yii::app()->c->errorcodeinfo;
        // 校验用户信息
        $user_info['id'] = $this->user_id;
        if (!$user_info || !$user_info['id']) {
            $this->echoJson(array() , 3007 , $error_code_info[3007]);
        }
        if (empty($_POST)) {
            $this->echoJson(array() , 3000 , $error_code_info[3000]);
        }
        if (empty($_POST['platform_id'])) {
            $this->echoJson(array() , 3043 , $error_code_info[3043]);
        }
        if (!is_numeric($_POST['platform_id'])) {
            $this->echoJson(array() , 3044 , $error_code_info[3044]);
        }
        if (empty($_POST['name'])) {
            $this->echoJson(array() , 3045 , $error_code_info[3045]);
        }
        if (empty($_POST['money'])) {
            $this->echoJson(array() , 3046 , $error_code_info[3046]);
        }
        if (!is_numeric($_POST['money']) || $_POST['money'] <= 0) {
            $this->echoJson(array() , 3047 , $error_code_info[3047]);
        }
        if (empty($_POST['intention'])) {
            $this->echoJson(array() , 3048 , $error_code_info[3048]);
        }
        if (!in_array($_POST['intention'] , array(1 , 2 , 3))) {
            $this->echoJson(array() , 3049 , $error_code_info[3049]);
        }
        if (empty($_POST['debt'])) {
            $this->echoJson(array() , 3050 , $error_code_info[3050]);
        }
        if (empty($_POST['id_type'])) {
            $this->echoJson(array() , 3062 , $error_code_info[3062]);
        }
        if (!is_numeric($_POST['id_type']) || !in_array($_POST['id_type'] , array(1 , 2 , 3 , 4 , 5 , 6))) {
            $this->echoJson(array() , 3063 , $error_code_info[3063]);
        }
        if (empty($_POST['id_no'])) {
            $this->echoJson(array() , 3064 , $error_code_info[3064]);
        }
        $id_no = trim($_POST['id_no']);
        if (!FunctionUtil::isIdCard($id_no) && $_POST['id_type'] == 1) {
            $this->echoJson(array() , 3065 , $error_code_info[3065]);
        }
        foreach ($_POST['debt'] as $key => $value) {
            $debt = $this->upload_base64($value);
            if (!$debt) {
                $this->echoJson(array() , 3052 , '第'.($key+1).'张债权凭证上传失败');
            }
            $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR . $debt;
            $re       = $this->upload($debt, $saveName);
            if ($re === false) {
                $this->echoJson(array() , 3052 , '第'.($key+1).'张债权凭证上传OSS失败');
            }else{
                $debt_attestation[] = $saveName;
            }
        }

        $platform_id          = intval($_POST['platform_id']);
        $name                 = trim($_POST['name']);
        $money                = $_POST['money'];
        $debt_number          = FunctionUtil::getAgRequestNo();
        $debt_intention       = intval($_POST['intention']);
        $debt_attestation_str = implode(',' , $debt_attestation);
        $addtime              = time();
        $addip                = Yii::app()->request->userHostAddress;
        $id_type              = intval($_POST['id_type']);

        $sql = "INSERT INTO ag_debt_declare_record (user_id , platform_id , name , money , debt_number , debt_intention , debt_attestation , addtime , addip , id_type , id_no) 
                VALUES({$user_info['id']} , {$platform_id} , '{$name}' , {$money} , '{$debt_number}' , {$debt_intention} , '{$debt_attestation_str}' , {$addtime} , '{$addip}' , {$id_type} , '{$id_no}') ";
        $result = Yii::app()->agdb->createCommand($sql)->execute();
        if (!$result) {
            $this->echoJson(array() , 3054 , $error_code_info[3054]);
        }
        $this->echoJson(array() , 0 , '添加成功');
    }

    /**
     * 文件上传
     * @param $file
     * @param $key
     * @return bool
     */
    private function upload($file, $key)
    {
        Yii::log(basename($file).'文件正在上传!', CLogger::LEVEL_INFO);
        try {
            ini_set('memory_limit', '2048M');
            $re = Yii::app()->oss->bigFileUpload($file, $key);
            unlink($file);
            return $re;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }
}
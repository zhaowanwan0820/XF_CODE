<?php
use iauth\models\AuthAssignment;
class JYSDebtController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success' , 'Error' , 'addLoanListCondition' , 'addDealLoadBYDealCondition' , 'addDealLoadBYUserCondition' , 'addDebtListCondition'
        );
    }

    /**
     * 成功提示页
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionSuccess($msg = '成功' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    /**
     * 失败提示页
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionError($msg = '失败' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }

    /**
     * 交易所 在途出借人明细 列表
     */
    public function actionDealLoadBYUser()
    {
        if (!empty($_POST)) {
            set_time_limit(0);
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 5) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入所属平台';
                echo exit(json_encode($result_data));
            }
            $platform_id = intval($_POST['deal_type']);
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_user_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    if ($condition['true_count'] > 500) {
                        header ( "Content-type:application/json; charset=utf-8" );
                        $result_data['data']  = array();
                        $result_data['count'] = 0;
                        $result_data['code']  = 1;
                        $result_data['info']  = '批量条件超过500行，暂不支持列表查询，请使用导出功能！';
                        echo exit(json_encode($result_data));
                    }
                    $con_data = json_decode($condition['data_json'] , true);
                    $platform_id = $condition['platform'];
                }
            }
            $where = " WHERE deal_load.status IN (1, 3) AND deal_load.platform_id = {$platform_id} ";
            $model = Yii::app()->offlinedb;
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND deal_load.user_id = '{$user_id}' ";
            }
            //检验用户手机号
            if (!empty($_POST['user_mobile'])) {
                $user_mobile = trim($_POST['user_mobile']);
                $user_mobile = GibberishAESUtil::enc($user_mobile, Yii::app()->c->idno_key);
                $sql = "SELECT user_id FROM offline_user_platform WHERE phone = '{$user_mobile}' ";
                $u_id = $model->createCommand($sql)->queryScalar();
                if ($u_id) {
                    $where .= " AND deal_load.user_id = '{$u_id}' ";
                } else {
                    $where .= " AND deal_load.user_id = -1 ";
                }
            }
            // 校验用户证件号
            if (!empty($_POST['user_idno'])) {
                $user_idno = trim($_POST['user_idno']);
                $user_idno = GibberishAESUtil::enc($user_idno, Yii::app()->c->idno_key);
                $sql = "SELECT user_id FROM offline_user_platform WHERE idno = '{$user_idno}' ";
                $user_id = $model->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND deal_load.user_id = '{$user_id}' ";
                } else {
                    $where .= " AND deal_load.user_id = -1 ";
                }
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
            if ($condition) {
                if ($con_data) {
                    $count    = 0;
                    $con_page = ceil(count($con_data) / 1000);
                    for ($i = 0; $i < $con_page; $i++) {
                        $con_data_arr = array();
                        for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                            if (!empty($con_data[$j])) {
                                $con_data_arr[] = $con_data[$j];
                            }
                        }
                        $con_data_str = implode(',' , $con_data_arr);
                        $where_con    = $where." AND deal_load.user_id IN ({$con_data_str}) ";
                        $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM offline_deal_load AS deal_load {$where_con} ";
                        $count_con = $model->createCommand($sql)->queryScalar();
                        $count += $count_con;
                    }
                    $con_data_str = implode(',' , $con_data);
                    $where .= " AND deal_load.user_id IN ({$con_data_str}) ";
                } else {
                    $where .= " AND deal_load.user_id = -1 ";
                }
            } else {
                $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM offline_deal_load AS deal_load {$where} ";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT deal_load.user_id AS id , SUM(deal_load.wait_capital) AS wait_capital , SUM(deal_load.wait_interest) AS wait_interest , user.money , user.lock_money 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_user_platform AS user ON deal_load.user_id = user.user_id 
                    {$where} GROUP BY deal_load.user_id ORDER BY deal_load.user_id DESC ";
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
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            
            $sex[0] = '女';
            $sex[1] = '男';
            $user_id_arr = array();
            foreach ($list as $key => $value) {
                $user_id_arr[] = $value['id'];
            }
            $user_id_str = implode(',' , $user_id_arr);
            $sql = "SELECT id , real_name , mobile , idno , sex , byear FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
            $user_res = Yii::app()->fdb->createCommand($sql)->queryAll();
            $user_info = array();
            foreach ($user_res as $key => $value) {
                $user_info[$value['id']] = $value;
            }
            $sql = "SELECT user_id , SUM(yes_interest) AS yes_interest FROM offline_deal_load WHERE user_id IN ({$user_id_str}) GROUP BY user_id ";
            $interest_res  = $model->createCommand($sql)->queryAll();
            $interest_data = array();
            if ($interest_res) {
                foreach ($interest_res as $key => $value) {
                    $interest_data[$value['user_id']] = $value['yes_interest'];
                }
            }
            foreach ($list as $key => $value) {
                $value['real_name'] = $user_info[$value['id']]['real_name'];
                $value['mobile']    = GibberishAESUtil::dec($user_info[$value['id']]['mobile'], Yii::app()->c->idno_key);
                $value['mobile_a']  = substr($value['mobile'] , 0 , 7);
                // $value['mobile_b']  = $this->strEncrypt($value['mobile'] , 3 , 4);
                $value['idno']      = GibberishAESUtil::dec($user_info[$value['id']]['idno'], Yii::app()->c->idno_key);
                // $value['idno']      = $this->strEncrypt($value['idno'] , 6 , 8);
                $value['sex']       = $sex[$user_info[$value['id']]['sex']];
                $value['byear']     = date('Y' , time()) - $user_info[$value['id']]['byear'];
                if ($value['byear'] > 150) {
                    $value['byear'] = '——';
                }
                $value['yes_interest']  = $interest_data[$value['id']] ? $interest_data[$value['id']] : '0.00';
                $value['money']         = number_format($value['money'], 2, '.', ',');
                $value['lock_money']    = number_format($value['lock_money'], 2, '.', ',');
                $value['wait_capital']  = number_format($value['wait_capital'], 2, '.', ',');
                $value['wait_interest'] = number_format($value['wait_interest'], 2, '.', ',');
                $value['revenue']       = number_format($value['yes_interest'], 2, '.', ',');
                $value['mobile_area']   = '';
                if (!empty($value['mobile_a']) && is_numeric($value['mobile_a'])) {
                    $mobile_array[] = $value['mobile_a'];
                }
                
                $listInfo[] = $value;
            }
            if ($mobile_array) {
                $mobile_string = implode(',' , $mobile_array);
                $sql = "SELECT mobile , provice , city FROM firstp2p_mobile_area WHERE mobile IN ({$mobile_string}) ";
                $mobile_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($mobile_res as $key => $value) {
                    $mobile_data[$value['mobile']] = $value['provice'] . $value['city'];
                }
            } else {
                $mobile_data = array();
            }
            foreach ($listInfo as $key => $value) {
                $listInfo[$key]['mobile_area'] = $mobile_data[$value['mobile_a']];
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $DealLoadBYUser2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/JYSDebt/DealLoadBYUser2Excel') || empty($authList)) {
            $DealLoadBYUser2Excel = 1;
        }
        return $this->renderPartial('DealLoadBYUser' , array('DealLoadBYUser2Excel' => $DealLoadBYUser2Excel));
    }

    /**
     * 交易所 在途出借人明细 列表 批量条件上传
     */
    public function actionaddDealLoadBYUserCondition()
    {
        set_time_limit(0);
        if (in_array($_GET['download'] , array(1))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
                $name = '在途出借人明细 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            if (empty($_POST['deal_type'])) {
                return $this->actionError('请正确输入所属平台' , 5);
            }
            $sql = "SELECT * FROM offline_platform WHERE id = '{$_POST['deal_type']}' ";
            $platform = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$platform) {
                return $this->actionError('所属平台输入错误' , 5);
            }
            if (!in_array($_POST['type'] , array(1))) {
                return $this->actionError('请正确输入查询类型' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            $type         = intval($_POST['type']);
            $file_address = $upload_xls['data'];

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$file_address;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('上传的文件中无数据' , 5);
            }
            if ($Rows > 10001) {
                return $this->actionError('上传的文件中数据超过一万行' , 5);
            }
            unset($data[0]);
            $name  = $platform['name'];
            $model = Yii::app()->offlinedb;
            if ($type == 1) {

                $name .= ' 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT user_id FROM offline_user_platform WHERE user_id IN ({$user_id_str}) ";
                $user_id_data = $model->createCommand($sql)->queryColumn();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDealLoadBYUserCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                $sql = "SELECT user_id FROM ag_wx_assignee_info WHERE status IN (2, 3)";
                $assignee = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $user_id_data)) {
                        $false_id_arr[] = $value[0];
                    } else if (in_array($value[0] , $assignee)) {
                        $false_id_arr[] = $value[0].'(受让方账户数据请单独导出)';
                    } else {
                        $data_array[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                if (!$data_array) {
                    $data_array = array();
                }
                $data_json = json_encode($data_array);
            }
            
            $sql = "INSERT INTO xf_deal_load_user_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform['id']} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
            $model_a = Yii::app()->fdb;
            $add = $model_a->createCommand($sql)->execute();
            $add_id = $model_a->getLastInsertID();

            if ($add) {
                return $this->renderPartial('addDealLoadBYUserCondition', array('end' => 2 , 'count' => $count , 'true_count' => $true_count , 'false_count' => $false_count , 'false_id_str' => $false_id_str , 'add_id' => $add_id , 'add_name' => $name));
            } else {
                return $this->actionError('保存查询条件失败' , 5);
            }
        }

        return $this->renderPartial('addDealLoadBYUserCondition', array('end' => 0));
    }

    /**
     * 交易所 在途出借人明细 导出
     */
    public function actionDealLoadBYUser2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 5) {
                echo '<h1>请正确输入所属平台</h1>';exit;
            }
            $sql = "SELECT * FROM offline_platform WHERE id = '{$_GET['deal_type']}' ";
            $platform = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$platform) {
                echo '<h1>所属平台输入错误</h1>';exit;
            }
            $platform_id = $platform['id'];
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_user_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    $con_data = json_decode($condition['data_json'] , true);
                    $platform_id = $condition['platform'];
                    $redis_key = 'Offline_Deal_Load_BY_User_Download_'.$platform_id.'_Condition_'.$condition['id'];
                    $check = Yii::app()->rcache->get($redis_key);
                    if($check){
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                }
            }
            $where = " WHERE deal_load.status IN (1, 3) AND deal_load.platform_id = {$platform_id} ";
            if (empty($_GET['user_id']) && empty($_GET['user_mobile']) && empty($_GET['user_idno']) && empty($_GET['condition_id'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            $model = Yii::app()->offlinedb;
            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND deal_load.user_id = '{$user_id}' ";
            }
            //检验用户手机号
            if (!empty($_GET['user_mobile'])) {
                $user_mobile = trim($_GET['user_mobile']);
                $user_mobile = GibberishAESUtil::enc($user_mobile, Yii::app()->c->idno_key);
                $sql = "SELECT user_id FROM offline_user_platform WHERE phone = '{$user_mobile}' ";
                $user_id = $model->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND deal_load.user_id = '{$user_id}' ";
                } else {
                    $where .= " AND deal_load.user_id = '' ";
                }
            }
            // 校验用户证件号
            if (!empty($_GET['user_idno'])) {
                $user_idno = trim($_GET['user_idno']);
                $user_idno = GibberishAESUtil::enc($user_idno, Yii::app()->c->idno_key);
                $sql = "SELECT user_id FROM offline_user_platform WHERE idno = '{$user_idno}' ";
                $user_id = $model->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND deal_load.user_id = '{$user_id}' ";
                } else {
                    $where .= " AND deal_load.user_id = '' ";
                }
            }
            if ($condition) {
                if ($con_data) {
                    $count    = 0;
                    $con_page = ceil(count($con_data) / 1000);
                    for ($i = 0; $i < $con_page; $i++) {
                        $con_data_arr = array();
                        for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                            if (!empty($con_data[$j])) {
                                $con_data_arr[] = $con_data[$j];
                            }
                        }
                        $con_data_str = implode(',' , $con_data_arr);
                        $where_con    = $where." AND deal_load.user_id IN ({$con_data_str}) ";
                        $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM offline_deal_load AS deal_load {$where_con} ";
                        $count_con = $model->createCommand($sql)->queryScalar();
                        $count += $count_con;
                    }
                    $con_data_str = implode(',' , $con_data);
                    $where .= " AND deal_load.user_id IN ({$con_data_str}) ";
                } else {
                    $where .= " AND deal_load.user_id = -1 ";
                }
            } else {
                $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM offline_deal_load AS deal_load {$where} ";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT deal_load.user_id AS id , SUM(deal_load.wait_capital) AS wait_capital , SUM(deal_load.wait_interest) AS wait_interest , user.money , user.lock_money 
                        FROM offline_deal_load AS deal_load 
                        LEFT JOIN offline_user_platform AS user ON deal_load.user_id = user.user_id 
                        {$where} GROUP BY deal_load.user_id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();

                $sex[0] = '女';
                $sex[1] = '男';
                $user_id_arr = array();
                foreach ($list as $key => $value) {
                    $user_id_arr[] = $value['id'];
                }
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT id , real_name , mobile , idno , sex , byear FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                $user_info = array();
                foreach ($user_res as $key => $value) {
                    $user_info[$value['id']] = $value;
                }
                $sql = "SELECT user_id , SUM(yes_interest) AS yes_interest FROM offline_deal_load WHERE user_id IN ({$user_id_str}) GROUP BY user_id ";
                $interest_res  = $model->createCommand($sql)->queryAll();
                $interest_data = array();
                if ($interest_res) {
                    foreach ($interest_res as $key => $value) {
                        $interest_data[$value['user_id']] = $value['yes_interest'];
                    }
                }
                foreach ($list as $key => $value) {
                    $value['real_name'] = $user_info[$value['id']]['real_name'];
                    $value['mobile']    = GibberishAESUtil::dec($user_info[$value['id']]['mobile'], Yii::app()->c->idno_key);
                    $value['mobile_a']  = substr($value['mobile'] , 0 , 7);
                    // $value['mobile_b']  = $this->strEncrypt($value['mobile'] , 3 , 4);
                    $value['idno']      = GibberishAESUtil::dec($user_info[$value['id']]['idno'], Yii::app()->c->idno_key);
                    // $value['idno']      = $this->strEncrypt($value['idno'] , 6 , 8);
                    $value['sex']       = $sex[$user_info[$value['id']]['sex']];
                    $value['byear']     = date('Y' , time()) - $user_info[$value['id']]['byear'];
                    if ($value['byear'] > 150) {
                        $value['byear'] = '——';
                    }
                    $value['yes_interest'] = $interest_data[$value['id']] ? $interest_data[$value['id']] : '0.00';
                    $value['mobile_area']  = '';
                    if (!empty($value['mobile_a']) && is_numeric($value['mobile_a'])) {
                        $mobile_array[] = $value['mobile_a'];
                    }
                    
                    $listInfo[] = $value;
                }
                if ($mobile_array) {
                    $mobile_string = implode(',' , $mobile_array);
                    $sql = "SELECT mobile , provice , city FROM firstp2p_mobile_area WHERE mobile IN ({$mobile_string}) ";
                    $mobile_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                    foreach ($mobile_res as $key => $value) {
                        $mobile_data[$value['mobile']] = $value['provice'] . $value['city'];
                    }
                } else {
                    $mobile_data = array();
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['mobile_area'] = $mobile_data[$value['mobile_a']];
                }
            }

            $name  = $platform['name'].' 在途出借人明细 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "用户ID,用户姓名,性别,年龄,手机号所在地,在途本金,在途利息\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['real_name']},{$value['sex']},{$value['byear']},{$value['mobile_area']},{$value['wait_capital']},{$value['wait_interest']}\n";
                $data .= iconv('utf-8', 'GBK', $temp);
            }
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=".$name);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');
            echo $data;
        }
    }

    /**
     * 交易所 在途项目明细 列表
     */
    public function actionDealLoadBYDeal()
    {
        if (!empty($_POST)) {
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 5) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入所属平台';
                echo exit(json_encode($result_data));
            }
            $platform_id = intval($_POST['deal_type']);
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_list_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    if ($condition['true_count'] > 500) {
                        header ( "Content-type:application/json; charset=utf-8" );
                        $result_data['data']  = array();
                        $result_data['count'] = 0;
                        $result_data['code']  = 1;
                        $result_data['info']  = '批量条件超过500行，暂不支持列表查询，请使用导出功能！';
                        echo exit(json_encode($result_data));
                    }
                    $con_data = json_decode($condition['data_json'] , true);
                    $platform_id = $condition['platform'];
                }
            }
            $where = " WHERE repay_status = 0 AND platform_id = {$platform_id} ";
            $model = Yii::app()->offlinedb;
            // 校验借款编号
            if (!empty($_POST['deal_id'])) {
                $deal_id = trim($_POST['deal_id']);
                $where  .= " AND deal_id = '{$deal_id}' ";
            }
            //检验借款标题
            if (!empty($_POST['deal_name'])) {
                $deal_name = trim($_POST['deal_name']);
                $where    .= " AND deal_name = '{$deal_name}' ";
            }
            // 校验项目ID
            if (!empty($_POST['project_id'])) {
                $project_id = trim($_POST['project_id']);
                $where     .= " AND project_id = '{$project_id}' ";
            }
            // 校验项目名称
            if (!empty($_POST['project_name'])) {
                $project_name = trim($_POST['project_name']);
                $where       .= " AND project_name = '{$project_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_POST['jys_record_number'])) {
                $jys_record_number = trim($_POST['jys_record_number']);
                $where            .= " AND jys_record_number = '{$jys_record_number}' ";
            }
            // 校验借款方名称
            if (!empty($_POST['user_name'])) {
                $user_name = trim($_POST['user_name']);
                $where    .= " AND deal_user_real_name = '{$user_name}' ";
            }
            //检验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory_name}' ";
                $advisory_id = $model->createCommand($sql)->queryColumn();
                if ($advisory_id) {
                    $advisory_id_str = implode(',' , $advisory_id);
                    $where .= " AND deal_advisory_id IN ({$advisory_id_str}) ";
                } else {
                    $where .= " AND deal_advisory_id = -1 ";
                }
            }
            //检验融资担保机构
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND deal_agency_id = -1 ";
                }
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
            if ($condition) {
                if ($con_data) {
                    $count    = 0;
                    $con_page = ceil(count($con_data) / 1000);
                    for ($i = 0; $i < $con_page; $i++) {
                        $con_data_arr = array();
                        for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                            if (!empty($con_data[$j])) {
                                $con_data_arr[] = $con_data[$j];
                            }
                        }
                        $con_data_str = implode(',' , $con_data_arr);
                        $where_con    = $where." AND deal_id IN ({$con_data_str}) ";
                        $sql          = "SELECT count(DISTINCT deal_id) AS count FROM offline_stat_repay {$where_con}";
                        $count_con    = $model->createCommand($sql)->queryScalar();
                        $count       += $count_con;
                    }
                    $con_data_str = implode(',' , $con_data);
                    $where       .= " AND deal_id IN ({$con_data_str}) ";
                } else {
                    $where .= " AND deal_id = '' ";
                }
            } else {
                $sql   = "SELECT count(DISTINCT deal_id) AS count FROM offline_stat_repay {$where}";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            $time = strtotime(date("Y-m-d" , time())) - 28800;
            // 查询数据
            $sql = "SELECT deal_id , deal_name , project_id , project_name , jys_record_number , project_product_class , deal_loantype , deal_repay_time , deal_user_id , deal_user_real_name , deal_advisory_id , deal_advisory_name , deal_agency_id , deal_agency_name , jys_name , limit_type , MAX(loan_repay_time) AS max_repay_time , MIN(loan_repay_time) AS min_repay_time , SUM(CASE WHEN repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_capital , SUM(CASE WHEN repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_interest , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_capital , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_interest FROM offline_stat_repay {$where} GROUP BY deal_id ORDER BY deal_id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            if (strlen($sql) > 1048576) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                echo exit(json_encode($result_data));
            }
            $list = $model->createCommand($sql)->queryAll();
            if (!$list) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            $loantype = Yii::app()->c->xf_config['loantype'];
            foreach ($list as $key => $value) {
                if ($value['deal_loantype'] == 5 || ($value['deal_loantype'] == 6 && $value['limit_type'] == 1) || ($value['deal_loantype'] == 9 && $value['limit_type'] == 1)) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }
                $value['deal_loantype']    = $loantype[$value['deal_loantype']];
                $value['max_repay_time']   = date("Y-m-d" , ($value['max_repay_time'] + 28800));
                $value['wait_capital']     = number_format($value['wait_capital'], 2, '.', ',');
                $value['wait_interest']    = number_format($value['wait_interest'], 2, '.', ',');
                $value['overdue_day']      = round(($time - $value['min_repay_time']) / 86400);
                $value['overdue_capital']  = number_format($value['overdue_capital'], 2, '.', ',');
                $value['overdue_interest'] = number_format($value['overdue_interest'], 2, '.', ',');
                
                $listInfo[] = $value;
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $DealLoadBYDeal2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/JYSDebt/DealLoadBYDeal2Excel') || empty($authList)) {
            $DealLoadBYDeal2Excel = 1;
        }
        return $this->renderPartial('DealLoadBYDeal' , array('DealLoadBYDeal2Excel' => $DealLoadBYDeal2Excel));
    }

    /**
     * 交易所 在途项目明细 列表 批量条件上传
     */
    public function actionaddDealLoadBYDealCondition()
    {
        set_time_limit(0);
        if (in_array($_GET['download'] , array(1 , 2 , 3))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款方名称');
                $name = '在途项目明细 通过上传借款方名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资经办机构名称');
                $name = '在途项目明细 通过上传融资经办机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资担保机构名称');
                $name = '在途项目明细 通过上传融资担保机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            if (empty($_POST['deal_type'])) {
                return $this->actionError('请正确输入所属平台' , 5);
            }
            $sql = "SELECT * FROM offline_platform WHERE id = '{$_POST['deal_type']}' ";
            $platform = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$platform) {
                return $this->actionError('所属平台输入错误' , 5);
            }
            if (!in_array($_POST['type'] , array(1, 2, 3))) {
                return $this->actionError('请正确输入查询类型' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            $type         = intval($_POST['type']);
            $file_address = $upload_xls['data'];

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$file_address;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('上传的文件中无数据' , 5);
            }
            if ($Rows > 1001) {
                return $this->actionError('上传的文件中数据超过一千行' , 5);
            }
            unset($data[0]);
            $name = $platform['name'];
            $model = Yii::app()->offlinedb;
            if ($type == 1) {

                $name .= ' 通过上传借款方名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $com_a = $model->createCommand("SELECT deal_id , deal_user_real_name FROM offline_stat_repay WHERE deal_user_real_name IN ({$user_id_str}) ")->queryAll();
                $com_arr  = array();
                $com_name = array();
                if ($com_a) {
                    foreach ($com_a as $key => $value) {
                        $com_arr[] = $value['deal_id'];
                        $com_name[] = $value['deal_user_real_name'];
                    }
                }
                $count = count($data);
                if (!$com_arr) {
                    return $this->renderPartial('addDealLoadBYDealCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $com_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;

                $data_json = json_encode($com_arr);

            } else if ($type == 2) {

                $name .= ' 通过上传融资经办机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT deal_id , deal_advisory_name FROM offline_stat_repay WHERE deal_advisory_name IN ({$user_id_str}) ";
                $user_id_data = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDealLoadBYDealCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($user_id_data as $key => $value) {
                    $agency_id[] = $value['deal_id'];
                    $agency_name[] = $value['deal_advisory_name'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $agency_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;

                $data_json = json_encode($agency_id);

            } else if ($type == 3) {

                $name .= ' 通过上传融资担保机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT deal_id , deal_agency_name FROM offline_stat_repay WHERE deal_agency_name IN ({$user_id_str}) ";
                $user_id_data = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDealLoadBYDealCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($user_id_data as $key => $value) {
                    $agency_id[] = $value['deal_id'];
                    $agency_name[] = $value['deal_agency_name'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $agency_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;

                $data_json = json_encode($agency_id);

            }
            
            $sql = "INSERT INTO xf_deal_list_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform['id']} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
            $model_a = Yii::app()->fdb;
            $add = $model_a->createCommand($sql)->execute();
            $add_id = $model_a->getLastInsertID();

            if ($add) {
                return $this->renderPartial('addDealLoadBYDealCondition', array('end' => 2 , 'count' => $count , 'true_count' => $true_count , 'false_count' => $false_count , 'false_id_str' => $false_id_str , 'add_id' => $add_id , 'add_name' => $name));
            } else {
                return $this->actionError('保存查询条件失败' , 5);
            }
        }

        return $this->renderPartial('addDealLoadBYDealCondition' , array('end' => 0));
    }

    /**
     * 交易所 在途项目明细 列表 导出
     */
    public function actionDealLoadBYDeal2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 5) {
                echo '<h1>请正确输入所属平台</h1>';exit;
            }
            $sql = "SELECT * FROM offline_platform WHERE id = '{$_GET['deal_type']}' ";
            $platform = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$platform) {
                echo '<h1>所属平台输入错误</h1>';exit;
            }

            $platform_id = $platform['id'];
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_list_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    $con_data = json_decode($condition['data_json'] , true);
                    $platform_id = $condition['platform'];
                    $redis_key = 'Offline_Deal_Load_BY_Deal_Download_'.$platform_id.'_Condition_'.$condition['id'];
                    $check = Yii::app()->rcache->get($redis_key);
                    if($check){
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                }
            }
            $where = " WHERE repay_status = 0 AND platform_id = {$platform_id} ";
            if (empty($_GET['deal_id']) && empty($_GET['deal_name']) && empty($_GET['project_id']) && empty($_GET['project_name']) && empty($_GET['jys_record_number']) && empty($_GET['user_name']) && empty($_GET['advisory_name']) && empty($_GET['agency_name']) && empty($_GET['condition_id'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            $model = Yii::app()->offlinedb;
            // 校验借款编号
            if (!empty($_GET['deal_id'])) {
                $deal_id = trim($_GET['deal_id']);
                $where  .= " AND deal_id = '{$deal_id}' ";
            }
            //检验借款标题
            if (!empty($_GET['deal_name'])) {
                $deal_name = trim($_GET['deal_name']);
                $where    .= " AND deal_name = '{$deal_name}' ";
            }


            // 校验借款方名称
            if (!empty($_GET['user_name'])) {
                $user_name = trim($_GET['user_name']);
                $where    .= " AND deal_user_real_name = '{$user_name}' ";
            }

            if ($condition) {
                if ($con_data) {
                    $count    = 0;
                    $con_page = ceil(count($con_data) / 1000);
                    for ($i = 0; $i < $con_page; $i++) {
                        $con_data_arr = array();
                        for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                            if (!empty($con_data[$j])) {
                                $con_data_arr[] = $con_data[$j];
                            }
                        }
                        $con_data_str = implode(',' , $con_data_arr);
                        $where_con    = $where." AND deal_id IN ({$con_data_str}) ";
                        $sql          = "SELECT count(DISTINCT deal_id) AS count FROM offline_stat_repay {$where_con}";
                        $count_con    = $model->createCommand($sql)->queryScalar();
                        $count        += $count_con;
                    }
                    $con_data_str = implode(',' , $con_data);
                    $where       .= " AND deal_id IN ({$con_data_str}) ";
                } else {
                    $where .= " AND deal_id = '' ";
                }
            } else {
                $sql   = "SELECT count(DISTINCT deal_id) AS count FROM offline_stat_repay {$where}";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count  = ceil($count / 500);
            $time        = strtotime(date("Y-m-d" , time())) - 28800;
            $loantype = Yii::app()->c->xf_config['loantype'];
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT deal_id , deal_name , project_id , project_name , jys_record_number , project_product_class , deal_loantype , deal_repay_time , deal_user_id , deal_user_real_name , deal_advisory_id , deal_advisory_name , deal_agency_id , deal_agency_name , jys_name , limit_type , MAX(loan_repay_time) AS max_repay_time , MIN(loan_repay_time) AS min_repay_time , SUM(CASE WHEN repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_capital , SUM(CASE WHEN repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_interest , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_capital , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_interest FROM offline_stat_repay {$where} GROUP BY deal_id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();
                foreach ($list as $key => $value) {
                    if ($value['deal_loantype'] == 5 || ($value['deal_loantype'] == 6 && $value['limit_type'] == 1) || ($value['deal_loantype'] == 9 && $value['limit_type'] == 1)) {
                        $value['deal_repay_time'] .= '天';
                    } else {
                        $value['deal_repay_time'] .= '个月';
                    }
                    $value['deal_loantype']    = $loantype[$value['deal_loantype']];
                    $value['max_repay_time']   = date("Y-m-d" , ($value['max_repay_time'] + 28800));
                    $value['overdue_day']      = round(($time - $value['min_repay_time']) / 86400);

                    $listInfo[] = $value;
                }
            }
            $name  = $platform['name'].' 在途项目明细 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "产品编号,产品名称,期限,还款方式,在途本金,在途利息,计划最大还款时间,逾期天数,逾期本金,逾期利息,发行人/融资方简称\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['deal_id']},{$value['deal_name']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['wait_capital']},{$value['wait_interest']},{$value['max_repay_time']},{$value['overdue_day']},{$value['overdue_capital']},{$value['overdue_interest']},{$value['deal_user_real_name']}\n";
                $data .= iconv('utf-8', 'GBK', $temp);
            }
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=".$name);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');
            echo $data;
            if ($condition) {
                $redis_time = 3600;
                $redis_key = 'Offline_Deal_Load_BY_Deal_Download_'.$platform_id.'_Condition_'.$condition['id'];
                $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
                if(!$set){
                    Yii::log("{$redis_key} redis download set error","error");
                }
            }
        }
    }

    /**
     * 交易所 在途投资明细 列表
     */
    public function actionLoanList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 5) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入所属平台';
                echo exit(json_encode($result_data));
            }
            $platform_id = intval($_POST['deal_type']);
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_list_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    if ($condition['true_count'] > 500) {
                        header ( "Content-type:application/json; charset=utf-8" );
                        $result_data['data']  = array();
                        $result_data['count'] = 0;
                        $result_data['code']  = 1;
                        $result_data['info']  = '批量条件超过500行，暂不支持列表查询，请使用导出功能！';
                        echo exit(json_encode($result_data));
                    }
                    $con_data = json_decode($condition['data_json'] , true);
                    $platform_id = $condition['platform'];
                }
            }
            $where = " WHERE deal_load.status IN (1, 3) AND deal_load.platform_id = {$platform_id} ";
            $model = Yii::app()->offlinedb;
            // 校验投资记录ID
            if (!empty($_POST['deal_load_id'])) {
                $deal_load_id = trim($_POST['deal_load_id']);
                $where       .= " AND deal_load.id = '{$deal_load_id}' ";
            }
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND deal_load.user_id = '{$user_id}' ";
            }
            // 校验借款编号
            if (!empty($_POST['deal_id'])) {
                $deal_id = trim($_POST['deal_id']);
                $where  .= " AND deal_load.deal_id = '{$deal_id}' ";
            }
            // 校验借款标题
            if (!empty($_POST['name'])) {
                $name   = trim($_POST['name']);
                $where .= " AND deal.deal_name = '{$name}' ";
            }
            // 校验借款方名称
            if (!empty($_POST['company'])) {
                $company = trim($_POST['company']);
                $where  .= " AND deal.deal_user_real_name = '{$company}' ";
            }
            // 校验审核状态
            if (!empty($_POST['audit_status'])) {
                if ($_POST['audit_status'] === 'no') {
                    $where .= " AND audit.status IS NULL AND audit.id IS NULL ";
                } else {
                    $a_s    = intval($_POST['audit_status']);
                    $where .= " AND audit.status = {$a_s} ";
                }
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
            if ($condition) {
                if ($con_data) {
                    $count    = 0;
                    $con_page = ceil(count($con_data) / 1000);
                    for ($i = 0; $i < $con_page; $i++) {
                        $con_data_arr = array();
                        for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                            if (!empty($con_data[$j])) {
                                $con_data_arr[] = $con_data[$j];
                            }
                        }
                        $con_data_str = implode(',' , $con_data_arr);
                        $where_con    = $where." AND deal_load.id IN ({$con_data_str}) ";
                        $sql = "SELECT count(DISTINCT deal_load.id) AS count 
                                FROM offline_deal_load AS deal_load 
                                LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                                LEFT JOIN offline_deal_load_audit AS audit ON deal_load.id = audit.deal_load_id AND audit.platform_id = {$platform_id}  
                                {$where_con} ";
                        $count_con = $model->createCommand($sql)->queryScalar();
                        $count += $count_con;
                    }
                    $con_data_str = implode(',' , $con_data);
                    $where .= " AND deal_load.id IN ({$con_data_str}) ";
                } else {
                    $where .= " AND deal_load.id = -1 ";
                }
            } else {
                $sql = "SELECT count(DISTINCT deal_load.id) AS count 
                        FROM offline_deal_load AS deal_load 
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                        LEFT JOIN offline_deal_load_audit AS audit ON deal_load.id = audit.deal_load_id AND audit.platform_id = {$platform_id}  
                        {$where} ";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT deal_load.id , deal_load.money , deal_load.wait_capital , deal_load.user_id , deal_load.deal_id , deal_load.create_time , deal_load.wait_interest , deal_load.yes_interest , deal.deal_name , deal.jys_record_number , deal.deal_agency_id , deal.deal_user_id , deal.deal_repay_start_time , deal.deal_advisory_name , deal.deal_agency_name , deal.project_product_class , deal.project_name , deal.deal_loantype , deal.deal_repay_time , deal.deal_rate , deal.deal_user_real_name , MAX(deal.loan_repay_time) AS max_repay_time , deal_load.black_status , deal.limit_type , audit.status AS audit_status , audit.id AS audit_id 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                    LEFT JOIN offline_deal_load_audit AS audit ON deal_load.id = audit.deal_load_id AND audit.platform_id = {$platform_id}  
                    {$where} 
                    GROUP BY deal_load.id ORDER BY CASE WHEN audit.status = 1 THEN 1 WHEN audit.status = 3 THEN 2 WHEN audit.status IS NULL THEN 3 WHEN audit.status = 2 THEN 4 ELSE 0 END ASC , audit.audit_time DESC , deal_load.id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            if (strlen($sql) > 1048576) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                echo exit(json_encode($result_data));
            }
            $list = $model->createCommand($sql)->queryAll();
            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            $edit_status = 0;
            $audit_edit_status = 0;
            $info_status = 0;
            if (!empty($authList) && strstr($authList,'/user/JYSDebt/JYSBlackEditAdd') || empty($authList)) {
                $edit_status = 1;
            }
            $loantype = Yii::app()->c->xf_config['loantype'];
            if (!empty($authList) && strstr($authList,'/user/JYSDebt/JYSDealLoadAudit') || empty($authList)) {
                $audit_edit_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/JYSDebt/JYSDealLoadInfo') || empty($authList)) {
                $info_status = 1;
            }
            $audit_status = array(1 => '待审核' , 2 => '审核通过', 3 => '审核未通过');
            foreach ($list as $key => $value) {
                $value['deal_type']      = $platform_id;
                $value['create_time']    = date('Y-m-d H:i:s', $value['create_time']);
                $value['money']          = number_format($value['money'], 2, '.', ',');
                $value['wait_capital']   = number_format($value['wait_capital'], 2, '.', ',');
                $value['max_repay_time'] = date('Y-m-d', ($value['max_repay_time'] + 28800));
                if ($value['deal_loantype'] == 5 || ($value['deal_loantype'] == 6 && $value['limit_type'] == 1) || ($value['deal_loantype'] == 9 && $value['limit_type'] == 1)) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }
                $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                $value['deal_repay_start_time'] = date('Y-m-d' , ($value['deal_repay_start_time'] + 28800));
                $value['wait_interest']         = number_format($value['wait_interest'] , 2, '.', ',');
                $value['real_name']             = '';
                $value['edit_status']           = $edit_status;
                $value['audit_edit_status']     = $audit_edit_status;
                $value['info_status']           = $info_status;
                if ($value['audit_status']) {
                    $value['audit_status_name'] = $audit_status[$value['audit_status']];
                } else {
                    $value['audit_status_name'] = '未上传';
                }

                $listInfo[] = $value;

                $user_id_arr[] = $value['user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT user_id , real_name FROM offline_user_platform WHERE user_id IN ({$user_id_str}) ";
                $user_infos_res = $model->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['user_id']] = $value['real_name'];
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['real_name'] = $user_infos[$value['user_id']];
                }
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $LoanList2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/JYSDebt/LoanList2Excel') || empty($authList)) {
            $LoanList2Excel = 1;
        }
        return $this->renderPartial('LoanList', array('LoanList2Excel' => $LoanList2Excel));
    }

    /**
     * 交易所 在途投资明细 审核
     */
    public function actionJYSDealLoadAudit()
    {
        if (!empty($_POST)) {
            if (!is_numeric($_POST['id'])) {
                return $this->actionError('审核信息ID错误' , 5);
            }
            $model = Yii::app()->offlinedb;
            $id    = intval($_POST['id']);
            $sql   = "SELECT * FROM offline_deal_load_audit WHERE id = {$id} ";
            $audit = $model->createCommand($sql)->queryRow();
            if (!$audit) {
                return $this->actionError('审核信息ID错误' , 5);
            }
            if ($audit['status'] != 1) {
                return $this->actionError('审核信息状态错误' , 5);
            }
            if ($audit['update_time'] != $_POST['update_time']) {
                return $this->actionError('审核信息发生变更，请重新查看！' , 5);
            }
            $sql = "SELECT * FROM offline_deal_load WHERE platform_id = {$audit['platform_id']} AND id = {$audit['deal_load_id']} AND user_id = {$audit['user_id']} ";
            $deal_load = $model->createCommand($sql)->queryRow();
            if (!$deal_load) {
                return $this->actionError('投资记录不存在' , 5);
            }
            if ($deal_load['status'] != 3) {
                return $this->actionError('投资记录状态错误' , 5);
            }
            if (!in_array($_POST['status'] , [2, 3])) {
                return $this->actionError('请正确选择审核结果' , 5);
            }
            $status = intval($_POST['status']);
            if ($status == 3 && empty($_POST['reason'])) {
                return $this->actionError('请输入拒绝原因' , 5);
            }
            $reason     = trim($_POST['reason']);
            $time       = time();
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $model->beginTransaction();

            $update_deal_load = true;
            $add_contract_task = true;
            if ($status == 2) {
                $sql = "UPDATE offline_deal_load SET status = 1 WHERE id = {$deal_load['id']} AND status = 3 ";
                $update_deal_load = $model->createCommand($sql)->execute();

                $sql = "INSERT INTO offline_contract_task (type , contract_no , contract_type , borrow_id , tender_id , user_id , status , download , investtime , addtime , handletime , platform_id) VALUES (1 , '{$audit['contract_number']}' , 1 , {$deal_load['deal_id']} , {$deal_load['id']} , {$audit['user_id']} , 2 , '{$audit['pic_address_json']}' , {$deal_load['create_time']} , {$time} , {$time} , {$audit['platform_id']}) ";
                $add_contract_task = $model->createCommand($sql)->execute();
            }
            
            $sql = "UPDATE offline_deal_load_audit SET status = {$status} , update_time = {$time} , audit_time = {$time} , audit_user_id = {$op_user_id} , audit_ip = '{$ip}' , reason = '{$reason}' WHERE id = {$audit['id']} AND status = 1 AND update_time = {$audit['update_time']} ";
            $update_audit = $model->createCommand($sql)->execute();

            if ($update_deal_load && $add_contract_task && $update_audit) {
                $model->commit();
                return $this->actionSuccess('操作成功' , 3);
            } else {
                $model->rollback();
                return $this->actionError('操作失败' , 5);
            }
        }

        if (!empty($_GET['id'])) {
            if (!is_numeric($_GET['id'])) {
                return $this->actionError('审核信息ID错误' , 5);
            }
            $id    = intval($_GET['id']);
            $sql   = "SELECT * FROM offline_deal_load_audit WHERE id = {$id} ";
            $audit = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$audit) {
                return $this->actionError('审核信息ID错误' , 5);
            }
            if ($audit['status'] != 1) {
                return $this->actionError('审核信息状态错误' , 5);
            }
            $pic_address = json_decode($audit['pic_address_json'] , true);
            foreach ($pic_address as $key => $value) {
                $audit['pic_address'][] = Yii::app()->c->oss_preview_address.$value;
            }
            return $this->renderPartial('JYSDealLoadAudit', array('res' => $audit));
        }
    }

    /**
     * 交易所 在途投资明细 详情
     */
    public function actionJYSDealLoadInfo()
    {
        if (!empty($_GET['id'])) {
            $id    = intval($_GET['id']);
            $sql   = "SELECT * FROM offline_deal_load_audit WHERE id = {$id} ";
            $audit = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$audit) {
                return $this->actionError('审核信息ID错误' , 5);
            }
            if (!in_array($audit['status'] , [2, 3])) {
                return $this->actionError('审核信息状态错误' , 5);
            }
            $pic_address = json_decode($audit['pic_address_json'] , true);
            foreach ($pic_address as $key => $value) {
                $audit['pic_address'][] = Yii::app()->c->oss_preview_address.$value;
            }
            $audit['audit_user_name'] = Yii::app()->db->createCommand("SELECT realname FROM itz_user WHERE id = {$audit['audit_user_id']} ")->queryScalar();
            $audit['audit_time'] = date('Y-m-d H:i:s' , $audit['audit_time']);
            return $this->renderPartial('JYSDealLoadInfo', array('res' => $audit));
        }
    }

    /**
     * 交易所 在途投资明细 列表 批量条件上传
     */
    public function actionaddLoanListCondition()
    {
        set_time_limit(0);
        if (in_array($_GET['download'] , array(1 , 2 , 3))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
                $name = '在途投资明细 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款编号');
                $name = '在途投资明细 通过上传借款编号查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
                $name = '在途投资明细 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            if (empty($_POST['deal_type'])) {
                return $this->actionError('请正确输入所属平台' , 5);
            }
            $sql = "SELECT * FROM offline_platform WHERE id = '{$_POST['deal_type']}' ";
            $platform = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$platform) {
                return $this->actionError('所属平台输入错误' , 5);
            }
            if (!in_array($_POST['type'] , array(1, 2, 3))) {
                return $this->actionError('请正确输入查询类型' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            $type         = intval($_POST['type']);
            $file_address = $upload_xls['data'];

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$file_address;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('上传的文件中无数据' , 5);
            }
            unset($data[0]);
            $name = $platform['name'];
            $model = Yii::app()->offlinedb;
            if ($type == 1) {
                if ($Rows > 10001) {
                    return $this->actionError('上传的文件中数据超过一万行' , 5);
                }
                $name .= ' 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT user_id FROM offline_user_platform WHERE user_id IN ({$user_id_str}) ";
                $user_id_data = $model->createCommand($sql)->queryColumn();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addLoanListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                $sql = "SELECT user_id FROM ag_wx_assignee_info WHERE status IN (2, 3)";
                $assignee = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $user_id_data)) {
                        $false_id_arr[] = $value[0];
                    } else if (in_array($value[0] , $assignee)) {
                        $false_id_arr[] = $value[0].'(受让方账户数据请单独导出)';
                    } else {
                        $user_id_true[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                if ($user_id_true) {
                    $user_id_str = implode(',' , $user_id_true);
                    $sql = "SELECT id FROM offline_deal_load WHERE user_id IN ({$user_id_str}) AND (wait_capital > 0 OR wait_interest > 0) ";
                    $deal_load = $model->createCommand($sql)->queryColumn();
                    if (!$deal_load) {
                        $deal_load = array();
                    }
                    $data_json = json_encode($deal_load);
                } else {
                    $data_json = json_encode(array());
                }

            } else if ($type == 2) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行' , 5);
                }
                $name .= ' 通过上传借款编号查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id FROM offline_deal WHERE id IN ({$user_id_str}) AND deal_status = 4";
                $user_id_data = $model->createCommand($sql)->queryColumn();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addLoanListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $user_id_data)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                $user_id_str = implode(',' , $user_id_data);
                $sql = "SELECT id FROM offline_deal_load WHERE deal_id IN ({$user_id_str}) AND (wait_capital > 0 OR wait_interest > 0) ";
                $deal_load = $model->createCommand($sql)->queryColumn();
                if (!$deal_load) {
                    $deal_load = array();
                }
                $data_json = json_encode($deal_load);

            } else if ($type == 3) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行' , 5);
                }
                $name .= ' 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id , name FROM offline_deal WHERE name IN ({$user_id_str}) AND deal_status = 4";
                $deal_res = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$deal_res) {
                    return $this->renderPartial('addLoanListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($deal_res as $key => $value) {
                    $deal_name[] = $value['name'];
                    $deal_id[] = $value['id'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $deal_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                $user_id_str = implode(',' , $deal_id);
                $sql = "SELECT id FROM offline_deal_load WHERE deal_id IN ({$user_id_str}) AND (wait_capital > 0 OR wait_interest > 0) ";
                $deal_load = $model->createCommand($sql)->queryColumn();
                if (!$deal_load) {
                    $deal_load = array();
                }
                $data_json = json_encode($deal_load);

            }
            
            $sql = "INSERT INTO xf_deal_load_list_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform['id']} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
            $model_a = Yii::app()->fdb;
            $add = $model_a->createCommand($sql)->execute();
            $add_id = $model_a->getLastInsertID();

            if ($add) {
                return $this->renderPartial('addLoanListCondition', array('end' => 2 , 'count' => $count , 'true_count' => $true_count , 'false_count' => $false_count , 'false_id_str' => $false_id_str , 'add_id' => $add_id , 'add_name' => $name));
            } else {
                return $this->actionError('保存查询条件失败' , 5);
            }
        }

        return $this->renderPartial('addLoanListCondition', array('end' => 0));
    }

    /**
     * 交易所 在途投资明细 列表 导出
     */
    public function actionLoanList2Excel()
    {
        if (!empty($_GET)) {
            set_time_limit(0);
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 5) {
                echo '<h1>请正确输入所属平台</h1>';exit;
            }
            $sql = "SELECT * FROM offline_platform WHERE id = '{$_GET['deal_type']}' ";
            $platform = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$platform) {
                echo '<h1>所属平台输入错误</h1>';exit;
            }
            $platform_id = $platform['id'];
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_list_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    $con_data = json_decode($condition['data_json'] , true);
                    $platform_id = $condition['platform'];
                    $redis_key = 'Offline_Deal_Load_List_Download_'.$platform_id.'_Condition_'.$condition['id'];
                    $check = Yii::app()->rcache->get($redis_key);
                    if($check){
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                }
            }
            $where = " WHERE deal_load.status IN (1, 3) AND deal_load.platform_id = {$platform_id} ";
            $model = Yii::app()->offlinedb;
            // 校验投资记录ID
            if (!empty($_GET['deal_load_id'])) {
                $deal_load_id = trim($_GET['deal_load_id']);
                $where       .= " AND deal_load.id = '{$deal_load_id}' ";
            }
            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND deal_load.user_id = '{$user_id}' ";
            }
            // 校验借款编号
            if (!empty($_GET['deal_id'])) {
                $deal_id = trim($_GET['deal_id']);
                $where  .= " AND deal_load.deal_id = '{$deal_id}' ";
            }
            // 校验借款标题
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.deal_name = '{$name}' ";
            }
            // 校验借款方名称
            if (!empty($_GET['company'])) {
                $company = trim($_GET['company']);
                $where  .= " AND deal.deal_user_real_name = '{$company}' ";
            }
            // 校验审核状态
            if (!empty($_GET['audit_status'])) {
                if ($_GET['audit_status'] === 'no') {
                    $where .= " AND audit.status IS NULL AND audit.id IS NULL ";
                } else {
                    $a_s    = intval($_GET['audit_status']);
                    $where .= " AND audit.status = {$a_s} ";
                }
            }
            if ($_GET['deal_load_id']      == '' &&
                $_GET['user_id']           == '' &&
                $_GET['deal_id']           == '' &&
                $_GET['name']              == '' &&
                $_GET['company']           == '' &&
                $_GET['audit_status']      == '' &&
                $_GET['condition_id']      == '' )
            {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            if ($condition) {
                if ($con_data) {
                    $count    = 0;
                    $con_page = ceil(count($con_data) / 1000);
                    for ($i = 0; $i < $con_page; $i++) {
                        $con_data_arr = array();
                        for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                            if (!empty($con_data[$j])) {
                                $con_data_arr[] = $con_data[$j];
                            }
                        }
                        $con_data_str = implode(',' , $con_data_arr);
                        $where_con    = $where." AND deal_load.id IN ({$con_data_str}) ";
                        $sql = "SELECT count(DISTINCT deal_load.id) AS count 
                                FROM offline_deal_load AS deal_load 
                                LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                                LEFT JOIN offline_deal_load_audit AS audit ON deal_load.id = audit.deal_load_id AND audit.platform_id = {$platform_id} 
                                {$where_con} ";
                        $count_con = $model->createCommand($sql)->queryScalar();
                        $count += $count_con;
                    }
                    $con_data_str = implode(',' , $con_data);
                    $where .= " AND deal_load.id IN ({$con_data_str}) ";
                } else {
                    $where .= " AND deal_load.id = -1 ";
                }
            } else {
                $sql = "SELECT count(DISTINCT deal_load.id) AS count 
                        FROM offline_deal_load AS deal_load 
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                        LEFT JOIN offline_deal_load_audit AS audit ON deal_load.id = audit.deal_load_id AND audit.platform_id = {$platform_id} 
                        {$where} ";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $loantype = Yii::app()->c->xf_config['loantype'];
            $audit_status = array(1 => '待审核' , 2 => '审核通过', 3 => '审核未通过');
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT deal_load.id , deal_load.money , deal_load.wait_capital , deal_load.user_id , deal_load.deal_id , deal_load.create_time , deal_load.wait_interest , deal_load.yes_interest , deal.deal_name , deal.jys_record_number , deal.deal_agency_id , deal.deal_user_id , deal.deal_repay_start_time , deal.deal_advisory_name , deal.deal_agency_name , deal.project_product_class , deal.project_name , deal.deal_loantype , deal.deal_repay_time , deal.deal_rate , deal.deal_user_real_name , MAX(deal.loan_repay_time) AS max_repay_time , deal.limit_type , audit.status AS audit_status , audit.id AS audit_id 
                        FROM offline_deal_load AS deal_load 
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                        LEFT JOIN offline_deal_load_audit AS audit ON deal_load.id = audit.deal_load_id AND audit.platform_id = {$platform_id} 
                        {$where} GROUP BY deal_load.id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();

                foreach ($list as $key => $value) {
                    $value['create_time']    = date('Y-m-d H:i:s', $value['create_time']);
                    $value['max_repay_time'] = date('Y-m-d', ($value['max_repay_time'] + 28800));
                    if ($value['deal_loantype'] == 5 || ($value['deal_loantype'] == 6 && $value['limit_type'] == 1) || ($value['deal_loantype'] == 9 && $value['limit_type'] == 1)) {
                        $value['deal_repay_time'] .= '天';
                    } else {
                        $value['deal_repay_time'] .= '个月';
                    }
                    $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                    $value['deal_repay_start_time'] = date('Y-m-d' , ($value['deal_repay_start_time'] + 28800));
                    $value['real_name']             = '';
                    if ($value['audit_status']) {
                        $value['audit_status_name'] = $audit_status[$value['audit_status']];
                    } else {
                        $value['audit_status_name'] = '未上传';
                    }

                    $listInfo[] = $value;

                    $user_id_arr[] = $value['user_id'];
                }
                if ($user_id_arr) {
                    $user_id_str = implode(',' , $user_id_arr);
                    $sql = "SELECT user_id , real_name FROM offline_user_platform WHERE user_id IN ({$user_id_str}) ";
                    $user_infos_res = $model->createCommand($sql)->queryAll();
                    foreach ($user_infos_res as $key => $value) {
                        $user_infos[$value['user_id']] = $value['real_name'];
                    }
                    foreach ($listInfo as $key => $value) {
                        $listInfo[$key]['real_name'] = $user_infos[$value['user_id']];
                    }
                }
            }

            $name = $platform['name'].' 在途投资明细 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "投资记录ID,产品编号,产品名称,用户ID,用户姓名,投资时间,投资金额,期限,还款方式,计划最大回款时间,年化收益率,计息时间,剩余待还本金,剩余待还利息,发行人/融资方简称,审核状态\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['deal_id']},{$value['deal_name']},{$value['user_id']},{$value['real_name']},{$value['create_time']},{$value['money']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['max_repay_time']},{$value['deal_rate']},{$value['deal_repay_start_time']},{$value['wait_capital']},{$value['wait_interest']},{$value['deal_user_real_name']},{$value['audit_status_name']}\n";
                $data .= iconv('utf-8', 'GBK', $temp);
            }
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=".$name);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');
            echo $data;
            if ($condition) {
                $redis_time = 3600;
                $redis_key = 'Offline_Deal_Load_List_Download_'.$platform_id.'_Condition_'.$condition['id'];
                $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
                if(!$set){
                    Yii::log("{$redis_key} redis download set error","error");
                }
            }
        }
    }

    /**
     * 交易所 在途投资明细 黑名单
     */
    public function actionJYSBlackEditAdd()
    {

        return $this->renderPartial('JYSBlackEditAdd');
    }

    /**
     * 交易所 化债数据查询 列表
     */
    public function actionGetDebtList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE deal.is_zdx = 0 ";
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_debt_list_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    if ($condition['true_count'] > 500) {
                        header ( "Content-type:application/json; charset=utf-8" );
                        $result_data['data']  = array();
                        $result_data['count'] = 0;
                        $result_data['code']  = 1;
                        $result_data['info']  = '批量条件超过500行，暂不支持列表查询！';
                        echo exit(json_encode($result_data));
                    }
                    $con_data = json_decode($condition['data_json'] , true);
                    if ($condition['platform']) {
                        $_POST['deal_type'] = $condition['platform'];
                    }
                }
            }
            // 交易所
            if (in_array($_POST['deal_type'] , [5])) {

                $where = " WHERE debt.platform_id = {$_POST['deal_type']} ";
                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile      = trim($_POST['mobile']);
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
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta    = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $d_src  = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$d_src} ";
                }
                // 校验兑换平台
                if (!empty($_POST['platform_no']) && $_POST['debt_src'] == 1) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id']) && $_POST['debt_src'] == 1) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $com_arr = array();
                    if ($com_a) {
                        foreach ($com_a as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if ($com_b) {
                        foreach ($com_b as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where  .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                        $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($user_id_arr) {
                            $user_id_str = implode(',' , $user_id_arr);
                            $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                        } else {
                            $where      .= " AND deal.user_id = '' ";
                        }
                    }
                }
                //咨询方查询
                if (!empty($_POST['advisory'])) {
                    $advisory = trim($_POST['advisory']);
                    $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$name}' ";
                    $advisory_list = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if ($advisory_list) {
                        $adv_arr = implode(',' , $advisory_list);
                        $where  .= " AND deal.advisory_id IN ({$adv_arr}) ";
                    } else {
                        $where  .= " AND deal.advisory_id IS NULL ";
                    }
                }
                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',' , $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($t_user_id) {
                        $t_user_id_str = implode(',' , $t_user_id);
                        $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                        $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',' , $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
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
                //后台用户
                $adminUserInfo  = \Yii::app()->user->getState('_user');
                if(!empty($adminUserInfo['username'])){
                    if($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin){
                        if($adminUserInfo['user_type'] == 2){
                            $deallist = Yii::app()->offlinedb->createCommand("SELECT offline_deal.id deal_id from offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' and offline_deal_agency.is_effect = 1 and offline_deal.id > 0")->queryAll();
                            if(!empty($deallist)){
                                $dealIds = implode(",",ItzUtil::array_column($deallist,"deal_id"));
                                $where .= " AND deal.id IN({$dealIds})";
                            }else{
                                //不是超级管理员并且没有$dealIds
                                $where .= " AND deal.id < 0";
                            }
                        }

                    }
                }
                // 查询数据总量
                if ($condition) {
                    $redis_time = 86400;
                    if ($_POST['deal_type'] == 3) {
                        $redis_key = 'XF_Debt_List_Count_GCWJ_Condition_'.$condition['id'];
                    } else if ($_POST['deal_type'] == 4) {
                        $redis_key = 'XF_Debt_List_Count_ZDX_Condition_'.$condition['id'];
                    } else if ($_POST['deal_type'] == 5) {
                        $redis_key = 'XF_Debt_List_Count_JYS_Condition_'.$condition['id'];
                    }
                    $redis_val  = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',' , $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                    } else {
                        if ($con_data) {
                            $count    = 0;
                            $con_page = ceil(count($con_data) / 1000);
                            for ($i = 0; $i < $con_page; $i++) {
                                $con_data_arr = array();
                                for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                                    if (!empty($con_data[$j])) {
                                        $con_data_arr[] = $con_data[$j];
                                    }
                                }
                                $con_data_str = implode(',' , $con_data_arr);
                                $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                                $sql = "SELECT count(debt.id) AS count 
                                        FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id  
                                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                                $count_con = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
                                $count += $count_con;
                            }
                            $con_data_str = implode(',' , $con_data);
                            $where .= " AND debt.id IN ({$con_data_str}) ";
                            $set = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                            if(!$set){
                                Yii::log("{$redis_key} redis count set error","error");
                            }
                        } else {
                            $where .= " AND debt.id = '' ";
                        }
                    }
                } else {
                    $sql = "SELECT count(debt.id) AS count 
                            FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                            LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                    $count = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
                }
                if ($count == 0) {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id  
                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                if (strlen($sql) > 1048576) {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                    echo exit(json_encode($result_data));
                }
                $list       = Yii::app()->offlinedb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                $platform_no[1] = '有解';
                $platform_no[2] = '悠融优选';

                // $load_src[0] = '常规债权';
                // $load_src[1] = '一键下车退回债权';
                // $load_src[2] = '权益兑换退回债权';

                $debt_id_arr = array();
                //获取当前账号所有子权限
                $authList = \Yii::app()->user->getState('_auth');
                $info_status = 0;
                if (!empty($authList) && strstr($authList,'/user/JYSDebt/DebtInfo') || empty($authList)) {
                    $info_status = 1;
                }
                foreach ($list as $key => $value){
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']           = number_format($value['money'] , 2 , '.' , ',');
                    $value['sold_money']      = number_format($value['sold_money'] , 2 , '.' , ',');
                    $value['deal_load_money'] = number_format($value['deal_load_money'] , 2 , '.' , ',');
                    $value['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $value['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $value['platform_no'] = '——';
                    }
                    $value['debt_src']        = $debt_src[$value['debt_src']];
                    // $value['load_src']        = $load_src[$value['load_src']];
                    $value['info_status']     = $info_status;

                    $listInfo[]    = $value;
                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',' , $debt_id_arr);
                    $sql = "SELECT debt_id , user_id , new_tender_id , status FROM offline_debt_tender WHERE debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            $user_id_arr[] = $value['user_id'];
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',' , $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM offline_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                $user_id_str = implode(',' , $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $temp['mobile']    = $this->strEncrypt($temp['mobile'] , 3 , 4);

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $listInfo[$key]['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['user_id'];
                        $listInfo[$key]['t_real_name']  = $user_id_data[$debt_tender[$value['id']]['user_id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = $user_id_data[$debt_tender[$value['id']]['user_id']]['mobile'];
                        $listInfo[$key]['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $listInfo[$key]['t_user_id']    = '——';
                        $listInfo[$key]['t_real_name']  = '——';
                        $listInfo[$key]['t_mobile']     = '——';
                        $listInfo[$key]['oss_download'] = '';
                    }

                    $listInfo[$key]['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);
                }
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList,'/user/JYSDebt/DebtListExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        $channel_id = Yii::app()->c->channel_id;
        return $this->renderPartial('GetDebtList', array('daochu_status' => $daochu_status, 'channel_id' => $channel_id));
    }

    /**
     * 化债数据查询 列表 批量条件上传
     */
    public function actionaddDebtListCondition()
    {
        set_time_limit(0);
        if (in_array($_GET['download'] , array(1 , 3))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
                $name = '化债数据查询 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '产品名称');
                $name = '化债数据查询 通过上传产品名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            if (!in_array($_POST['deal_type'] , array(5))) {
                return $this->actionError('请正确选择所属平台' , 5);
            } 
            if (!in_array($_POST['type'] , array(1, 3))) {
                return $this->actionError('请正确输入查询类型' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            $type         = intval($_POST['type']);
            $file_address = $upload_xls['data'];

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$file_address;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('上传的文件中无数据' , 5);
            }
            unset($data[0]);
            $name = '';
            if (in_array($_POST['deal_type'] , [5])) {
                $model = Yii::app()->offlinedb;
                $platform = intval($_POST['deal_type']);
                $name .= '交易所';
                $table = 'offline';
            }
            if ($type == 1) {
                if ($Rows > 10001) {
                    return $this->actionError('上传的文件中数据超过一万行' , 5);
                }
                $name .= ' 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_id_data = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDebtListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                $sql = "SELECT user_id FROM ag_wx_assignee_info WHERE status IN (2, 3)";
                $assignee = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $user_id_data)) {
                        $false_id_arr[] = $value[0];
                    } else if (in_array($value[0] , $assignee)) {
                        $false_id_arr[] = $value[0].'(受让方账户数据请单独导出)';
                    } else {
                        $user_id_true[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                if ($user_id_true) {
                    $user_id_str = implode(',' , $user_id_true);
                    $sql = "SELECT id FROM firstp2p_debt WHERE user_id IN ({$user_id_str})";
                    $debt = $model->createCommand($sql)->queryColumn();
                    if (!$debt) {
                        $debt = array();
                    }
                    $data_json = json_encode($debt);
                } else {
                    $data_json = json_encode(array());
                }

            } else if ($type == 3) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行' , 5);
                }
                $name .= ' 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id , name FROM {$table}_deal WHERE name IN ({$user_id_str}) ";
                $deal_res = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$deal_res) {
                    return $this->renderPartial('addDebtListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($deal_res as $key => $value) {
                    $deal_name[] = $value['name'];
                    $deal_id[] = $value['id'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $deal_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                $user_id_str = implode(',' , $deal_id);
                $sql = "SELECT id FROM {$table}_debt WHERE borrow_id IN ({$user_id_str})";
                $debt = $model->createCommand($sql)->queryColumn();
                if (!$debt) {
                    $debt = array();
                }
                $data_json = json_encode($debt);

            }
            
            $sql = "INSERT INTO xf_debt_list_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
            $model_a = Yii::app()->fdb;
            $add = $model_a->createCommand($sql)->execute();
            $add_id = $model_a->getLastInsertID();

            if ($add) {
                return $this->renderPartial('addDebtListCondition', array('end' => 2 , 'count' => $count , 'true_count' => $true_count , 'false_count' => $false_count , 'false_id_str' => $false_id_str , 'add_id' => $add_id , 'add_name' => $name));
            } else {
                return $this->actionError('保存查询条件失败' , 5);
            }
        }

        return $this->renderPartial('addDebtListCondition', array('end' => 0));
    }


    /**
     * 化债数据查询 列表 导出
     */
    public function actionDebtListExcel()
    {
        set_time_limit(0);
        // 条件筛选
        $where = " WHERE deal.is_zdx = 0 ";
        if (!empty($_GET['condition_id'])) {
            $sql = "SELECT * FROM xf_debt_list_condition WHERE id = '{$_GET['condition_id']}' ";
            $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($condition) {
                $con_data = json_decode($condition['data_json'] , true);
                if ($condition['platform']) {
                    $_GET['deal_type'] = $condition['platform'];
                }
                if ($_GET['deal_type'] == 1) {
                    $redis_key = 'XF_Debt_List_Download_ZX_Condition_'.$condition['id'];
                } else if ($_GET['deal_type'] == 2) {
                    $redis_key = 'XF_Debt_List_Download_PH_Condition_'.$condition['id'];
                } else if ($_GET['deal_type'] == 3) {
                    $redis_key = 'XF_Debt_List_Download_GCWJ_Condition_'.$condition['id'];
                } else if ($_GET['deal_type'] == 4) {
                    $redis_key = 'XF_Debt_List_Download_ZDX_Condition_'.$condition['id'];
                } else if ($_GET['deal_type'] == 5) {
                    $redis_key = 'XF_Debt_List_Download_JYS_Condition_'.$condition['id'];
                }
                $check = Yii::app()->rcache->get($redis_key);
                if($check){
                    echo '<h1>此下载地址已失效</h1>';
                    exit;
                }
            }
        }
        if ($_GET['user_id']=='' && $_GET['serial_number']=='' && $_GET['borrow_id']=='' && $_GET['tender_id']=='' && $_GET['status']=='' && $_GET['name']=='' && $_GET['mobile']=='' && $_GET['debt_src']=='' && $_GET['platform_no']=='' && $_GET['channel_id']=='' && $_GET['company']=='' && $_GET['advisory']=='' && $_GET['t_user_id']=='' && $_GET['t_mobile'] && $_GET['start']=='' && $_GET['end']=='' && $_GET['condition_id']=='') {
            echo '<h1>请输入至少一个查询条件</h1>';
            exit;
        }
        if (empty($_GET['deal_type'])) {
            $_GET['deal_type'] = 5;
        }
        if (in_array($_GET['deal_type'] , [5])) {

            $where = " WHERE debt.platform_id = {$_GET['deal_type']} ";
            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = intval($_GET['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_GET['serial_number'])) {
                $serial_number = trim($_GET['serial_number']);
                $where        .= " AND debt.serial_number = '{$serial_number}' ";
            }
            // 校验用户手机号
            if (!empty($_GET['mobile'])) {
                $mobile      = trim($_GET['mobile']);
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
            if (!empty($_GET['borrow_id'])) {
                $borrow_id = intval($_GET['borrow_id']);
                $where    .= " AND debt.borrow_id = {$borrow_id} ";
            }
            // 校验投资记录ID
            if (!empty($_GET['tender_id'])) {
                $tender_id = intval($_GET['tender_id']);
                $where    .= " AND debt.tender_id = {$tender_id} ";
            }
            // 校验转让状态
            if (!empty($_GET['status'])) {
                $sta    = intval($_GET['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验项目名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验债转类型
            if (!empty($_GET['debt_src'])) {
                $d_src  = intval($_GET['debt_src']);
                $where .= " AND debt.debt_src = {$d_src} ";
            }
            // 校验兑换平台
            if (!empty($_GET['platform_no']) && $_GET['debt_src'] == 1) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id']) && $_GET['debt_src'] == 1) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款方名称
            if (!empty($_GET['company'])) {
                $company = trim($_GET['company']);
                $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $com_arr = array();
                if ($com_a) {
                    foreach ($com_a as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if ($com_b) {
                    foreach ($com_b as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where  .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                    $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($user_id_arr) {
                        $user_id_str = implode(',' , $user_id_arr);
                        $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                    } else {
                        $where      .= " AND deal.user_id = '' ";
                    }
                }
            }

            //咨询方查询
            if (!empty($_GET['advisory'])) {
                $advisory = trim($_GET['advisory']);
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$name}' ";
                $advisory_list = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if ($advisory_list) {
                    $adv_arr = implode(',' , $advisory_list);
                    $where  .= " AND deal.advisory_id IN ({$adv_arr}) ";
                } else {
                    $where  .= " AND deal.advisory_id IS NULL ";
                }
            }
            // 校验受让人ID
            if (!empty($_GET['t_user_id'])) {
                $t_user_id = intval($_GET['t_user_id']);
                $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($t_user_id) {
                    $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                    $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',' , $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_GET['t_mobile'])) {
                $t_mobile = trim($_GET['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($t_user_id) {
                    $t_user_id_str = implode(',' , $t_user_id);
                    $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                    $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',' , $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验转让完成时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND debt.successtime >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND debt.successtime <= {$end} ";
            }
            //后台用户
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if(!empty($adminUserInfo['username'])){
                if($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin){
                    if($adminUserInfo['user_type'] == 2){
                        $deallist = Yii::app()->offlinedb->createCommand("SELECT offline_deal.id deal_id from offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' and offline_deal_agency.is_effect = 1 and offline_deal.id > 0")->queryAll();
                        if(!empty($deallist)){
                            $dealIds = implode(",",ItzUtil::array_column($deallist,"deal_id"));
                            $where .= " AND deal.id IN({$dealIds})";
                        }else{
                            $where .= " AND deal.id < 0";
                        }
                    }

                }
            }
            // 查询数据总量
            if ($condition) {
                $redis_time = 86400;
                if ($_GET['deal_type'] == 3) {
                    $redis_key = 'XF_Debt_List_Count_GCWJ_Condition_'.$condition['id'];
                } else if ($_GET['deal_type'] == 4) {
                    $redis_key = 'XF_Debt_List_Count_ZDX_Condition_'.$condition['id'];
                } else if ($_GET['deal_type'] == 5) {
                    $redis_key = 'XF_Debt_List_Count_JYS_Condition_'.$condition['id'];
                }
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',' , $con_data);
                    $where .= " AND debt.id IN ({$con_data_str}) ";
                } else {
                    if ($con_data) {
                        $count    = 0;
                        $con_page = ceil(count($con_data) / 1000);
                        for ($i = 0; $i < $con_page; $i++) {
                            $con_data_arr = array();
                            for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) { 
                                if (!empty($con_data[$j])) {
                                    $con_data_arr[] = $con_data[$j];
                                }
                            }
                            $con_data_str = implode(',' , $con_data_arr);
                            $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                            $sql = "SELECT count(debt.id) AS count 
                                    FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                            $count_con = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
                            $count += $count_con;
                        }
                        $con_data_str = implode(',' , $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                        $set = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                        if(!$set){
                            Yii::log("{$redis_key} redis count set error","error");
                        }
                    } else {
                        $where .= " AND debt.id = '' ";
                    }
                }
            } else {
                $sql = "SELECT count(debt.id) AS count 
                        FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                $count = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.user_id ASC , debt.addtime DESC LIMIT {$pass} , 500 ";
                $list = Yii::app()->offlinedb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                $platform_no[1] = '有解';
                $platform_no[2] = '悠融优选';

                $debt_id_arr = array();
                foreach ($list as $key => $value){
                    $list[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $list[$key]['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $list[$key]['successtime'] = '——';
                    }
                    // $list[$key]['money']           = number_format($value['money'] , 2 , '.' , ',');
                    // $list[$key]['sold_money']      = number_format($value['sold_money'] , 2 , '.' , ',');
                    // $list[$key]['deal_load_money'] = number_format($value['deal_load_money'] , 2 , '.' , ',');
                    $list[$key]['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $list[$key]['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $list[$key]['platform_no'] = '——';
                    }
                    $list[$key]['debt_src']        = $debt_src[$value['debt_src']];

                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',' , $debt_id_arr);
                    $sql = "SELECT debt_id , user_id , new_tender_id , status FROM offline_debt_tender WHERE debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            $user_id_arr[] = $value['user_id'];
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',' , $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM offline_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                $user_id_str = implode(',' , $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    // $temp['mobile']    = $this->strEncrypt($temp['mobile'] , 3 , 4);

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($list as $key => $value) {
                    $value['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $value['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $value['t_user_id']    = $debt_tender[$value['id']]['user_id'];
                        $value['t_real_name']  = $user_id_data[$debt_tender[$value['id']]['user_id']]['real_name'];
                        $value['t_mobile']     = $user_id_data[$debt_tender[$value['id']]['user_id']]['mobile'];
                        $value['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $value['t_user_id']    = '——';
                        $value['t_real_name']  = '——';
                        $value['t_mobile']     = '——';
                        $value['oss_download'] = '';
                    }
                    $value['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);

                    $listInfo[] = $value;
                }
            }
        }
        if ($_GET['deal_type'] == 1) {
            $name = '化债数据查询 尊享 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        } else if ($_GET['deal_type'] == 2) {
            $name = '化债数据查询 普惠 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        } else if ($_GET['deal_type'] == 3) {
            $name = '化债数据查询 工场微金 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        } else if ($_GET['deal_type'] == 4) {
            $name = '化债数据查询 智多新 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        } else if ($_GET['deal_type'] == 5) {
            $name = '化债数据查询 交易所 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        }
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "债转ID,债转编号,转让人ID,转让人姓名,转让人手机号,借款编号,借款标题,投资记录ID,投资金额,发起债转金额,已转出金额,折扣,转让状态,债转类型,兑换平台,债转合同编号,受让人ID,受让人姓名,受让人手机号,发起时间,转让完成时间,债转合同地址\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['serial_number']},{$value['user_id']},{$value['real_name']},{$value['mobile']},{$value['borrow_id']},{$value['name']},{$value['tender_id']},{$value['deal_load_money']},{$value['money']},{$value['sold_money']},{$value['discount']},{$value['status']},{$value['debt_src']},{$value['platform_no']},{$value['contract_number']},{$value['t_user_id']},{$value['t_real_name']},{$value['t_mobile']},{$value['addtime']},{$value['successtime']},{$value['oss_download']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }

        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data;
        if ($condition) {
            $redis_time = 3600;
            if ($_GET['deal_type'] == 1) {
                $redis_key = 'XF_Debt_List_Download_ZX_Condition_'.$condition['id'];
            } else if ($_GET['deal_type'] == 2) {
                $redis_key = 'XF_Debt_List_Download_PH_Condition_'.$condition['id'];
            } else if ($_GET['deal_type'] == 3) {
                $redis_key = 'XF_Debt_List_Download_GCWJ_Condition_'.$condition['id'];
            } else if ($_GET['deal_type'] == 4) {
                $redis_key = 'XF_Debt_List_Download_ZDX_Condition_'.$condition['id'];
            } else if ($_GET['deal_type'] == 5) {
                $redis_key = 'XF_Debt_List_Download_JYS_Condition_'.$condition['id'];
            }
            $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
            if(!$set){
                Yii::log("{$redis_key} redis download set error","error");
            }
        }
    }
}
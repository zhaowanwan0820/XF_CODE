<?php
use iauth\models\AuthAssignment;
class LoanController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success' , 'Error' , 'addLoanListCondition' , 'addDealLoadBYDealCondition' , 'addDealLoadBYUserCondition' , 'addRechargeWithdrawCondition' , 'EditLoad'
        );
    }

    /**
     * 在途投资明细 列表 批量条件上传
     */
    public function actionaddLoanListCondition()
    {
        set_time_limit(0);
        if (in_array($_GET['download'] , array(1 , 2 , 3 , 4 , 5))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
                $name = '在途投资明细(尊享+普惠) 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款编号');
                $name = '在途投资明细(尊享+普惠) 通过上传借款编号查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
                $name = '在途投资明细(尊享+普惠) 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 4) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '项目名称');
                $name = '在途投资明细(尊享+普惠) 通过上传项目名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 5) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '交易所备案编号');
                $name = '在途投资明细(尊享+普惠) 通过上传交易所备案编号查询 '.date("Y年m月d日 H时i分s秒" , time());
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
            if (!in_array($_POST['deal_type'] , array(1 , 2))) {
                return $this->actionError('请正确选择所属平台' , 5);
            } 
            if (!in_array($_POST['type'] , array(1, 2, 3, 4, 5))) {
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
            if ($_POST['deal_type'] == 1) {
                $model = Yii::app()->fdb;
                $platform = 1;
                $name .= '尊享';
            } else if ($_POST['deal_type'] == 2) {
                $model = Yii::app()->phdb;
                $platform = 2;
                $name .= '普惠';
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
                    $sql = "SELECT id FROM firstp2p_deal_load WHERE user_id IN ({$user_id_str}) AND status = 1";
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
                $sql = "SELECT id FROM firstp2p_deal WHERE id IN ({$user_id_str}) AND deal_status = 4";
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
                $sql = "SELECT id FROM firstp2p_deal_load WHERE deal_id IN ({$user_id_str}) AND status = 1";
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
                $sql = "SELECT id , name FROM firstp2p_deal WHERE name IN ({$user_id_str}) AND deal_status = 4";
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
                $sql = "SELECT id FROM firstp2p_deal_load WHERE deal_id IN ({$user_id_str}) AND status = 1";
                $deal_load = $model->createCommand($sql)->queryColumn();
                if (!$deal_load) {
                    $deal_load = array();
                }
                $data_json = json_encode($deal_load);

            } else if ($type == 4) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行' , 5);
                }
                $name .= ' 通过上传项目名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id , name FROM firstp2p_deal_project WHERE name IN ({$user_id_str}) ";
                $project_res = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$project_res) {
                    return $this->renderPartial('addLoanListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($project_res as $key => $value) {
                    $project_name[] = $value['name'];
                    $project_id[] = $value['id'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $project_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                $user_id_str = implode(',' , $project_id);
                $sql = "SELECT deal_load.id 
                        FROM firstp2p_deal_load AS deal_load 
                        INNER JOIN firstp2p_deal AS deal ON deal_load.deal_id = deal.id 
                        INNER JOIN firstp2p_deal_project AS project ON project.id = deal.project_id 
                        WHERE project.id IN ({$user_id_str}) AND deal.deal_status = 4 AND deal_load.status = 1";
                $deal_load = $model->createCommand($sql)->queryColumn();
                if (!$deal_load) {
                    $deal_load = array();
                }
                $data_json = json_encode($deal_load);

            } else if ($type == 5) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行' , 5);
                }
                $name .= ' 通过上传交易所备案编号查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id , jys_record_number FROM firstp2p_deal WHERE jys_record_number IN ({$user_id_str}) AND deal_status = 4";
                $deal_res = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$deal_res) {
                    return $this->renderPartial('addLoanListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($deal_res as $key => $value) {
                    $deal_name[] = $value['jys_record_number'];
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
                $sql = "SELECT id FROM firstp2p_deal_load WHERE deal_id IN ({$user_id_str}) AND status = 1";
                $deal_load = $model->createCommand($sql)->queryColumn();
                if (!$deal_load) {
                    $deal_load = array();
                }
                $data_json = json_encode($deal_load);
                
            }
            
            $sql = "INSERT INTO xf_deal_load_list_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
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
     * 在途投资明细 列表
     * 提供查询字段：
     * @param user_id       int     用户ID
     * @param borrow_id     int     项目ID
     * @param tender_id     int     投资记录ID
     * @param name          string  项目名称
     * @param status        int     转让状态 1-新建转让中，2-转让成功，3-取消转让，4-过期
     * @param mobile        string  用户手机号
     * @param debt_src      int     债转类型 1-权益兑换、2-债转交易、3债权划扣
     * @param deal_type     int     项目类型[1-尊享 2-普惠供应链]
     * @param limit         int     每页数据显示量 默认10
     * @param page          int     当前页数 默认1
     */
    public function actionGetLoanList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE deal_load.status = 1 ";
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
                    if ($condition['platform'] == 1) {
                        $_POST['deal_type'] = 1;
                    } else if ($condition['platform'] == 2) {
                        $_POST['deal_type'] = 2;
                    }
                }
            }
            if (!in_array($_POST['deal_type'] , array(1 , 2))) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确选择所属平台';
                echo exit(json_encode($result_data));
            }
            if ($_POST['deal_type'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($_POST['deal_type'] == 2) {
                $model = Yii::app()->phdb;
            }
            // 尊享 & 普惠
            if ($_POST['deal_type'] == 1 || $_POST['deal_type'] == 2) {
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
                // 校验项目ID
                if (!empty($_POST['project_id'])) {
                    $project_id = trim($_POST['project_id']);
                    $where     .= " AND deal.project_id = '{$project_id}' ";
                }
                // 校验项目名称
                if (!empty($_POST['project_name'])) {
                    $project_name = trim($_POST['project_name']);
                    $where       .= " AND deal.project_name = '{$project_name}' ";
                }
                // 校验交易所备案号
                if (!empty($_POST['jys_record_number'])) {
                    $jys_record_number = trim($_POST['jys_record_number']);
                    $where            .= " AND deal.jys_record_number = '{$jys_record_number}' ";
                }
                // 校验融资方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $where  .= " AND deal.deal_user_real_name = '{$company}' ";
                }
                // 检验融资经办机构
                if (!empty($_POST['advisory_name'])) {
                    $advisory_name = trim($_POST['advisory_name']);
                    $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$advisory_name}' ";
                    $advisory_id = $model->createCommand($sql)->queryColumn();
                    if ($advisory_id) {
                        $advisory_id_str = implode(',' , $advisory_id);
                        $where .= " AND deal.deal_advisory_id IN ({$advisory_id_str}) ";
                    } else {
                        $where .= " AND deal.deal_advisory_id = -1 ";
                    }
                }
                // 检验融资担保机构
                if (!empty($_POST['agency_name'])) {
                    $agency_name = trim($_POST['agency_name']);
                    $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$agency_name}' ";
                    $agency_id = $model->createCommand($sql)->queryColumn();
                    if ($agency_id) {
                        $agency_id_str = implode(',' , $agency_id);
                        $where .= " AND deal.deal_agency_id IN ({$agency_id_str}) ";
                    } else {
                        $where .= " AND deal.deal_agency_id = -1 ";
                    }
                }
                // 校验用户类型
                if (in_array($_POST['debt_type'] , [1, 2])) {
                    $where .= " AND deal_load.debt_type = '{$_POST['debt_type']}' ";
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
                if ($_POST['deal_load_id']      == '' &&
                    $_POST['user_id']           == '' &&
                    $_POST['deal_id']           == '' &&
                    $_POST['name']              == '' &&
                    $_POST['project_id']        == '' &&
                    $_POST['project_name']      == '' &&
                    $_POST['jys_record_number'] == '' &&
                    $_POST['company']           == '' &&
                    $_POST['advisory_name']     == '' &&
                    $_POST['agency_name']       == '' &&
                    $_POST['debt_type']         == '' &&
                    $_POST['condition_id']      == '' )
                {
                    $redis_time = 86400;
                    if ($_POST['deal_type'] == 1) {
                        $redis_key = 'XF_Deal_Load_List_Count_ZX';
                    } else if ($_POST['deal_type'] == 2) {
                        $redis_key = 'XF_Deal_Load_List_Count_PH';
                    }
                    $redis_val = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                    } else {
                        $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load LEFT JOIN ag_wx_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where}";
                        $count = $model->createCommand($sql)->queryScalar();
                        $set   = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                        if(!$set){
                            Yii::log("{$redis_key} redis count set error","error");
                        }
                    }
                } else {
                    if ($condition) {
                        $redis_time = 86400;
                        if ($_POST['deal_type'] == 1) {
                            $redis_key = 'XF_Deal_Load_List_Count_ZX_Condition_'.$condition['id'];
                        } else if ($_POST['deal_type'] == 2) {
                            $redis_key = 'XF_Deal_Load_List_Count_PH_Condition_'.$condition['id'];
                        }
                        $redis_val = Yii::app()->rcache->get($redis_key);
                        if ($redis_val) {
                            $count = $redis_val;
                            $con_data_str = implode(',' , $con_data);
                            $where .= " AND deal_load.id IN ({$con_data_str}) ";
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
                                    $where_con    = $where." AND deal_load.id IN ({$con_data_str}) ";
                                    $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load LEFT JOIN ag_wx_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where_con} ";
                                    $count_con = $model->createCommand($sql)->queryScalar();
                                    $count += $count_con;
                                }
                                $con_data_str = implode(',' , $con_data);
                                $where .= " AND deal_load.id IN ({$con_data_str}) ";
                                $set = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                                if(!$set){
                                    Yii::log("{$redis_key} redis count set error","error");
                                }
                            } else {
                                $where .= " AND deal_load.id = '' ";
                            }
                        }
                    } else {
                        $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load LEFT JOIN ag_wx_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where} ";
                        $count = $model->createCommand($sql)->queryScalar();
                    }
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
                $sql = "SELECT deal_load.id , deal_load.money , deal_load.wait_capital , deal_load.user_id , deal_load.deal_id , deal_load.create_time , deal_load.wait_interest , deal_load.yes_interest , deal.deal_name , deal.jys_record_number , deal.deal_agency_id , deal.deal_user_id , deal.deal_repay_start_time , deal.deal_advisory_name , deal.deal_agency_name , deal.project_product_class , deal.project_name , deal.deal_loantype , deal.deal_repay_time , deal.deal_rate , deal.deal_user_real_name , MAX(deal.loan_repay_time) AS max_repay_time , deal_load.black_status , deal_load.debt_type 
                        FROM firstp2p_deal_load AS deal_load 
                        LEFT JOIN ag_wx_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                        {$where} GROUP BY deal_load.id ORDER BY deal_load.id DESC ";
                $page_count = ceil($count / $limit);
                if ($page > $page_count) {
                    $page = $page_count;
                }
                if ($page < 1) {
                    $page = 1;
                }
                $pass     = ($page - 1) * $limit;
                $sql     .= " LIMIT {$pass} , {$limit} ";
                if (strlen($sql) > 1048576) {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                    echo exit(json_encode($result_data));
                }
                $list     = $model->createCommand($sql)->queryAll();
                //获取当前账号所有子权限
                $authList = \Yii::app()->user->getState('_auth');
                $edit_status = 0;
                if (!empty($authList) && strstr($authList,'/user/Loan/BlackEditAdd') || empty($authList)) {
                    $edit_status = 1;
                }
                $loantype = Yii::app()->c->xf_config['loantype'];
                $debt_type   = array(1 => '原债权人' , 2 => '新债权人');
                foreach ($list as $key => $value) {
                    $value['deal_type']      = $_POST['deal_type'];
                    $value['create_time']    = date('Y-m-d H:i:s', $value['create_time']);
                    $value['money']          = number_format($value['money'], 2, '.', ',');
                    $value['wait_capital']   = number_format($value['wait_capital'], 2, '.', ',');
                    $value['max_repay_time'] = date('Y-m-d', ($value['max_repay_time'] + 28800));
                    if ($value['deal_loantype'] == 5) {
                        $value['deal_repay_time'] .= '天';
                    } else {
                        $value['deal_repay_time'] .= '个月';
                    }
                    $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                    $value['deal_repay_start_time'] = date('Y-m-d' , ($value['deal_repay_start_time'] + 28800));
                    $value['wait_interest']         = number_format($value['wait_interest'] , 2, '.', ',');
                    $value['real_name']             = '';
                    $value['edit_status']           = $edit_status;
                    $value['debt_type']             = $debt_type[$value['debt_type']];

                    $listInfo[] = $value;

                    $user_id_arr[] = $value['user_id'];
                }
                if ($user_id_arr) {
                    $user_id_str = implode(',' , $user_id_arr);
                    $sql = "SELECT id , real_name FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_infos_res = $model->createCommand($sql)->queryAll();
                    foreach ($user_infos_res as $key => $value) {
                        $user_infos[$value['id']] = $value['real_name'];
                    }
                    foreach ($listInfo as $key => $value) {
                        $listInfo[$key]['real_name'] = $user_infos[$value['user_id']];
                    }
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
        if (!empty($authList) && strstr($authList,'/user/Loan/LoanList2Excel') || empty($authList)) {
            $LoanList2Excel = 1;
        }
        return $this->renderPartial('GetLoanList', array('LoanList2Excel' => $LoanList2Excel));
    }

    /**
     * 在途投资明细 列表 导出
     */
    public function actionLoanList2Excel()
    {
        if (!empty($_GET)) {
            set_time_limit(0);
            // 条件筛选
            $where = "WHERE deal_load.status = 1 ";
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_list_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    $con_data = json_decode($condition['data_json'] , true);
                    if ($condition['platform'] == 1) {
                        $_GET['deal_type'] = 1;
                    } else {
                        $_GET['deal_type'] = 2;
                    }
                    if ($_GET['deal_type'] == 1) {
                        $redis_key = 'XF_Deal_Load_List_Download_ZX_Condition_'.$condition['id'];
                    } else if ($_GET['deal_type'] == 2) {
                        $redis_key = 'XF_Deal_Load_List_Download_PH_Condition_'.$condition['id'];
                    }
                    $check = Yii::app()->rcache->get($redis_key);
                    if($check){
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                }
            }
            if (!in_array($_GET['deal_type'] , array(1 , 2))) {
                echo '<h1>请正确选择所属平台</h1>';
                exit;
            }
            if ($_GET['deal_type'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($_GET['deal_type'] == 2) {
                $model = Yii::app()->phdb;
            }
            // 尊享 & 普惠
            if ($_GET['deal_type'] == 1 || $_GET['deal_type'] == 2) {
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
                // 校验项目ID
                if (!empty($_GET['project_id'])) {
                    $project_id = trim($_GET['project_id']);
                    $where     .= " AND deal.project_id = '{$project_id}' ";
                }
                // 校验项目名称
                if (!empty($_GET['project_name'])) {
                    $project_name = trim($_GET['project_name']);
                    $where       .= " AND deal.project_name = '{$project_name}' ";
                }
                // 校验交易所备案号
                if (!empty($_GET['jys_record_number'])) {
                    $jys_record_number = trim($_GET['jys_record_number']);
                    $where            .= " AND deal.jys_record_number = '{$jys_record_number}' ";
                }
                // 校验融资方名称
                if (!empty($_GET['company'])) {
                    $company = trim($_GET['company']);
                    $where  .= " AND deal.deal_user_real_name = '{$company}' ";
                }
                // 检验融资经办机构
                if (!empty($_GET['advisory_name'])) {
                    $advisory_name = trim($_GET['advisory_name']);
                    $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$advisory_name}' ";
                    $advisory_id = $model->createCommand($sql)->queryColumn();
                    if ($advisory_id) {
                        $advisory_id_str = implode(',' , $advisory_id);
                        $where .= " AND deal.deal_advisory_id IN ({$advisory_id_str}) ";
                    } else {
                        $where .= " AND deal.deal_advisory_id = -1 ";
                    }
                }
                // 检验融资担保机构
                if (!empty($_GET['agency_name'])) {
                    $agency_name = trim($_GET['agency_name']);
                    $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$agency_name}' ";
                    $agency_id = $model->createCommand($sql)->queryColumn();
                    if ($agency_id) {
                        $agency_id_str = implode(',' , $agency_id);
                        $where .= " AND deal.deal_agency_id IN ({$agency_id_str}) ";
                    } else {
                        $where .= " AND deal.deal_agency_id = -1 ";
                    }
                }
                // 校验用户类型
                if (in_array($_GET['debt_type'] , [1, 2])) {
                    $where .= " AND deal_load.debt_type = '{$_GET['debt_type']}' ";
                }
                if ($_GET['deal_load_id']      == '' &&
                    $_GET['user_id']           == '' &&
                    $_GET['deal_id']           == '' &&
                    $_GET['name']              == '' &&
                    $_GET['project_id']        == '' &&
                    $_GET['project_name']      == '' &&
                    $_GET['jys_record_number'] == '' &&
                    $_GET['company']           == '' &&
                    $_GET['advisory_name']     == '' &&
                    $_GET['agency_name']       == '' &&
                    $_GET['debt_type']         == '' &&
                    $_GET['condition_id']      == '' )
                {
                    echo '<h1>请输入至少一个查询条件</h1>';
                    exit;
                }
                if ($condition) {
                    $redis_time = 86400;
                    if ($_GET['deal_type'] == 1) {
                        $redis_key = 'XF_Deal_Load_List_Count_ZX_Condition_'.$condition['id'];
                    } else if ($_GET['deal_type'] == 2) {
                        $redis_key = 'XF_Deal_Load_List_Count_PH_Condition_'.$condition['id'];
                    }
                    $redis_val = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',' , $con_data);
                        $where .= " AND deal_load.id IN ({$con_data_str}) ";
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
                                $where_con    = $where." AND deal_load.id IN ({$con_data_str}) ";
                                $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load LEFT JOIN ag_wx_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where_con} ";
                                $count_con = $model->createCommand($sql)->queryScalar();
                                $count += $count_con;
                            }
                            $con_data_str = implode(',' , $con_data);
                            $where .= " AND deal_load.id IN ({$con_data_str}) ";
                            $set = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                            if(!$set){
                                Yii::log("{$redis_key} redis count set error","error");
                            }
                        } else {
                            $where .= " AND deal_load.id = '' ";
                        }
                    }
                } else {
                    $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load LEFT JOIN ag_wx_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where} ";
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
                    $sql = "SELECT deal_load.id , deal_load.money , deal_load.wait_capital , deal_load.user_id , deal_load.deal_id , deal_load.create_time , deal_load.wait_interest , deal_load.yes_interest , deal.deal_name , deal.jys_record_number , deal.deal_agency_id , deal.deal_user_id , deal.deal_repay_start_time , deal.deal_advisory_name , deal.deal_agency_name , deal.project_product_class , deal.project_name , deal.deal_loantype , deal.deal_repay_time , deal.deal_rate , deal.deal_user_real_name , MAX(deal.loan_repay_time) AS max_repay_time , deal_load.debt_type 
                            FROM firstp2p_deal_load AS deal_load 
                            LEFT JOIN ag_wx_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                            {$where} GROUP BY deal_load.id LIMIT {$pass} , 500 ";
                    $list = $model->createCommand($sql)->queryAll();

                    $loantype = Yii::app()->c->xf_config['loantype'];
                    $debt_type   = array(1 => '原债权人' , 2 => '新债权人');
                    foreach ($list as $key => $value) {
                        $value['deal_type']      = $_POST['deal_type'];
                        $value['create_time']    = date('Y-m-d H:i:s', $value['create_time']);
                        // $value['money']          = number_format($value['money'], 2, '.', ',');
                        // $value['wait_capital']   = number_format($value['wait_capital'], 2, '.', ',');
                        $value['max_repay_time'] = date('Y-m-d', ($value['max_repay_time'] + 28800));
                        if ($value['deal_loantype'] == 5) {
                            $value['deal_repay_time'] .= '天';
                        } else {
                            $value['deal_repay_time'] .= '个月';
                        }
                        $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                        $value['deal_repay_start_time'] = date('Y-m-d' , ($value['deal_repay_start_time'] + 28800));
                        // $value['wait_interest']         = number_format($value['wait_interest'] , 2, '.', ',');
                        $value['real_name']             = '';
                        $value['debt_type']             = $debt_type[$value['debt_type']];

                        $listInfo[] = $value;

                        $user_id_arr[] = $value['user_id'];
                    }
                    if ($user_id_arr) {
                        $user_id_str = implode(',' , $user_id_arr);
                        $sql = "SELECT id , real_name FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                        $user_infos_res = $model->createCommand($sql)->queryAll();
                        foreach ($user_infos_res as $key => $value) {
                            $user_infos[$value['id']] = $value['real_name'];
                        }
                        foreach ($listInfo as $key => $value) {
                            $listInfo[$key]['real_name'] = $user_infos[$value['user_id']];
                        }
                    }
                }
            }
            if ($_GET['deal_type'] == 1) {
                $name = '在途投资明细(尊享+普惠) 尊享 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            } else {
                $name = '在途投资明细(尊享+普惠) 普惠 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            }
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "投资记录ID,借款编号,借款标题,项目名称,交易所备案编号,用户ID,用户姓名,投资时间,投资金额,产品大类,借款期限,还款方式,计划最大回款时间,年化收益率,计息时间,剩余待还本金,剩余待还利息,融资方ID,融资方名称,融资经办机构,融资担保机构,用户类型\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['deal_id']},{$value['deal_name']},{$value['project_name']},{$value['jys_record_number']},{$value['user_id']},{$value['real_name']},{$value['create_time']},{$value['money']},{$value['project_product_class']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['max_repay_time']},{$value['deal_rate']},{$value['deal_repay_start_time']},{$value['wait_capital']},{$value['wait_interest']},{$value['deal_user_id']},{$value['deal_user_real_name']},{$value['deal_advisory_name']},{$value['deal_agency_name']},{$value['debt_type']}\n";
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
                    $redis_key = 'XF_Deal_Load_List_Download_ZX_Condition_'.$condition['id'];
                } else if ($_GET['deal_type'] == 2) {
                    $redis_key = 'XF_Deal_Load_List_Download_PH_Condition_'.$condition['id'];
                }
                $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
                if(!$set){
                    Yii::log("{$redis_key} redis download set error","error");
                }
            }
        }
    }

    /**
     * 编辑加入还是取消黑名单
     */
    public function actionEditLoad()
    {
        $load_id = $_REQUEST['loan_id'];
        $dealtype = $_REQUEST['deal_type'];
        $black_status = $_REQUEST['status'];
        $join_reason = $_REQUEST['join_reason'];
        if (!in_array($black_status, [1, 2]) || !is_numeric($black_status) || !is_numeric($load_id)) {
            $this->echoJson([], 1, "数据异常");
        }
        //添加黑名单验证是否填写理由
        if($black_status == 2){
            if(empty($join_reason)){
                $this->echoJson([], 1, "请填写加入黑名单理由");
            }
        }
        if ($dealtype == 1) {
            //尊享库
            $model = Yii::app()->fdb;
            $table = 'firstp2p';
        } elseif ($dealtype == 2) {
            //普惠库
            $model = Yii::app()->phdb;
            $table = 'firstp2p';
        } elseif (in_array($dealtype , [3, 4, 5])) {
            $model = Yii::app()->offlinedb;
            $table = 'offline';
        }
        if (!empty($load_id)) {
            $now = time();
            $join_reason = !empty($join_reason) ? $join_reason : '';
            $dealLoadInfo = $model->createCommand("update {$table}_deal_load set black_status = {$black_status},update_black_time= '{$now}',join_reason = '{$join_reason}' WHERE id={$load_id}")->execute();
            if (!$dealLoadInfo) {
                $this->echoJson([], 1, "更新失败");
            }
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 编辑取消黑名单
     * @return string
     * @throws CException
     */
    public function actionBlackEditAdd()
    {

        return $this->renderPartial('BlackEditAdd');
    }

    /**
     * 待还款列表撤销操作
     */
    public function actionSetrevoke()
    {
        $ag_wx_repayment_plan_id = $_POST['id'];
        $task_remark = $_POST['task_remark'];
        if(!is_numeric($ag_wx_repayment_plan_id) || empty($ag_wx_repayment_plan_id)){
            $this->echoJson([], 1, "还款计划ID错误");
        }
        if(empty($task_remark)){
            $this->echoJson([], 1, "请填写撤销原因");
        }
        $model = Yii::app()->fdb;
        $repaymentPlanInfo = $model->createCommand("select * from ag_wx_repayment_plan where id = $ag_wx_repayment_plan_id")->queryRow();
        if(empty($repaymentPlanInfo)){
            $this->echoJson([], 1, "还款计划不存在");
        }
        if($repaymentPlanInfo['status'] != 0){
            $this->echoJson([], 1, "只有待审核状态可以进行撤销");
        }
        $sql = ItzUtil::get_update_db_sql("ag_wx_repayment_plan", ['status' => 5,'task_remark' => $task_remark], "id = {$ag_wx_repayment_plan_id} and status = 0");
        $saveret = $model->createCommand($sql)->execute();
        if (!$saveret) {
            $this->echoJson([], 1, "还款计划更新失败，失败ID：{$ag_wx_repayment_plan_id}");
        }
        $this->echoJson([], 0, "撤销成功");
    }

    /**
     * 债转邮件通知记录 列表
     */
    public function actionEmailNoticeList()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " 1 = 1 ";
            // 校验平台ID
            if (!empty($_POST['platform_id'])) {
                $platform = intval($_POST['platform_id']);
                $where   .= " AND platform_id = {$platform} ";
            }
            // 校验担保方名称
            if (!empty($_POST['agency_name'])) {
                $agency = trim($_POST['agency_name']);
                $where .= " AND agency_name = '{$agency}' ";
            }
            // 校验咨询方名称
            if (!empty($_POST['advisory_name'])) {
                $advisory = trim($_POST['advisory_name']);
                $where   .= " AND advisory_name = '{$advisory}' ";
            }
            // 校验债务方名称
            if (!empty($_POST['company_name'])) {
                $company = trim($_POST['company_name']);
                $where  .= " AND company_name = '{$company}' ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status']);
                $where .= " AND status = {$sta} ";
            }
            // 校验债转起始时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND debt_start_time >= {$start} ";
            }
            // 校验债转结束时间
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND debt_end_time <= {$end} ";
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
            $sql   = "SELECT count(id) AS count FROM ag_wx_email_notice WHERE {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql  = "SELECT * FROM ag_wx_email_notice WHERE {$where} ORDER BY id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            // 获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            $start_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Loan/StartEmailNotice') || empty($authList)) {
                $start_status = 1;
            }
            $info_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Loan/EmailNoticeInfo') || empty($authList)) {
                $info_status = 1;
            }
            $platform_id[1] = '尊享';
            $platform_id[2] = '普惠';
            $status[1] = '待启动';
            $status[2] = '已启动';
            $status[3] = '发送中';
            $status[4] = '发送完成';
            foreach ($list as $key => $value){
                $value['start_status'] = $start_status;
                $value['info_status']  = $info_status;
                $value['platform_id']  = $platform_id[$value['platform_id']];
                $value['status_name']  = $status[$value['status']];
                if ($value['debt_start_time'] != 0) {
                    $value['debt_start_time'] = date('Y-m-d H:i:s' , $value['debt_start_time']);
                } else {
                    $value['debt_start_time'] = '——';
                }
                if ($value['debt_end_time'] != 0) {
                    $value['debt_end_time'] = date('Y-m-d H:i:s' , $value['debt_end_time']);
                } else {
                    $value['debt_end_time'] = '——';
                }
                if ($value['add_time'] != 0) {
                    $value['add_time'] = date('Y-m-d H:i:s' , $value['add_time']);
                } else {
                    $value['add_time'] = '——';
                }
                if ($value['success_time'] != 0) {
                    $value['success_time'] = date('Y-m-d H:i:s' , $value['success_time']);
                } else {
                    $value['success_time'] = '——';
                }
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
        $add_status = 0;
        if (!empty($authList) && strstr($authList,'/user/Loan/AddEmailNotice') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('EmailNoticeList' , array('add_status' => $add_status));
    }

    /**
     * 债转邮件通知记录 新增
     */
    public function actionAddEmailNotice()
    {
        if (!empty($_POST)) {
            if (empty($_POST['platform_id']) || !in_array($_POST['platform_id'], array(1 , 2))) {
                $this->echoJson(array() , 1, '请正确选择所属平台');
            }
            if ($_POST['platform_id'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($_POST['platform_id'] == 2) {
                $model = Yii::app()->phdb;
            }
            if (empty($_POST['agency_name']) && empty($_POST['advisory_name']) && empty($_POST['company_name'])) {
                $this->echoJson(array() , 2, '担保方名称、咨询方名称、债务方名称需要至少填写一项');
            }
            $where = '';
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $sql         = "SELECT id FROM firstp2p_deal_agency WHERE type = 1 AND name = '{$agency_name}' AND is_effect = 1";
                $agency_id   = $model->createCommand($sql)->queryScalar();
                if (!$agency_id) {
                    $this->echoJson(array() , 3, '担保方名称输入错误');
                }
                $where      .= " AND deal.agency_id = {$agency_id} ";
            } else {
                $agency_name = '';
                $agency_id  = 0;
            }
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $sql           = "SELECT id FROM firstp2p_deal_agency WHERE type = 2 AND name = '{$advisory_name}' AND is_effect = 1";
                $advisory_id   = $model->createCommand($sql)->queryScalar();
                if (!$advisory_id) {
                    $this->echoJson(array() , 4, '咨询方名称输入错误');
                }
                $where        .= " AND deal.advisory_id = {$advisory_id} ";
            } else {
                $advisory_name = '';
                $advisory_id   = 0;
            }
            if (!empty($_POST['company_name'])) {
                $company_name = trim($_POST['company_name']);
                $sql = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company_name}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                $com_a = Yii::app()->fdb->createCommand($sql)->queryScalar();
                $sql = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company_name}' AND e.company_purpose = 2 AND u.user_type = 1";
                $com_b = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if (!$com_a && !$com_b) {
                    $this->echoJson(array() , 5, '债务方名称输入错误');
                }
                if ($com_a && $com_b) {
                    if ($com_a != $com_b) {
                        $this->echoJson(array() , 6, '债务方名称查询到的信息不一致');
                    }
                    $user_id = $com_a;
                } else {
                    if ($com_a) {
                        $user_id = $com_a;
                    }
                    if ($com_b) {
                        $user_id = $com_b;
                    }
                }
                $where .= " AND deal.user_id = {$user_id} ";
            } else {
                $company_name = '';
                $user_id      = 0;
            }
            if (!empty($_POST['start_time'])) {
                $debt_start_time = strtotime($_POST['start_time']);
                $where .= " AND debt.successtime >= {$debt_start_time} ";
            } else {
                $debt_start_time = 0;
            }
            if (!empty($_POST['end_time'])) {
                $debt_end_time = strtotime($_POST['end_time']);
                $where .= " AND debt.successtime <= {$debt_end_time} ";
            } else {
                $debt_end_time = 0;
            }
            if ($debt_end_time < $debt_start_time) {
                $this->echoJson(array() , 7, '债转结束时间不可小于债转起始时间');
            }
            $sql = "SELECT debt.id FROM firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id WHERE debt.status = 2 AND debt.is_mail = 0 AND debt.email_notice_id = 0 {$where} ";
            $debt_id_arr = $model->createCommand($sql)->queryColumn();
            $count = count($debt_id_arr);
            if ($count == 0) {
                $this->echoJson(array() , 8, '未查询到未通知的债转信息');
            }
            
            if (empty($_POST['email_address'])) {
                $this->echoJson(array() , 9, '请输入接收邮件邮箱');
            }
            $email = explode(';', $_POST['email_address']);
            foreach ($email as $key => $value) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->echoJson(array() , 10, '邮箱地址输入错误');
                }
            }
            Yii::app()->fdb->beginTransaction();
            $model->beginTransaction();
            $time = time();
            $sql = "INSERT INTO ag_wx_email_notice (platform_id , agency_name , agency_id , advisory_name , advisory_id , company_name , user_id , status , email_address , debt_start_time , debt_end_time , debt_number , add_time) VALUES ({$_POST['platform_id']} , '{$agency_name}' , {$agency_id} , '{$advisory_name}' , {$advisory_id} , '{$company_name}' , {$user_id} , 1 , '{$_POST['email_address']}' , {$debt_start_time} , {$debt_end_time} , {$count} , {$time}) ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            $email_notice_id = Yii::app()->fdb->getLastInsertID();
            
            $debt_id_str = implode(',' , $debt_id_arr);
            $sql = "UPDATE firstp2p_debt SET email_notice_id = {$email_notice_id} WHERE id IN ({$debt_id_str}) ";
            $update = $model->createCommand($sql)->execute();
            if (!$result || !$update) {
                Yii::app()->fdb->rollback();
                $model->rollback();
                $this->echoJson(array() , 11, '新增失败');
            }
            Yii::app()->fdb->commit();
            $model->commit();
            $this->echoJson(array() , 0, '新增成功');
        }

        return $this->renderPartial('AddEmailNotice');
    }

    /**
     * 债转邮件通知记录 启动
     */
    public function actionStartEmailNotice()
    {
        if (!empty($_POST['id'])) {
            if (!is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1, 'ID格式错误');
            }
            // $sql   = "SELECT * FROM ag_wx_email_notice WHERE status IN (2 , 3)";
            // $check = Yii::app()->fdb->createCommand($sql)->queryRow();
            // if ($check) {
            //     $this->echoJson(array() , 2, '已存在发送中的通知记录，请等待其结束后再启动');
            // }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_email_notice WHERE id = {$id}";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 3, 'ID输入错误');
            }
            if ($res['status'] != 1) {
                $this->echoJson(array() , 4, '此通知记录的状态错误');
            }
            if ($res['platform_id'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($res['platform_id'] == 2) {
                $model = Yii::app()->phdb;
            }
            $where = '';
            if ($res['agency_id'] != 0) {
                $where .= " AND deal.agency_id = {$res['agency_id']} ";
            }
            if ($res['advisory_id'] != 0) {
                $where .= " AND deal.advisory_id = {$res['advisory_id']} ";
            }
            if ($res['user_id'] != 0) {
                $where .= " AND deal.user_id = {$res['user_id']} ";
            }
            if ($res['debt_start_time'] != 0) {
                $where .= " AND debt.successtime >= {$res['debt_start_time']} ";
            }
            if ($res['debt_end_time'] != 0) {
                $where .= " AND debt.successtime <= {$res['debt_end_time']} ";
            }
            $sql = "SELECT count(debt.id) AS count FROM firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id WHERE debt.status = 2 AND debt.is_mail = 0 AND debt.email_notice_id = {$res['id']} {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                $this->echoJson(array() , 5, '未查询到相关的债转信息');
            }
            if ($count != $res['debt_number']) {
                $this->echoJson(array() , 6, '此通知记录的债转总条数错误');
            }
            $time       = time();
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $op_ip      = Yii::app()->request->userHostAddress;
            $sql = "UPDATE ag_wx_email_notice SET status = 2 , op_user_id = {$op_user_id} , op_ip = '{$op_ip}' , op_time = {$time} WHERE id = {$res['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array() , 8, '启动失败');
            }
            $this->echoJson(array() , 0, '启动成功');
        }
    }

    /**
     * 债转邮件通知记录 详情
     */
    public function actionEmailNoticeInfo()
    {
        if (!empty($_POST['id'])) {

            if (!is_numeric($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = 'ID格式错误';
                echo exit(json_encode($result_data));
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_email_notice WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 2;
                $result_data['info']  = 'ID输入错误';
                echo exit(json_encode($result_data));
            }
            if ($res['platform_id'] == 1) {
                $model = Yii::app()->fdb;
                $platform = '尊享';
            } else if ($res['platform_id'] == 2) {
                $model = Yii::app()->phdb;
                $platform = '普惠';
            }
            // 条件筛选
            $where = " email_notice_id = {$id} ";
            // 校验债转ID
            if (!empty($_POST['debt_id'])) {
                $debt_id = intval($_POST['debt_id']);
                $where  .= " AND id = {$debt_id} ";
            }
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND user_id = {$user_id} ";
            }
            // 校验债转类型
            if (!empty($_POST['debt_src'])) {
                $d_src  = intval($_POST['debt_src']);
                $where .= " AND debt_src = {$d_src} ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status'])-1;
                $where .= " AND is_mail = {$sta} ";
            }
            // 校验债转时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND successtime >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND successtime <= {$end} ";
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
            $sql   = "SELECT count(id) AS count FROM firstp2p_debt WHERE {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql  = "SELECT id , is_mail , user_id , money , debt_src , successtime FROM firstp2p_debt WHERE {$where} ORDER BY id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();

            $status[0] = '未通知';
            $status[1] = '已通知';

            $debt_src[1] = '权益兑换';
            $debt_src[2] = '债转交易';
            $debt_src[3] = '债权划扣';
            $debt_src[4] = '一键下车';
            $debt_src[5] = '一键下车退回';
            $debt_src[6] = '权益兑换退回';
            foreach ($list as $key => $value){
                $value['platform']    = $platform;
                $value['status']      = $status[$value['is_mail']];
                $value['debt_src']    = $debt_src[$value['debt_src']];
                $value['money']       = number_format($value['money'] , 2 , '.' , ',');
                $value['successtime'] = date('Y-m-d H:i:s' , $value['successtime']);

                $listInfo[] = $value;
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('EmailNoticeInfo');
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
     * 上传xls文件
     * @param name  string  文件名称
     * @return array
     */
    private function upload_xls($name)
    {
        $file  = $_FILES[$name];
        $types = array('xls');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的xls文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的xls文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => 'xls文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有xls文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => 'xls文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => 'xls文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if(!in_array($file_type, $types)){
            return array('code' => 2007 , 'info' => 'xls文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000,99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/select_condition/' . $dir)) {
            $mkdir = mkdir('./upload/select_condition/' . $dir , 0777 , true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建xls文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/select_condition/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存xls文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存xls文件失败' , 'data' => '');
        }
    }

    /**
     * 在途项目明细 列表
     */
    public function actionDealLoadBYDeal()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE stat_repay.repay_status = 0 ";
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
                    if ($condition['platform'] == 1) {
                        $_POST['platform'] = 1;
                    } else {
                        $_POST['platform'] = 2;
                    }
                }
            }
            if ($_POST['platform'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($_POST['platform'] == 2) {
                $model = Yii::app()->phdb;
            } else {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '所属平台输入错误';
                echo exit(json_encode($result_data));
            }
            // 校验借款编号
            if (!empty($_POST['deal_id'])) {
                $deal_id = trim($_POST['deal_id']);
                $where  .= " AND stat_repay.deal_id = '{$deal_id}' ";
            }
            //检验借款标题
            if (!empty($_POST['deal_name'])) {
                $deal_name = trim($_POST['deal_name']);
                $where    .= " AND stat_repay.deal_name = '{$deal_name}' ";
            }
            // 校验项目ID
            if (!empty($_POST['project_id'])) {
                $project_id = trim($_POST['project_id']);
                $where     .= " AND stat_repay.project_id = '{$project_id}' ";
            }
            // 校验项目名称
            if (!empty($_POST['project_name'])) {
                $project_name = trim($_POST['project_name']);
                $where       .= " AND stat_repay.project_name = '{$project_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_POST['jys_record_number'])) {
                $jys_record_number = trim($_POST['jys_record_number']);
                $where            .= " AND stat_repay.jys_record_number = '{$jys_record_number}' ";
            }
            // 校验融资方名称
            if (!empty($_POST['user_name'])) {
                $user_name = trim($_POST['user_name']);
                $where    .= " AND stat_repay.deal_user_real_name = '{$user_name}' ";
            }
            //检验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$advisory_name}' ";
                $advisory_id = $model->createCommand($sql)->queryColumn();
                if ($advisory_id) {
                    $advisory_id_str = implode(',' , $advisory_id);
                    $where .= " AND stat_repay.deal_advisory_id IN ({$advisory_id_str}) ";
                } else {
                    $where .= " AND stat_repay.deal_advisory_id = -1 ";
                }
            }
            //检验融资担保机构
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND stat_repay.deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND stat_repay.deal_agency_id = -1 ";
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
            if (empty($_POST['deal_id']) && empty($_POST['deal_name']) && empty($_POST['project_id']) && empty($_POST['project_name']) && empty($_POST['jys_record_number']) && empty($_POST['user_name']) && empty($_POST['advisory_name']) && empty($_POST['agency_name']) && empty($_POST['condition_id'])) {
                $redis_time = 86400;
                if ($_POST['platform'] == 1) {
                    $redis_key = 'XF_Deal_Load_BY_Deal_Count_ZX';
                } else if ($_POST['platform'] == 2) {
                    $redis_key = 'XF_Deal_Load_BY_Deal_Count_PH';
                }
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                } else {
                    $sql   = "SELECT count(DISTINCT stat_repay.deal_id) AS count FROM ag_wx_stat_repay AS stat_repay {$where}";
                    $count = $model->createCommand($sql)->queryScalar();
                    $set   = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                    if(!$set){
                        Yii::log("{$redis_key} redis count set error","error");
                    }
                }
            } else {
                if ($condition) {
                    $redis_time = 86400;
                    if ($_POST['platform'] == 1) {
                        $redis_key = 'XF_Deal_Load_BY_Deal_Count_ZX_Condition_'.$condition['id'];
                    } else if ($_POST['platform'] == 2) {
                        $redis_key = 'XF_Deal_Load_BY_Deal_Count_PH_Condition_'.$condition['id'];
                    }
                    $redis_val  = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',' , $con_data);
                        $where       .= " AND stat_repay.deal_id IN ({$con_data_str}) ";
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
                                $where_con    = $where." AND stat_repay.deal_id IN ({$con_data_str}) ";
                                $sql          = "SELECT count(DISTINCT stat_repay.deal_id) AS count FROM ag_wx_stat_repay AS stat_repay {$where_con}";
                                $count_con    = $model->createCommand($sql)->queryScalar();
                                $count       += $count_con;
                            }
                            $con_data_str = implode(',' , $con_data);
                            $where       .= " AND stat_repay.deal_id IN ({$con_data_str}) ";
                            $set          = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                            if(!$set){
                                Yii::log("{$redis_key} redis count set error","error");
                            }
                        } else {
                            $where .= " AND stat_repay.deal_id = '' ";
                        }
                    }
                } else {
                    $sql   = "SELECT count(DISTINCT stat_repay.deal_id) AS count FROM ag_wx_stat_repay AS stat_repay {$where}";
                    $count = $model->createCommand($sql)->queryScalar();
                }
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
            $sql = "SELECT stat_repay.deal_id , stat_repay.deal_name , stat_repay.project_id , stat_repay.project_name , stat_repay.jys_record_number , stat_repay.project_product_class , stat_repay.deal_loantype , stat_repay.deal_repay_time , stat_repay.deal_user_id , stat_repay.deal_user_real_name , stat_repay.deal_advisory_id , stat_repay.deal_advisory_name , stat_repay.deal_agency_id , stat_repay.deal_agency_name , stat_repay.jys_name , MAX(stat_repay.loan_repay_time) AS max_repay_time , MIN(stat_repay.loan_repay_time) AS min_repay_time , SUM(CASE WHEN stat_repay.repay_type = 1 THEN stat_repay.repay_amount - stat_repay.repaid_amount ELSE 0 END) AS wait_capital , SUM(CASE WHEN stat_repay.repay_type = 2 THEN stat_repay.repay_amount - stat_repay.repaid_amount ELSE 0 END) AS wait_interest , SUM(CASE WHEN stat_repay.loan_repay_time < {$time} AND stat_repay.repay_type = 1 THEN stat_repay.repay_amount - stat_repay.repaid_amount ELSE 0 END) AS overdue_capital , SUM(CASE WHEN stat_repay.loan_repay_time < {$time} AND stat_repay.repay_type = 2 THEN stat_repay.repay_amount - stat_repay.repaid_amount ELSE 0 END) AS overdue_interest , deal.investor_number , deal.investor_wait_capital , deal.recipient_number , deal.recipient_wait_capital FROM ag_wx_stat_repay AS stat_repay LEFT JOIN firstp2p_deal AS deal ON stat_repay.deal_id = deal.id {$where} GROUP BY stat_repay.deal_id ORDER BY stat_repay.deal_id DESC ";
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
                if ($value['deal_loantype'] == 5) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }
                $value['deal_loantype']          = $loantype[$value['deal_loantype']];
                $value['max_repay_time']         = date("Y-m-d" , ($value['max_repay_time'] + 28800));
                $value['wait_capital']           = number_format($value['wait_capital'], 2, '.', ',');
                $value['wait_interest']          = number_format($value['wait_interest'], 2, '.', ',');
                $value['overdue_day']            = round(($time - $value['min_repay_time']) / 86400);
                $value['overdue_capital']        = number_format($value['overdue_capital'], 2, '.', ',');
                $value['overdue_interest']       = number_format($value['overdue_interest'], 2, '.', ',');
                $value['investor_wait_capital']  = number_format($value['investor_wait_capital'], 2, '.', ',');
                $value['recipient_wait_capital'] = number_format($value['recipient_wait_capital'], 2, '.', ',');
                
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
        if (!empty($authList) && strstr($authList,'/user/Loan/DealLoadBYDeal2Excel') || empty($authList)) {
            $DealLoadBYDeal2Excel = 1;
        }
        return $this->renderPartial('DealLoadBYDeal' , array('DealLoadBYDeal2Excel' => $DealLoadBYDeal2Excel));
    }

    /**
     * 在途项目明细 列表 批量条件上传
     */
    public function actionaddDealLoadBYDealCondition()
    {
        set_time_limit(0);
        if (in_array($_GET['download'] , array(1 , 2 , 3 , 4 , 5 , 6))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资方名称');
                $name = '在途项目明细(尊享+普惠) 通过上传融资方名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资经办机构名称');
                $name = '在途项目明细(尊享+普惠) 通过上传融资经办机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资担保机构名称');
                $name = '在途项目明细(尊享+普惠) 通过上传融资担保机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 4) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
                $name = '在途项目明细(尊享+普惠) 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 5) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '项目名称');
                $name = '在途项目明细(尊享+普惠) 通过上传项目名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 6) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '交易所备案编号');
                $name = '在途项目明细(尊享+普惠) 通过上传交易所备案编号查询 '.date("Y年m月d日 H时i分s秒" , time());
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
            if (!in_array($_POST['deal_type'] , array(1, 2))) {
                return $this->actionError('请正确选择所属平台' , 5);
            } 
            if (!in_array($_POST['type'] , array(1, 2, 3, 4, 5, 6))) {
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
            $name = '';
            if ($_POST['deal_type'] == 1) {
                $model = Yii::app()->fdb;
                $platform = 1;
                $name .= '尊享';
            } else {
                $model = Yii::app()->phdb;
                $platform = 2;
                $name .= '普惠';
            }
            if ($type == 1) {

                $name .= ' 通过上传融资方名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $com_a = $model->createCommand("SELECT deal_id , deal_user_real_name FROM ag_wx_stat_repay WHERE deal_user_real_name IN ({$user_id_str}) ")->queryAll();
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
                $sql = "SELECT deal_id , deal_advisory_name FROM ag_wx_stat_repay WHERE deal_advisory_name IN ({$user_id_str}) ";
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
                $sql = "SELECT deal_id , deal_agency_name FROM ag_wx_stat_repay WHERE deal_agency_name IN ({$user_id_str}) ";
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

            } else if ($type == 4) {

                $name .= ' 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT deal_id , deal_name FROM ag_wx_stat_repay WHERE deal_name IN ({$user_id_str}) ";
                $user_id_data = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDealLoadBYDealCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($user_id_data as $key => $value) {
                    $deal_id[] = $value['deal_id'];
                    $deal_name[] = $value['deal_name'];
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

                $data_json = json_encode($deal_id);

            } else if ($type == 5) {

                $name .= ' 通过上传项目名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT deal_id , project_name FROM ag_wx_stat_repay WHERE project_name IN ({$user_id_str}) ";
                $user_id_data = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDealLoadBYDealCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($user_id_data as $key => $value) {
                    $deal_id[] = $value['deal_id'];
                    $project_name[] = $value['project_name'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $project_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;

                $data_json = json_encode($deal_id);

            } else if ($type == 6) {

                $name .= ' 通过上传交易所备案编号查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT deal_id , jys_record_number FROM ag_wx_stat_repay WHERE jys_record_number IN ({$user_id_str}) ";
                $user_id_data = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDealLoadBYDealCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($user_id_data as $key => $value) {
                    $deal_id[] = $value['deal_id'];
                    $jys_record_number[] = $value['jys_record_number'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $jys_record_number)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;

                $data_json = json_encode($deal_id);
            }
            
            $sql = "INSERT INTO xf_deal_list_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
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
     * 在途项目明细 列表 导出
     */
    public function actionDealLoadBYDeal2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            $where = " WHERE stat_repay.repay_status = 0 ";
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_list_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    $con_data = json_decode($condition['data_json'] , true);
                    if ($condition['platform'] == 1) {
                        $_GET['platform'] = 1;
                    } else {
                        $_GET['platform'] = 2;
                    }
                    if ($_GET['platform'] == 1) {
                        $redis_key = 'XF_Deal_Load_BY_Deal_Download_ZX_Condition_'.$condition['id'];
                    } else if ($_GET['platform'] == 2) {
                        $redis_key = 'XF_Deal_Load_BY_Deal_Download_PH_Condition_'.$condition['id'];
                    }
                    $check = Yii::app()->rcache->get($redis_key);
                    if($check){
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                }
            }

            if (empty($_GET['deal_id']) && empty($_GET['deal_name']) && empty($_GET['project_id']) && empty($_GET['project_name']) && empty($_GET['jys_record_number']) && empty($_GET['user_name']) && empty($_GET['advisory_name']) && empty($_GET['agency_name']) && empty($_GET['condition_id'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            if ($_GET['platform'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($_GET['platform'] == 2) {
                $model = Yii::app()->phdb;
            } else {
                echo '<h1>所属平台输入错误</h1>';
                exit;
            }
            // 校验借款编号
            if (!empty($_GET['deal_id'])) {
                $deal_id = trim($_GET['deal_id']);
                $where  .= " AND stat_repay.deal_id = '{$deal_id}' ";
            }
            //检验借款标题
            if (!empty($_GET['deal_name'])) {
                $deal_name = trim($_GET['deal_name']);
                $where    .= " AND stat_repay.deal_name = '{$deal_name}' ";
            }
            // 校验项目ID
            if (!empty($_GET['project_id'])) {
                $project_id = trim($_GET['project_id']);
                $where     .= " AND stat_repay.project_id = '{$project_id}' ";
            }
            // 校验项目名称
            if (!empty($_GET['project_name'])) {
                $project_name = trim($_GET['project_name']);
                $where       .= " AND stat_repay.project_name = '{$project_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_GET['jys_record_number'])) {
                $jys_record_number = trim($_GET['jys_record_number']);
                $where            .= " AND stat_repay.jys_record_number = '{$jys_record_number}' ";
            }
            // 校验借款人名称
            if (!empty($_GET['user_name'])) {
                $user_name = trim($_GET['user_name']);
                $where    .= " AND stat_repay.deal_user_real_name = '{$user_name}' ";
            }
            //检验融资经办机构
            if (!empty($_GET['advisory_name'])) {
                $advisory_name = trim($_GET['advisory_name']);
                $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$advisory_name}' ";
                $advisory_id = $model->createCommand($sql)->queryColumn();
                if ($advisory_id) {
                    $advisory_id_str = implode(',' , $advisory_id);
                    $where .= " AND stat_repay.deal_advisory_id IN ({$advisory_id_str}) ";
                } else {
                    $where .= " AND stat_repay.deal_advisory_id = -1 ";
                }
            }
            //检验融资担保机构
            if (!empty($_GET['agency_name'])) {
                $agency_name = trim($_GET['agency_name']);
                $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND stat_repay.deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND stat_repay.deal_agency_id = -1 ";
                }
            }
            if ($condition) {
                $redis_time = 86400;
                if ($_GET['platform'] == 1) {
                    $redis_key = 'XF_Deal_Load_BY_Deal_Count_ZX_Condition_'.$condition['id'];
                } else if ($_GET['platform'] == 2) {
                    $redis_key = 'XF_Deal_Load_BY_Deal_Count_PH_Condition_'.$condition['id'];
                }
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',' , $con_data);
                    $where       .= " AND stat_repay.deal_id IN ({$con_data_str}) ";
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
                            $where_con    = $where." AND stat_repay.deal_id IN ({$con_data_str}) ";
                            $sql          = "SELECT count(DISTINCT stat_repay.deal_id) AS count FROM ag_wx_stat_repay AS stat_repay {$where_con}";
                            $count_con    = $model->createCommand($sql)->queryScalar();
                            $count        += $count_con;
                        }
                        $con_data_str = implode(',' , $con_data);
                        $where       .= " AND stat_repay.deal_id IN ({$con_data_str}) ";
                        $set          = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                        if(!$set){
                            Yii::log("{$redis_key} redis count set error","error");
                        }
                    } else {
                        $where .= " AND stat_repay.deal_id = '' ";
                    }
                }
            } else {
                $sql   = "SELECT count(DISTINCT stat_repay.deal_id) AS count FROM ag_wx_stat_repay AS stat_repay {$where}";
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
                $sql = "SELECT stat_repay.deal_id , stat_repay.deal_name , stat_repay.project_id , stat_repay.project_name , stat_repay.jys_record_number , stat_repay.project_product_class , stat_repay.deal_loantype , stat_repay.deal_repay_time , stat_repay.deal_user_id , stat_repay.deal_user_real_name , stat_repay.deal_advisory_id , stat_repay.deal_advisory_name , stat_repay.deal_agency_id , stat_repay.deal_agency_name , stat_repay.jys_name , MAX(stat_repay.loan_repay_time) AS max_repay_time , MIN(stat_repay.loan_repay_time) AS min_repay_time , SUM(CASE WHEN stat_repay.repay_type = 1 THEN stat_repay.repay_amount - stat_repay.repaid_amount ELSE 0 END) AS wait_capital , SUM(CASE WHEN stat_repay.repay_type = 2 THEN stat_repay.repay_amount - stat_repay.repaid_amount ELSE 0 END) AS wait_interest , SUM(CASE WHEN stat_repay.loan_repay_time < {$time} AND stat_repay.repay_type = 1 THEN stat_repay.repay_amount - stat_repay.repaid_amount ELSE 0 END) AS overdue_capital , SUM(CASE WHEN stat_repay.loan_repay_time < {$time} AND stat_repay.repay_type = 2 THEN stat_repay.repay_amount - stat_repay.repaid_amount ELSE 0 END) AS overdue_interest , deal.investor_number , deal.investor_wait_capital , deal.recipient_number , deal.recipient_wait_capital FROM ag_wx_stat_repay AS stat_repay LEFT JOIN firstp2p_deal AS deal ON stat_repay.deal_id = deal.id {$where} GROUP BY stat_repay.deal_id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();
                foreach ($list as $key => $value) {
                    if ($value['deal_loantype'] == 5) {
                        $value['deal_repay_time'] .= '天';
                    } else {
                        $value['deal_repay_time'] .= '个月';
                    }
                    $value['deal_loantype']    = $loantype[$value['deal_loantype']];
                    $value['max_repay_time']   = date("Y-m-d" , ($value['max_repay_time'] + 28800));
                    // $value['wait_capital']     = number_format($value['wait_capital'], 2, '.', ',');
                    // $value['wait_interest']    = number_format($value['wait_interest'], 2, '.', ',');
                    $value['overdue_day']      = round(($time - $value['min_repay_time']) / 86400);
                    // $value['overdue_capital']  = number_format($value['overdue_capital'], 2, '.', ',');
                    // $value['overdue_interest'] = number_format($value['overdue_interest'], 2, '.', ',');

                    $listInfo[] = $value;
                }
            }
            if ($_GET['platform'] == 1) {
                $name = '在途项目明细(尊享+普惠) 尊享 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            } else {
                $name = '在途项目明细(尊享+普惠) 普惠 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            }
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "借款编号,借款标题,项目ID,项目名称,交易所备案号,交易所名称,产品大类,借款期限,还款方式,在途本金,在途利息,计划最大还款时间,逾期天数,逾期本金,逾期利息,借款人ID,借款人名称,融资经办机构名称,融资担保机构名称,投资人人数,投资人持有金额,受让人人数,受让人持有金额\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['deal_id']},{$value['deal_name']},{$value['project_id']},{$value['project_name']},{$value['jys_record_number']},{$value['jys_name']},{$value['project_product_class']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['wait_capital']},{$value['wait_interest']},{$value['max_repay_time']},{$value['overdue_day']},{$value['overdue_capital']},{$value['overdue_interest']},{$value['deal_user_id']},{$value['deal_user_real_name']},{$value['deal_advisory_name']},{$value['deal_agency_name']},{$value['investor_number']},{$value['investor_wait_capital']},{$value['recipient_number']},{$value['recipient_wait_capital']}\n";
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
                if ($_GET['platform'] == 1) {
                    $redis_key = 'XF_Deal_Load_BY_Deal_Download_ZX_Condition_'.$condition['id'];
                } else if ($_GET['platform'] == 2) {
                    $redis_key = 'XF_Deal_Load_BY_Deal_Download_PH_Condition_'.$condition['id'];
                }
                $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
                if(!$set){
                    Yii::log("{$redis_key} redis download set error","error");
                }
            }
        }
    }

    /**
     * 在途出借人明细 列表
     */
    public function actionDealLoadBYUser()
    {
        if (!empty($_POST)) {
            set_time_limit(0);
            // 条件筛选
            $where = " WHERE deal_load.status = 1 ";
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
                    if ($condition['platform'] == 1) {
                        $_POST['platform'] = 1;
                    } else {
                        $_POST['platform'] = 2;
                    }
                }
            }
            if ($_POST['platform'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($_POST['platform'] == 2) {
                $model = Yii::app()->phdb;
            } else {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '所属平台输入错误';
                echo exit(json_encode($result_data));
            }
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND deal_load.user_id = '{$user_id}' ";
            }
            //检验用户手机号
            if (!empty($_POST['user_mobile'])) {
                $user_mobile = trim($_POST['user_mobile']);
                $user_mobile = GibberishAESUtil::enc($user_mobile, Yii::app()->c->idno_key);
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$user_mobile}' ";
                $user_id = $model->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND deal_load.user_id = '{$user_id}' ";
                } else {
                    $where .= " AND deal_load.user_id = '' ";
                }
            }
            // 校验用户证件号
            if (!empty($_POST['user_idno'])) {
                $user_idno = trim($_POST['user_idno']);
                $user_idno = GibberishAESUtil::enc($user_idno, Yii::app()->c->idno_key);
                $sql = "SELECT id FROM firstp2p_user WHERE idno = '{$user_idno}' ";
                $user_id = $model->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND deal_load.user_id = '{$user_id}' ";
                } else {
                    $where .= " AND deal_load.user_id = '' ";
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
            if (empty($_POST['user_id']) && empty($_POST['user_mobile']) && empty($_POST['user_idno']) && empty($_POST['condition_id'])) {
                $redis_time = 86400;
                if ($_POST['platform'] == 1) {
                    $redis_key = 'XF_Deal_Load_BY_User_Count_ZX';
                } else if ($_POST['platform'] == 2) {
                    $redis_key = 'XF_Deal_Load_BY_User_Count_PH';
                }
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                } else {
                    $sql   = "SELECT count(DISTINCT deal_load.user_id) AS count FROM firstp2p_deal_load AS deal_load {$where} ";
                    $count = $model->createCommand($sql)->queryScalar();
                    $set   = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                    if(!$set){
                        Yii::log("{$redis_key} redis count set error","error");
                    }
                }
            } else {
                if ($condition) {
                    $redis_time = 86400;
                    if ($_POST['platform'] == 1) {
                        $redis_key = 'XF_Deal_Load_BY_User_Count_ZX_Condition_'.$condition['id'];
                    } else if ($_POST['platform'] == 2) {
                        $redis_key = 'XF_Deal_Load_BY_User_Count_PH_Condition_'.$condition['id'];
                    }
                    $redis_val = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',' , $con_data);
                        $where .= " AND deal_load.user_id IN ({$con_data_str}) ";
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
                                $where_con    = $where." AND deal_load.user_id IN ({$con_data_str}) ";
                                $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM firstp2p_deal_load AS deal_load {$where_con} ";
                                $count_con = $model->createCommand($sql)->queryScalar();
                                $count += $count_con;
                            }
                            $con_data_str = implode(',' , $con_data);
                            $where .= " AND deal_load.user_id IN ({$con_data_str}) ";
                            $set = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                            if(!$set){
                                Yii::log("{$redis_key} redis count set error","error");
                            }
                        } else {
                            $where .= " AND deal_load.user_id = '' ";
                        }
                    }
                } else {
                    $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM firstp2p_deal_load AS deal_load {$where} ";
                    $count = $model->createCommand($sql)->queryScalar();
                }
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
            $sql = "SELECT deal_load.user_id AS id , user.real_name , user.mobile , user.idno , user_group.name AS group_name , user.sex , user.byear , bind.refer_user_id , refer.real_name AS refer_name , bind.short_alias , refer_group.name AS refer_group_name , user.money , user.lock_money , user.ph_money , user.ph_lock_money , user.zx_recharge , user.zx_withdraw , user.ph_recharge , user.ph_withdraw , SUM(deal_load.wait_capital) AS wait_capital , SUM(deal_load.wait_interest) AS wait_interest 
                    FROM firstp2p_deal_load AS deal_load 
                    LEFT JOIN firstp2p_user AS user ON user.id = deal_load.user_id 
                    LEFT JOIN firstp2p_user_group AS user_group ON user.group_id = user_group.id 
                    LEFT JOIN firstp2p_coupon_bind AS bind ON user.id = bind.user_id 
                    LEFT JOIN firstp2p_user AS refer ON refer.id = bind.refer_user_id 
                    LEFT JOIN firstp2p_user_group AS refer_group ON refer.group_id = refer_group.id 
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
                $value['mobile']   = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                $value['mobile_a'] = substr($value['mobile'] , 0 , 7);
                // $value['mobile_b'] = $this->strEncrypt($value['mobile'] , 3 , 4);
                $value['idno']     = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                // $value['idno']     = $this->strEncrypt($value['idno'] , 6 , 8);
                $value['sex']      = $sex[$value['sex']];
                $value['byear']    = date('Y' , time()) - $value['byear'];
                if ($value['byear'] > 150) {
                    $value['byear'] = '——';
                }
                if ($_POST['platform'] == 1) {
                    $value['recharge_money'] = number_format($value['zx_recharge'], 2, '.', ',');
                    $value['withdraw_money'] = number_format($value['zx_withdraw'], 2, '.', ',');
                } else if ($_POST['platform'] == 2) {
                    $value['money']      = $value['ph_money'];
                    $value['lock_money'] = $value['ph_lock_money'];

                    $value['recharge_money'] = number_format(($value['ph_recharge']), 2, '.', ',');
                    $value['withdraw_money'] = number_format(($value['ph_withdraw']), 2, '.', ',');
                }
                $value['money']         = number_format($value['money'], 2, '.', ',');
                $value['lock_money']    = number_format($value['lock_money'], 2, '.', ',');
                $value['wait_capital']  = number_format($value['wait_capital'], 2, '.', ',');
                $value['wait_interest'] = number_format($value['wait_interest'], 2, '.', ',');
                $value['revenue']       = '0.00';
                if (!empty($value['mobile_a']) && is_numeric($value['mobile_a'])) {
                    $mobile_array[] = $value['mobile_a'];
                }
                
                $listInfo[] = $value;
                $user_id_arr[] = $value['id'];
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
            $interest_data = array();
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT user_id , SUM(yes_interest) AS yes_interest FROM firstp2p_deal_load WHERE user_id IN ({$user_id_str}) GROUP BY user_id ";
                $interest_res = $model->createCommand($sql)->queryAll();
                if ($interest_res) {
                    foreach ($interest_res as $key => $value) {
                        $interest_data[$value['user_id']] = $value['yes_interest'];
                    }
                }
            }
            foreach ($listInfo as $key => $value) {
                $listInfo[$key]['mobile_area'] = $mobile_data[$value['mobile_a']];
                $listInfo[$key]['revenue']     = $interest_data[$value['id']];
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
        if (!empty($authList) && strstr($authList,'/user/Loan/DealLoadBYUser2Excel') || empty($authList)) {
            $DealLoadBYUser2Excel = 1;
        }
        return $this->renderPartial('DealLoadBYUser' , array('DealLoadBYUser2Excel' => $DealLoadBYUser2Excel));
    }

    /**
     * 在途出借人明细 导出
     */
    public function actionDealLoadBYUser2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            $where = " WHERE deal_load.status = 1 ";
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_user_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    $con_data = json_decode($condition['data_json'] , true);
                    if ($condition['platform'] == 1) {
                        $_GET['platform'] = 1;
                    } else {
                        $_GET['platform'] = 2;
                    }
                    if ($_GET['platform'] == 1) {
                        $redis_key = 'XF_Deal_Load_BY_User_Download_ZX_Condition_'.$condition['id'];
                    } else if ($_GET['platform'] == 2) {
                        $redis_key = 'XF_Deal_Load_BY_User_Download_PH_Condition_'.$condition['id'];
                    }
                    $check = Yii::app()->rcache->get($redis_key);
                    if($check){
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                }
            }
            if (!in_array($_GET['platform'] , array(1 , 2))) {
                echo '<h1>请正确选择所属平台</h1>';
                exit;
            }
            if (empty($_GET['user_id']) && empty($_GET['user_mobile']) && empty($_GET['user_idno']) && empty($_GET['condition_id'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            if ($_GET['platform'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($_GET['platform'] == 2) {
                $model = Yii::app()->phdb;
            }
            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND deal_load.user_id = '{$user_id}' ";
            }
            //检验用户手机号
            if (!empty($_GET['user_mobile'])) {
                $user_mobile = trim($_GET['user_mobile']);
                $user_mobile = GibberishAESUtil::enc($user_mobile, Yii::app()->c->idno_key);
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$user_mobile}' ";
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
                $sql = "SELECT id FROM firstp2p_user WHERE idno = '{$user_idno}' ";
                $user_id = $model->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND deal_load.user_id = '{$user_id}' ";
                } else {
                    $where .= " AND deal_load.user_id = '' ";
                }
            }
            if ($condition) {
                $redis_time = 86400;
                if ($_GET['platform'] == 1) {
                    $redis_key = 'XF_Deal_Load_BY_User_Count_ZX_Condition_'.$condition['id'];
                } else if ($_GET['platform'] == 2) {
                    $redis_key = 'XF_Deal_Load_BY_User_Count_PH_Condition_'.$condition['id'];
                }
                $redis_val = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',' , $con_data);
                    $where_all = " WHERE user.is_online = 1 AND user.id IN ({$con_data_str}) ";
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
                            $where_con    = " WHERE user.is_online = 1 AND user.id IN ({$con_data_str}) ";
                            $sql = "SELECT count(DISTINCT user.id) AS count FROM firstp2p_user AS user {$where_con} ";
                            $count_con = $model->createCommand($sql)->queryScalar();
                            $count += $count_con;
                        }
                        $con_data_str = implode(',' , $con_data);
                        $where_all = " WHERE user.is_online = 1 AND user.id IN ({$con_data_str}) ";
                        $set = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                        if(!$set){
                            Yii::log("{$redis_key} redis count set error","error");
                        }
                    } else {
                        $where_all = " WHERE user.is_online = 1 AND user.id = '' ";
                    }
                }
            } else {
                $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM firstp2p_deal_load AS deal_load {$where} ";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            if ($condition) {
                for ($i = 0; $i < $page_count; $i++) {
                    $pass = $i * 500;
                    // 查询数据
                    $sql = "SELECT user.id , user.real_name , user.mobile , user.idno , user_group.name AS group_name , user.sex , user.byear , bind.refer_user_id , refer.real_name AS refer_name , bind.short_alias , refer_group.name AS refer_group_name , user.money , user.lock_money , user.ph_money , user.ph_lock_money , user.zx_recharge , user.zx_withdraw , user.ph_recharge , user.ph_withdraw 
                            FROM firstp2p_user AS user 
                            LEFT JOIN firstp2p_deal_load AS deal_load ON user.id = deal_load.user_id 
                            LEFT JOIN firstp2p_user_group AS user_group ON user.group_id = user_group.id 
                            LEFT JOIN firstp2p_coupon_bind AS bind ON user.id = bind.user_id 
                            LEFT JOIN firstp2p_user AS refer ON refer.id = bind.refer_user_id 
                            LEFT JOIN firstp2p_user_group AS refer_group ON refer.group_id = refer_group.id 
                            {$where_all} GROUP BY user.id LIMIT {$pass} , 500 ";
                    $list = $model->createCommand($sql)->queryAll();
                    // 用户的账户信息
                    $user_ids = implode(",", ArrayUtil::array_column($list , "id"));
                    // 尊享 在途本金 & 在途利息 & 历史累计收益额
                    $zx_wait_capital_res = Yii::app()->fdb->createCommand("SELECT user_id , SUM(CASE WHEN status = 1 THEN wait_capital ELSE 0 END) AS wait_capital , SUM(CASE WHEN status = 1 THEN wait_interest ELSE 0 END) AS wait_interest , SUM(yes_interest) AS yes_interest FROM firstp2p_deal_load WHERE user_id IN ({$user_ids}) GROUP BY user_id")->queryAll();
                    if (!empty($zx_wait_capital_res)) {
                        foreach ($zx_wait_capital_res as $key => $value) {
                            $zx_wait_capital[$value['user_id']] = $value;
                        }
                    }
                    // 普惠 在途本金 & 在途利息 & 历史累计收益额
                    $ph_wait_capital_res = Yii::app()->phdb->createCommand("SELECT user_id , SUM(CASE WHEN status = 1 THEN wait_capital ELSE 0 END) AS wait_capital , SUM(CASE WHEN status = 1 THEN wait_interest ELSE 0 END) AS wait_interest , SUM(yes_interest) AS yes_interest FROM firstp2p_deal_load WHERE user_id IN ({$user_ids}) GROUP BY user_id")->queryAll();
                    if (!empty($ph_wait_capital_res)) {
                        foreach ($ph_wait_capital_res as $key => $value) {
                            $ph_wait_capital[$value['user_id']] = $value;
                        }
                    }
                    $sex[0] = '女';
                    $sex[1] = '男';
                    foreach ($list as $key => $value) {
                        $list[$key]['mobile']   = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                        $list[$key]['mobile_a'] = substr($list[$key]['mobile'] , 0 , 7);
                        // $value['mobile_b'] = $this->strEncrypt($value['mobile'] , 3 , 4);
                        $list[$key]['idno']     = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                        // $value['idno']     = $this->strEncrypt($value['idno'] , 6 , 8);
                        $list[$key]['sex']      = $sex[$value['sex']];
                        $list[$key]['byear']    = date('Y' , time()) - $value['byear'];
                        if ($list[$key]['byear'] > 150) {
                            $list[$key]['byear'] = '——';
                        }
                        if (!empty($zx_wait_capital[$value['id']])) {
                            $list[$key]['zx_wait_capital']  = $zx_wait_capital[$value['id']]['wait_capital'];
                            $list[$key]['zx_wait_interest'] = $zx_wait_capital[$value['id']]['wait_interest'];
                            $list[$key]['zx_revenue']       = $zx_wait_capital[$value['id']]['yes_interest'];
                        } else {
                            $list[$key]['zx_wait_capital']  = 0;
                            $list[$key]['zx_wait_interest'] = 0;
                            $list[$key]['zx_revenue']       = 0;
                        }
                        if (!empty($ph_wait_capital[$value['id']])) {
                            $list[$key]['ph_wait_capital']  = $ph_wait_capital[$value['id']]['wait_capital'];
                            $list[$key]['ph_wait_interest'] = $ph_wait_capital[$value['id']]['wait_interest'];
                            $list[$key]['ph_revenue']       = $ph_wait_capital[$value['id']]['yes_interest'];
                        } else {
                            $list[$key]['ph_wait_capital']  = 0;
                            $list[$key]['ph_wait_interest'] = 0;
                            $list[$key]['ph_revenue']       = 0;
                        }
                        if ($list[$key]['zx_wait_capital'] == 0 && $list[$key]['zx_wait_interest'] == 0 && $list[$key]['ph_wait_capital'] == 0 && $list[$key]['ph_wait_interest'] == 0) {
                            unset($list[$key]);
                        } else {
                            $list[$key]['total_capital']  = $list[$key]['zx_wait_capital'] + $list[$key]['ph_wait_capital'];
                            $list[$key]['total_interest'] = $list[$key]['zx_wait_interest'] + $list[$key]['ph_wait_interest'];
                            if (!empty($list[$key]['mobile_a']) && is_numeric($list[$key]['mobile_a'])) {
                                $mobile_array[] = $list[$key]['mobile_a'];
                            }
                        }
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
                    foreach ($list as $key => $value) {
                        $value['mobile_area'] = $mobile_data[$value['mobile_a']];

                        $listInfo[] = $value;
                    }
                }
                $name = '在途出借人明细(尊享+普惠) '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
                $name  = iconv('utf-8', 'GBK', $name);
                $data  = "用户ID,用户姓名,用户所属组别名称,性别,年龄,手机号所在地,服务人ID,服务人姓名,服务人邀请码,服务人所属组别名称,尊享账户余额,普惠账户余额,尊享账户冻结金额,普惠账户冻结金额,尊享历史充值金额,普惠历史充值金额,尊享历史提现金额,普惠历史提现金额,尊享在途本金,普惠在途本金,尊享在途利息,普惠在途利息,总在途本金,总在途利息,尊享历史累计收益额,普惠历史累计收益额\n";
                $data  = iconv('utf-8', 'GBK', $data);
                foreach ($listInfo as $key => $value) {
                    $temp  = "{$value['id']},{$value['real_name']},{$value['group_name']},{$value['sex']},{$value['byear']},{$value['mobile_area']},{$value['refer_user_id']},{$value['refer_name']},{$value['short_alias']},{$value['refer_group_name']},{$value['money']},{$value['ph_money']},{$value['lock_money']},{$value['ph_lock_money']},{$value['zx_recharge']},{$value['ph_recharge']},{$value['zx_withdraw']},{$value['ph_withdraw']},{$value['zx_wait_capital']},{$value['ph_wait_capital']},{$value['zx_wait_interest']},{$value['ph_wait_interest']},{$value['total_capital']},{$value['total_interest']},{$value['zx_revenue']},{$value['ph_revenue']}\n";
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
                    if ($_GET['platform'] == 1) {
                        $redis_key = 'XF_Deal_Load_BY_User_Download_ZX_Condition_'.$condition['id'];
                    } else if ($_GET['platform'] == 2) {
                        $redis_key = 'XF_Deal_Load_BY_User_Download_PH_Condition_'.$condition['id'];
                    }
                    $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
                    if(!$set){
                        Yii::log("{$redis_key} redis download set error","error");
                    }
                }
                exit;
            }
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT deal_load.user_id AS id , user.real_name , user.mobile , user.idno , user_group.name AS group_name , user.sex , user.byear , bind.refer_user_id , refer.real_name AS refer_name , bind.short_alias , refer_group.name AS refer_group_name , user.money , user.lock_money , user.ph_money , user.ph_lock_money , user.zx_recharge , user.zx_withdraw , user.ph_recharge , user.ph_withdraw , SUM(wait_capital) AS wait_capital , SUM(wait_interest) AS wait_interest 
                        FROM firstp2p_deal_load AS deal_load 
                        LEFT JOIN firstp2p_user AS user ON user.id = deal_load.user_id 
                        LEFT JOIN firstp2p_user_group AS user_group ON user.group_id = user_group.id 
                        LEFT JOIN firstp2p_coupon_bind AS bind ON user.id = bind.user_id 
                        LEFT JOIN firstp2p_user AS refer ON refer.id = bind.refer_user_id 
                        LEFT JOIN firstp2p_user_group AS refer_group ON refer.group_id = refer_group.id 
                        {$where} GROUP BY deal_load.user_id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();

                $sex[0] = '女';
                $sex[1] = '男';
                $user_id_arr = array();
                foreach ($list as $key => $value) {
                    $list[$key]['mobile']   = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                    $list[$key]['mobile_a'] = substr($list[$key]['mobile'] , 0 , 7);
                    // $value['mobile_b'] = $this->strEncrypt($value['mobile'] , 3 , 4);
                    $list[$key]['idno']     = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                    // $value['idno']     = $this->strEncrypt($value['idno'] , 6 , 8);
                    $list[$key]['sex']      = $sex[$value['sex']];
                    $list[$key]['byear']    = date('Y' , time()) - $value['byear'];
                    if ($list[$key]['byear'] > 150) {
                        $list[$key]['byear'] = '——';
                    }
                    if ($_GET['platform'] == 1) {
                        $list[$key]['recharge_money'] = $value['zx_recharge'];
                        $list[$key]['withdraw_money'] = $value['zx_withdraw'];
                    } else if ($_GET['platform'] == 2) {
                        $list[$key]['money']      = $value['ph_money'];
                        $list[$key]['lock_money'] = $value['ph_lock_money'];

                        $list[$key]['recharge_money'] = $value['ph_recharge'];
                        $list[$key]['withdraw_money'] = $value['ph_withdraw'];
                    }
                    if (!empty($list[$key]['mobile_a']) && is_numeric($list[$key]['mobile_a'])) {
                        $mobile_array[] = $list[$key]['mobile_a'];
                    }
                    $list[$key]['yes_interest'] = '0.00';
                    $user_id_arr[] = $value['id'];
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
                $interest_data = array();
                if ($user_id_arr) {
                    $user_id_str = implode(',' , $user_id_arr);
                    $sql = "SELECT user_id , SUM(yes_interest) AS yes_interest FROM firstp2p_deal_load WHERE user_id IN ({$user_id_str}) GROUP BY user_id ";
                    $interest_res = $model->createCommand($sql)->queryAll();
                    if ($interest_res) {
                        foreach ($interest_res as $key => $value) {
                            $interest_data[$value['user_id']] = $value['yes_interest'];
                        }
                    }
                }
                foreach ($list as $key => $value) {
                    $value['mobile_area']  = $mobile_data[$value['mobile_a']];
                    $value['yes_interest'] = $interest_data[$value['id']];

                    $listInfo[] = $value;
                }
            }
            if ($_GET['platform'] == 1) {
                $name = '在途出借人明细(尊享+普惠) 尊享 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            } else if ($_GET['platform'] == 2) {
                $name = '在途出借人明细(尊享+普惠) 普惠 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            }
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "用户ID,用户姓名,用户所属组别名称,性别,年龄,手机号所在地,服务人ID,服务人姓名,服务人邀请码,服务人所属组别名称,账户余额,账户冻结金额,历史充值金额,历史提现金额,在途本金,在途利息,历史累计收益额\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['real_name']},{$value['group_name']},{$value['sex']},{$value['byear']},{$value['mobile_area']},{$value['refer_user_id']},{$value['refer_name']},{$value['short_alias']},{$value['refer_group_name']},{$value['money']},{$value['lock_money']},{$value['recharge_money']},{$value['withdraw_money']},{$value['wait_capital']},{$value['wait_interest']},{$value['yes_interest']}\n";
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
     * 在途出借人明细 列表 批量条件上传
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
                $name = '在途出借人明细(尊享+普惠) 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
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
            $name     = '';
            $model    = Yii::app()->fdb;
            $platform = 1;
            if ($type == 1) {

                $name .= '通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_id_data = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDealLoadBYUserCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                $sql = "SELECT user_id FROM ag_wx_assignee_info WHERE status IN (2, 3)";
                $assignee = Yii::app()->fdb->createCommand($sql)->queryColumn();
                // 校验在途
                $sql = "SELECT DISTINCT user_id FROM firstp2p_deal_load WHERE status = 1 AND user_id IN ({$user_id_str}) ";
                $zx_deal_load = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $ph_deal_load = Yii::app()->phdb->createCommand($sql)->queryColumn();
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $user_id_data)) {
                        $false_id_arr[] = $value[0].'(未查询到用户)';
                    } else if (in_array($value[0] , $assignee)) {
                        $false_id_arr[] = $value[0].'(受让方账户数据请单独导出)';
                    } else if (!in_array($value[0] , $zx_deal_load) && !in_array($value[0] , $ph_deal_load)) {
                        $false_id_arr[] = $value[0].'(非在途用户)';
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
            
            $sql = "INSERT INTO xf_deal_load_user_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
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
     * 选择方案明细
     */
    public function actionPlanDetail()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            $model = Yii::app()->fdb;
            // 检验借款标题
            if (!empty($_POST['deal_name'])) {
                $deal_name = trim($_POST['deal_name']);
                $where    .= " AND d.name = '{$deal_name}' ";
            }
            // 检验投资记录ID
            if (!empty($_POST['deal_load_id'])) {
                $deal_load_id = trim($_POST['deal_load_id']);
                $where       .= " AND l.id = '{$deal_load_id}' ";
            }
            // 检验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND l.user_id = '{$user_id}' ";
            }
            // 校验还款计划方案
            if ($_POST['repay_way'] == 1) {
                $where .= " AND l.repay_way = 1 ";
            } else if ($_POST['repay_way'] == 2) {
                $where .= " AND l.repay_way > 1 ";
            }
            // 校验选择方案时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND l.confirm_repay_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND l.confirm_repay_time <= {$end} ";
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
            if (empty($_POST['deal_name']) && empty($_POST['deal_load_id']) && empty($_POST['user_id']) && empty($_POST['repay_way']) && empty($_POST['start']) && empty($_POST['end'])) {
                $redis_time = 86400;
                $redis_key  = 'XF_PlanDetail_Count_ZX';
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                } else {
                    $sql = "SELECT COUNT(*) 
                            FROM firstp2p_deal_load AS l INNER JOIN firstp2p_deal_loan_repay AS r ON l.id = r.deal_loan_id 
                            INNER JOIN firstp2p_deal AS d ON l.deal_id = d.id AND l.user_id = r.loan_user_id AND r.type = 1 AND l.repay_way > 0 AND r.money > 0 {$where}";
                    $count = $model->createCommand($sql)->queryScalar();
                    $set   = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                    if(!$set){
                        Yii::log("{$redis_key} redis count set error","error");
                    }
                }
            } else {
                $sql = "SELECT COUNT(*) 
                        FROM firstp2p_deal_load AS l INNER JOIN firstp2p_deal_loan_repay AS r ON l.id = r.deal_loan_id 
                        INNER JOIN firstp2p_deal AS d ON l.deal_id = d.id AND l.user_id = r.loan_user_id AND r.type = 1 AND l.repay_way > 0 AND r.money > 0 {$where}";
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
            $sql = "SELECT d.name AS deal_name , l.id AS deal_load_id , l.user_id , r.money , l.repay_way , l.confirm_repay_time 
                    FROM firstp2p_deal_load AS l INNER JOIN firstp2p_deal_loan_repay AS r ON l.id = r.deal_loan_id 
                    INNER JOIN firstp2p_deal AS d ON l.deal_id = d.id AND l.user_id = r.loan_user_id AND r.type = 1 AND l.repay_way > 0 AND r.money > 0 {$where}";
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
            foreach ($list as $key => $value) {
                if ($value['repay_way'] == 1) {
                    $value['repay_way_name'] = '现金兑付';
                } else {
                    $value['repay_way_name'] = '实物抵债兑付';
                }
                $value['money']              = number_format($value['money'], 2, '.', ',');
                $value['confirm_repay_time'] = date("Y-m-d H:i:s" , $value['confirm_repay_time']);

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
        $PlanDetail2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/Loan/PlanDetail2Excel') || empty($authList)) {
            $PlanDetail2Excel = 1;
        }
        return $this->renderPartial('PlanDetail', array('PlanDetail2Excel' => $PlanDetail2Excel));
    }

    /**
     * 选择方案明细 导出
     */
    public function actionPlanDetail2Excel()
    {
        if (!empty($_GET)) {
            // 条件筛选
            $where = "";
            $model = Yii::app()->fdb;
            // 检验借款标题
            if (!empty($_GET['deal_name'])) {
                $deal_name = trim($_GET['deal_name']);
                $where    .= " AND d.name = '{$deal_name}' ";
            }
            // 检验投资记录ID
            if (!empty($_GET['deal_load_id'])) {
                $deal_load_id = trim($_GET['deal_load_id']);
                $where       .= " AND l.id = '{$deal_load_id}' ";
            }
            // 检验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND l.user_id = '{$user_id}' ";
            }
            // 校验还款计划方案
            if ($_GET['repay_way'] == 1) {
                $where .= " AND l.repay_way = 1 ";
            } else if ($_GET['repay_way'] == 2) {
                $where .= " AND l.repay_way > 1 ";
            }
            // 校验选择方案时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start']);
                $where .= " AND l.confirm_repay_time >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end']);
                $where .= " AND l.confirm_repay_time <= {$end} ";
            }
            if (empty($_GET['deal_name']) && empty($_GET['deal_load_id']) && empty($_GET['user_id']) && empty($_GET['repay_way']) && empty($_GET['start']) && empty($_GET['end'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            } else {
                $sql = "SELECT COUNT(*) 
                        FROM firstp2p_deal_load AS l INNER JOIN firstp2p_deal_loan_repay AS r ON l.id = r.deal_loan_id 
                        INNER JOIN firstp2p_deal AS d ON l.deal_id = d.id AND l.user_id = r.loan_user_id AND r.type = 1 AND l.repay_way > 0 AND r.money > 0 {$where}";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            // 查询数据
            $sql = "SELECT d.name AS deal_name , l.id AS deal_load_id , l.user_id , r.money , l.repay_way , l.confirm_repay_time 
                    FROM firstp2p_deal_load AS l INNER JOIN firstp2p_deal_loan_repay AS r ON l.id = r.deal_loan_id 
                    INNER JOIN firstp2p_deal AS d ON l.deal_id = d.id AND l.user_id = r.loan_user_id AND r.type = 1 AND l.repay_way > 0 AND r.money > 0 {$where}";
            $list = $model->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            foreach ($list as $key => $value) {
                if ($value['repay_way'] == 1) {
                    $value['repay_way_name'] = '现金兑付';
                } else {
                    $value['repay_way_name'] = '实物抵债兑付';
                }
                $value['confirm_repay_time'] = date("Y-m-d H:i:s" , $value['confirm_repay_time']);

                $listInfo[] = $value;
            }
            $name = '选择方案明细 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "借款标题,投资记录ID,用户ID,待还本金,还款计划方案,选择方案时间\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['deal_name']},{$value['deal_load_id']},{$value['user_id']},{$value['money']},{$value['repay_way_name']},{$value['confirm_repay_time']}\n";
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
     * 出借人充提差 列表
     */
    public function actionRechargeWithdraw()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $model = Yii::app()->fdb;
            $where = " WHERE 1 = 1 ";
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_user_recharge_withdraw_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = $model->createCommand($sql)->queryRow();
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
                }
            }
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND user_id = '{$user_id}' ";
            }
            // 检验用户手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key);
                $where .= " AND mobile = '{$mobile}' ";
            }
            // 校验用户证件号
            if (!empty($_POST['idno'])) {
                $idno   = trim($_POST['idno']);
                $idno   = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key);
                $where .= " AND idno = '{$idno}' ";
            }
            // 校验账户类型
            if (!empty($_POST['user_purpose'])) {
                $user_purpose = intval($_POST['user_purpose']) - 1;
                $where       .= " AND user_purpose = '{$user_purpose}' ";
            }
            // 校验在途平台
            if (!empty($_POST['platform'])) {
                if ($_POST['platform'] == 1) {
                    $where .= " AND is_online = 1 AND (zx_wait_capital > 0 OR zx_new_wait_capital > 0 OR ph_wait_capital > 0 OR ph_new_wait_capital > 0 OR ph_zdx_wait_capital > 0 OR ph_zdx_new_wait_capital > 0)";
                } else if ($_POST['platform'] == 2) {
                    $where .= " AND is_online = 1 AND (zx_wait_capital > 0 OR zx_new_wait_capital > 0) ";
                } else if ($_POST['platform'] == 3) {
                    $where .= " AND is_online = 1 AND (ph_wait_capital > 0 OR ph_new_wait_capital > 0 OR ph_zdx_wait_capital > 0 OR ph_zdx_new_wait_capital > 0) ";
                } else if ($_POST['platform'] == 4) {
                    $where .= " AND is_online = 1 AND (ph_wait_capital > 0 OR ph_new_wait_capital > 0) ";
                } else if ($_POST['platform'] == 5) {
                    $where .= " AND is_online = 1 AND (ph_zdx_wait_capital > 0 OR ph_zdx_new_wait_capital > 0) ";
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
            if (empty($_POST['user_id']) && empty($_POST['mobile']) && empty($_POST['idno']) && empty($_POST['user_purpose']) && empty($_POST['platform']) && empty($_POST['condition_id'])) {
                $redis_time = 86400;
                $redis_key  = 'XF_User_Recharge_Withdraw_Count';
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                } else {
                    $sql   = "SELECT count(user_id) AS count FROM xf_user_recharge_withdraw {$where} ";
                    $count = $model->createCommand($sql)->queryScalar();
                    $set   = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                    if(!$set){
                        Yii::log("{$redis_key} redis count set error","error");
                    }
                }
            } else {
                if ($condition) {
                    $redis_time = 86400;
                    $redis_key  = 'XF_User_Recharge_Withdraw_Count_Condition_'.$condition['id'];
                    $redis_val  = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',' , $con_data);
                        $where       .= " AND user_id IN ({$con_data_str}) ";
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
                                $where_con    = $where." AND user_id IN ({$con_data_str}) ";
                                $sql          = "SELECT count(user_id) AS count FROM xf_user_recharge_withdraw {$where_con}";
                                $count_con    = $model->createCommand($sql)->queryScalar();
                                $count       += $count_con;
                            }
                            $con_data_str = implode(',' , $con_data);
                            $where       .= " AND user_id IN ({$con_data_str}) ";
                            $set          = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                            if(!$set){
                                Yii::log("{$redis_key} redis count set error","error");
                            }
                        } else {
                            $where .= " AND user_id = '' ";
                        }
                    }
                } else {
                    $sql   = "SELECT count(user_id) AS count FROM xf_user_recharge_withdraw {$where} ";
                    $count = $model->createCommand($sql)->queryScalar();
                }
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
            $sql = "SELECT * FROM xf_user_recharge_withdraw {$where} ORDER BY user_id DESC";
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
            $user_purpose = array("0" => "借贷混合用户", "1" => "投资户", "2" => "融资户", "3" => "咨询户", "4" => "担保/代偿I户", "5" => "渠道户", "6" => "渠道虚拟户", "7" => "资产收购户", "8" => "担保/代偿II-b户", "9" => "受托资产管理户", "10" => "交易中心（所）", "11" => "平台户", "12" => "保证金户", "13" => "支付户", "14" => "投资券户", "15" => "红包户", "16" => "担保/代偿II-a户", "17" => "放贷户", "18" => "垫资户", "19" => "管理户", "20" => "商户账户", "21" => "营销补贴户");
            foreach ($list as $key => $value) {
                $value['mobile']           = GibberishAESUtil::dec($value['mobile'] , Yii::app()->c->idno_key);
                $value['idno']             = GibberishAESUtil::dec($value['idno'] , Yii::app()->c->idno_key);
                $value['user_purpose']     = $user_purpose[$value['user_purpose']];

                $value['increase']               = bcadd($value['zx_increase'] , $value['ph_increase'] , 6);
                $value['reduce']                 = bcadd($value['zx_reduce'] , $value['ph_reduce'] , 6);
                $value['increase_reduce']        = bcadd($value['zx_increase_reduce'] , $value['ph_increase_reduce'] , 6);
                $value['zx_wait_capital_total']  = bcadd($value['zx_wait_capital'] , $value['zx_new_wait_capital'] , 6);
                $value['ph_wait_capital_total']  = bcadd($value['ph_wait_capital'] , $value['ph_new_wait_capital'] , 6);
                $value['zdx_wait_capital_total'] = bcadd($value['ph_zdx_wait_capital'] , $value['ph_zdx_new_wait_capital'] , 6);
                $value['wait_capital']           = bcadd($value['zx_wait_capital_total'] , $value['ph_wait_capital_total'] , 6);
                $value['wait_capital']           = bcadd($value['wait_capital'] , $value['zdx_wait_capital_total'] , 6);
                $value['wait_interest']          = bcadd($value['zx_wait_interest'] , $value['ph_wait_interest'] , 6);

                $value['increase']               = number_format($value['increase'], 6, '.', ',');
                $value['reduce']                 = number_format($value['reduce'], 6, '.', ',');
                $value['increase_reduce']        = number_format($value['increase_reduce'], 6, '.', ',');
                $value['zx_wait_capital_total']  = number_format($value['zx_wait_capital_total'], 6, '.', ',');
                $value['ph_wait_capital_total']  = number_format($value['ph_wait_capital_total'], 6, '.', ',');
                $value['zdx_wait_capital_total'] = number_format($value['zdx_wait_capital_total'], 6, '.', ',');
                $value['wait_capital']           = number_format($value['wait_capital'], 6, '.', ',');
                $value['wait_interest']          = number_format($value['wait_interest'], 6, '.', ',');

                $value['zx_money']                = number_format($value['zx_money'], 6, '.', ',');
                $value['zx_lock_money']           = number_format($value['zx_lock_money'], 6, '.', ',');
                $value['ph_money']                = number_format($value['ph_money'], 6, '.', ',');
                $value['ph_lock_money']           = number_format($value['ph_lock_money'], 6, '.', ',');
                $value['zx_recharge']             = number_format($value['zx_recharge'], 6, '.', ',');
                $value['zx_withdraw']             = number_format($value['zx_withdraw'], 6, '.', ',');
                $value['ph_recharge']             = number_format($value['ph_recharge'], 6, '.', ',');
                $value['ph_withdraw']             = number_format($value['ph_withdraw'], 6, '.', ',');
                $value['zx_wait_capital']         = number_format($value['zx_wait_capital'], 6, '.', ',');
                $value['zx_new_wait_capital']     = number_format($value['zx_new_wait_capital'], 6, '.', ',');
                $value['zx_wait_interest']        = number_format($value['zx_wait_interest'], 6, '.', ',');
                $value['zx_buy_debt']             = number_format($value['zx_buy_debt'], 6, '.', ',');
                $value['zx_increase']             = number_format($value['zx_increase'], 6, '.', ',');
                $value['zx_sell_debt']            = number_format($value['zx_sell_debt'], 6, '.', ',');
                $value['zx_exchange']             = number_format($value['zx_exchange'], 6, '.', ',');
                $value['zx_deduct']               = number_format($value['zx_deduct'], 6, '.', ',');
                $value['zx_repay']                = number_format($value['zx_repay'], 6, '.', ',');
                $value['zx_reduce']               = number_format($value['zx_reduce'], 6, '.', ',');
                $value['zx_increase_reduce']      = number_format($value['zx_increase_reduce'], 6, '.', ',');
                $value['ph_wait_capital']         = number_format($value['ph_wait_capital'], 6, '.', ',');
                $value['ph_new_wait_capital']     = number_format($value['ph_new_wait_capital'], 6, '.', ',');
                $value['ph_wait_interest']        = number_format($value['ph_wait_interest'], 6, '.', ',');
                $value['ph_buy_debt']             = number_format($value['ph_buy_debt'], 6, '.', ',');
                $value['ph_zdx_buy_debt']         = number_format($value['ph_zdx_buy_debt'], 6, '.', ',');
                $value['ph_increase']             = number_format($value['ph_increase'], 6, '.', ',');
                $value['ph_sell_debt']            = number_format($value['ph_sell_debt'], 6, '.', ',');
                $value['ph_exchange']             = number_format($value['ph_exchange'], 6, '.', ',');
                $value['ph_deduct']               = number_format($value['ph_deduct'], 6, '.', ',');
                $value['ph_repay']                = number_format($value['ph_repay'], 6, '.', ',');
                $value['ph_zdx_sell_debt']        = number_format($value['ph_zdx_sell_debt'], 6, '.', ',');
                $value['ph_zdx_exchange']         = number_format($value['ph_zdx_exchange'], 6, '.', ',');
                $value['ph_zdx_deduct']           = number_format($value['ph_zdx_deduct'], 6, '.', ',');
                $value['ph_zdx_repay']            = number_format($value['ph_zdx_repay'], 6, '.', ',');
                $value['ph_reduce']               = number_format($value['ph_reduce'], 6, '.', ',');
                $value['ph_increase_reduce']      = number_format($value['ph_increase_reduce'], 6, '.', ',');
                $value['ph_zdx_wait_capital']     = number_format($value['ph_zdx_wait_capital'], 6, '.', ',');
                $value['ph_zdx_new_wait_capital'] = number_format($value['ph_zdx_new_wait_capital'], 6, '.', ',');
                
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
        $RechargeWithdraw2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/Loan/RechargeWithdraw2Excel') || empty($authList)) {
            $RechargeWithdraw2Excel = 1;
        }
        return $this->renderPartial('RechargeWithdraw' , array('RechargeWithdraw2Excel' => $RechargeWithdraw2Excel));
    }

    /**
     * 出借人充提差 列表 批量条件上传
     */
    public function actionaddRechargeWithdrawCondition()
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
                $name = '出借人充提差 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
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
            if ($Rows > 100001) {
                return $this->actionError('上传的文件中数据超过十万行' , 5);
            }
            unset($data[0]);
            $name  = '';
            $model = Yii::app()->fdb;
            if ($type == 1) {

                $name .= ' 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $com_a = $model->createCommand("SELECT user_id FROM xf_user_recharge_withdraw WHERE user_id IN ({$user_id_str}) ")->queryColumn();
                $count = count($data);
                if (!$com_a) {
                    return $this->renderPartial('addRechargeWithdrawCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $com_a)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;

                $data_json = json_encode($com_a);
            }
            
            $sql = "INSERT INTO xf_user_recharge_withdraw_condition (type , name , file_address , true_count , false_count , data_json) VALUES ({$type} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
            $add = $model->createCommand($sql)->execute();
            $add_id = $model->getLastInsertID();

            if ($add) {
                return $this->renderPartial('addRechargeWithdrawCondition', array('end' => 2 , 'count' => $count , 'true_count' => $true_count , 'false_count' => $false_count , 'false_id_str' => $false_id_str , 'add_id' => $add_id , 'add_name' => $name));
            } else {
                return $this->actionError('保存查询条件失败' , 5);
            }
        }

        return $this->renderPartial('addRechargeWithdrawCondition' , array('end' => 0));
    }

    /**
     * 出借人充提差 列表 导出
     */
    public function actionRechargeWithdraw2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            $model = Yii::app()->fdb;
            $where = " WHERE 1 = 1 ";
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_user_recharge_withdraw_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = $model->createCommand($sql)->queryRow();
                if ($condition) {
                    $redis_key = 'XF_User_Recharge_Withdraw_Download_Condition_'.$condition['id'];
                    $check = Yii::app()->rcache->get($redis_key);
                    if($check){
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                    $con_data = json_decode($condition['data_json'] , true);
                }
            }
            if (empty($_GET['user_id']) && empty($_GET['mobile']) && empty($_GET['idno']) && empty($_GET['user_purpose']) && empty($_GET['condition_id']) && empty($_GET['platform'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND user_id = '{$user_id}' ";
            }
            // 检验用户手机号
            if (!empty($_GET['mobile'])) {
                $mobile = trim($_GET['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key);
                $where .= " AND mobile = '{$mobile}' ";
            }
            // 校验用户证件号
            if (!empty($_GET['idno'])) {
                $idno   = trim($_GET['idno']);
                $idno   = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key);
                $where .= " AND idno = '{$idno}' ";
            }
            // 校验账户类型
            if (!empty($_GET['user_purpose'])) {
                $user_purpose = intval($_GET['user_purpose']) - 1;
                $where       .= " AND user_purpose = '{$user_purpose}' ";
            }
            // 校验在途平台
            if (!empty($_GET['platform'])) {
                if ($_GET['platform'] == 1) {
                    $where .= " AND is_online = 1 AND (zx_wait_capital > 0 OR zx_new_wait_capital > 0 OR ph_wait_capital > 0 OR ph_new_wait_capital > 0 OR ph_zdx_wait_capital > 0 OR ph_zdx_new_wait_capital > 0)";
                } else if ($_GET['platform'] == 2) {
                    $where .= " AND is_online = 1 AND (zx_wait_capital > 0 OR zx_new_wait_capital > 0) ";
                } else if ($_GET['platform'] == 3) {
                    $where .= " AND is_online = 1 AND (ph_wait_capital > 0 OR ph_new_wait_capital > 0 OR ph_zdx_wait_capital > 0 OR ph_zdx_new_wait_capital > 0) ";
                } else if ($_GET['platform'] == 4) {
                    $where .= " AND is_online = 1 AND (ph_wait_capital > 0 OR ph_new_wait_capital > 0) ";
                } else if ($_GET['platform'] == 5) {
                    $where .= " AND is_online = 1 AND (ph_zdx_wait_capital > 0 OR ph_zdx_new_wait_capital > 0) ";
                }
            }
            if ($condition) {
                $redis_time = 86400;
                $redis_key  = 'XF_User_Recharge_Withdraw_Count_Condition_'.$condition['id'];
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',' , $con_data);
                    $where       .= " AND user_id IN ({$con_data_str}) ";
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
                            $where_con    = $where." AND user_id IN ({$con_data_str}) ";
                            $sql          = "SELECT count(user_id) AS count FROM xf_user_recharge_withdraw {$where_con}";
                            $count_con    = $model->createCommand($sql)->queryScalar();
                            $count       += $count_con;
                        }
                        $con_data_str = implode(',' , $con_data);
                        $where       .= " AND user_id IN ({$con_data_str}) ";
                        $set          = Yii::app()->rcache->set($redis_key , $count , $redis_time);
                        if(!$set){
                            Yii::log("{$redis_key} redis count set error","error");
                        }
                    } else {
                        $where .= " AND user_id = '' ";
                    }
                }
            } else {
                $sql   = "SELECT count(user_id) AS count FROM xf_user_recharge_withdraw {$where}";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count  = ceil($count / 500);
            $user_purpose = array("0" => "借贷混合用户", "1" => "投资户", "2" => "融资户", "3" => "咨询户", "4" => "担保/代偿I户", "5" => "渠道户", "6" => "渠道虚拟户", "7" => "资产收购户", "8" => "担保/代偿II-b户", "9" => "受托资产管理户", "10" => "交易中心（所）", "11" => "平台户", "12" => "保证金户", "13" => "支付户", "14" => "投资券户", "15" => "红包户", "16" => "担保/代偿II-a户", "17" => "放贷户", "18" => "垫资户", "19" => "管理户", "20" => "商户账户", "21" => "营销补贴户");
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql  = "SELECT * FROM xf_user_recharge_withdraw {$where} LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();
                foreach ($list as $key => $value) {
                    $value['mobile']           = GibberishAESUtil::dec($value['mobile'] , Yii::app()->c->idno_key);
                    $value['idno']             = GibberishAESUtil::dec($value['idno'] , Yii::app()->c->idno_key);
                    $value['user_purpose']     = $user_purpose[$value['user_purpose']];

                    $value['increase']               = bcadd($value['zx_increase'] , $value['ph_increase'] , 6);
                    $value['reduce']                 = bcadd($value['zx_reduce'] , $value['ph_reduce'] , 6);
                    $value['increase_reduce']        = bcadd($value['zx_increase_reduce'] , $value['ph_increase_reduce'] , 6);
                    $value['zx_wait_capital_total']  = bcadd($value['zx_wait_capital'] , $value['zx_new_wait_capital'] , 6);
                    $value['ph_wait_capital_total']  = bcadd($value['ph_wait_capital'] , $value['ph_new_wait_capital'] , 6);
                    $value['zdx_wait_capital_total'] = bcadd($value['ph_zdx_wait_capital'] , $value['ph_zdx_new_wait_capital'] , 6);
                    $value['wait_capital']           = bcadd($value['zx_wait_capital_total'] , $value['ph_wait_capital_total'] , 6);
                    $value['wait_capital']           = bcadd($value['wait_capital'] , $value['zdx_wait_capital_total'] , 6);
                    $value['wait_interest']          = bcadd($value['zx_wait_interest'] , $value['ph_wait_interest'] , 6);
                    
                    $listInfo[] = $value;
                }
            }
            $name = '出借人充提差 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "用户ID,会员名称,用户姓名,手机号,证件号,账户类型,充值总额,提现总额,充提差,在途本金总额,在途利息总额,尊享在途本金,——尊享历史在途本金,——尊享新增在途本金,尊享在途利息,尊享账户余额,尊享冻结金额,尊享充值,——尊享历史充值,——尊享债转转入,尊享提现,——尊享历史提现,——尊享债权兑换,——尊享债权划扣,——尊享线下还款,——尊享债转转出,普惠在途本金,——普惠历史在途本金,——普惠新增在途本金,智多新在途本金,——智多新历史在途本金,——智多新新增在途本金,普惠(含智多新)在途利息,普惠(含智多新）账户余额,普惠(含智多新)冻结金额,普惠(含智多新)充值,——普惠(含智多新)历史充值,——普惠债转转入,——智多新债转转入,普惠(含智多新)提现,——普惠(含智多新)历史提现,——普惠债权兑换,——智多新债权兑换,——普惠债权划扣,——智多新债权划扣,——普惠线下还款,——智多新线下还款,——普惠债转转出,——智多新债转转出\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['user_id']},{$value['user_name']},{$value['real_name']},{$value['mobile']},'{$value['idno']}',{$value['user_purpose']},{$value['increase']},{$value['reduce']},{$value['increase_reduce']},{$value['wait_capital']},{$value['wait_interest']},{$value['zx_wait_capital_total']},{$value['zx_wait_capital']},{$value['zx_new_wait_capital']},{$value['zx_wait_interest']},{$value['zx_money']},{$value['zx_lock_money']},{$value['zx_increase']},{$value['zx_recharge']},{$value['zx_buy_debt']},{$value['zx_reduce']},{$value['zx_withdraw']},{$value['zx_exchange']},{$value['zx_deduct']},{$value['zx_repay']},{$value['zx_sell_debt']},{$value['ph_wait_capital_total']},{$value['ph_wait_capital']},{$value['ph_new_wait_capital']},{$value['zdx_wait_capital_total']},{$value['ph_zdx_wait_capital']},{$value['ph_zdx_new_wait_capital']},{$value['ph_wait_interest']},{$value['ph_money']},{$value['ph_lock_money']},{$value['ph_increase']},{$value['ph_recharge']},{$value['ph_buy_debt']},{$value['ph_zdx_buy_debt']},{$value['ph_reduce']},{$value['ph_withdraw']},{$value['ph_exchange']},{$value['ph_zdx_exchange']},{$value['ph_deduct']},{$value['ph_zdx_deduct']},{$value['ph_repay']},{$value['ph_zdx_repay']},{$value['ph_sell_debt']},{$value['ph_zdx_sell_debt']}\n";
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
                $redis_key  = 'XF_User_Recharge_Withdraw_Download_Condition_'.$condition['id'];
                $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
                if(!$set){
                    Yii::log("{$redis_key} redis download set error","error");
                }
            }
        }
    }
}
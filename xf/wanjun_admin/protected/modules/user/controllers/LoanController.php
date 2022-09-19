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
        if (in_array($_GET['download'] , array(1 , 2 , 3 , 4 , 5, 6))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '出借人ID');
                $name = '债权列表 通过上传出借人ID查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款编号');
                $name = '债权列表 通过上传借款编号查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
                $name = '债权列表 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 4) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '项目名称');
                $name = '债权列表 通过上传项目名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 5) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '交易所备案编号');
                $name = '债权列表 通过上传交易所备案编号查询 '.date("Y年m月d日 H时i分s秒" , time());
            }else if ($_GET['download'] == 6) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款方ID');
                $name = '债权列表 通过上传借款方ID查询 '.date("Y年m月d日 H时i分s秒" , time());
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
            unset($data[0]);
            $model = Yii::app()->phdb;
            $platform = 2;
            $name = ' ';
            if ($type == 1) {
                if ($Rows > 10001) {
                    return $this->actionError('上传的文件中数据超过一万行' , 5);
                }
                $name .= ' 通过上传出借人ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_id_data = $model->createCommand($sql)->queryColumn();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addLoanListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $user_id_str));
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
                
            } else if ($type == 6) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行' , 5);
                }
                $name .= ' 通过上传借款方ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id , user_id FROM firstp2p_deal WHERE user_id IN ({$user_id_str}) AND deal_status = 4";
                $deal_res = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$deal_res) {
                    return $this->renderPartial('addLoanListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($deal_res as $key => $value) {
                    $user_ids[] = $value['user_id'];
                    $deal_id[] = $value['id'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $user_ids)) {
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
            $model_a = Yii::app()->phdb;
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
        $tpl_data = [
            'LoanList2Excel' => 0,
            'province_name_list' => Yii::app()->c->xf_config['province_name'],
        ];

        $result_data = [
            'data' => [],
            'count' =>0,
            'code' => 0,
            'info' => '',
        ];

        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_list_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->phdb->createCommand($sql)->queryRow();
                $con_data = json_decode($condition['data_json'] , true);
                if(!$condition || empty($con_data)){
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['code']  = 1;
                    $result_data['info']  = '无相关数据';
                    echo exit(json_encode($result_data));
                }
                if ($condition['true_count'] > 500) {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件超过500行，暂不支持列表查询，请使用导出功能！';
                    echo exit(json_encode($result_data));
                }
            }

            $model = Yii::app()->phdb;

            //平台
            if (!empty($_POST['deal_type']) && in_array($_POST['deal_type'], [2,4])) {
                $deal_type = trim($_POST['deal_type']);
                $where .= " AND deal.platform_id = $deal_type ";
            }
            // 校验投资记录ID
            if (!empty($_POST['deal_load_id'])) {
                $deal_load_id = trim($_POST['deal_load_id']);
                $where .= " AND deal_load.id = $deal_load_id  ";
            }
            //先锋ID
            if (!empty($_POST['xf_deal_load_id'])) {
                $xf_deal_load_id = trim($_POST['xf_deal_load_id']);
                $where .= " AND deal_load.xf_id = $xf_deal_load_id ";
            }
            // 校验借款编号
            if (!empty($_POST['deal_id'])) {
                $deal_id = trim($_POST['deal_id']);
                $where  .= " AND deal_load.deal_id = $deal_id ";
            }
            // 校验借款标题
            if (!empty($_POST['name'])) {
                $name   = trim($_POST['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验项目ID
            if (!empty($_POST['project_id'])) {
                $project_id = trim($_POST['project_id']);
                $where .= " AND deal.project_id = $project_id ";
            }
            // 校验项目名称
            if (!empty($_POST['project_name'])) {
                $project_name = trim($_POST['project_name']);
                $deal_project_id = $this->getProjectIds($project_name);
                $deal_project_id_str = implode(',' , $deal_project_id);
                $where .= " AND deal.project_id IN ({$deal_project_id_str}) ";
            }
            // 校验借款方ID
            if (!empty($_POST['company_id'])) {
                $company_id = trim($_POST['company_id']);
                $where  .= " AND deal.user_id = $company_id ";
            }

            // 校验借款方名称
            if (!empty($_POST['company'])) {
                $company = trim($_POST['company']);
                $deal_user_id = $this->getUserIds($company);
                $deal_user_id_str = implode(',' , $deal_user_id);
                $where .= " AND deal.user_id IN ({$deal_user_id_str}) ";
            }

            // 借款人债权所属省份
            if (!empty($_POST['province_name']) && $_POST['province_name'] != -1) {
                $province_name = trim($_POST['province_name']);
                $where  .= " AND deal_load.province_name = '{$province_name}' ";
            }

            // 检验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $advisory_id = $this->getAgencyIds($advisory_name);
                $advisory_id_str = implode(',' , $advisory_id);
                $where .= " AND deal.advisory_id IN ({$advisory_id_str}) ";
            }
            // 检验融资担保机构
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $agency_id = $this->getAgencyIds($agency_name);
                $agency_id_str = implode(',' , $agency_id);
                $where .= " AND deal.agency_id IN ({$agency_id_str}) ";
            }

            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND deal_load.user_id = $user_id ";
            }

            // 出借人名称
            if (!empty($_POST['real_name'])) {
                // 检验融资经办机构
                $real_name = trim($_POST['real_name']);
                $sql = "SELECT id FROM firstp2p_user WHERE real_name = '{$real_name}' ";
                $deal_user_id = $model->createCommand($sql)->queryColumn();
                if ($deal_user_id) {
                    $deal_user_id_str = implode(',' , $deal_user_id);
                    $where .= " AND deal_load.user_id IN ({$deal_user_id_str}) ";
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
                $count = 0;
                if ($con_data) {
                    $con_page = ceil(count($con_data) / 1000);
                    for ($i = 0; $i < $con_page; $i++) {
                        $con_data_arr = array();
                        for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                            if (!empty($con_data[$j])) {
                                $con_data_arr[] = $con_data[$j];
                            }
                        }
                        $con_data_str = implode(',' , $con_data_arr);
                        $where = " AND deal_load.id IN ({$con_data_str}) ";
                        $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load  where deal_load.status=1 {$where} ";
                        $count_con = $model->createCommand($sql)->queryScalar();
                        $count += $count_con;
                    }
                }
            } else {
                $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load LEFT JOIN firstp2p_deal AS deal ON deal_load.deal_id = deal.id WHERE deal_load.status = 1  {$where} ";
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
            $sql = "SELECT deal_load.id,deal.platform_id,deal_load.deal_id,deal.name as deal_name,deal.project_id,deal_project.name as project_name,
       deal_project.product_class as project_product_class,deal_load.wait_capital, deal.user_id as deal_user_id,deal_user.real_name as deal_user_real_name,
       deal_user.user_type,deal_load.province_name,deal_load.card_address, deal_advisory.name as deal_advisory_name,deal_agency.name as deal_agency_name,
       deal_load.user_id,deal_load_user.real_name, deal_load.xf_id 
                     FROM firstp2p_deal_load AS deal_load 
                     LEFT JOIN firstp2p_deal  AS deal ON deal_load.deal_id = deal.id 
                     LEFT JOIN firstp2p_deal_project  AS deal_project ON deal_project.id = deal.project_id
                     LEFT JOIN firstp2p_user  AS deal_user ON deal.user_id = deal_user.id 
                     LEFT JOIN firstp2p_user  AS deal_load_user ON deal_load.user_id = deal_load_user.id
                     LEFT JOIN firstp2p_deal_agency  AS deal_advisory ON deal_advisory.id = deal.advisory_id 
                     LEFT JOIN firstp2p_deal_agency  AS deal_agency ON deal_agency.id = deal.agency_id 
                     where deal_load.status=1 {$where} GROUP BY deal_load.id ORDER BY deal_load.id DESC ";
           // echo $sql;die;
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
            foreach ($list as $key => $value) {
                $value['xf_id'] = $value['xf_id'] ?: '-';
                $value['user_type_name'] = Yii::app()->c->xf_config['user_type'][$value['user_type']] ?: "未知";
                $value['platform_name'] = Yii::app()->c->xf_config['deal_type_cn'][$value['platform_id']] ?: "未知";
                $value['province_name_name'] = Yii::app()->c->xf_config['province_name'][$value['province_name']] ?: "未知";
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
        if (!empty($authList) && strstr($authList,'/user/Loan/LoanList2Excel') || empty($authList)) {
            $tpl_data['LoanList2Excel'] = 1;
        }
        return $this->renderPartial('GetLoanList', $tpl_data);
    }

    /**
     * 在途投资明细 列表 导出
     */
    public function actionLoanList2Excel()
    {
        if (!empty($_GET)) {
            set_time_limit(0);
            // 条件筛选
            $where = " ";
            $model = Yii::app()->phdb;
            //平台
            if (!empty($_GET['deal_type']) && in_array($_GET['deal_type'], [2,4])) {
                $deal_type = trim($_GET['deal_type']);
                $where .= " AND deal.platform_id = $deal_type ";
            }
            // 校验投资记录ID
            if (!empty($_GET['deal_load_id'])) {
                $deal_load_id = trim($_GET['deal_load_id']);
                $where .= " AND deal_load.id = $deal_load_id  ";
            }
            //先锋ID
            if (!empty($_GET['xf_deal_load_id'])) {
                $xf_deal_load_id = trim($_GET['xf_deal_load_id']);
                $where .= " AND deal_load.xf_id = $xf_deal_load_id ";
            }
            // 校验借款编号
            if (!empty($_GET['deal_id'])) {
                $deal_id = trim($_GET['deal_id']);
                $where  .= " AND deal_load.deal_id = $deal_id ";
            }
            // 校验借款标题
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验项目ID
            if (!empty($_GET['project_id'])) {
                $project_id = trim($_GET['project_id']);
                $where .= " AND deal.project_id = $project_id ";
            }
            // 校验项目名称
            if (!empty($_GET['project_name'])) {
                // 检验融资经办机构
                $project_name = trim($_GET['project_name']);
                $sql = "SELECT id FROM firstp2p_deal_project WHERE name = '{$project_name}' ";
                $deal_project_id = $model->createCommand($sql)->queryColumn();
                if ($deal_project_id) {
                    $deal_project_id_str = implode(',' , $deal_project_id);
                    $where .= " AND deal.project_id IN ({$deal_project_id_str}) ";
                } else {
                    $where .= " AND deal.project_id = -1 ";
                }
            }
            // 校验借款方ID
            if (!empty($_GET['company_id'])) {
                $company_id = trim($_GET['company_id']);
                $where  .= " AND deal.user_id = $company_id ";
            }

            // 校验借款方名称
            if (!empty($_GET['company'])) {
                // 检验融资经办机构
                $company = trim($_GET['company']);
                $sql = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                $deal_user_id = $model->createCommand($sql)->queryColumn();
                if ($deal_user_id) {
                    $deal_user_id_str = implode(',' , $deal_user_id);
                    $where .= " AND deal.user_id IN ({$deal_user_id_str}) ";
                } else {
                    $where .= " AND deal.user_id = -1 ";
                }
            }

            // 借款人债权所属省份
            if (!empty($_GET['province_name']) && $_GET['province_name'] != -1) {
                $province_name = trim($_GET['province_name']);
                $where  .= " AND deal_load.province_name = '{$province_name}' ";
            }

            // 检验融资经办机构
            if (!empty($_GET['advisory_name'])) {
                $advisory_name = trim($_GET['advisory_name']);
                $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$advisory_name}' ";
                $advisory_id = $model->createCommand($sql)->queryColumn();
                if ($advisory_id) {
                    $advisory_id_str = implode(',' , $advisory_id);
                    $where .= " AND deal.advisory_id IN ({$advisory_id_str}) ";
                } else {
                    $where .= " AND deal.advisory_id = -1 ";
                }
            }
            // 检验融资担保机构
            if (!empty($_GET['agency_name'])) {
                $agency_name = trim($_GET['agency_name']);
                $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND deal.agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND deal.agency_id = -1 ";
                }
            }

            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND deal_load.user_id = $user_id ";
            }

            // 出借人名称
            if (!empty($_GET['real_name'])) {
                // 检验融资经办机构
                $real_name = trim($_GET['real_name']);
                $sql = "SELECT id FROM firstp2p_user WHERE real_name = '{$real_name}' ";
                $deal_user_id = $model->createCommand($sql)->queryColumn();
                if ($deal_user_id) {
                    $deal_user_id_str = implode(',' , $deal_user_id);
                    $where .= " AND deal_load.user_id IN ({$deal_user_id_str}) ";
                } else {
                    $where .= " AND deal_load.user_id = -1 ";
                }
            }
            if ($_GET['deal_load_id']      == '' &&
                $_GET['xf_deal_load_id']      == '' &&
                $_GET['user_id']           == '' &&
                $_GET['xf_status']         == '' &&
                $_GET['deal_id']           == '' &&
                $_GET['name']              == '' &&
                $_GET['real_name']              == '' &&
                $_GET['project_id']        == '' &&
                $_GET['project_name']      == '' &&
                $_GET['jys_record_number'] == '' &&
                $_GET['company']           == '' &&
                $_GET['company_id']           == '' &&
                $_GET['deal_type']           == '' &&
                $_GET['advisory_name']     == '' &&
                $_GET['agency_name']       == '' &&
                $_GET['province_name']         == '' &&
                $_GET['debt_type']         == '' &&
                $_GET['condition_id']      == '' )
            {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            $count  = 0;
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_list_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->phdb->createCommand($sql)->queryRow();
                $con_data = json_decode($condition['data_json'] , true);
                if (!$condition || !$con_data) {
                    exit ('<h1>暂无数据</h1>');
                }
                $con_page = ceil(count($con_data) / 1000);
                for ($i = 0; $i < $con_page; $i++) {
                    $con_data_arr = array();
                    for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                        if (!empty($con_data[$j])) {
                            $con_data_arr[] = $con_data[$j];
                        }
                    }
                    $con_data_str = implode(',' , $con_data_arr);
                    $where = " AND deal_load.id IN ({$con_data_str}) ";
                    $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load  where deal_load.status=1 {$where} ";
                    $count_con = Yii::app()->phdb->createCommand($sql)->queryScalar();
                    $count += $count_con;
                }
            } else {
                $sql = "SELECT count(DISTINCT deal_load.id) AS count FROM firstp2p_deal_load AS deal_load LEFT JOIN firstp2p_deal AS deal ON deal_load.deal_id = deal.id WHERE deal_load.status = 1  {$where} ";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                $sql = "SELECT deal_load.id,deal.platform_id,deal_load.deal_id,deal.name as deal_name,deal.project_id,deal_project.name as project_name,
       deal_project.product_class as project_product_class,deal_load.wait_capital, deal.user_id as deal_user_id,deal_user.real_name as deal_user_real_name,
       deal_user.user_type,deal_load.province_name,deal_load.card_address, deal_advisory.name as deal_advisory_name,deal_agency.name as deal_agency_name,
       deal_load.user_id,deal_load_user.real_name, deal_load.xf_id 
                     FROM firstp2p_deal_load AS deal_load 
                     LEFT JOIN firstp2p_deal  AS deal ON deal_load.deal_id = deal.id 
                     LEFT JOIN firstp2p_deal_project  AS deal_project ON deal_project.id = deal.project_id
                     LEFT JOIN firstp2p_user  AS deal_user ON deal.user_id = deal_user.id 
                     LEFT JOIN firstp2p_user  AS deal_load_user ON deal_load.user_id = deal_load_user.id
                     LEFT JOIN firstp2p_deal_agency  AS deal_advisory ON deal_advisory.id = deal.advisory_id 
                     LEFT JOIN firstp2p_deal_agency  AS deal_agency ON deal_agency.id = deal.agency_id 
                     where deal_load.status=1 {$where} GROUP BY deal_load.id ORDER BY deal_load.id DESC  LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();
                foreach ($list as $key => $value) {
                    $value['user_type_name'] = Yii::app()->c->xf_config['user_type'][$value['user_type']] ?: "未知";
                    $value['platform_name'] = Yii::app()->c->xf_config['deal_type_cn'][$value['platform_id']] ?: "未知";
                    $value['province_name_name'] = Yii::app()->c->xf_config['province_name'][$value['province_name']] ?: "未知";
                    $listInfo[] = $value;
                }
            }
            $name = '债权列表'.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "投资记录ID,平台,借款编号,借款标题,项目ID,项目名称,产品大类,债权金额（在途）,借款方ID,借款方名称,借款人类型,归属地（省）,详细地址,融资经办机构名称,担保机构名称,出借人ID,出借人姓名,先锋投资记录ID\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['platform_name']},{$value['deal_id']},{$value['deal_name']},{$value['project_id']},{$value['project_name']},{$value['project_product_class']},{$value['wait_capital']},{$value['deal_user_id']},{$value['deal_user_real_name']},{$value['user_type_name']},{$value['province_name_name']},{$value['card_address']},{$value['deal_advisory_name']},{$value['deal_agency_name']},{$value['user_id']},{$value['real_name']},{$value['xf_id']}\n";
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
        $tpl_data = [
            'DealLoadBYDeal2Excel' => 0,
            //'province_name_list' => Yii::app()->c->xf_config['province_name'],
        ];

        $result_data = [
            'data' => [],
            'count' =>0,
            'code' => 0,
            'info' => '',
        ];
        if (!empty($_POST)) {
            // 条件筛选
            $where = " ";
            $model = Yii::app()->phdb;
            // 校验借款编号
            if (!empty($_POST['deal_id'])) {
                $deal_id = trim($_POST['deal_id']);
                $where .= " AND deal.id = '{$deal_id}' ";
            }
            if (!empty($_POST['platform_id'])) {
                $platform_id = trim($_POST['platform_id']);
                $where .= " AND deal.platform_id = '{$platform_id}' ";
            }
            //检验借款标题
            if (!empty($_POST['deal_name'])) {
                $deal_name = trim($_POST['deal_name']);
                $where .= " AND deal.name = '{$deal_name}' ";
            }
            // 校验项目ID
            if (!empty($_POST['project_id'])) {
                $project_id = trim($_POST['project_id']);
                $where .= " AND deal.project_id = '{$project_id}' ";
            }
            // 校验项目名称
            if (!empty($_POST['project_name'])) {
                $project_name = trim($_POST['project_name']);
                $deal_project_id = $this->getProjectIds($project_name);
                $deal_project_id_str = implode(',' , $deal_project_id);
                $where .= " AND deal.project_id IN ({$deal_project_id_str}) ";
            }

            // 校验借款方名称
            if (!empty($_POST['user_name'])) {
                $company = trim($_POST['user_name']);
                $deal_user_id = $this->getUserIds($company);
                $deal_user_id_str = implode(',' , $deal_user_id);
                $where .= " AND deal.user_id IN ({$deal_user_id_str}) ";
            }
            // 校验借款方名称
            if (!empty($_POST['company_id'])) {
                $company_id = trim($_POST['company_id']);
                $where    .= " AND deal.user_id = '{$company_id}' ";
            }
            //检验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $advisory_id = $this->getAgencyIds($advisory_name);
                $advisory_id_str = implode(',' , $advisory_id);
                $where .= " AND deal.advisory_id IN ({$advisory_id_str}) ";
            }
            //检验融资担保机构
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $agency_id = $this->getAgencyIds($agency_name);
                $agency_id_str = implode(',' , $agency_id);
                $where .= " AND deal.agency_id IN ({$agency_id_str}) ";
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
            $count = 0;
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_list_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->phdb->createCommand($sql)->queryRow();
                $con_data = json_decode($condition['data_json'] , true);
                if(!$condition || empty($con_data)){
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['code']  = 1;
                    $result_data['info']  = '无相关数据';
                    echo exit(json_encode($result_data));
                }
                if ($condition['true_count'] > 500) {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件超过500行，暂不支持列表查询，请使用导出功能！';
                    echo exit(json_encode($result_data));
                }
                $con_page = ceil(count($con_data) / 1000);
                for ($i = 0; $i < $con_page; $i++) {
                    $con_data_arr = array();
                    for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                        if (!empty($con_data[$j])) {
                            $con_data_arr[] = $con_data[$j];
                        }
                    }
                    $con_data_str = implode(',' , $con_data_arr);
                    $where = " AND deal.id IN ({$con_data_str}) ";
                    $sql = "SELECT count(DISTINCT deal.id) AS count FROM firstp2p_deal AS deal where deal.deal_status=4 {$where}";
                    $count_con = $model->createCommand($sql)->queryScalar();
                    $count += $count_con;
                }
            }else{
                $sql = "SELECT count(DISTINCT deal.id) AS count FROM firstp2p_deal AS deal where deal_status=4 {$where}";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            $time = strtotime(date("Y-m-d" , time())) - 28800;
            //查询数据
            $sql = "SELECT deal.id as deal_id , deal.platform_id, deal.name as deal_name, deal.project_id,deal_project.name as project_name , deal_project.product_class as project_product_class , 
               deal.repay_time as deal_repay_time, deal.loantype as deal_loantype , deal.max_repay_time,deal.ph_wait_capital,deal.user_id as deal_user_id,deal_user.real_name as deal_user_real_name,
               deal_advisory.name as deal_advisory_name,deal_agency.name as deal_agency_name,deal.deal_src ,deal.min_repay_time 
                FROM firstp2p_deal AS deal 
                LEFT JOIN firstp2p_deal_project  AS deal_project ON deal_project.id = deal.project_id
                LEFT JOIN firstp2p_user  AS deal_user ON deal.user_id = deal_user.id 
                LEFT JOIN firstp2p_deal_agency  AS deal_advisory ON deal_advisory.id = deal.advisory_id 
                LEFT JOIN firstp2p_deal_agency  AS deal_agency ON deal_agency.id = deal.agency_id 
                where deal.deal_status=4 {$where} GROUP BY deal.id ORDER BY deal.id DESC ";
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
                $result_data['code']  = 1;
                $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                echo exit(json_encode($result_data));
            }
            $list = $model->createCommand($sql)->queryAll();
            if (!$list) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            foreach ($list as $key => $value) {
                if ($value['deal_loantype'] == 5) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }

                $value['deal_loantype'] = Yii::app()->c->xf_config['loantype'][$value['deal_loantype']];
                $value['max_repay_time'] = date("Y-m-d" , ($value['max_repay_time'] + 28800));
                $value['overdue_day'] = round(($time - $value['min_repay_time']) / 86400);
                $value['platform_name'] = Yii::app()->c->xf_config['deal_type_cn'][$value['platform_id']] ?: "未知";
                $value['deal_src'] = Yii::app()->c->xf_config['deal_src'][$value['deal_src']] ?: "未知";
                $ph_wait_capital = $value['ph_wait_capital'];
                $value['ph_wait_capital'] = number_format($value['ph_wait_capital'], 2, '.', ',');

                $d_sql = "SELECT  count(distinct a.user_id) as investor_number,sum(a.wait_capital) as wait_capital,sum(a.wait_interest) as overdue_interest   from firstp2p_deal_load a 
                             where a.deal_id={$value['deal_id']} and a.status=1 and a.wait_capital>0 and a.xf_status=0 and a.black_status=1";
                $deal_load = Yii::app()->phdb->createCommand($d_sql)->queryRow();
                $wait_capital = $deal_load['wait_capital'] ?: 0;
                $overdue_interest = $deal_load['overdue_interest'] ?: 0;
                $value['sign_capital'] = number_format($wait_capital, 2, '.', ',');
                $value['overdue_interest'] = number_format($overdue_interest, 2, '.', ',');
                $value['investor_number'] = $deal_load['investor_number'] ?: 0;
                $value['sign_capital_wait_capital'] = round($wait_capital/$ph_wait_capital*100, 2).'%';
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
        if (!empty($authList) && strstr($authList,'/user/Loan/DealLoadBYDeal2Excel') || empty($authList)) {
            $tpl_data['DealLoadBYDeal2Excel'] = 1;
        }
        return $this->renderPartial('DealLoadBYDeal' , $tpl_data);
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
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款方名称');
                $name = '标的列表 通过上传借款方名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资经办机构名称');
                $name = '标的列表 通过上传融资经办机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资担保机构名称');
                $name = '标的列表 通过上传融资担保机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 4) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
                $name = '标的列表 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 5) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '项目名称');
                $name = '标的列表 通过上传项目名称查询 '.date("Y年m月d日 H时i分s秒" , time());
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
            if ($Rows > 1001) {
                return $this->actionError('上传的文件中数据超过一千行' , 5);
            }
            unset($data[0]);
            $name = '';
            $model = Yii::app()->phdb;
            $platform = 2;
            if ($type == 1) {
                $name .= ' 通过上传借款方名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $com_a = $model->createCommand("SELECT deal_id , deal_user_real_name FROM ag_wx_stat_repay WHERE deal_user_real_name IN ({$user_id_str})  group by deal_id ")->queryAll();
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
                $sql = "SELECT deal_id , deal_advisory_name FROM ag_wx_stat_repay WHERE deal_advisory_name IN ({$user_id_str}) group by deal_id ";
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
                $sql = "SELECT deal_id , deal_agency_name FROM ag_wx_stat_repay WHERE deal_agency_name IN ({$user_id_str}) group by deal_id ";
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
                $sql = "SELECT deal_id , deal_name FROM ag_wx_stat_repay WHERE deal_name IN ({$user_id_str}) group by deal_id ";
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
                $sql = "SELECT deal_id , project_name FROM ag_wx_stat_repay WHERE project_name IN ({$user_id_str}) group by deal_id ";
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

            }
            
            $sql = "INSERT INTO xf_deal_list_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
            $model_a = Yii::app()->phdb;
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
        if(empty($_GET)){
            die('请选择一个查询条件再导出');
        }
        set_time_limit(0);
        // 条件筛选
        $where = "";
        $count = 0;
        if (empty($_GET['platform_id']) &&
            empty($_GET['deal_id']) &&
            empty($_GET['deal_name']) &&
            empty($_GET['project_id']) &&
            empty($_GET['project_name']) &&
            empty($_GET['user_name']) &&
            empty($_GET['company_id']) &&
            empty($_GET['advisory_name']) &&
            empty($_GET['agency_name']) &&
            empty($_GET['deal_src']) &&
            empty($_GET['condition_id'])) {
            echo '<h1>请输入至少一个查询条件</h1>';
            exit;
        }
        if (!empty($_GET['deal_id'])) {
            $deal_id = trim($_GET['deal_id']);
            $where .= " AND deal.id = '{$deal_id}' ";
        }
        if (!empty($_GET['platform_id'])) {
            $platform_id = trim($_GET['platform_id']);
            $where .= " AND deal.platform_id = '{$platform_id}' ";
        }
        //检验借款标题
        if (!empty($_GET['deal_name'])) {
            $deal_name = trim($_GET['deal_name']);
            $where .= " AND deal.name = '{$deal_name}' ";
        }
        // 校验项目ID
        if (!empty($_GET['project_id'])) {
            $project_id = trim($_GET['project_id']);
            $where .= " AND deal.project_id = '{$project_id}' ";
        }
        // 校验项目名称
        if (!empty($_GET['project_name'])) {
            $project_name = trim($_GET['project_name']);
            $deal_project_id = $this->getProjectIds($project_name);
            $deal_project_id_str = implode(',' , $deal_project_id);
            $where .= " AND deal.project_id IN ({$deal_project_id_str}) ";
        }

        // 校验借款方名称
        if (!empty($_GET['user_name'])) {
            $company = trim($_GET['user_name']);
            $deal_user_id = $this->getUserIds($company);
            $deal_user_id_str = implode(',' , $deal_user_id);
            $where .= " AND deal.user_id IN ({$deal_user_id_str}) ";
        }
        // 校验借款方名称
        if (!empty($_GET['company_id'])) {
            $company_id = trim($_GET['company_id']);
            $where    .= " AND deal.user_id = '{$company_id}' ";
        }
        //检验融资经办机构
        if (!empty($_GET['advisory_name'])) {
            $advisory_name = trim($_GET['advisory_name']);
            $advisory_id = $this->getAgencyIds($advisory_name);
            $advisory_id_str = implode(',' , $advisory_id);
            $where .= " AND deal.advisory_id IN ({$advisory_id_str}) ";
        }
        //检验融资担保机构
        if (!empty($_GET['agency_name'])) {
            $agency_name = trim($_GET['agency_name']);
            $agency_id = $this->getAgencyIds($agency_name);
            $agency_id_str = implode(',' , $agency_id);
            $where .= " AND deal.agency_id IN ({$agency_id_str}) ";
        }
        if (!empty($_GET['condition_id'])) {
            $sql = "SELECT * FROM xf_deal_list_condition WHERE id = '{$_GET['condition_id']}' ";
            $condition = Yii::app()->phdb->createCommand($sql)->queryRow();
            $con_data = json_decode($condition['data_json'] , true);
            if (!$condition || !$con_data) {
                exit ('<h1>暂无数据</h1>');
            }
            $con_page = ceil(count($con_data) / 1000);
            for ($i = 0; $i < $con_page; $i++) {
                $con_data_arr = array();
                for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                    if (!empty($con_data[$j])) {
                        $con_data_arr[] = $con_data[$j];
                    }
                }
                $con_data_str = implode(',' , $con_data_arr);
                $where = " AND deal.id IN ({$con_data_str}) ";
                $sql = "SELECT count(DISTINCT deal.id) AS count FROM firstp2p_deal AS deal where deal.deal_status=4 {$where}";
                $count_con = Yii::app()->phdb->createCommand($sql)->queryScalar();
                $count += $count_con;
            }
        }else{
            $sql = "SELECT count(DISTINCT deal.id) AS count FROM firstp2p_deal AS deal where deal_status=4 {$where}";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
        }
        if ($count == 0) {
            echo '<h1>暂无数据</h1>';
            exit;
        }
        $page_count = ceil($count / 500);
        $time = strtotime(date("Y-m-d" , time())) - 28800;
        $loantype = Yii::app()->c->xf_config['loantype'];
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            //查询数据
            $sql = "SELECT deal.id as deal_id , deal.platform_id, deal.name as deal_name, deal.project_id,deal_project.name as project_name , deal_project.product_class as project_product_class , 
               deal.repay_time as deal_repay_time, deal.loantype as deal_loantype , deal.max_repay_time,deal.ph_wait_capital,deal.user_id as deal_user_id,deal_user.real_name as deal_user_real_name,
               deal_advisory.name as deal_advisory_name,deal_agency.name as deal_agency_name,deal.deal_src ,deal.min_repay_time 
                FROM firstp2p_deal AS deal 
                LEFT JOIN firstp2p_deal_project  AS deal_project ON deal_project.id = deal.project_id
                LEFT JOIN firstp2p_user  AS deal_user ON deal.user_id = deal_user.id 
                LEFT JOIN firstp2p_deal_agency  AS deal_advisory ON deal_advisory.id = deal.advisory_id 
                LEFT JOIN firstp2p_deal_agency  AS deal_agency ON deal_agency.id = deal.agency_id 
                where deal.deal_status=4 {$where} GROUP BY deal.id ORDER BY deal.id DESC LIMIT {$pass} , 500  ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                if ($value['deal_loantype'] == 5) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }

                $value['deal_loantype'] = Yii::app()->c->xf_config['loantype'][$value['deal_loantype']];
                $value['max_repay_time'] = date("Y-m-d" , ($value['max_repay_time'] + 28800));
                $value['overdue_day'] = round(($time - $value['min_repay_time']) / 86400);
                $value['platform_name'] = Yii::app()->c->xf_config['deal_type_cn'][$value['platform_id']] ?: "未知";
                $value['deal_src'] = Yii::app()->c->xf_config['deal_src'][$value['deal_src']] ?: "未知";
                $d_sql = "SELECT  count(distinct a.user_id) as investor_number,sum(a.wait_capital) as wait_capital,sum(a.wait_interest) as overdue_interest   from firstp2p_deal_load a 
                             where a.deal_id={$value['deal_id']} and a.status=1 and a.wait_capital>0 and a.xf_status=0 and a.black_status=1";
                $deal_load = Yii::app()->phdb->createCommand($d_sql)->queryRow();
                $wait_capital = $deal_load['wait_capital'] ?: 0;
                $overdue_interest = $deal_load['overdue_interest'] ?: 0;
                $value['sign_capital'] = $wait_capital;
                $value['overdue_interest'] = $overdue_interest;
                $value['investor_number'] = $deal_load['investor_number'] ?: 0;
                $value['sign_capital_wait_capital'] = round($value['sign_capital']/$value['ph_wait_capital']*100, 2).'%';
                $listInfo[] = $value;
            }
        }
        $name = '标的列表 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "借款编号,平台,借款标题,项目ID,项目名称,产品大类,借款期限,还款方式,应还款日期,逾期天数,逾期利息,原普惠在途本金,万峻持有总授权,授权与在途比例,借款方ID,借款方名称,融资经办机构名称,融资担保机构名称,当前债权人数,标的来源\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['deal_id']},{$value['platform_name']},{$value['deal_name']},{$value['project_id']},{$value['project_name']},{$value['project_product_class']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['max_repay_time']},{$value['overdue_day']},{$value['overdue_interest']},{$value['ph_wait_capital']},{$value['sign_capital']},{$value['sign_capital_wait_capital']},{$value['deal_user_id']},{$value['deal_user_real_name']},{$value['deal_advisory_name']},{$value['deal_agency_name']},{$value['investor_number']},{$value['deal_src']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data;
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
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '出借人ID');
                $name = '出借人列表 通过上传出借人ID查询 '.date("Y年m月d日 H时i分s秒" , time());
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

                $name .= '通过上传出借人ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_id_data = Yii::app()->phdb->createCommand($sql)->queryColumn();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDealLoadBYUserCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }

                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $user_id_data)) {
                        $false_id_arr[] = $value[0].'(未查询到用户)';
                    }  else {
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
            $model_a = Yii::app()->phdb;
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
     * 借款人列表
     * @return mixed
     */
    public function actionDealUser()
    {
        $tpl_data = [
            'dealUser2Excel' => 0,
            'province_name_list' => Yii::app()->c->xf_config['province_name'],
        ];

        // 校验用户ID
        $where = '';
        if (\Yii::app()->request->isPostRequest) {
            if (!empty($_POST['id'])) {
                $user_id = intval($_POST['id']);
                $where  .= " AND fu.id = {$user_id} ";
            }
            // 校验姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where  .= " AND fu.real_name = '{$real_name}' ";
            }
            // 校验手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key);
                $where .= " AND fu.mobile = '{$mobile}' ";
            }
            // 校验卡号
            if (!empty($_POST['bankcard'])) {
                $bank_card = trim($_POST['bankcard']);
                $bank_card = GibberishAESUtil::enc($bank_card, Yii::app()->c->idno_key);
                $where .= " AND fub.bankcard = '{$bank_card}' ";
            }
            // 校验证件号
            if (!empty($_POST['idno'])) {
                $idno = trim($_POST['idno']);
                $idno = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key);
                $where .= " AND fu.idno = '{$idno}' ";
            }
            // 查询归属地
            if (!empty($_POST['province_name']) && $_POST['province_name'] != -1) {
                $province_name = trim($_POST['province_name']);
                $where  .= " AND fu.province_name = '{$province_name}' ";
            }
            // 校验类型
            if (isset($_POST['user_type']) && $_POST['user_type'] !== '') {
                $where .= " AND fu.user_type = {$_POST['user_type']} ";
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
            $sql = "SELECT count(distinct fu.id) from firstp2p_user fu  
LEFT JOIN firstp2p_user_bankcard fub on fub.user_id=fu.id and fub.verify_status=1 
where fu.user_loan_type in (1,2)   {$where}  ";
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
            $sql = "SELECT fu.id,fu.real_name,fu.mobile,fu.idno,fu.user_type,fu.province_name,fu.card_address,fub.bankcard,fb.name
                    from firstp2p_user fu  
                    LEFT JOIN firstp2p_user_bankcard fub on fub.user_id=fu.id and fub.verify_status=1 
                    LEFT JOIN firstp2p_bank fb on fb.id=fub.bank_id
                    where fu.user_loan_type in (1,2)   {$where} 
                    GROUP BY fu.id ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            $wj_uid = Yii::app()->c->xf_config['displace_uid'];
            foreach ($list as $key => $value) {
                $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key) ?: '-';
                $value['idno'] = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key) ?: '-';
                $value['bankcard'] = GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key) ?: '-';
                $value['user_type_name'] = Yii::app()->c->xf_config['user_type'][$value['user_type']] ?: "未知";
                $value['province_name_name'] = Yii::app()->c->xf_config['province_name'][$value['province_name']] ?: "未知";

                //普惠本金
                $d_sql = "SELECT  sum(a.wait_capital) as wait_capital,sum(b.borrow_amount) as borrow_amount,sum(b.ph_wait_capital)  as ph_wait_capital  from firstp2p_deal_load a left join firstp2p_deal b on a.deal_id=b.id
                            where b.user_id={$value['id']} and a.status=1 and a.wait_capital>0 and a.xf_status=0 and a.black_status=1";
                $phwait_capital = Yii::app()->phdb->createCommand($d_sql)->queryRow();

                //授权金额
                $s_sql = "SELECT  sum(a.wait_capital) as wait_capital from firstp2p_deal_load a left join firstp2p_deal b on a.deal_id=b.id
                            where b.user_id={$value['id']} and a.status=1 and a.wait_capital>0 and a.xf_status=0 and a.black_status=1";
                $sq_capital = Yii::app()->phdb->createCommand($s_sql." and a.user_id!={$wj_uid}")->queryScalar() ?: 0;

                $value['wait_capital'] = number_format($phwait_capital['wait_capital'] , 2 , '.' , ',') ?: 0;
                $value['borrow_amount'] = number_format($phwait_capital['borrow_amount'] , 2 , '.' , ',') ?: 0;
                $value['ph_wait_capital'] = number_format($phwait_capital['ph_wait_capital'] , 2 , '.' , ',') ?: 0;
                $value['sq_amount'] = number_format($sq_capital , 2 , '.' , ',');
                $value['wj_sq_amount'] = number_format($value['wait_capital']-$value['sq_amount'] , 2 , '.' , ',');
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
        //导出列表数据权限
        if (!empty($authList) && strstr($authList,'/user/Loan/dealUser2Excel') || empty($authList)) {
            $tpl_data['dealUser2Excel'] = 1;
        }
        return $this->renderPartial('DealUser' , $tpl_data);
    }

    /**
     * 导出借款人列表
     */
    public function actionDealUser2Excel()
    {
        if(empty($_GET)){
            die('请选择一个查询条件再导出');
        }
        set_time_limit(0);
        // 条件筛选
        $where = " ";
        if (!empty($_GET['id'])) {
            $user_id = intval($_GET['id']);
            $where  .= " AND fu.id = {$user_id} ";
        }
        // 校验姓名
        if (!empty($_GET['real_name'])) {
            $real_name = trim($_GET['real_name']);
            $where  .= " AND fu.real_name = '{$real_name}' ";
        }
        // 校验手机号
        if (!empty($_GET['mobile'])) {
            $mobile = trim($_GET['mobile']);
            $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key);
            $where .= " AND fu.mobile = '{$mobile}' ";
        }
        // 校验卡号
        if (!empty($_GET['bankcard'])) {
            $bank_card = trim($_GET['bankcard']);
            $bank_card = GibberishAESUtil::enc($bank_card, Yii::app()->c->idno_key);
            $where .= " AND fub.bankcard = '{$bank_card}' ";
        }
        // 校验证件号
        if (!empty($_GET['idno'])) {
            $idno = trim($_GET['idno']);
            $idno = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key);
            $where .= " AND fu.idno = '{$idno}' ";
        }
        // 查询归属地
        if (!empty($_GET['province_name'])) {
            $province_name = trim($_GET['province_name']);
            $where  .= " AND fu.province_name = '{$province_name}' ";
        }
        // 校验类型
        if (isset($_GET['user_type']) && $_GET['user_type'] !== '') {
            $where .= " AND fu.user_type = {$_GET['user_type']} ";
        }
        if ($_GET['id']      == '' &&
            $_GET['real_name']      == '' &&
            $_GET['mobile']      == '' &&
            $_GET['bankcard']           == '' &&
            $_GET['idno']         == '' &&
            $_GET['province_name']           == '' &&
            $_GET['user_type']              == ''
               )
        {
            echo '<h1>请输入至少一个查询条件</h1>';
            exit;
        }
        $sql = "SELECT count(distinct fu.id) from firstp2p_user fu  
LEFT JOIN firstp2p_user_bankcard fub on fub.user_id=fu.id and fub.verify_status=1 
where fu.user_loan_type in (1,2)   {$where}  ";
        $count = Yii::app()->phdb->createCommand($sql)->queryScalar();

        if ($count == 0) {
            echo '<h1>暂无数据</h1>';
            exit;
        }
        $page_count = ceil($count / 500);
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            // 查询数据
            $sql = "SELECT fu.id,fu.real_name,fu.mobile,fu.idno,fu.user_type,fu.province_name,fu.card_address,fub.bankcard,fb.name
                    from firstp2p_user fu  
                    LEFT JOIN firstp2p_user_bankcard fub on fub.user_id=fu.id and fub.verify_status=1 
                    LEFT JOIN firstp2p_bank fb on fb.id=fub.bank_id
                    where fu.user_loan_type in (1,2)   {$where} 
                    GROUP BY fu.id  LIMIT {$pass} , 500 ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            $wj_uid = Yii::app()->c->xf_config['displace_uid'];
            foreach ($list as $key => $value) {
                $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key) ?: '-';
                $value['idno'] = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key) ?: '-';
                $value['bankcard'] = GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key) ?: '-';
                $value['user_type_name'] = Yii::app()->c->xf_config['user_type'][$value['user_type']] ?: "未知";
                $value['province_name_name'] = Yii::app()->c->xf_config['province_name'][$value['province_name']] ?: "未知";

                //普惠本金
                $d_sql = "SELECT  sum(a.wait_capital) as wait_capital,sum(b.borrow_amount) as borrow_amount,sum(b.ph_wait_capital)  as ph_wait_capital   from firstp2p_deal_load a left join firstp2p_deal b on a.deal_id=b.id
                            where b.user_id={$value['id']} and a.status=1 and a.wait_capital>0 and a.xf_status=0 and a.black_status=1";
                $phwait_capital = Yii::app()->phdb->createCommand($d_sql)->queryRow();

                //授权金额
                $s_sql = "SELECT  sum(a.wait_capital) as wait_capital from firstp2p_deal_load a left join firstp2p_deal b on a.deal_id=b.id
                            where b.user_id={$value['id']} and a.status=1 and a.wait_capital>0 and a.xf_status=0 and a.black_status=1";
                $sq_capital = Yii::app()->phdb->createCommand($s_sql." and a.user_id!={$wj_uid}")->queryScalar() ?: 0;

                $value['wait_capital'] = $phwait_capital['wait_capital'] ?: 0;
                $value['borrow_amount'] = $phwait_capital['borrow_amount'] ?: 0;
                $value['ph_wait_capital'] = $phwait_capital['ph_wait_capital'] ?: 0;
                $value['sq_amount'] = $sq_capital;
                $value['wj_sq_amount'] = bcsub($value['wait_capital'],$value['sq_amount'] ,2);
                $listInfo[] = $value;
            }
        }
        $name = '借款人列表'.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "借款方ID,借款方名称,手机号,证件号,借款方类型,归属地(省),详细地址,银行卡号,开户行,原普惠借款本金,原普惠待还本金,投资人置换后持有金额,万峻持有金额,万峻持有授权金额\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['real_name']},{$value['mobile']},'{$value['idno']},{$value['user_type_name']},{$value['province_name_name']},{$value['card_address']},'{$value['bankcard']},{$value['name']},{$value['borrow_amount']},{$value['ph_wait_capital']},{$value['sq_amount']},{$value['wj_sq_amount']},{$value['wait_capital']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data;
    }

    public function actionDealAgency()
    {

        $where = " ";
        if (!empty($_POST)) {
            //  姓名
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $where  .= " AND agency.name = '{$agency_name}' ";
            }
            if (empty($where) ){
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
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

            $sql = "SELECT count(distinct agency.name) 
                    from firstp2p_deal_load deal_load 
                    left join firstp2p_deal deal on deal.id=deal_load.deal_id 
                    left join firstp2p_deal_agency agency on agency.id=deal.agency_id  
                    where deal_load.status=1 and  agency.agency_type=1  {$where} ";
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
            $sql = "SELECT agency.id,agency.name as agency_name,agency.agency_deal_num,agency.agency_deal_amount ,
                    sum(deal_load.wait_capital) as sign_agency_deal_amount,count(distinct deal_load.deal_id) as sign_agency_deal_num
                    from firstp2p_deal_load deal_load 
                    left join firstp2p_deal deal on deal.id=deal_load.deal_id 
                    left join firstp2p_deal_agency agency on agency.id=deal.agency_id  
                    where deal_load.status=1 and  agency.agency_type=1  {$where} 
                    GROUP BY agency.name ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['agency_deal_amount'] = number_format($value['agency_deal_amount'], 2, '.', ',');
                $value['sign_agency_deal_amount'] = number_format($value['sign_agency_deal_amount'], 2, '.', ',');

                /*
                $sql = "SELECT sum(ph_increase_reduce) as ph_increase_reduce  from xf_user_recharge_withdraw WHERE user_id in (
                            SELECT DISTINCT user_id from firstp2p_deal_load where deal_id in (
                            SELECT id from firstp2p_deal where agency_id={$value['id']})) ";
                $ph_increase_reduce = Yii::app()->phdb->createCommand($sql)->queryScalar() ?: 0;
                $value['ph_increase_reduce'] = number_format($ph_increase_reduce, 2, '.', ',');
*/
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }


        return $this->renderPartial('DealAgency' );
    }
    public function actionDealAdvisory()
    {

        $where = " ";
        if (!empty($_POST)) {
            //  姓名
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $where  .= " AND agency.name = '{$agency_name}' ";
            }
            if (empty($where) ){
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
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

            $sql = "SELECT count(distinct agency.name) 
                    from firstp2p_deal_load deal_load 
                    left join firstp2p_deal deal on deal.id=deal_load.deal_id 
                    left join firstp2p_deal_agency agency on agency.id=deal.advisory_id  
                    where deal_load.status=1 and  agency.agency_type=2  {$where} ";
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
            $sql = "SELECT agency.id,agency.name as agency_name,agency.advisory_deal_num,agency.advisory_deal_amount ,
                    sum(deal_load.wait_capital) as sign_agency_deal_amount,count(distinct deal_load.deal_id) as sign_agency_deal_num
                    from firstp2p_deal_load deal_load 
                    left join firstp2p_deal deal on deal.id=deal_load.deal_id 
                    left join firstp2p_deal_agency agency on agency.id=deal.advisory_id  
                    where deal_load.status=1 and  agency.agency_type=2  {$where} 
                    GROUP BY agency.name ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['advisory_deal_amount'] = number_format($value['advisory_deal_amount'], 2, '.', ',');
                $value['sign_agency_deal_amount'] = number_format($value['sign_agency_deal_amount'], 2, '.', ',');


                /*
                $sql = "SELECT sum(ph_increase_reduce) as ph_increase_reduce  from xf_user_recharge_withdraw WHERE user_id in (
                            SELECT DISTINCT user_id from firstp2p_deal_load where deal_id in (
                            SELECT id from firstp2p_deal where advisory_id={$value['id']})) ";
                $ph_increase_reduce = Yii::app()->phdb->createCommand($sql)->queryScalar() ?: 0;
                $value['ph_increase_reduce'] = number_format($ph_increase_reduce, 2, '.', ',');*/
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }


        return $this->renderPartial('DealAdvisory' );
    }


    /**
     * @return mixed
     */
    public function actionDisplaceList()
    {
        $tpl_data = [
            'displaceList2Excel' => 0,
            'displaceListContract2Excel' => 0,
            'province_name_list' => Yii::app()->c->xf_config['province_name'],
        ];
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page' => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'user_id' =>Yii::app()->request->getParam('user_id'),
                'real_name' =>Yii::app()->request->getParam('real_name'),
                'mobile_phone' =>Yii::app()->request->getParam('mobile_phone'),
                'status' =>Yii::app()->request->getParam('status'),
                'province_name' =>Yii::app()->request->getParam('province_name'),
                'displace_type' =>Yii::app()->request->getParam('displace_type'),
                'idno' =>Yii::app()->request->getParam('idno'),
                'bank_card' =>Yii::app()->request->getParam('bank_card'),
                'id' =>Yii::app()->request->getParam('id'),
            ];
            //获取用户列表
            $importFileInfo = DisplaceService::getInstance()->getList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        //导出置换列表数据权限
        if (!empty($authList) && strstr($authList,'/user/Loan/displaceList2Excel') || empty($authList)) {
            $tpl_data['displaceList2Excel'] = 1;
        }
        //导出置换合同权限
        if (!empty($authList) && strstr($authList,'/user/Loan/displaceListContract2Excel') || empty($authList)) {
            $tpl_data['displaceListContract2Excel'] = 1;
        }

        return $this->renderPartial('displaceList', $tpl_data);
    }


    /**
     * 合同导出
     * @return mixed
     */
    public function actionDisplaceListContract2Excel()
    {
        set_time_limit(0); // 设置脚本最大执行时间 为0 永不过期

        $base_dir      = 'upload/contract';
        $time = date("YmdHis");
        $dir = $base_dir. '/置换记录ID-' .$_GET['id']. '/';

        $params = [
            'user_id' =>$_GET['user_id'],
            'real_name' =>$_GET['real_name'],
            'mobile_phone' =>$_GET['mobile_phone'],
            'status' =>$_GET['status'],
            'province_name' =>$_GET['province_name'],
            'displace_type' =>$_GET['displace_type'],
            'idno' =>$_GET['idno'],
            'bank_card' =>$_GET['bank_card'],
            'id' =>$_GET['id'],
            'dir_path'=>$dir
        ];
        $contract_ret = DisplaceService::getInstance()->createDisplaceContract($params);
        if($contract_ret == false){
            exit('下载失败！');
        }
        $zipName = $base_dir.'/置换记录ID-' .$_GET['id'].'.zip';
        $fp = fopen($zipName, 'w');
        fclose($fp);
        $zip = new \ZipArchive();
        // OVERWRITE选项表示每次压缩时都覆盖原有内容，但是如果没有那个压缩文件的话就会报错，所以事先要创建好压缩文件
        // 也可以使用CREATE选项，此选项表示每次压缩时都是追加，不是覆盖，如果事先压缩文件不存在会自动创建
        if ($zip->open($zipName, \ZipArchive::OVERWRITE) === true) {
            $this->addFileToZip($dir, $zip);
            $zip->close();
        } else {
            exit('下载失败！');
        }

        //ob_clean();
        $file = fopen($zipName, "r");
        //返回的文件类型
        Header("Content-type: application/octet-stream");
        //按照字节大小返回
        Header("Accept-Ranges: bytes");
        //返回文件的大小
        Header("Accept-Length: ".filesize($zipName));
        //这里设置客户端的弹出对话框显示的文件名
        Header("Content-Disposition: attachment; filename=置换记录ID-".$_GET['id'].".zip");
        //一次只传输1024个字节的数据给客户端
        //向客户端回送数据
        $buffer=1024;//
        //判断文件是否读完
        while (!feof($file)) {
            //将文件读入内存
            $file_data = fread($file, $buffer);
            //每次向客户端回送1024个字节的数据
            echo $file_data;
        }
        return true;
    }

    protected function addFileToZip($path, $zip)
    {
        // 打开文件夹资源
        $handler = opendir($path);
        // 循环读取文件夹内容
        while (($filename = readdir($handler)) !== false) {
            // 过滤掉Linux系统下的.和..文件夹

            if ($filename != '.' && $filename != '..') {
                // 文件指针当前位置指向的如果是文件夹，就递归压缩

                if (is_dir($path.'/'.$filename)) {
                    $this->addFileToZip($path.'/'.$filename, $zip);
                } else {
                    // 为了在压缩文件的同时也将文件夹压缩，可以设置第二个参数为文件夹/文件的形式，文件夹不存在自动创建压缩文件夹
                    $zip->addFile($path.'/'.$filename);
                    //$zip->renameName($path.'/'.$filename, $filename);
                }
            }
        }
        @closedir($handler);
    }

    /**
     * 出借人列表
     * @return mixed
     */
    public function actionDealLoadBYUser()
    {
        $tpl_data = [
            'DealLoadBYUser2Excel' => 0,
            'province_name_list' => Yii::app()->c->xf_config['province_name'],
        ];

        // 校验用户ID
        $where = '  ';
        if (\Yii::app()->request->isPostRequest) {
            //批量查询
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_deal_load_user_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->phdb->createCommand($sql)->queryRow();
                $con_data = json_decode($condition['data_json'] , true);
                if (!$condition || empty($con_data)) {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '无相关数据！';
                    echo exit(json_encode($result_data));
                }
                if ($condition['true_count'] > 500) {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件超过500行，暂不支持列表查询，请使用导出功能！';
                    echo exit(json_encode($result_data));
                }
            }

            if (!empty($_POST['id'])) {
                $user_id = intval($_POST['id']);
                $where  .= " AND fu.id = {$user_id} ";
            }
            // 校验姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where  .= " AND fu.real_name = '{$real_name}' ";
            }
            // 校验手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key);
                $where .= " AND fu.mobile = '{$mobile}' ";
            }
            // 校验证件号
            if (!empty($_POST['idno'])) {
                $idno = trim($_POST['idno']);
                $idno = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key);
                $where .= " AND fu.idno = '{$idno}' ";
            }
            // 查询归属地
            if (!empty($_POST['province_name']) && $_POST['province_name'] != -1) {
                $province_name = trim($_POST['province_name']);
                $where  .= " AND fu.province_name = '{$province_name}' ";
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
            $count = 0;
            if ($condition ) {
                if ($con_data) {
                    $con_page = ceil(count($con_data) / 1000);
                    for ($i = 0; $i < $con_page; $i++) {
                        $con_data_arr = array();
                        for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                            if (!empty($con_data[$j])) {
                                $con_data_arr[] = $con_data[$j];
                            }
                        }
                        $con_data_str = implode(',' , $con_data_arr);
                        $where    =   "  and fu.id IN ({$con_data_str}) ";
                        $sql = "SELECT count(1) AS count FROM firstp2p_user fu where fu.user_loan_type in (0,2)  $where ";
                        $count_con = Yii::app()->phdb->createCommand($sql)->queryScalar();
                        $count += $count_con;
                    }
                }
            } else {
                $sql = "SELECT count(distinct fu.id) from firstp2p_user fu  
                            LEFT JOIN firstp2p_user_bankcard fub on fub.user_id=fu.id and fub.verify_status=1 
                            where fu.user_loan_type in (0,2)   {$where}  ";
                $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            }


            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT urw.ph_increase_reduce,fu.id,fu.real_name,fu.mobile,fu.idno,fu.user_type,fu.province_name,fu.card_address,fub.bankcard,fb.name
                    from firstp2p_user fu  
                    LEFT JOIN firstp2p_user_bankcard fub on fub.user_id=fu.id and fub.verify_status=1 
                    LEFT JOIN firstp2p_bank fb on fb.id=fub.bank_id 
                    LEFT JOIN xf_user_recharge_withdraw urw on fu.id=urw.user_id
                    where fu.user_loan_type in (0,2) and fu.is_online=1   {$where} 
                    GROUP BY fu.id ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key) ?: '-';
                $value['idno'] = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key) ?: '-';
                $value['bankcard'] = GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key) ?: '-';
                $value['user_type_name'] = Yii::app()->c->xf_config['user_type'][$value['user_type']] ?: "未知";
                $value['province_name_name'] = Yii::app()->c->xf_config['province_name'][$value['province_name']] ?: "未知";

                $d_sql = "SELECT  sum( wait_capital) as wait_capital,count( id) as c_deal_load   from firstp2p_deal_load  
                            where user_id={$value['id']} and  status=1 and  wait_capital>0 and xf_status=0 and  black_status=1";
                $phwait_capital = Yii::app()->phdb->createCommand($d_sql)->queryRow();

                $value['wait_capital'] = number_format($phwait_capital['wait_capital'] , 2 , '.' , ',') ?: 0;
                $value['c_deal_load'] = $phwait_capital['c_deal_load'] ?: 0;
                $value['repay_amount'] = 0;

                $displace_uid = Yii::app()->c->xf_config['displace_uid'];
                if($value['id'] == $displace_uid){
                    $value['ph_increase_reduce'] = $value['wait_capital'];
                }else{
                    $value['ph_increase_reduce'] = number_format($value['ph_increase_reduce'] , 2 , '.' , ',') ?: 0;
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
        //导出列表数据权限
        if (!empty($authList) && strstr($authList,'/user/Loan/DealLoadBYUser2Excel') || empty($authList)) {
            $tpl_data['DealLoadBYUser2Excel'] = 1;
        }
        return $this->renderPartial('DealLoadBYUser' , $tpl_data);
    }

    /**
     * 导出出借人列表
     */
    public function actionDealLoadBYUser2Excel()
    {
        if(empty($_GET)){
            die('请选择一个查询条件再导出');
        }
        set_time_limit(0);
        // 条件筛选
        $where = " ";
        if (!empty($_GET['id'])) {
            $user_id = intval($_GET['id']);
            $where  .= " AND fu.id = {$user_id} ";
        }
        // 校验姓名
        if (!empty($_GET['real_name'])) {
            $real_name = trim($_GET['real_name']);
            $where  .= " AND fu.real_name = '{$real_name}' ";
        }
        // 校验手机号
        if (!empty($_GET['mobile'])) {
            $mobile = trim($_GET['mobile']);
            $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key);
            $where .= " AND fu.mobile = '{$mobile}' ";
        }
        // 校验证件号
        if (!empty($_GET['idno'])) {
            $idno = trim($_GET['idno']);
            $idno = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key);
            $where .= " AND fu.idno = '{$idno}' ";
        }
        // 查询归属地
        if (!empty($_GET['province_name'])) {
            $province_name = trim($_GET['province_name']);
            $where  .= " AND fu.province_name = '{$province_name}' ";
        }
        if ($_GET['id']      == '' &&
            $_GET['real_name']      == '' &&
            $_GET['mobile']      == '' &&
            $_GET['idno']         == '' &&
            $_GET['province_name']           == '' &&
            $_GET['condition_id']           == ''
        )
        {
            echo '<h1>请输入至少一个查询条件</h1>';
            exit;
        }
        $count  = 0;
        if (!empty($_GET['condition_id'])) {
            $sql = "SELECT * FROM xf_deal_load_user_condition WHERE id = '{$_GET['condition_id']}' ";
            $condition = Yii::app()->phdb->createCommand($sql)->queryRow();
            $con_data = json_decode($condition['data_json'] , true);
            if (!$condition || !$con_data) {
                exit ('<h1>暂无数据</h1>');
            }
            $con_page = ceil(count($con_data) / 1000);
            for ($i = 0; $i < $con_page; $i++) {
                $con_data_arr = array();
                for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                    if (!empty($con_data[$j])) {
                        $con_data_arr[] = $con_data[$j];
                    }
                }
                $con_data_str = implode(',' , $con_data_arr);
                $where    =   "  and fu.id IN ({$con_data_str}) ";
                $sql = "SELECT count(1) AS count FROM firstp2p_user fu where fu.user_loan_type in (0,2)  $where ";
                $count_con = Yii::app()->phdb->createCommand($sql)->queryScalar();
                $count += $count_con;
            }
        }else{
            $sql = "SELECT count(distinct fu.id) from firstp2p_user fu  
LEFT JOIN firstp2p_user_bankcard fub on fub.user_id=fu.id and fub.verify_status=1 
where fu.user_loan_type in (0,2)   {$where}  ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
        }
        if ($count == 0) {
            echo '<h1>暂无数据</h1>';
            exit;
        }
        $page_count = ceil($count / 500);
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            // 查询数据
            $sql = "SELECT urw.ph_increase_reduce,fu.id,fu.real_name,fu.mobile,fu.idno,fu.user_type,fu.province_name,fu.card_address,fub.bankcard,fb.name
                    from firstp2p_user fu  
                    LEFT JOIN firstp2p_user_bankcard fub on fub.user_id=fu.id and fub.verify_status=1 
                    LEFT JOIN firstp2p_bank fb on fb.id=fub.bank_id
                    LEFT JOIN xf_user_recharge_withdraw urw on fu.id=urw.user_id
                    where fu.user_loan_type in (0,2)   {$where} 
                    GROUP BY fu.id  LIMIT {$pass} , 500 ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key) ?: '-';
                $value['idno'] = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key) ?: '-';
                $value['bankcard'] = GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key) ?: '-';
                $value['user_type_name'] = Yii::app()->c->xf_config['user_type'][$value['user_type']] ?: "未知";
                $value['province_name_name'] = Yii::app()->c->xf_config['province_name'][$value['province_name']] ?: "未知";

                $d_sql = "SELECT  sum( wait_capital) as wait_capital,count( id) as c_deal_load   from firstp2p_deal_load  
                            where user_id={$value['id']} and  status=1 and  wait_capital>0 and xf_status=0 and  black_status=1";
                $phwait_capital = Yii::app()->phdb->createCommand($d_sql)->queryRow();

                $value['wait_capital'] = $phwait_capital['wait_capital'] ?: 0;
                $value['c_deal_load'] = $phwait_capital['c_deal_load'] ?: 0;
                $value['repay_amount'] = 0;

                $displace_uid = Yii::app()->c->xf_config['displace_uid'];
                if($value['id'] == $displace_uid){
                    $value['ph_increase_reduce'] = $value['wait_capital'];
                }else{
                    $value['ph_increase_reduce'] = $value['ph_increase_reduce'] ?: 0;
                }

                $listInfo[] = $value;
            }
        }
        $name = '出借人列表'.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "出借人ID,出借人名称,手机号,证件号,银行卡号,开户行,归属地(省),详细地址,在途金额,原普惠充提差,持有债权数量,已分配回款金额\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['real_name']},{$value['mobile']},'{$value['idno']},'{$value['bankcard']},{$value['name']},{$value['province_name_name']},{$value['card_address']},{$value['wait_capital']},{$value['ph_increase_reduce']},{$value['c_deal_load']},{$value['repay_amount']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data;
    }

    private function getUserIds($real_name){
        $return_data = [];
        if(empty($real_name)){
            $return_data[] = -1;
            return $return_data;
        }
        $sql = "SELECT id FROM firstp2p_user WHERE real_name = '{$real_name}' ";
        $deal_user_id = Yii::app()->phdb->createCommand($sql)->queryColumn();
        if(!$deal_user_id) {
            $return_data[] = -1;
            return $return_data;
        }
        return $deal_user_id;
    }

    private function getAgencyIds($real_name){
        $return_data = [];
        if(empty($real_name)){
            $return_data[] = -1;
            return $return_data;
        }
        $sql = "SELECT id FROM firstp2p_deal_agency WHERE name = '{$real_name}' ";
        $deal_user_id = Yii::app()->phdb->createCommand($sql)->queryColumn();
        if(!$deal_user_id) {
            $return_data[] = -1;
            return $return_data;
        }
        return $deal_user_id;
    }

    private function getProjectIds($real_name){
        $return_data = [];
        if(empty($real_name)){
            $return_data[] = -1;
            return $return_data;
        }
        $sql = "SELECT id FROM firstp2p_deal_project WHERE name = '{$real_name}' ";
        $deal_user_id = Yii::app()->phdb->createCommand($sql)->queryColumn();
        if(!$deal_user_id) {
            $return_data[] = -1;
            return $return_data;
        }
        return $deal_user_id;
    }

    /**
     *置换数据报表
     */
    public function actionDisplaceStatReport()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE  1=1 ";
            // 校验查询时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start'].' 00:00:00');
                $where .= " AND add_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end'].' 23:59:59');
                $where .= " AND add_time <= {$end} ";
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
            $sql   = "SELECT count(*) AS count FROM xf_displace_stat {$where} ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql  = "SELECT * FROM xf_displace_stat {$where} ORDER BY add_time DESC  ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value){
                $value['add_time'] = date('Y-m-d', $value['add_time']);
                $value['total_wait_capital'] = number_format($value['total_wait_capital'] , 2 , '.' , ',');
                $not_wj_wait_capital = bcsub($value['total_wait_capital'], $value['wj_wait_capital'], 2);
                 $value['not_wj_wait_capital'] = number_format($not_wj_wait_capital , 2 , '.' , ',');
                 $value['wj_wait_capital'] = number_format($value['wj_wait_capital'] , 2 , '.' , ',');
                 $value['fdd_displace_amount'] = number_format($value['fdd_displace_amount'] , 2 , '.' , ',');
                 $value['other_displace_amount'] = number_format($value['other_displace_amount'] , 2 , '.' , ',');
                $value['other_displace_rw_total'] = number_format($value['other_displace_rw_total'] , 2 , '.' , ',');
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
        $displaceStatReport2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/XFDebt/displaceStatReport2Excel') || empty($authList)) {
            $displaceStatReport2Excel = 1;
        }
        return $this->renderPartial('displaceStatReport' , array('displaceStatReport2Excel' => $displaceStatReport2Excel  ));
    }

    /**
     * 置换报表 导出
     */
    public function actionDisplaceStatReport2Excel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " WHERE  1=1 ";
            if (empty($_GET['start']) && empty($_GET['end'])  ) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            // 校验查询时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND add_time >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND add_time <= {$end} ";
            }

            // 查询数据
            $sql  = "SELECT * FROM xf_displace_stat {$where} ORDER BY id DESC ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            foreach ($list as $key => $value){
                $value['add_time'] = date('Y-m-d', $value['add_time']);
                 $value['not_wj_wait_capital'] = bcsub($value['total_wait_capital'], $value['wj_wait_capital'], 2);
                $listInfo[] = $value;
            }

            $name = '数据报表'.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "日期,持有在途债权总人数,法大大签约置换人数,用户点击确认置换人数,用户其他方式置换人数,系统批量操作人数,在途合计金额,在途金额（排除万峻）,万峻在途合计,法大大签约置换在途金额,非法大大签约置换在途金额\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['add_time']},{$value['user_number']},{$value['fdd_sign_user_number']},{$value['confirm_user_number']},{$value['other_user_number']},{$value['system_batch_user_number']},{$value['total_wait_capital']},{$value['not_wj_wait_capital']},{$value['wj_wait_capital']},{$value['fdd_displace_amount']},{$value['other_displace_amount']}\n";
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
     * 置换统计
     */
    public function actionDisplaceStat(){
        $tpl_data = [
            'total_user' => 0,
            'fdd_sign_num' => 0,
            'sub_sign_num' => 0,
            'other_sign_num' => 0,
            'system_sign_num' => 0,
            'capital_total' => 0.00,
            'no_wj_capital' => 0.00,
            'wj_capital' => 0.00,
            'fdd_displace_capital' => 0.00,
            'no_fdd_displace_capital' => 0.00,
        ];

        $sign_sql = "SELECT count(DISTINCT user_id) as c_user, sum(wait_capital) as s_wait_capital from firstp2p_deal_load WHERE status=1";
        $load_info = Yii::app()->phdb->createCommand($sign_sql)->queryRow();
        if($load_info){
            $tpl_data['total_user'] = $load_info['c_user'];
            $tpl_data['capital_total'] = $load_info['s_wait_capital'];
        }

        $displace_uid = Yii::app()->c->xf_config['displace_uid'];
        $sign_sql = "SELECT sum(wait_capital) as s_wait_capital from  firstp2p_deal_load WHERE status=1 and user_id=$displace_uid";
        $tpl_data['wj_capital'] = Yii::app()->phdb->createCommand($sign_sql)->queryScalar() ?: 0;
        $tpl_data['no_wj_capital'] = bcsub($tpl_data['capital_total'], $tpl_data['wj_capital'], 2);
        $tpl_data['capital_total'] = number_format($tpl_data['capital_total'] , 2 , '.' , ',');
        $tpl_data['no_wj_capital'] = number_format($tpl_data['no_wj_capital'] , 2 , '.' , ',');
        $tpl_data['wj_capital'] = number_format($tpl_data['wj_capital'] , 2 , '.' , ',');
        $sign_sql = "SELECT count(DISTINCT user_id) as c_user_id,displace_type,sum(displace_capital) as s_displace_capital from xf_displace_record WHERE status=5 group by displace_type";
        $displace_list = Yii::app()->phdb->createCommand($sign_sql)->queryAll();
        if(!$displace_list){
            return $this->renderPartial('displaceStat', $tpl_data);
        }

        foreach ($displace_list as $value){
            if($value['displace_type'] == 1){
                $tpl_data['fdd_sign_num'] = $value['c_user_id'];
                $tpl_data['fdd_displace_capital'] = $value['s_displace_capital'];
            }
            if($value['displace_type'] == 0){
                $tpl_data['system_sign_num'] = $value['c_user_id'];
                $tpl_data['no_fdd_displace_capital'] = bcadd($value['s_displace_capital'], $tpl_data['no_fdd_displace_capital'], 2);
            }
            if($value['displace_type'] == 2){
                $tpl_data['sub_sign_num'] = $value['c_user_id'];
                $tpl_data['no_fdd_displace_capital'] = bcadd($value['s_displace_capital'], $tpl_data['no_fdd_displace_capital'], 2);
            }
            if($value['displace_type'] == 3){
                $tpl_data['other_sign_num'] = $value['c_user_id'];
                $tpl_data['no_fdd_displace_capital'] = bcadd($value['s_displace_capital'], $tpl_data['no_fdd_displace_capital'], 2);
            }
        }
        $tpl_data['fdd_displace_capital'] = number_format($tpl_data['fdd_displace_capital'] , 2 , '.' , ',');
        $tpl_data['no_fdd_displace_capital'] = number_format($tpl_data['no_fdd_displace_capital'] , 2 , '.' , ',');
        return $this->renderPartial('displaceStat', $tpl_data);
    }

    /**
     * @return mixed
     */
    public function actionDisplaceList2Excel(){
        if(empty($_GET)){
            die('请选择一个查询条件再导出');
        }
        set_time_limit(0);
        // 条件筛选
        $where = " ";
        if (!empty($_GET['user_id'])) {
            $condition[] = " a.user_id = ".$_GET['user_id'];
        }
        if (!empty($_GET['real_name'])) {
            $condition[] = " a.real_name = '".$_GET['real_name']."'";
        }
        if (!empty($_GET['mobile_phone'])) {
            $condition[] = " a.mobile_phone = '".$_GET['mobile_phone']."'";
        }
        if (!empty($_GET['idno'])) {
            $condition[] = " a.idno = '".$_GET['idno']."'";
        }
        if (!empty($_GET['id'])) {
            $condition[] = " a.id = '".$_GET['id']."'";
        }
        if (!empty($_GET['bank_card'])) {
            $condition[] = " a.bank_card = '".$_GET['bank_card']."'";
        }
        if (isset($_GET['status']) && $_GET['status'] >= 0) {
            $condition[] = " a.status = '".$_GET['status']."'";
        }
        if (isset($_GET['province_name']) && $_GET['province_name'] >= 0) {
            $condition[] = " a.province_name = '".$_GET['province_name']."'";
        }
        if (isset($_GET['displace_type']) && $_GET['displace_type'] >= 0) {
            $condition[] = " a.displace_type = '".$_GET['displace_type']."'";
        }
        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition);
        }
        $count = XfDisplaceRecord::model()->countBySql('select count(1) from xf_displace_record as a  '.$where);
        if ($count == 0) {
            echo '<h1>暂无数据</h1>';
            exit;
        }
        $page_count = ceil($count / 500);
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            // 查询数据
            $sql = "select a.*  from xf_displace_record as a  {$where} order by a.id desc   LIMIT {$pass} , 500 ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $item) {
                $now_time = time();
                if($item['status'] == 5 && $now_time>$item['displace_time']+864000){
                    $item['status'] = 6 ;//置换完成10天后用户可见
                }
                $item['user_sign_time']= !empty($item['user_sign_time']) ? date('Y/m/d H:i:s', $item['user_sign_time']) : '-';
                $item['displace_time']= !empty($item['displace_time']) ? date('Y/m/d H:i:s', $item['displace_time']) : '-';
                $item['move_time']= !empty($item['move_time']) ? date('Y/m/d H:i:s', $item['move_time']) : '-';
                $item['debt_time']= !empty($item['debt_time']) ? date('Y/m/d H:i:s', $item['debt_time']) : '-';
                $item['province_name_cn'] = Yii::app()->c->xf_config['province_name'][$item['province_name']];
                $item['status_cn'] = Yii::app()->c->xf_config['displace_status'][$item['status']];
                $item['displace_type_cn'] = Yii::app()->c->xf_config['displace_type'][$item['displace_type']];

                $listInfo[] = $item;
            }
        }
        $name = '债权置换记录列表'.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "置换记录ID,出借人ID,姓名,手机号,证件号,归属地(省),详细地址,银行卡号,置换金额,原普惠充提差,用户签约时间,债转完成时间,债权迁移完成时间,置换完成时间,置换方式,置换状态\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['user_id']},{$value['real_name']},{$value['mobile_phone']},'{$value['idno']},{$value['province_name_cn']},{$value['card_address']},'{$value['bank_card']},{$value['displace_capital']},{$value['ph_increase_reduce']},{$value['user_sign_time']},{$value['debt_time']},{$value['move_time']},{$value['displace_time']},{$value['displace_type_cn']},{$value['status_cn']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data;
    }

    public function actionDisplaceDetail()
    {
        if (\Yii::app()->request->isPostRequest) {
            // 条件筛选
            $displace_id = \Yii::app()->request->getParam('id');
            $page =  \Yii::app()->request->getParam('page') ?: 1;
            $limit = 10;
            $model = Yii::app()->phdb;
            $sql = "SELECT count(DISTINCT id) AS count FROM xf_displace_deal_load  WHERE displace_id = $displace_id ";
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
            $sql = "SELECT deal_load.id,deal.platform_id,deal_load.deal_id,deal.name as deal_name,deal.project_id,deal_project.name as project_name,
       deal_project.product_class as project_product_class,deal_load.displace_amount, deal.user_id as deal_user_id,deal_user.real_name as deal_user_real_name,
       deal_user.user_type,deal_load.province_name,deal_load.card_address, deal_advisory.name as deal_advisory_name,deal_agency.name as deal_agency_name,
       deal_load.user_id,deal_load_user.real_name 
                     FROM xf_displace_deal_load AS deal_load 
                     LEFT JOIN firstp2p_deal  AS deal ON deal_load.deal_id = deal.id 
                     LEFT JOIN firstp2p_deal_project  AS deal_project ON deal_project.id = deal.project_id
                     LEFT JOIN firstp2p_user  AS deal_user ON deal.user_id = deal_user.id 
                     LEFT JOIN firstp2p_user  AS deal_load_user ON deal_load.user_id = deal_load_user.id
                     LEFT JOIN firstp2p_deal_agency  AS deal_advisory ON deal_advisory.id = deal.advisory_id 
                     LEFT JOIN firstp2p_deal_agency  AS deal_agency ON deal_agency.id = deal.agency_id 
                     where deal_load.displace_id=$displace_id GROUP BY deal_load.id ORDER BY deal_load.id DESC ";
            // echo $sql;die;
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
            foreach ($list as $key => $value) {
                $value['user_type_name'] = Yii::app()->c->xf_config['user_type'][$value['user_type']] ?: "未知";
                $value['platform_name'] = Yii::app()->c->xf_config['deal_type_cn'][$value['platform_id']] ?: "未知";
                $value['province_name_name'] = Yii::app()->c->xf_config['province_name'][$value['province_name']] ?: "未知";
                $listInfo[] = $value;
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }
        return $this->renderPartial('displaceDetail');
    }

    /**
     * 登录记录列表
     * @return mixed
     */
    public function actionLoginList()
    {
        $tpl_data = [
            'loginList2Excel' => 0,
        ];

        // 校验用户ID
        $where = '';
        if (\Yii::app()->request->isPostRequest) {
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND  user_id = {$user_id} ";
            }
            // 校验姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where  .= " AND  real_name = '{$real_name}' ";
            }
            // 校验手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $where .= " AND mobile_phone = '{$mobile}' ";
            }
            // 校验证件号
            if (!empty($_POST['idno'])) {
                $idno = trim($_POST['idno']);
                $where .= " AND idno = '{$idno}' ";
            }
            // 校验查询时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start'].' 00:00:00');
                $where .= " AND login_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end'].' 23:59:59');
                $where .= " AND login_time <= {$end} ";
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
            $sql = "SELECT count(distinct user_id) from xf_user_login_log  where 1=1   {$where}  ";
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
            $sql = "SELECT user_id,real_name,mobile_phone,idno,count(1) as login_count,max(login_time) as login_time from xf_user_login_log  where 1=1   {$where}  group by user_id order by login_time desc ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['login_time'] = date("Y-m-d H:i:s", $value['login_time']);
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
        //导出列表数据权限
        if (!empty($authList) && strstr($authList,'/user/Loan/loginList2Excel') || empty($authList)) {
            $tpl_data['loginList2Excel'] = 1;
        }
        return $this->renderPartial('loginList' , $tpl_data);
    }


    public function actionLoginView()
    {
        if (\Yii::app()->request->isPostRequest) {
            $user_id = Yii::app()->request->getParam('user_id') ?: 0;
            $limit = \Yii::app()->request->getParam('limit') ?: 10;
            $page = \Yii::app()->request->getParam('page') ?: 1;
            $sql = "SELECT count(1) from xf_user_login_log  where  user_id = $user_id  ";
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
            $sql = "SELECT * from xf_user_login_log  where  user_id = $user_id order by login_time desc ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['login_time'] = date("Y-m-d H:i:s", $value['login_time']);
                $value['data_src'] = Yii::app()->c->xf_config['login_data_src'][$value['data_src']];
                $value['login_device'] = $value['login_device'] == 'null(null)' ? "PC" : $value['login_device'];
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }
        return $this->renderPartial('loginView',['user_id'=>Yii::app()->request->getParam('user_id')]);
    }

    /**
     * 导出登录记录列表
     */
    public function actionLoginList2Excel()
    {
        if(empty($_GET)){
            die('请选择一个查询条件再导出');
        }
        set_time_limit(0);
        // 条件筛选
        $where = " ";
        if (!empty($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            $where  .= " AND  user_id = {$user_id} ";
        }
// 校验姓名
        if (!empty($_GET['real_name'])) {
            $real_name = trim($_GET['real_name']);
            $where  .= " AND  real_name = '{$real_name}' ";
        }
// 校验手机号
        if (!empty($_GET['mobile'])) {
            $mobile = trim($_GET['mobile']);
            $where .= " AND mobile_phone = '{$mobile}' ";
        }
// 校验证件号
        if (!empty($_GET['idno'])) {
            $idno = trim($_GET['idno']);
            $where .= " AND idno = '{$idno}' ";
        }
// 校验查询时间
        if (!empty($_GET['start'])) {
            $start  = strtotime($_GET['start'].' 00:00:00');
            $where .= " AND login_time >= {$start} ";
        }
        if (!empty($_GET['end'])) {
            $end    = strtotime($_GET['end'].' 23:59:59');
            $where .= " AND login_time <= {$end} ";
        }
        if ($_GET['user_id']      == '' &&
            $_GET['real_name']      == '' &&
            $_GET['mobile']      == '' &&
            $_GET['idno']         == '' &&
            $_GET['start']           == '' &&
            $_GET['end']              == ''
        )
        {
            echo '<h1>请输入至少一个查询条件</h1>';
            exit;
        }
        $sql = "SELECT count(distinct user_id) from xf_user_login_log  where 1=1   {$where}  ";
        $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
        if ($count == 0) {
            echo '<h1>暂无数据</h1>';
            exit;
        }
        $page_count = ceil($count / 500);
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            // 查询数据
            $sql = "SELECT user_id,real_name,mobile_phone,idno,count(1) as login_count,max(login_time) as login_time from xf_user_login_log  where 1=1   {$where}  group by user_id order by login_time desc LIMIT {$pass} , 500 ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['login_time'] = date("Y-m-d H:i:s", $value['login_time']);
                $listInfo[] = $value;
            }
        }
        $name = '用户登录记录'.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "出借人ID,出借人姓名,出借人手机号,出借人证件号,登录次数,最后登录时间\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['user_id']},{$value['real_name']},{$value['mobile_phone']},'{$value['idno']},{$value['login_count']},{$value['login_time']} \n";
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
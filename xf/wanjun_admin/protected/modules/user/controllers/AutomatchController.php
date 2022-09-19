<?php

class AutomatchController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success', 'Error', 'CheckAgencyName', 'ZXPartialRepayDetail', 'PHPartialRepayDetail', 'GCWJPartialRepayDetail', 'ZDXPartialRepayDetail'
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
     * 上传压缩文件
     * @param name  string  压缩文件名称
     * @return array
     */
    private function upload_rar($name)
    {
        $file  = $_FILES[$name];
        $types = array('rar' , 'zip' , '7z');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的压缩文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的压缩文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => '压缩文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有压缩文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => '压缩文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => '压缩文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if(!in_array($file_type, $types)){
            return array('code' => 2007 , 'info' => '压缩文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000,99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir , 0777 , true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建压缩文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存压缩文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存压缩文件失败' , 'data' => '');
        }
    }

    /**
     * 文件上传OSS
     * @param filePath
     * @param ossPath
     * @return bool
     */
    private function upload_oss($filePath, $ossPath)
    {
        Yii::log(basename($filePath).'文件正在上传!', CLogger::LEVEL_INFO);
        try {
            ini_set('memory_limit', '2048M');
            $res = Yii::app()->oss->bigFileUpload($filePath, $ossPath);
            unlink($filePath);
            return $res;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * AJAX检验咨询方名称
     */
    public function actionCheckAgencyName()
    {
        if (empty($_POST['platform_id']) || !in_array($_POST['platform_id'] , [1, 2, 3, 4])) {
            $this->echoJson([], 1, '请正确输入平台ID');
        }
        $agency_name = trim($_POST['agency_name']);
        if (empty($agency_name)) {
            $this->echoJson([], 2, '请输入咨询方名称');
        }
        if ($_POST['platform_id'] == 1) {
            $model = Yii::app()->fdb;
            $table = 'firstp2p_deal_agency';
        } else if ($_POST['platform_id'] == 2) {
            $model = Yii::app()->phdb;
            $table = 'firstp2p_deal_agency';
        } else if (in_array($_POST['platform_id'] , [3, 4])) {
            $model = Yii::app()->offlinedb;
            $table = 'offline_deal_agency';
        }
        $sql = "SELECT * FROM {$table} WHERE is_effect = 1 AND name = '{$agency_name}' ";
        $res = $model->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson([], 3, '通过此咨询方名称未查询到对应信息');
        }
        $this->echoJson([], 0, '查询成功');
    }

    /**
     * 检验咨询方名称
     */
    private function checkAgencyName($platform_id = 0 , $agency_name = '')
    {
        if (!in_array($platform_id , [1, 2, 3, 4])) {
            return false;
        }
        if (empty($agency_name)) {
            return false;
        }
        if ($platform_id == 1) {
            $model = Yii::app()->fdb;
            $table = 'firstp2p_deal_agency';
        } else if ($platform_id == 2) {
            $model = Yii::app()->phdb;
            $table = 'firstp2p_deal_agency';
        } else if (in_array($platform_id , [3, 4])) {
            $model = Yii::app()->offlinedb;
            $table = 'offline_deal_agency';
        }
        $sql = "SELECT * FROM {$table} WHERE is_effect = 1 AND name = '{$agency_name}' ";
        $res = $model->createCommand($sql)->queryRow();
        if (!$res) {
            return false;
        }
        return $res;
    }

    /**
     * 尊享匹配债权部分还款 新增
     */
    public function actionAddZXPartialRepay()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '还款金额（单位元，保留两位小数）');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '尊享匹配债权还本录入 '.date("Y年m月d日 H时i分s秒" , time());

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
            // 校验付款方
            $param['platform_id'] = 1;
            if (empty($_POST['pay_user'])) {
                return $this->actionError('请输入付款方' , 5);
            }
            $param['pay_user'] = trim($_POST['pay_user']);
            // 校验还款信息
            if (empty($_FILES['template'])) {
                return $this->actionError('请上传还款信息' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            // 检验咨询方名称
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $agency_info = $this->checkAgencyName(1, $agency_name);
                if (!$agency_info) {
                    return $this->actionError('通过此咨询方名称未查询到对应信息' , 5);
                }
                $param['advisory_id'] = $agency_info['id'];
            } else {
                $param['advisory_id'] = 0;
            }
            // 校验计划还款日期
            if (!empty($_POST['pay_plan_time'])) {
                $param['pay_plan_time'] = strtotime($_POST['pay_plan_time']);
            } else {
                $param['pay_plan_time'] = strtotime(date('Y-m-d' , time()));
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$upload_xls['data'];
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('还款信息中无数据' , 5);
            }
            if ($Rows > 50001) {
                return $this->actionError('还款信息中的数据超过5万行' , 5);
            }
            unset($data[0]);
            foreach ($data as $key => $value) {
                if (empty($value[0])) {
                    return $this->actionError('第'.($key+1).'行缺少用户ID' , 5);
                }
                if (empty($value[1])) {
                    return $this->actionError('第'.($key+1).'行缺少还款金额' , 5);
                }
                if (!is_numeric($value[0])) {
                    return $this->actionError('第'.($key+1).'行用户ID格式错误' , 5);
                }
                if (!is_numeric($value[1])) {
                    return $this->actionError('第'.($key+1).'行还款金额格式错误' , 5);
                }
                if ($value[1] <= 0) {
                    return $this->actionError('第'.($key+1).'行还款金额输入错误，应为正数' , 5);
                }
            }
            $upload_oss = $this->upload_oss('./'.$upload_xls['data'] , 'partial_repay/'.$upload_xls['data']);
            if ($upload_oss === false) {
                unlink('./'.$upload_xls['data']);
                return $this->actionError('上传文件至OSS失败' , 5);
            }
            $param['template_url'] = 'partial_repay/'.$upload_xls['data'];
            $param['data']         = $data;
            unlink('./'.$upload_xls['data']);
            $result = AutomatchService::getInstance()->addPartialRepayment($param);
            if ($result['code'] != 0) {  
                return $this->actionError($result['info'] , 5);
            }
            return $this->actionSuccess($result['info'] , 3);
        }

        return $this->renderPartial('AddZXPartialRepay', array());
    }

    /**
     * 尊享匹配债权部分还款 列表
     */
    public function actionZXPartialRepayList()
    {
        if (!empty($_POST)) {
            $param['platform_id'] = 1;
            if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
                $param['id'] = intval($_POST['id']);
            } else {
                $param['id'] = '';
            }
            if (!empty($_POST['status']) && is_numeric($_POST['status'])) {
                $param['status'] = intval($_POST['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_POST['start'])) {
                $param['start'] = strtotime($_POST['start']);
            } else {
                $param['start'] = '';
            }
            if (!empty($_POST['end'])) {
                $param['end'] = strtotime($_POST['end']);
            } else {
                $param['end'] = '';
            }
            if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                $param['limit'] = intval($_POST['limit']);
                if ($param['limit'] < 1) {
                    $param['limit'] = 1;
                }
            } else {
                $param['limit'] = 10;
            }
            if (!empty($_POST['page']) && is_numeric($_POST['page'])) {
                $param['page'] = intval($_POST['page']);
                if ($param['page'] < 1) {
                    $param['page'] = 1;
                }
            } else {
                $param['page'] = 1;
            }
            $result = AutomatchService::getInstance()->PartialRepaymentList($param);
            if ($result['code'] !== 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = $result['code'];
                $result_data['info']  = $result['info'];
                echo exit(json_encode($result_data));
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $result['data'];
            $result_data['count'] = $result['count'];
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/Automatch/AddZXPartialRepay') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('ZXPartialRepayList', array('add_status' => $add_status));
    }

    /**
     * 尊享匹配债权部分还款 详情
     */
    public function actionZXPartialRepayDetail()
    {
        if (!empty($_POST)) {
            $param['platform_id'] = 1;
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入信息';
                echo exit(json_encode($result_data));
            }
            $param['id'] = intval($_POST['id']);
            if (!empty($_POST['name'])) {
                $param['name'] = trim($_POST['name']);
            } else {
                $param['name'] = '';
            }
            if (!empty($_POST['deal_loan_id']) && is_numeric($_POST['deal_loan_id'])) {
                $param['deal_loan_id'] = intval($_POST['deal_loan_id']);
            } else {
                $param['deal_loan_id'] = '';
            }
            if (!empty($_POST['user_id']) && is_numeric($_POST['user_id'])) {
                $param['user_id'] = intval($_POST['user_id']);
            } else {
                $param['user_id'] = '';
            }
            if (!empty($_POST['status']) && is_numeric($_POST['status'])) {
                $param['status'] = intval($_POST['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_POST['repay_status']) && is_numeric($_POST['repay_status'])) {
                $param['repay_status'] = intval($_POST['repay_status'])-1;
            } else {
                $param['repay_status'] = '';
            }
            if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                $param['limit'] = intval($_POST['limit']);
                if ($param['limit'] < 1) {
                    $param['limit'] = 1;
                }
            } else {
                $param['limit'] = 10;
            }
            if (!empty($_POST['page']) && is_numeric($_POST['page'])) {
                $param['page'] = intval($_POST['page']);
                if ($param['page'] < 1) {
                    $param['page'] = 1;
                }
            } else {
                $param['page'] = 1;
            }
            $result = AutomatchService::getInstance()->PartialRepaymentDetail($param);
            if ($result['code'] !== 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = $result['code'];
                $result_data['info']  = $result['info'];
                echo exit(json_encode($result_data));
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $result['data'];
            $result_data['count'] = $result['count'];
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList      = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList,'/user/Automatch/ZXPartialRepayExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('ZXPartialRepayDetail', array('daochu_status' => $daochu_status));
    }

    /**
     * 尊享匹配债权部分还款 导出
     */
    public function actionZXPartialRepayExcel()
    {
        if (!empty($_GET)) {
            $param['platform_id'] = 1;
            $param['download']    = 1;
            // 条件筛选
            if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
                echo '<h1>请正确输入信息</h1>';exit;
            }
            $param['id'] = intval($_GET['id']);
            if (!empty($_GET['name'])) {
                $param['name'] = trim($_GET['name']);
            } else {
                $param['name'] = '';
            }
            if (!empty($_GET['deal_loan_id']) && is_numeric($_GET['deal_loan_id'])) {
                $param['deal_loan_id'] = intval($_GET['deal_loan_id']);
            } else {
                $param['deal_loan_id'] = '';
            }
            if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
                $param['user_id'] = intval($_GET['user_id']);
            } else {
                $param['user_id'] = '';
            }
            if (!empty($_GET['status']) && is_numeric($_GET['status'])) {
                $param['status'] = intval($_GET['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_GET['repay_status']) && is_numeric($_GET['repay_status'])) {
                $param['repay_status'] = intval($_GET['repay_status'])-1;
            } else {
                $param['repay_status'] = '';
            }
            $result = AutomatchService::getInstance()->PartialRepaymentDetail($param);
            if (empty($result['data'])) {
                echo '<h1>未查询到数据</h1>';exit;
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
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
            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '到期日');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '投资记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '还款金额');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '导入状态');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '实际还款时间');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '还款状态');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '失败原因');

            foreach ($result['data'] as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['end_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['deal_loan_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['repay_money'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['status'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['repay_yestime']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['repay_status']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['remark']);
            }
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = "尊享匹配债权部分还款详情 ".date("Y年m月d日 H时i分s秒" , time());
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
        }
    }

    /**
     * 尊享匹配债权部分还款 编辑
     */
    public function actionEditZXPartialRepay()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])){
                return $this->actionError('请输入ID' , 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_partial_repayment WHERE id = {$id}";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('状态错误' , 5);
            }
            // 校验还款凭证
            if (empty($_FILES['proof']) && empty($res['proof_url'])) {
                return $this->actionError('请上传还款凭证' , 5);
            }
            $upload_rar = $this->upload_rar('proof');
            if ($upload_rar['code'] != 0) {
                if (!empty($res['proof_url'])) {
                    $proof_url = $res['proof_url'];
                } else {
                    return $this->actionError($upload_rar['info'] , 5);
                }
            } else {
                $proof_url  = $upload_rar['data'];
                $upload_oss = $this->upload_oss('./'.$proof_url , 'partial_repay/'.$proof_url);
                if ($upload_oss === false) {
                    unlink('./'.$proof_url);
                    return $this->actionError('上传文件至OSS失败' , 5);
                }
                $proof_url = 'partial_repay/'.$proof_url;
            }
            // 校验计划还款日期
            $now = strtotime(date('Y-m-d' , time()));
            if (!empty($_POST['pay_plan_time'])) {
                $pay_plan_time = strtotime($_POST['pay_plan_time']);
            } else {
                $pay_plan_time = $now;
            }
            if ($pay_plan_time < $now) {
                return $this->actionError('计划还款时间必须大于等于今日凌晨' , 5);
            }
            $time = time();
            $sql  = "UPDATE ag_wx_partial_repayment SET proof_url = '{$proof_url}' , pay_plan_time = {$pay_plan_time} , updatetime = {$time} WHERE id = {$res['id']}";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                return $this->actionError('保存失败' , 5);
            }
            return $this->actionSuccess('保存成功' , 3);
        }

        if (empty($_GET['id'])) {
            return $this->actionError('请输入ID' , 5);
        }
        $id  = intval($_GET['id']);
        $sql = "SELECT * FROM ag_wx_partial_repayment WHERE id = {$id}";
        $res = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$res) {
            return $this->actionError('ID输入错误' , 5);
        }
        if ($res['status'] != 1) {
            return $this->actionError('状态错误' , 5);
        }
        $res['pay_plan_time'] = date('Y-m-d' , $res['pay_plan_time']);
        $proof_url            = explode('/', $res['proof_url']);
        $res['proof_url']     = $proof_url[2];

        return $this->renderPartial('EditZXPartialRepay', array('res' => $res));
    }

    /**
     * 尊享匹配债权部分还款 移除
     */
    public function actionDeleteZXPartialRepay()
    {
        $param['platform_id'] = 1;
        $param['status']      = 6;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 尊享匹配债权部分还款 通过
     */
    public function actionAllowedZXPartialRepay()
    {
        $param['platform_id'] = 1;
        $param['status']      = 2;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 尊享匹配债权部分还款 拒绝
     */
    public function actionRefuseZXPartialRepay()
    {
        $param['platform_id'] = 1;
        $param['status']      = 3;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        if (empty($_POST['remark'])) {
            $this->echoJson([], 1, "请输入拒绝原因");
        }
        $param['remark'] = trim($_POST['remark']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 普惠匹配债权部分还款 新增
     */
    public function actionAddPHPartialRepay()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '还款金额（单位元，保留两位小数）');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '普惠匹配债权还本录入 '.date("Y年m月d日 H时i分s秒" , time());

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
            // 校验付款方
            $param['platform_id'] = 2;
            if (empty($_POST['pay_user'])) {
                return $this->actionError('请输入付款方' , 5);
            }
            $param['pay_user'] = trim($_POST['pay_user']);
            // 校验还款信息
            if (empty($_FILES['template'])) {
                return $this->actionError('请上传还款信息' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            // 检验咨询方名称
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $agency_info = $this->checkAgencyName(2, $agency_name);
                if (!$agency_info) {
                    return $this->actionError('通过此咨询方名称未查询到对应信息' , 5);
                }
                $param['advisory_id'] = $agency_info['id'];
            } else {
                $param['advisory_id'] = 0;
            }
            // 校验计划还款日期
            if (!empty($_POST['pay_plan_time'])) {
                $param['pay_plan_time'] = strtotime($_POST['pay_plan_time']);
            } else {
                $param['pay_plan_time'] = strtotime(date('Y-m-d' , time()));
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$upload_xls['data'];
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('还款信息中无数据' , 5);
            }
            if ($Rows > 50001) {
                return $this->actionError('还款信息中的数据超过5万行' , 5);
            }
            unset($data[0]);
            foreach ($data as $key => $value) {
                if (empty($value[0])) {
                    return $this->actionError('第'.($key+1).'行缺少用户ID' , 5);
                }
                if (empty($value[1])) {
                    return $this->actionError('第'.($key+1).'行缺少还款金额' , 5);
                }
                if (!is_numeric($value[0])) {
                    return $this->actionError('第'.($key+1).'行用户ID格式错误' , 5);
                }
                if (!is_numeric($value[1])) {
                    return $this->actionError('第'.($key+1).'行还款金额格式错误' , 5);
                }
                if ($value[1] <= 0) {
                    return $this->actionError('第'.($key+1).'行还款金额输入错误，应为正数' , 5);
                }
            }
            $upload_oss = $this->upload_oss('./'.$upload_xls['data'] , 'partial_repay/'.$upload_xls['data']);
            if ($upload_oss === false) {
                unlink('./'.$upload_xls['data']);
                return $this->actionError('上传文件至OSS失败' , 5);
            }
            $param['template_url'] = 'partial_repay/'.$upload_xls['data'];
            $param['data']         = $data;
            unlink('./'.$upload_xls['data']);
            $result = AutomatchService::getInstance()->addPartialRepayment($param);
            if ($result['code'] != 0) {  
                return $this->actionError($result['info'] , 5);
            }
            return $this->actionSuccess($result['info'] , 3);
        }

        return $this->renderPartial('AddPHPartialRepay', array());
    }

    /**
     * 普惠匹配债权部分还款 列表
     */
    public function actionPHPartialRepayList()
    {
        if (!empty($_POST)) {
            $param['platform_id'] = 2;
            if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
                $param['id'] = intval($_POST['id']);
            } else {
                $param['id'] = '';
            }
            if (!empty($_POST['status']) && is_numeric($_POST['status'])) {
                $param['status'] = intval($_POST['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_POST['start'])) {
                $param['start'] = strtotime($_POST['start']);
            } else {
                $param['start'] = '';
            }
            if (!empty($_POST['end'])) {
                $param['end'] = strtotime($_POST['end']);
            } else {
                $param['end'] = '';
            }
            if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                $param['limit'] = intval($_POST['limit']);
                if ($param['limit'] < 1) {
                    $param['limit'] = 1;
                }
            } else {
                $param['limit'] = 10;
            }
            if (!empty($_POST['page']) && is_numeric($_POST['page'])) {
                $param['page'] = intval($_POST['page']);
                if ($param['page'] < 1) {
                    $param['page'] = 1;
                }
            } else {
                $param['page'] = 1;
            }
            $result = AutomatchService::getInstance()->PartialRepaymentList($param);
            if ($result['code'] !== 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = $result['code'];
                $result_data['info']  = $result['info'];
                echo exit(json_encode($result_data));
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $result['data'];
            $result_data['count'] = $result['count'];
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/Automatch/AddPHPartialRepay') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('PHPartialRepayList', array('add_status' => $add_status));
    }

    /**
     * 普惠匹配债权部分还款 详情
     */
    public function actionPHPartialRepayDetail()
    {
        if (!empty($_POST)) {
            $param['platform_id'] = 2;
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入信息';
                echo exit(json_encode($result_data));
            }
            $param['id'] = intval($_POST['id']);
            if (!empty($_POST['name'])) {
                $param['name'] = trim($_POST['name']);
            } else {
                $param['name'] = '';
            }
            if (!empty($_POST['deal_loan_id']) && is_numeric($_POST['deal_loan_id'])) {
                $param['deal_loan_id'] = intval($_POST['deal_loan_id']);
            } else {
                $param['deal_loan_id'] = '';
            }
            if (!empty($_POST['user_id']) && is_numeric($_POST['user_id'])) {
                $param['user_id'] = intval($_POST['user_id']);
            } else {
                $param['user_id'] = '';
            }
            if (!empty($_POST['status']) && is_numeric($_POST['status'])) {
                $param['status'] = intval($_POST['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_POST['repay_status']) && is_numeric($_POST['repay_status'])) {
                $param['repay_status'] = intval($_POST['repay_status'])-1;
            } else {
                $param['repay_status'] = '';
            }
            if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                $param['limit'] = intval($_POST['limit']);
                if ($param['limit'] < 1) {
                    $param['limit'] = 1;
                }
            } else {
                $param['limit'] = 10;
            }
            if (!empty($_POST['page']) && is_numeric($_POST['page'])) {
                $param['page'] = intval($_POST['page']);
                if ($param['page'] < 1) {
                    $param['page'] = 1;
                }
            } else {
                $param['page'] = 1;
            }
            $result = AutomatchService::getInstance()->PartialRepaymentDetail($param);
            if ($result['code'] !== 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = $result['code'];
                $result_data['info']  = $result['info'];
                echo exit(json_encode($result_data));
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $result['data'];
            $result_data['count'] = $result['count'];
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList      = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList,'/user/Automatch/PHPartialRepayExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('PHPartialRepayDetail', array('daochu_status' => $daochu_status));
    }

    /**
     * 普惠匹配债权部分还款 导出
     */
    public function actionPHPartialRepayExcel()
    {
        if (!empty($_GET)) {
            $param['platform_id'] = 2;
            $param['download']    = 1;
            // 条件筛选
            if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
                echo '<h1>请正确输入信息</h1>';exit;
            }
            $param['id'] = intval($_GET['id']);
            if (!empty($_GET['name'])) {
                $param['name'] = trim($_GET['name']);
            } else {
                $param['name'] = '';
            }
            if (!empty($_GET['deal_loan_id']) && is_numeric($_GET['deal_loan_id'])) {
                $param['deal_loan_id'] = intval($_GET['deal_loan_id']);
            } else {
                $param['deal_loan_id'] = '';
            }
            if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
                $param['user_id'] = intval($_GET['user_id']);
            } else {
                $param['user_id'] = '';
            }
            if (!empty($_GET['status']) && is_numeric($_GET['status'])) {
                $param['status'] = intval($_GET['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_GET['repay_status']) && is_numeric($_GET['repay_status'])) {
                $param['repay_status'] = intval($_GET['repay_status'])-1;
            } else {
                $param['repay_status'] = '';
            }
            $result = AutomatchService::getInstance()->PartialRepaymentDetail($param);
            if (empty($result['data'])) {
                echo '<h1>未查询到数据</h1>';exit;
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
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
            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '到期日');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '投资记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '还款金额');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '导入状态');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '实际还款时间');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '还款状态');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '失败原因');

            foreach ($result['data'] as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['end_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['deal_loan_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['repay_money'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['status'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['repay_yestime']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['repay_status']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['remark']);
            }
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = "普惠匹配债权部分还款详情 ".date("Y年m月d日 H时i分s秒" , time());
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
        }
    }

    /**
     * 普惠匹配债权部分还款 编辑
     */
    public function actionEditPHPartialRepay()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])){
                return $this->actionError('请输入ID' , 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_partial_repayment WHERE id = {$id}";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('状态错误' , 5);
            }
            // 校验还款凭证
            if (empty($_FILES['proof']) && empty($res['proof_url'])) {
                return $this->actionError('请上传还款凭证' , 5);
            }
            $upload_rar = $this->upload_rar('proof');
            if ($upload_rar['code'] != 0) {
                if (!empty($res['proof_url'])) {
                    $proof_url = $res['proof_url'];
                } else {
                    return $this->actionError($upload_rar['info'] , 5);
                }
            } else {
                $proof_url  = $upload_rar['data'];
                $upload_oss = $this->upload_oss('./'.$proof_url , 'partial_repay/'.$proof_url);
                if ($upload_oss === false) {
                    unlink('./'.$proof_url);
                    return $this->actionError('上传文件至OSS失败' , 5);
                }
                $proof_url = 'partial_repay/'.$proof_url;
            }
            // 校验计划还款日期
            $now = strtotime(date('Y-m-d' , time()));
            if (!empty($_POST['pay_plan_time'])) {
                $pay_plan_time = strtotime($_POST['pay_plan_time']);
            } else {
                $pay_plan_time = $now;
            }
            if ($pay_plan_time < $now) {
                return $this->actionError('计划还款时间必须大于等于今日凌晨' , 5);
            }
            $time = time();
            $sql  = "UPDATE ag_wx_partial_repayment SET proof_url = '{$proof_url}' , pay_plan_time = {$pay_plan_time} , updatetime = {$time} WHERE id = {$res['id']}";
            $result = Yii::app()->phdb->createCommand($sql)->execute();
            if (!$result) {
                return $this->actionError('保存失败' , 5);
            }
            return $this->actionSuccess('保存成功' , 3);
        }

        if (empty($_GET['id'])) {
            return $this->actionError('请输入ID' , 5);
        }
        $id  = intval($_GET['id']);
        $sql = "SELECT * FROM ag_wx_partial_repayment WHERE id = {$id}";
        $res = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$res) {
            return $this->actionError('ID输入错误' , 5);
        }
        if ($res['status'] != 1) {
            return $this->actionError('状态错误' , 5);
        }
        $res['pay_plan_time'] = date('Y-m-d' , $res['pay_plan_time']);
        $proof_url            = explode('/', $res['proof_url']);
        $res['proof_url']     = $proof_url[2];

        return $this->renderPartial('EditPHPartialRepay', array('res' => $res));
    }

    /**
     * 普惠匹配债权部分还款 移除
     */
    public function actionDeletePHPartialRepay()
    {
        $param['platform_id'] = 2;
        $param['status']      = 6;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 普惠匹配债权部分还款 通过
     */
    public function actionAllowedPHPartialRepay()
    {
        $param['platform_id'] = 2;
        $param['status']      = 2;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 普惠匹配债权部分还款 拒绝
     */
    public function actionRefusePHPartialRepay()
    {
        $param['platform_id'] = 2;
        $param['status']      = 3;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        if (empty($_POST['remark'])) {
            $this->echoJson([], 1, "请输入拒绝原因");
        }
        $param['remark'] = trim($_POST['remark']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 工场微金匹配债权部分还款 新增
     */
    public function actionAddGCWJPartialRepay()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '还款金额（单位元，保留两位小数）');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '工场微金匹配债权还本录入 '.date("Y年m月d日 H时i分s秒" , time());

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
            // 校验付款方
            $param['platform_id'] = 3;
            if (empty($_POST['pay_user'])) {
                return $this->actionError('请输入付款方' , 5);
            }
            $param['pay_user'] = trim($_POST['pay_user']);
            // 校验还款信息
            if (empty($_FILES['template'])) {
                return $this->actionError('请上传还款信息' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            // 检验咨询方名称
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $agency_info = $this->checkAgencyName(3, $agency_name);
                if (!$agency_info) {
                    return $this->actionError('通过此咨询方名称未查询到对应信息' , 5);
                }
                $param['advisory_id'] = $agency_info['id'];
            } else {
                $param['advisory_id'] = 0;
            }
            // 校验计划还款日期
            if (!empty($_POST['pay_plan_time'])) {
                $param['pay_plan_time'] = strtotime($_POST['pay_plan_time']);
            } else {
                $param['pay_plan_time'] = strtotime(date('Y-m-d' , time()));
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$upload_xls['data'];
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('还款信息中无数据' , 5);
            }
            if ($Rows > 50001) {
                return $this->actionError('还款信息中的数据超过5万行' , 5);
            }
            unset($data[0]);
            foreach ($data as $key => $value) {
                if (empty($value[0])) {
                    return $this->actionError('第'.($key+1).'行缺少用户ID' , 5);
                }
                if (empty($value[1])) {
                    return $this->actionError('第'.($key+1).'行缺少还款金额' , 5);
                }
                if (!is_numeric($value[0])) {
                    return $this->actionError('第'.($key+1).'行用户ID格式错误' , 5);
                }
                if (!is_numeric($value[1])) {
                    return $this->actionError('第'.($key+1).'行还款金额格式错误' , 5);
                }
                if ($value[1] <= 0) {
                    return $this->actionError('第'.($key+1).'行还款金额输入错误，应为正数' , 5);
                }
            }
            $upload_oss = $this->upload_oss('./'.$upload_xls['data'] , 'partial_repay/'.$upload_xls['data']);
            if ($upload_oss === false) {
                unlink('./'.$upload_xls['data']);
                return $this->actionError('上传文件至OSS失败' , 5);
            }
            $param['template_url'] = 'partial_repay/'.$upload_xls['data'];
            $param['data']         = $data;
            unlink('./'.$upload_xls['data']);
            $result = AutomatchService::getInstance()->addPartialRepayment($param);
            if ($result['code'] != 0) {  
                return $this->actionError($result['info'] , 5);
            }
            return $this->actionSuccess($result['info'] , 3);
        }

        return $this->renderPartial('AddGCWJPartialRepay', array());
    }

    /**
     * 工场微金匹配债权部分还款 列表
     */
    public function actionGCWJPartialRepayList()
    {
        if (!empty($_POST)) {
            $param['platform_id'] = 3;
            if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
                $param['id'] = intval($_POST['id']);
            } else {
                $param['id'] = '';
            }
            if (!empty($_POST['status']) && is_numeric($_POST['status'])) {
                $param['status'] = intval($_POST['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_POST['start'])) {
                $param['start'] = strtotime($_POST['start']);
            } else {
                $param['start'] = '';
            }
            if (!empty($_POST['end'])) {
                $param['end'] = strtotime($_POST['end']);
            } else {
                $param['end'] = '';
            }
            if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                $param['limit'] = intval($_POST['limit']);
                if ($param['limit'] < 1) {
                    $param['limit'] = 1;
                }
            } else {
                $param['limit'] = 10;
            }
            if (!empty($_POST['page']) && is_numeric($_POST['page'])) {
                $param['page'] = intval($_POST['page']);
                if ($param['page'] < 1) {
                    $param['page'] = 1;
                }
            } else {
                $param['page'] = 1;
            }
            $result = AutomatchService::getInstance()->PartialRepaymentList($param);
            if ($result['code'] !== 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = $result['code'];
                $result_data['info']  = $result['info'];
                echo exit(json_encode($result_data));
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $result['data'];
            $result_data['count'] = $result['count'];
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/Automatch/AddGCWJPartialRepay') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('GCWJPartialRepayList', array('add_status' => $add_status));
    }

    /**
     * 工场微金匹配债权部分还款 详情
     */
    public function actionGCWJPartialRepayDetail()
    {
        if (!empty($_POST)) {
            $param['platform_id'] = 3;
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入信息';
                echo exit(json_encode($result_data));
            }
            $param['id'] = intval($_POST['id']);
            if (!empty($_POST['name'])) {
                $param['name'] = trim($_POST['name']);
            } else {
                $param['name'] = '';
            }
            if (!empty($_POST['deal_loan_id']) && is_numeric($_POST['deal_loan_id'])) {
                $param['deal_loan_id'] = intval($_POST['deal_loan_id']);
            } else {
                $param['deal_loan_id'] = '';
            }
            if (!empty($_POST['user_id']) && is_numeric($_POST['user_id'])) {
                $param['user_id'] = intval($_POST['user_id']);
            } else {
                $param['user_id'] = '';
            }
            if (!empty($_POST['status']) && is_numeric($_POST['status'])) {
                $param['status'] = intval($_POST['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_POST['repay_status']) && is_numeric($_POST['repay_status'])) {
                $param['repay_status'] = intval($_POST['repay_status'])-1;
            } else {
                $param['repay_status'] = '';
            }
            if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                $param['limit'] = intval($_POST['limit']);
                if ($param['limit'] < 1) {
                    $param['limit'] = 1;
                }
            } else {
                $param['limit'] = 10;
            }
            if (!empty($_POST['page']) && is_numeric($_POST['page'])) {
                $param['page'] = intval($_POST['page']);
                if ($param['page'] < 1) {
                    $param['page'] = 1;
                }
            } else {
                $param['page'] = 1;
            }
            $result = AutomatchService::getInstance()->PartialRepaymentDetail($param);
            if ($result['code'] !== 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = $result['code'];
                $result_data['info']  = $result['info'];
                echo exit(json_encode($result_data));
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $result['data'];
            $result_data['count'] = $result['count'];
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList      = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList,'/user/Automatch/GCWJPartialRepayExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('GCWJPartialRepayDetail', array('daochu_status' => $daochu_status));
    }

    /**
     * 工场微金匹配债权部分还款 导出
     */
    public function actionGCWJPartialRepayExcel()
    {
        if (!empty($_GET)) {
            $param['platform_id'] = 3;
            $param['download']    = 1;
            // 条件筛选
            if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
                echo '<h1>请正确输入信息</h1>';exit;
            }
            $param['id'] = intval($_GET['id']);
            if (!empty($_GET['name'])) {
                $param['name'] = trim($_GET['name']);
            } else {
                $param['name'] = '';
            }
            if (!empty($_GET['deal_loan_id']) && is_numeric($_GET['deal_loan_id'])) {
                $param['deal_loan_id'] = intval($_GET['deal_loan_id']);
            } else {
                $param['deal_loan_id'] = '';
            }
            if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
                $param['user_id'] = intval($_GET['user_id']);
            } else {
                $param['user_id'] = '';
            }
            if (!empty($_GET['status']) && is_numeric($_GET['status'])) {
                $param['status'] = intval($_GET['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_GET['repay_status']) && is_numeric($_GET['repay_status'])) {
                $param['repay_status'] = intval($_GET['repay_status'])-1;
            } else {
                $param['repay_status'] = '';
            }
            $result = AutomatchService::getInstance()->PartialRepaymentDetail($param);
            if (empty($result['data'])) {
                echo '<h1>未查询到数据</h1>';exit;
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
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
            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '到期日');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '投资记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '还款金额');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '导入状态');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '实际还款时间');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '还款状态');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '失败原因');

            foreach ($result['data'] as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['end_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['deal_loan_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['repay_money'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['status'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['repay_yestime']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['repay_status']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['remark']);
            }
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = "工场微金匹配债权部分还款详情 ".date("Y年m月d日 H时i分s秒" , time());
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
        }
    }

    /**
     * 工场微金匹配债权部分还款 编辑
     */
    public function actionEditGCWJPartialRepay()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])){
                return $this->actionError('请输入ID' , 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM offline_partial_repay WHERE id = {$id} AND platform_id = 4 ";
            $res = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('状态错误' , 5);
            }
            // 校验还款凭证
            if (empty($_FILES['proof']) && empty($res['proof_url'])) {
                return $this->actionError('请上传还款凭证' , 5);
            }
            $upload_rar = $this->upload_rar('proof');
            if ($upload_rar['code'] != 0) {
                if (!empty($res['proof_url'])) {
                    $proof_url = $res['proof_url'];
                } else {
                    return $this->actionError($upload_rar['info'] , 5);
                }
            } else {
                $proof_url  = $upload_rar['data'];
                $upload_oss = $this->upload_oss('./'.$proof_url , 'partial_repay/'.$proof_url);
                if ($upload_oss === false) {
                    unlink('./'.$proof_url);
                    return $this->actionError('上传文件至OSS失败' , 5);
                }
                $proof_url = 'partial_repay/'.$proof_url;
            }
            // 校验计划还款日期
            $now = strtotime(date('Y-m-d' , time()));
            if (!empty($_POST['pay_plan_time'])) {
                $pay_plan_time = strtotime($_POST['pay_plan_time']);
            } else {
                $pay_plan_time = $now;
            }
            if ($pay_plan_time < $now) {
                return $this->actionError('计划还款时间必须大于等于今日凌晨' , 5);
            }
            $time = time();
            $sql  = "UPDATE offline_partial_repay SET proof_url = '{$proof_url}' , pay_plan_time = {$pay_plan_time} , updatetime = {$time} WHERE id = {$res['id']} AND platform_id = 4 ";
            $result = Yii::app()->offlinedb->createCommand($sql)->execute();
            if (!$result) {
                return $this->actionError('保存失败' , 5);
            }
            return $this->actionSuccess('保存成功' , 3);
        }

        if (empty($_GET['id'])) {
            return $this->actionError('请输入ID' , 5);
        }
        $id  = intval($_GET['id']);
        $sql = "SELECT * FROM offline_partial_repay WHERE id = {$id} AND platform_id = 4 ";
        $res = Yii::app()->offlinedb->createCommand($sql)->queryRow();
        if (!$res) {
            return $this->actionError('ID输入错误' , 5);
        }
        if ($res['status'] != 1) {
            return $this->actionError('状态错误' , 5);
        }
        $res['pay_plan_time'] = date('Y-m-d' , $res['pay_plan_time']);
        $proof_url            = explode('/', $res['proof_url']);
        $res['proof_url']     = $proof_url[2];

        return $this->renderPartial('EditZDXPartialRepay', array('res' => $res));
    }

    /**
     * 工场微金匹配债权部分还款 移除
     */
    public function actionDeleteGCWJPartialRepay()
    {
        $param['platform_id'] = 3;
        $param['status']      = 6;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 工场微金匹配债权部分还款 通过
     */
    public function actionAllowedGCWJPartialRepay()
    {
        $param['platform_id'] = 3;
        $param['status']      = 2;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 工场微金匹配债权部分还款 拒绝
     */
    public function actionRefuseGCWJPartialRepay()
    {
        $param['platform_id'] = 3;
        $param['status']      = 3;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        if (empty($_POST['remark'])) {
            $this->echoJson([], 1, "请输入拒绝原因");
        }
        $param['remark'] = trim($_POST['remark']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 智多新匹配债权部分还款 新增
     */
    public function actionAddZDXPartialRepay()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '还款金额（单位元，保留两位小数）');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '智多新匹配债权还本录入 '.date("Y年m月d日 H时i分s秒" , time());

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
            // 校验付款方
            $param['platform_id'] = 4;
            if (empty($_POST['pay_user'])) {
                return $this->actionError('请输入付款方' , 5);
            }
            $param['pay_user'] = trim($_POST['pay_user']);
            // 校验还款信息
            if (empty($_FILES['template'])) {
                return $this->actionError('请上传还款信息' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            // 检验咨询方名称
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $agency_info = $this->checkAgencyName(1, $agency_name);
                if (!$agency_info) {
                    return $this->actionError('通过此咨询方名称未查询到对应信息' , 5);
                }
                $param['advisory_id'] = $agency_info['id'];
            } else {
                $param['advisory_id'] = 0;
            }
            // 校验计划还款日期
            if (!empty($_POST['pay_plan_time'])) {
                $param['pay_plan_time'] = strtotime($_POST['pay_plan_time']);
            } else {
                $param['pay_plan_time'] = strtotime(date('Y-m-d' , time()));
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$upload_xls['data'];
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('还款信息中无数据' , 5);
            }
            if ($Rows > 50001) {
                return $this->actionError('还款信息中的数据超过5万行' , 5);
            }
            unset($data[0]);
            foreach ($data as $key => $value) {
                if (empty($value[0])) {
                    return $this->actionError('第'.($key+1).'行缺少用户ID' , 5);
                }
                if (empty($value[1])) {
                    return $this->actionError('第'.($key+1).'行缺少还款金额' , 5);
                }
                if (!is_numeric($value[0])) {
                    return $this->actionError('第'.($key+1).'行用户ID格式错误' , 5);
                }
                if (!is_numeric($value[1])) {
                    return $this->actionError('第'.($key+1).'行还款金额格式错误' , 5);
                }
                if ($value[1] <= 0) {
                    return $this->actionError('第'.($key+1).'行还款金额输入错误，应为正数' , 5);
                }
            }
            $upload_oss = $this->upload_oss('./'.$upload_xls['data'] , 'partial_repay/'.$upload_xls['data']);
            if ($upload_oss === false) {
                unlink('./'.$upload_xls['data']);
                return $this->actionError('上传文件至OSS失败' , 5);
            }
            $param['template_url'] = 'partial_repay/'.$upload_xls['data'];
            $param['data']         = $data;
            unlink('./'.$upload_xls['data']);
            $result = AutomatchService::getInstance()->addPartialRepayment($param);
            if ($result['code'] != 0) {  
                return $this->actionError($result['info'] , 5);
            }
            return $this->actionSuccess($result['info'] , 3);
        }

        return $this->renderPartial('AddZDXPartialRepay', array());
    }

    /**
     * 智多新匹配债权部分还款 列表
     */
    public function actionZDXPartialRepayList()
    {
        if (!empty($_POST)) {
            $param['platform_id'] = 4;
            if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
                $param['id'] = intval($_POST['id']);
            } else {
                $param['id'] = '';
            }
            if (!empty($_POST['status']) && is_numeric($_POST['status'])) {
                $param['status'] = intval($_POST['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_POST['start'])) {
                $param['start'] = strtotime($_POST['start']);
            } else {
                $param['start'] = '';
            }
            if (!empty($_POST['end'])) {
                $param['end'] = strtotime($_POST['end']);
            } else {
                $param['end'] = '';
            }
            if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                $param['limit'] = intval($_POST['limit']);
                if ($param['limit'] < 1) {
                    $param['limit'] = 1;
                }
            } else {
                $param['limit'] = 10;
            }
            if (!empty($_POST['page']) && is_numeric($_POST['page'])) {
                $param['page'] = intval($_POST['page']);
                if ($param['page'] < 1) {
                    $param['page'] = 1;
                }
            } else {
                $param['page'] = 1;
            }
            $result = AutomatchService::getInstance()->PartialRepaymentList($param);
            if ($result['code'] !== 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = $result['code'];
                $result_data['info']  = $result['info'];
                echo exit(json_encode($result_data));
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $result['data'];
            $result_data['count'] = $result['count'];
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/Automatch/AddZDXartialRepay') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('ZDXPartialRepayList', array('add_status' => $add_status));
    }

    /**
     * 智多新匹配债权部分还款 详情
     */
    public function actionZDXPartialRepayDetail()
    {
        if (!empty($_POST)) {
            $param['platform_id'] = 4;
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入信息';
                echo exit(json_encode($result_data));
            }
            $param['id'] = intval($_POST['id']);
            if (!empty($_POST['name'])) {
                $param['name'] = trim($_POST['name']);
            } else {
                $param['name'] = '';
            }
            if (!empty($_POST['deal_loan_id']) && is_numeric($_POST['deal_loan_id'])) {
                $param['deal_loan_id'] = intval($_POST['deal_loan_id']);
            } else {
                $param['deal_loan_id'] = '';
            }
            if (!empty($_POST['user_id']) && is_numeric($_POST['user_id'])) {
                $param['user_id'] = intval($_POST['user_id']);
            } else {
                $param['user_id'] = '';
            }
            if (!empty($_POST['status']) && is_numeric($_POST['status'])) {
                $param['status'] = intval($_POST['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_POST['repay_status']) && is_numeric($_POST['repay_status'])) {
                $param['repay_status'] = intval($_POST['repay_status'])-1;
            } else {
                $param['repay_status'] = '';
            }
            if (!empty($_POST['limit']) && is_numeric($_POST['limit'])) {
                $param['limit'] = intval($_POST['limit']);
                if ($param['limit'] < 1) {
                    $param['limit'] = 1;
                }
            } else {
                $param['limit'] = 10;
            }
            if (!empty($_POST['page']) && is_numeric($_POST['page'])) {
                $param['page'] = intval($_POST['page']);
                if ($param['page'] < 1) {
                    $param['page'] = 1;
                }
            } else {
                $param['page'] = 1;
            }
            $result = AutomatchService::getInstance()->PartialRepaymentDetail($param);
            if ($result['code'] !== 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = $result['code'];
                $result_data['info']  = $result['info'];
                echo exit(json_encode($result_data));
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $result['data'];
            $result_data['count'] = $result['count'];
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList      = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList,'/user/Automatch/ZDXPartialRepayExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('ZDXPartialRepayDetail', array('daochu_status' => $daochu_status));
    }

    /**
     * 智多新匹配债权部分还款 导出
     */
    public function actionZDXPartialRepayExcel()
    {
        if (!empty($_GET)) {
            $param['platform_id'] = 4;
            $param['download']    = 1;
            // 条件筛选
            if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
                echo '<h1>请正确输入信息</h1>';exit;
            }
            $param['id'] = intval($_GET['id']);
            if (!empty($_GET['name'])) {
                $param['name'] = trim($_GET['name']);
            } else {
                $param['name'] = '';
            }
            if (!empty($_GET['deal_loan_id']) && is_numeric($_GET['deal_loan_id'])) {
                $param['deal_loan_id'] = intval($_GET['deal_loan_id']);
            } else {
                $param['deal_loan_id'] = '';
            }
            if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
                $param['user_id'] = intval($_GET['user_id']);
            } else {
                $param['user_id'] = '';
            }
            if (!empty($_GET['status']) && is_numeric($_GET['status'])) {
                $param['status'] = intval($_GET['status']);
            } else {
                $param['status'] = '';
            }
            if (!empty($_GET['repay_status']) && is_numeric($_GET['repay_status'])) {
                $param['repay_status'] = intval($_GET['repay_status'])-1;
            } else {
                $param['repay_status'] = '';
            }
            $result = AutomatchService::getInstance()->PartialRepaymentDetail($param);
            if (empty($result['data'])) {
                echo '<h1>未查询到数据</h1>';exit;
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
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
            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '到期日');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '投资记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '还款金额');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '导入状态');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '实际还款时间');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '还款状态');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '失败原因');

            foreach ($result['data'] as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['end_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['deal_loan_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['repay_money'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['status'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['repay_yestime']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['repay_status']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['remark']);
            }
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = "智多新匹配债权部分还款详情 ".date("Y年m月d日 H时i分s秒" , time());
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
        }
    }

    /**
     * 智多新匹配债权部分还款 编辑
     */
    public function actionEditZDXPartialRepay()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])){
                return $this->actionError('请输入ID' , 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM offline_partial_repay WHERE id = {$id} AND platform_id = 4 ";
            $res = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误' , 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('状态错误' , 5);
            }
            // 校验还款凭证
            if (empty($_FILES['proof']) && empty($res['proof_url'])) {
                return $this->actionError('请上传还款凭证' , 5);
            }
            $upload_rar = $this->upload_rar('proof');
            if ($upload_rar['code'] != 0) {
                if (!empty($res['proof_url'])) {
                    $proof_url = $res['proof_url'];
                } else {
                    return $this->actionError($upload_rar['info'] , 5);
                }
            } else {
                $proof_url  = $upload_rar['data'];
                $upload_oss = $this->upload_oss('./'.$proof_url , 'partial_repay/'.$proof_url);
                if ($upload_oss === false) {
                    unlink('./'.$proof_url);
                    return $this->actionError('上传文件至OSS失败' , 5);
                }
                $proof_url = 'partial_repay/'.$proof_url;
            }
            // 校验计划还款日期
            $now = strtotime(date('Y-m-d' , time()));
            if (!empty($_POST['pay_plan_time'])) {
                $pay_plan_time = strtotime($_POST['pay_plan_time']);
            } else {
                $pay_plan_time = $now;
            }
            if ($pay_plan_time < $now) {
                return $this->actionError('计划还款时间必须大于等于今日凌晨' , 5);
            }
            $time = time();
            $sql  = "UPDATE offline_partial_repay SET proof_url = '{$proof_url}' , pay_plan_time = {$pay_plan_time} , updatetime = {$time} WHERE id = {$res['id']} AND platform_id = 4 ";
            $result = Yii::app()->offlinedb->createCommand($sql)->execute();
            if (!$result) {
                return $this->actionError('保存失败' , 5);
            }
            return $this->actionSuccess('保存成功' , 3);
        }

        if (empty($_GET['id'])) {
            return $this->actionError('请输入ID' , 5);
        }
        $id  = intval($_GET['id']);
        $sql = "SELECT * FROM offline_partial_repay WHERE id = {$id} AND platform_id = 4 ";
        $res = Yii::app()->offlinedb->createCommand($sql)->queryRow();
        if (!$res) {
            return $this->actionError('ID输入错误' , 5);
        }
        if ($res['status'] != 1) {
            return $this->actionError('状态错误' , 5);
        }
        $res['pay_plan_time'] = date('Y-m-d' , $res['pay_plan_time']);
        $proof_url            = explode('/', $res['proof_url']);
        $res['proof_url']     = $proof_url[2];

        return $this->renderPartial('EditGCWJPartialRepay', array('res' => $res));
    }

    /**
     * 智多新匹配债权部分还款 移除
     */
    public function actionDeleteZDXPartialRepay()
    {
        $param['platform_id'] = 4;
        $param['status']      = 6;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 智多新匹配债权部分还款 通过
     */
    public function actionAllowedZDXPartialRepay()
    {
        $param['platform_id'] = 4;
        $param['status']      = 2;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 智多新匹配债权部分还款 拒绝
     */
    public function actionRefuseZDXPartialRepay()
    {
        $param['platform_id'] = 3;
        $param['status']      = 3;
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson([], 1, "请正确输入ID");
        }
        $param['id'] = intval($_POST['id']);
        if (empty($_POST['remark'])) {
            $this->echoJson([], 1, "请输入拒绝原因");
        }
        $param['remark'] = trim($_POST['remark']);
        $result = AutomatchService::getInstance()->updatePartialRepayment($param);
        if ($result['code'] !== 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "操作成功");
    }

}
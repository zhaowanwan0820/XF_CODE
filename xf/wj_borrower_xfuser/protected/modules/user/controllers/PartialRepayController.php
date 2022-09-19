<?php

class PartialRepayController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success' , 'Error' , 'PartialDetail' , 'PHPartialDetail' , 'XFPartialDetail' , 'CheckDealName'
        );
    }

    /**
     * 部分还款列表
     * @return string
     * @throws CException
     */
    public function actionPartialList()
    {
        $model = Yii::app()->fdb;
        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }
        //搜索条件
        $where = "1 = 1";
        //还款id
        if (!empty($_GET['id'])) {
            $where .= " and id = {$_GET['id']}";
        }
        //还款状态
        $pay_status = '';
        if (!empty($_GET['pay_status'])) {
            $pay_status = $_GET['pay_status'];
            $where .= " and status = {$pay_status}";
        }
        //还款开始时间与结束时间
        if (!empty($_GET['start']) && !empty($_GET['end'])) {
            $start = strtotime($_GET['start']);
            $end = strtotime($_GET['end']);
            $where .= " and pay_plan_time between {$start} and {$end}";
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        //1:待审核2:审核已通过3:审核未通过4:还款成功5:还款失败6:已撤销'
        $statusArr = [1 => '待审核', 2 => '审核已通过', 3 => '审核未通过', 4 => '还款成功', 5 => '还款失败'];
        //后台用户
        $adminUserInfo = \Yii::app()->user->getState('_user');
        if (!empty($adminUserInfo['username'])) {
            if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                if ($adminUserInfo['user_type'] == 2) {
                    $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                    if (!empty($deallist)) {
                        $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                        //查询成功导入的
                        $repayDetailInfo = Yii::app()->fdb->createCommand("select DISTINCT partial_repay_id from ag_wx_partial_repay_detail where deal_id IN($dealIds)")->queryAll();
                        if (!empty($repayDetailInfo)) {
                            $partial_repay_ids = implode(",", ItzUtil::array_column($repayDetailInfo, "partial_repay_id"));
                            $where .= " AND id IN({$partial_repay_ids})";
                        } else {
                            $where .= " AND id < 0";
                        }
                    } else {
                        //不是超级管理员并且没有$dealIds
                        $where .= " AND id < 0";
                    }
                }
            }
        }
        // 查询数据总量
        $count = $model->createCommand("select count(*) from ag_wx_partial_repayment where {$where} and type = 1 and status != 6")->queryScalar();
        if (!empty($count) && $count > 0) {
            $sql = "select * from ag_wx_partial_repayment where $where and type = 1 and status != 6 ORDER BY id DESC";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $listInfo = $model->createCommand($sql)->queryAll();
            $adminUser = ItzUtil::array_column($listInfo, 'admin_user_id');
            $adminUserId = array_unique($adminUser);
            $adminUserIds = implode(",", $adminUserId);
            $userInfo = Yii::app()->db->createCommand("select id,username from itz_user where id IN($adminUserIds)")->queryAll();
            $userInfoArr = ItzUtil::array_column($userInfo, 'username', 'id');
            $partial_repay_ids = implode(",", ItzUtil::array_column($listInfo, 'id'));
            //查看是否有导入失败记录
            $partialInfo = Yii::app()->fdb->createCommand("select count(*) num,partial_repay_id from ag_wx_partial_repay_detail where partial_repay_id IN({$partial_repay_ids}) and status = 2 group by partial_repay_id")->queryAll();
            $partialInfoArr = ItzUtil::array_column($partialInfo, 'num', 'partial_repay_id');
            foreach ($listInfo as $key => $value) {
                $dataInfo[] = array(
                    "id" => $value['id'],
                    "total_repayment" => ItzUtil::format_money($value['total_repayment']),
                    "success_number" => $value['success_number'],
                    "total_successful_amount" => ItzUtil::format_money($value['total_successful_amount']),
                    "fail_number" => $value['fail_number'],
                    "total_fail_amount" => ItzUtil::format_money($value['total_fail_amount']),
                    "pay_user" => $value['pay_user'],
                    "pay_plan_time" => date("Y-m-d", $value['pay_plan_time']),
                    "addtime" => date("Y-m-d H:i:s", $value['addtime']),
                    "admin_username" => $userInfoArr[$value['admin_user_id']],
                    "statusName" => $statusArr[$value['status']],
                    "task_success_time" => !empty($value['task_success_time']) ? date("Y-m-d H:i:s",$value['task_success_time']) : "----",
                    "proof_url" => $value['proof_url'],
                    "status" => $value['status'],
                    "remark" => $value['remark'],
                    "fail_status" => !empty($partialInfoArr[$value['id']]) ? 1 : 2,
                );
            }
        }
        $criteria = new CDbCriteria();
        $pages = new CPagination($count);
        $pages->pageSize = $limit;
        $pages->applyLimit($criteria);
        $pages = $this->widget('CLinkPager', array(
            'header' => '',
            'firstPageLabel' => '首页',
            'lastPageLabel' => '末页',
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'pages' => $pages,
            'maxButtonCount' => 8,
            'cssFile' => false,
            'htmlOptions' => array("class" => "pagination"),
            'selectedPageCssClass' => "active"
        ), true);
        return $this->renderPartial('PartialList', array('pages' => $pages, 'authList' => $authList, 'listInfo' => $dataInfo, 'statusArr' => $statusArr, 'pay_status' => $pay_status));
    }

    /**
     * 部分还款计划 审核通过
     * status 2:审核通过3:审核未通过6:已撤销
     * partial_repayment_id 部分还款列表主键id
     * admin_user_id 后台用户id
     * remark 拒绝理由
     *
     */
    public function actionAllowRepay()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $admin_user_id = Yii::app()->user->id;
        $status = $_REQUEST['status'];
        $data = array(
            "status" => $status,
            "partial_repayment_id" => $partial_repayment_id,
            "admin_user_id" => $admin_user_id,
        );
        $result = PartialRepaymentService::getInstance()->updatePartialRepayment($data);
        if ($result['code'] != 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "审核成功");
    }

    /**
     * 部分还款计划 移除
     * status 2:审核通过3:审核未通过6:已撤销
     * partial_repayment_id 部分还款列表主键id
     * admin_user_id 后台用户id
     */
    public function actionDelRepay()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $admin_user_id = Yii::app()->user->id;
        $status = $_REQUEST['status'];
        if ($status != 6) {
            $this->echoJson([], 1, "审核状态错误");
        }
        $data = array(
            "status" => $status,
            "partial_repayment_id" => $partial_repayment_id,
            "admin_user_id" => $admin_user_id,
        );
        $result = PartialRepaymentService::getInstance()->updatePartialRepayment($data);
        if ($result['code'] != 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "移除成功");
    }

    /**
     * 部分还款计划 拒绝
     * status 2:审核通过3:审核未通过6:已撤销
     * partial_repayment_id 部分还款列表主键id
     * admin_user_id 后台用户id
     * remark 拒绝理由
     */
    public function actionRefuseEdit()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $status = $_REQUEST['status'];
        if (!empty($status) && !empty($partial_repayment_id)) {
            if ($status != 3) {
                $this->echoJson([], 1, "审核状态错误");
            }
            $admin_user_id = Yii::app()->user->id;
            $remark = $_REQUEST['remark'];
            $data = array(
                "status" => $status,
                "partial_repayment_id" => $partial_repayment_id,
                "admin_user_id" => $admin_user_id,
                "remark" => $remark,
            );
            $result = PartialRepaymentService::getInstance()->updatePartialRepayment($data);
            if ($result['code'] != 0) {
                $this->echoJson([], $result['code'], $result['info']);
            }
            $this->echoJson([], 0, "拒绝成功");
        }
        return $this->renderPartial('RefuseEdit');
    }

    /**
     * 部分还款计划 查看详情
     */
    public function actionPartialDetail()
    {
        $model = Yii::app()->fdb;
        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }
        $pay_status = $_REQUEST['pay_status'];
        $repay_status = $_REQUEST['repay_status'];
        $name = $_REQUEST['name'];
        $user_id = $_REQUEST['user_id'];
        $partial_repay_id = $_REQUEST['partial_repayment_id'];
        $deal_loan_id = $_GET['deal_loan_id'];
        $export = !empty($_REQUEST['export']) ? $_REQUEST['export'] : 0; //导出数据
        if (empty($partial_repay_id) || !is_numeric($partial_repay_id)) {
            exit("partial_repayment_id 不存在");
        }
        if (!empty($pay_status) && !is_numeric($pay_status) && !in_array($pay_status, [0, 1, 2]) || !empty($user_id) && !is_numeric($user_id) || !empty($deal_loan_id) && !is_numeric($deal_loan_id)) {
            exit("参数错误");
        }
        //搜索条件
        $where = "partial_repay_id = $partial_repay_id";
        //借款标题
        if ($name) {
            $where .= " and name = '{$_GET['name']}'";
        }
        //导入状态
        if ($pay_status) {
            $where .= " and status = {$pay_status}";
        }
        //还款状态
        if ($repay_status) {
            $repay_status_code = [1 => 0,2 => 1];
            $repay_new_status = $repay_status_code[$repay_status];
            $where .= " and repay_status = {$repay_new_status}";
        }
        //用户ID
        if ($user_id) {
            $where .= " and user_id = {$user_id}";
        }
        //投资记录ID
        if ($deal_loan_id) {
            $where .= " and deal_loan_id = {$deal_loan_id}";
        }
        //导入状态1:成功,2:失败
        $statusArr = [1 => '成功', 2 => '失败'];
        //还款状态0:待还,1:已还
        $repayStatusArr = [0 => '待还', 1 => '已还'];
        // 查询数据总量
        $count = $model->createCommand("select count(*) from ag_wx_partial_repay_detail where {$where}")->queryScalar();
        if (!empty($count) && $count > 0) {
            $sql = "select * from ag_wx_partial_repay_detail where $where ORDER BY id DESC";
            $pass = ($page - 1) * $limit;
            //如果是导出数据
            if (empty($export)) {
                $sql .= " LIMIT {$pass} , {$limit} ";
            }
            $listInfo = $model->createCommand($sql)->queryAll();
            if (!empty($listInfo)) {
                foreach ($listInfo as $key => $value) {
                    $dataInfo[] = array(
                        "id" => $value['id'],
                        "name" => $value['name'],
                        "end_time" => !empty($value['end_time']) ? date("Y-m-d H:i:s", $value['end_time']) : "-",
                        "end_time" => date("Y-m-d H:i:s", $value['end_time']),
                        "deal_loan_id" => $value['deal_loan_id'],
                        "user_id" => $value['user_id'],
                        "repay_status" => $repayStatusArr[$value['repay_status']],
                        "repay_yestime" => !empty($value['repay_yestime']) ? date("Y-m-d",$value['repay_yestime']) : "-",
                        "repay_money" => $export == 1 ? $value['repay_money'] : ItzUtil::format_money($value['repay_money']),
                        "statusName" => $statusArr[$value['status']],
                        "remark" => $value['remark'],
                    );
                }
            }
            //导出操作
            if ($export == 1) {
                $dataRet = array(
                    "letter" => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','G'],
                    "tableheader" => ['序号', '借款标题', '到期日', '投资记录ID', '用户ID','还款状态','实际还款时间', '还款金额', '导入状态', '失败原因'],
                    "dataArr" => $dataInfo,
                );
                PartialRepaymentService::getInstance()->exportList($dataRet, "partial_repay_detail" . date("Y-m-d") . ".xls");
                die;
            }
        }
        $criteria = new CDbCriteria();
        $pages = new CPagination($count);
        $pages->pageSize = $limit;
        $pages->applyLimit($criteria);
        $pages = $this->widget('CLinkPager', array(
            'header' => '',
            'firstPageLabel' => '首页',
            'lastPageLabel' => '末页',
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'pages' => $pages,
            'maxButtonCount' => 8,
            'cssFile' => false,
            'htmlOptions' => array("class" => "pagination"),
            'selectedPageCssClass' => "active"
        ), true);
        return $this->renderPartial('PartialDetail', array('pages' => $pages, 'listInfo' => $dataInfo, 'statusArr' => $statusArr, 'repayStatusArr' => $repayStatusArr));
    }

    /**
     * 成功提示页 张健
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionSuccess($msg = '成功' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    /**
     * 失败提示页 张健
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionError($msg = '失败' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }

    /**
     * 上传xls文件 张健 
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
     * 部分还款录入
     */
    public function actionAddPartial()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '投资记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '还款金额（单位元，保留两位小数）');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '还款信息模板 '.date("Y年m月d日 H时i分s秒" , time());

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
            if (empty($_POST['pay_user'])) {
                return $this->actionError('请输入付款方' , 5);
            }
            $pay_user = trim($_POST['pay_user']);
            // 校验还款信息
            if (empty($_FILES['template'])) {
                return $this->actionError('请上传还款信息' , 5);
            }
            //是否出清
            if (empty($_POST['is_clear']) || !in_array($_POST['is_clear'], [1,2])) {
                return $this->actionError('请选择是否出清' , 5);
            }
            $is_clear = $_POST['is_clear'];
            
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            $template_url = $upload_xls['data'];
            // 校验计划还款日期
            if (!empty($_POST['pay_plan_time'])) {
                $pay_plan_time = strtotime($_POST['pay_plan_time']);
            } else {
                $pay_plan_time = strtotime(date('Y-m-d' , time()));
            }
            // 校验还款凭证
            // if (empty($_FILES['proof'])) {
            //     return $this->actionError('请上传还款凭证' , 5);
            // }
            // $upload_rar = $this->upload_rar('proof');
            // if ($upload_rar['code'] != 0) {
            //     return $this->actionError($upload_rar['info'] , 5);
            // }
            // $proof_url = $upload_rar['data'];
            $proof_url = '';

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$template_url;
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
            $result = PartialService::getInstance()->add_PH_partial_repayment($pay_user , $pay_plan_time , $template_url , $proof_url , $data, $is_clear);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'] , 5);
                unlink('./'.$template_url);
                unlink('./'.$proof_url);
            }
            return $this->actionSuccess($result['info'] , 3);
        }

        return $this->renderPartial('AddPartial', array());
    }

    /**
     * 部分还款列表
     * @return string
     * @throws CException
     */
    public function actionPHPartialList()
    {
        $model = Yii::app()->phdb;
        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }
        //搜索条件
        $where = "1 = 1";
        //还款id
        if (!empty($_GET['id'])) {
            $where .= " and id = {$_GET['id']}";
        }
        //还款状态
        $pay_status = '';
        if (!empty($_GET['pay_status'])) {
            $pay_status = $_GET['pay_status'];
            $where .= " and status = {$pay_status}";
        }
        //还款开始时间与结束时间
        if (!empty($_GET['start']) && !empty($_GET['end'])) {
            $start = strtotime($_GET['start']);
            $end = strtotime($_GET['end']);
            $where .= " and pay_plan_time between {$start} and {$end}";
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        //1:待审核2:审核已通过3:审核未通过4:还款成功5:还款失败6:已撤销'
        $statusArr = [1 => '待审核', 2 => '审核已通过', 3 => '审核未通过', 4 => '还款成功', 5 => '还款失败'];
        //后台用户
        $adminUserInfo = \Yii::app()->user->getState('_user');
        if (!empty($adminUserInfo['username'])) {
            if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                if ($adminUserInfo['user_type'] == 2) {
                    $deallist = Yii::app()->phdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                    if (!empty($deallist)) {
                        $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                        //查询成功导入的
                        $repayDetailInfo = Yii::app()->phdb->createCommand("select DISTINCT partial_repay_id from ag_wx_partial_repay_detail where deal_id IN($dealIds)")->queryAll();
                        if (!empty($repayDetailInfo)) {
                            $partial_repay_ids = implode(",", ItzUtil::array_column($repayDetailInfo, "partial_repay_id"));
                            $where .= " AND id IN({$partial_repay_ids})";
                        } else {
                            $where .= " AND id < 0";
                        }
                    } else {
                        //不是超级管理员并且没有$dealIds
                        $where .= " AND id < 0";
                    }
                }
            }
        }
        // 查询数据总量
        $count = $model->createCommand("select count(*) from ag_wx_partial_repayment where {$where} and type = 1 and status != 6")->queryScalar();
        if (!empty($count) && $count > 0) {
            $sql = "select * from ag_wx_partial_repayment where $where and type = 1 and status != 6 ORDER BY id DESC";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $listInfo = $model->createCommand($sql)->queryAll();
            $adminUser = ItzUtil::array_column($listInfo, 'admin_user_id');
            $adminUserId = array_unique($adminUser);
            $adminUserIds = implode(",", $adminUserId);
            $userInfo = Yii::app()->db->createCommand("select id,username from itz_user where id IN($adminUserIds)")->queryAll();
            $userInfoArr = ItzUtil::array_column($userInfo, 'username', 'id');
            $partial_repay_ids = implode(",", ItzUtil::array_column($listInfo, 'id'));
            //查看是否有导入失败记录
            $partialInfo = Yii::app()->phdb->createCommand("select count(*) num,partial_repay_id from ag_wx_partial_repay_detail where partial_repay_id IN({$partial_repay_ids}) and status = 2 group by partial_repay_id")->queryAll();
            $partialInfoArr = ItzUtil::array_column($partialInfo, 'num', 'partial_repay_id');
            foreach ($listInfo as $key => $value) {
                $dataInfo[] = array(
                    "id" => $value['id'],
                    "total_repayment" => ItzUtil::format_money($value['total_repayment']),
                    "success_number" => $value['success_number'],
                    "total_successful_amount" => ItzUtil::format_money($value['total_successful_amount']),
                    "fail_number" => $value['fail_number'],
                    "total_fail_amount" => ItzUtil::format_money($value['total_fail_amount']),
                    "pay_user" => $value['pay_user'],
                    "pay_plan_time" => date("Y-m-d", $value['pay_plan_time']),
                    "addtime" => date("Y-m-d H:i:s", $value['addtime']),
                    "admin_username" => $userInfoArr[$value['admin_user_id']],
                    "statusName" => $statusArr[$value['status']],
                    "task_success_time" => !empty($value['task_success_time']) ? date("Y-m-d H:i:s",$value['task_success_time']) : "----",
                    "proof_url" => $value['proof_url'],
                    "status" => $value['status'],
                    "remark" => $value['remark'],
                    "fail_status" => !empty($partialInfoArr[$value['id']]) ? 1 : 2,
                );
            }
        }
        $criteria = new CDbCriteria();
        $pages = new CPagination($count);
        $pages->pageSize = $limit;
        $pages->applyLimit($criteria);
        $pages = $this->widget('CLinkPager', array(
            'header' => '',
            'firstPageLabel' => '首页',
            'lastPageLabel' => '末页',
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'pages' => $pages,
            'maxButtonCount' => 8,
            'cssFile' => false,
            'htmlOptions' => array("class" => "pagination"),
            'selectedPageCssClass' => "active"
        ), true);
        return $this->renderPartial('PHPartialList', array('pages' => $pages, 'authList' => $authList, 'listInfo' => $dataInfo, 'statusArr' => $statusArr, 'pay_status' => $pay_status));
    }

    /**
     * 部分还款计划 审核通过
     * status 2:审核通过3:审核未通过6:已撤销
     * partial_repayment_id 部分还款列表主键id
     * admin_user_id 后台用户id
     * remark 拒绝理由
     *
     */
    public function actionPHAllowRepay()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $admin_user_id = Yii::app()->user->id;
        $status = $_REQUEST['status'];
        $data = array(
            "status" => $status,
            "partial_repayment_id" => $partial_repayment_id,
            "admin_user_id" => $admin_user_id,
        );
        $result = PartialRepaymentService::getInstance()->updatePHPartialRepayment($data);
        if ($result['code'] != 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "审核成功");
    }

    /**
     * 部分还款计划 移除
     * status 2:审核通过3:审核未通过6:已撤销
     * partial_repayment_id 部分还款列表主键id
     * admin_user_id 后台用户id
     */
    public function actionPHDelRepay()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $admin_user_id = Yii::app()->user->id;
        $status = $_REQUEST['status'];
        if ($status != 6) {
            $this->echoJson([], 1, "审核状态错误");
        }
        $data = array(
            "status" => $status,
            "partial_repayment_id" => $partial_repayment_id,
            "admin_user_id" => $admin_user_id,
        );
        $result = PartialRepaymentService::getInstance()->updatePHPartialRepayment($data);
        if ($result['code'] != 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "移除成功");
    }

    /**
     * 部分还款计划 拒绝
     * status 2:审核通过3:审核未通过6:已撤销
     * partial_repayment_id 部分还款列表主键id
     * admin_user_id 后台用户id
     * remark 拒绝理由
     */
    public function actionPHRefuseEdit()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $status = $_REQUEST['status'];
        if (!empty($status) && !empty($partial_repayment_id)) {
            if ($status != 3) {
                $this->echoJson([], 1, "审核状态错误");
            }
            $admin_user_id = Yii::app()->user->id;
            $remark = $_REQUEST['remark'];
            $data = array(
                "status" => $status,
                "partial_repayment_id" => $partial_repayment_id,
                "admin_user_id" => $admin_user_id,
                "remark" => $remark,
            );
            $result = PartialRepaymentService::getInstance()->updatePHPartialRepayment($data);
            if ($result['code'] != 0) {
                $this->echoJson([], $result['code'], $result['info']);
            }
            $this->echoJson([], 0, "拒绝成功");
        }
        return $this->renderPartial('PHRefuseEdit');
    }

    /**
     * 部分还款计划 查看详情
     */
    public function actionPHPartialDetail()
    {
        $model = Yii::app()->phdb;
        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }
        $pay_status = $_REQUEST['pay_status'];
        $repay_status = $_REQUEST['repay_status'];
        $name = $_REQUEST['name'];
        $user_id = $_REQUEST['user_id'];
        $partial_repay_id = $_REQUEST['partial_repayment_id'];
        $deal_loan_id = $_GET['deal_loan_id'];
        $export = !empty($_REQUEST['export']) ? $_REQUEST['export'] : 0; //导出数据
        if (empty($partial_repay_id) || !is_numeric($partial_repay_id)) {
            exit("partial_repayment_id 不存在");
        }
        if (!empty($pay_status) && !is_numeric($pay_status) && !in_array($pay_status, [0, 1, 2]) || !empty($user_id) && !is_numeric($user_id) || !empty($deal_loan_id) && !is_numeric($deal_loan_id)) {
            exit("参数错误");
        }
        //搜索条件
        $where = "partial_repay_id = $partial_repay_id";
        //借款标题
        if ($name) {
            $where .= " and name = '{$_GET['name']}'";
        }
        //导入状态
        if ($pay_status) {
            $where .= " and status = {$pay_status}";
        }
        //还款状态
        if ($repay_status) {
            $repay_status_code = [1 => 0,2 => 1];
            $repay_new_status = $repay_status_code[$repay_status];
            $where .= " and repay_status = {$repay_new_status}";
        }
        //用户ID
        if ($user_id) {
            $where .= " and user_id = {$user_id}";
        }
        //投资记录ID
        if ($deal_loan_id) {
            $where .= " and deal_loan_id = {$deal_loan_id}";
        }
        //导入状态1:成功,2:失败
        $statusArr = [1 => '成功', 2 => '失败'];
        //还款状态0:待还,1:已还
        $repayStatusArr = [0 => '待还', 1 => '已还'];
        // 查询数据总量
        $count = $model->createCommand("select count(*) from ag_wx_partial_repay_detail where {$where}")->queryScalar();
        if (!empty($count) && $count > 0) {
            $sql = "select * from ag_wx_partial_repay_detail where $where ORDER BY id DESC";
            $pass = ($page - 1) * $limit;
            //如果是导出数据
            if (empty($export)) {
                $sql .= " LIMIT {$pass} , {$limit} ";
            }
            $listInfo = $model->createCommand($sql)->queryAll();
            if (!empty($listInfo)) {
                foreach ($listInfo as $key => $value) {
                    $dataInfo[] = array(
                        "id" => $value['id'],
                        "name" => $value['name'],
                        "end_time" => !empty($value['end_time']) ? date("Y-m-d H:i:s", $value['end_time']) : "-",
                        "end_time" => date("Y-m-d H:i:s", $value['end_time']),
                        "deal_loan_id" => $value['deal_loan_id'],
                        "user_id" => $value['user_id'],
                        "repay_status" => $repayStatusArr[$value['repay_status']],
                        "repay_yestime" => !empty($value['repay_yestime']) ? date("Y-m-d",$value['repay_yestime']) : "-",
                        "repay_money" => $export == 1 ? $value['repay_money'] : ItzUtil::format_money($value['repay_money']),
                        "statusName" => $statusArr[$value['status']],
                        "remark" => $value['remark'],
                    );
                }
            }
            //导出操作
            if ($export == 1) {
                $dataRet = array(
                    "letter" => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','G'],
                    "tableheader" => ['序号', '借款标题', '到期日', '投资记录ID', '用户ID','还款状态','实际还款时间', '还款金额', '导入状态', '失败原因'],
                    "dataArr" => $dataInfo,
                );
                PartialRepaymentService::getInstance()->exportList($dataRet, "partial_repay_detail" . date("Y-m-d") . ".xls");
                die;
            }
        }
        $criteria = new CDbCriteria();
        $pages = new CPagination($count);
        $pages->pageSize = $limit;
        $pages->applyLimit($criteria);
        $pages = $this->widget('CLinkPager', array(
            'header' => '',
            'firstPageLabel' => '首页',
            'lastPageLabel' => '末页',
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'pages' => $pages,
            'maxButtonCount' => 8,
            'cssFile' => false,
            'htmlOptions' => array("class" => "pagination"),
            'selectedPageCssClass' => "active"
        ), true);
        return $this->renderPartial('PHPartialDetail', array('pages' => $pages, 'listInfo' => $dataInfo, 'statusArr' => $statusArr, 'repayStatusArr' => $repayStatusArr));
    }

    /**
     * 部分还款 编辑上传凭证
     */
    public function actionEditPartialProof()
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
                $proof_url = $upload_rar['data'];
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

        return $this->renderPartial('EditPartialProof', array('res' => $res));
    }

    /**
     * 上传压缩文件 张健 
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
     * 普惠 待还款列表 列表 张健
     * @param product_class     string      产品大类
     * @param deal_name         string      借款标题
     * @param approve_number    string      交易所备案号
     * @param project_name      string      项目名称
     * @param advisory_name     string      融资经办机构
     * @param real_name         string      借款人姓名
     * @param repay_type        int         还款资金类型 1-本金 2-利息
     * @param repay_status      int         还款状态 0-待还 1-已还清
     * @param start             string      开始日期
     * @param end               string      截止日期
     * @param limit             int         每页数据显示量 默认10
     * @param page              int         当前页数 默认1
     */
    public function actionLoanRepayList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            // 校验产品大类
            if (!empty($_POST['product_class'])) {
                $class  = trim($_POST['product_class']);
                $where .= " AND project_product_class = '{$class}' ";
            }
            // 校验借款标题
            if (!empty($_POST['deal_id'])) {
                $deal_id = intval($_POST['deal_id']);
                $where  .= " AND deal_id = {$deal_id} ";
            }
            // 校验借款标题
            if (!empty($_POST['deal_name'])) {
                $deal_name = trim($_POST['deal_name']);
                $where    .= " AND deal_name = '{$deal_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_POST['approve_number'])) {
                $approve_number = trim($_POST['approve_number']);
                $where         .= " AND jys_record_number = '{$approve_number}' ";
            }
            // 校验项目名称
            if (!empty($_POST['project_name'])) {
                $project_name = trim($_POST['project_name']);
                $where       .= " AND project_name = '{$project_name}' ";
            }
            // 校验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $where        .= " AND deal_advisory_name = '{$advisory_name}' ";
            }
            // 校验借款人姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND deal_user_real_name = '{$real_name}' ";
            }
            // 校验还款资金类型
            if (!empty($_POST['repay_type'])) {
                $repay_type = intval($_POST['repay_type']);
                $where     .= " AND repay_type = {$repay_type} ";
            }
            // 校验还款状态
            if (!empty($_POST['repay_status'])) {
                $repay_status = intval($_POST['repay_status']) - 1;
                $where       .= " AND repay_status = {$repay_status} ";
            }
            // 校验开始日期
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']) - 28800;
                $where .= " AND loan_repay_time >= {$start} ";
            }
            // 校验截止日期
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']) - 28800;
                $where .= " AND loan_repay_time <= {$end} ";
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
                        $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                        if(!empty($deallist)){
                            $dealIds = implode(",",ItzUtil::array_column($deallist,"deal_id"));
                            $where .= " AND deal_id IN({$dealIds})";
                        }else{
                            //不是超级管理员并且没有$dealIds
                            $where .= " AND deal_id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            $sql   = "SELECT count(id) AS count FROM ag_wx_stat_repay WHERE 1 = 1 {$where} ";
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
            $sql   = "SELECT * FROM ag_wx_stat_repay WHERE 1 = 1 {$where} ORDER BY deal_id , loan_repay_time";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();

            $loantype = Yii::app()->c->xf_config['loantype'];

            $type[1] = '本金';
            $type[2] = '利息';

            $status[0] = '待还';
            $status[1] = '已还';

            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');

            foreach ($list as $key => $value){
                if ($value['deal_loantype'] == 5) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }
                $value['deal_repay_start_time'] = date('Y-m-d' , $value['deal_repay_start_time'] + 28800);
                $value['loan_repay_time']       = date('Y-m-d' , $value['loan_repay_time'] + 28800);
                $value['real_amount']           = bcsub($value['repay_amount'] , $value['repaid_amount'] , 2);
                $value['real_amount']           = number_format($value['real_amount'] , 2 , '.' , ',');
                $value['repay_amount']          = number_format($value['repay_amount'] , 2 , '.' , ',');
                $value['repaid_amount']         = number_format($value['repaid_amount'] , 2 , '.' , ',');
                $value['borrow_amount']         = number_format($value['borrow_amount'] , 2 , '.' , ',');
                $value['repay_type']            = $type[$value['repay_type']];
                $value['repay_status_name']     = $status[$value['repay_status']];
                $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                $value['add_status']            = 0;
                $value['daochu_status']         = 0;
                if (!empty($authList) && strstr($authList,'/user/PartialRepay/StartLoanRepay') || empty($authList)) {
                    $value['add_status'] = 1;
                }
                if (!empty($authList) && strstr($authList,'/user/PartialRepay/LoanRepayListExcel') || empty($authList)) {
                    $value['daochu_status'] = 1;
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

        return $this->renderPartial('LoanRepayList', array());
    }

    /**
     * 普惠 待还款列表 列表 导出出借人信息 张健
     */
    public function actionLoanRepayListExcel()
    {
        if (empty($_GET['stat_repay_id'])) {
            echo iconv("UTF-8" , "gbk//TRANSLIT" , "<h1>请输入还款统计ID</h1>");exit;
        }
        if (!is_numeric($_GET['stat_repay_id'])) {
            echo iconv("UTF-8" , "gbk//TRANSLIT" , "<h1>还款统计ID类型输入错误</h1>");exit;
        }
        $id         = intval($_GET['stat_repay_id']);
        $sql        = "SELECT * FROM ag_wx_stat_repay WHERE id = {$id}";
        $stat_repay = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$stat_repay) {
            echo iconv("UTF-8" , "gbk//TRANSLIT" , "<h1>还款统计ID输入错误</h1>");exit;
        }
        $sql = "SELECT loan_user_id , money , deal_loan_id , deal_id
                FROM firstp2p_deal_loan_repay 
                WHERE deal_id = {$stat_repay['deal_id']} and type = {$stat_repay['repay_type']} and time = {$stat_repay['loan_repay_time']} and status = 0 and money > 0";
        $res = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$res) {
            echo iconv("UTF-8" , "gbk//TRANSLIT" , "<h1>未查询到还款计划</h1>");exit;
        }
        foreach ($res as $key => $value) {
            $ubc_array[] = $value['loan_user_id'];
            $dl_array[]  = $value['deal_loan_id'];
            $d_array[]   = $value['deal_id'];
            $u_array[]   = $value['loan_user_id'];
        }
        $ubc_string = implode(',' , $ubc_array);
        $dl_string  = implode(',' , $dl_array);
        $d_string   = implode(',' , $d_array);
        $u_string   = implode(',' , $u_array);

        $sql     = "SELECT user_id , bankcard , card_name , bankzone , bank_id FROM firstp2p_user_bankcard WHERE user_id IN ({$ubc_string}) AND verify_status = 1";
        $ubc_res = Yii::app()->fdb->createCommand($sql)->queryAll();
        if ($ubc_res) {
            foreach ($ubc_res as $key => $value) {
                $ubc_data[$value['user_id']] = $value;

                $bl_array[] = $value['bankzone'];
                $b_array[]  = $value['bank_id'];
            }
        } else {
            $ubc_data = array();
            $bl_array = array();
            $b_array  = array();
        }

        if ($b_array) {
            $b_string = implode("," , $b_array);
            $sql      = "SELECT id , name FROM firstp2p_bank WHERE id IN ({$b_string})";
            $b_res    = Yii::app()->fdb->createCommand($sql)->queryAll();
            if ($b_res) {
                foreach ($b_res as $key => $value) {
                    $b_data[$value['id']] = $value;
                }
            } else {
                $b_data = array();
            }
        } else {
            $b_data = array();
        }

        if ($bl_array) {
            $bl_string = "'".implode("','" , $bl_array)."'";
            $sql       = "SELECT name , province , city , bank_id FROM firstp2p_banklist WHERE name IN ({$bl_string}) AND status = 1";
            $bl_res    = Yii::app()->fdb->createCommand($sql)->queryAll();
            if ($bl_res) {
                foreach ($bl_res as $key => $value) {
                    $bl_data[$value['name']] = $value;
                }
            } else {
                $bl_data = array();
            }
        } else {
            $bl_data = array();
        }

        $sql    = "SELECT id , black_status , join_reason , debt_type FROM firstp2p_deal_load WHERE id IN ({$dl_string})";
        $dl_res = Yii::app()->phdb->createCommand($sql)->queryAll();
        if ($dl_res) {
            foreach ($dl_res as $key => $value) {
                $dl_data[$value['id']] = $value;
            }
        } else {
            $dl_data = array();
        }

        $sql   = "SELECT id , name FROM firstp2p_deal WHERE id IN ({$d_string})";
        $d_res = Yii::app()->phdb->createCommand($sql)->queryAll();
        if ($d_res) {
            foreach ($d_res as $key => $value) {
                $d_data[$value['id']] = $value;
            }
        } else {
            $d_data = array();
        }

        $sql   = "SELECT id , idno FROM firstp2p_user WHERE id IN ({$u_string})";
        $u_res = Yii::app()->fdb->createCommand($sql)->queryAll();
        if ($u_res) {
            foreach ($u_res as $key => $value) {
                $u_data[$value['id']] = $value;
            }
        } else {
            $u_data = array();
        }

        $sql      = "SELECT tender_id , status FROM firstp2p_debt WHERE tender_id IN ({$dl_string}) AND status IN (1 , 5 , 6)";
        $debt_res = Yii::app()->phdb->createCommand($sql)->queryAll();
        if ($debt_res) {
            foreach ($debt_res as $key => $value) {
                $debt_data[$value['tender_id']] = $value;
            }
        } else {
            $debt_data = array();
        }

        $status[1] = '否';
        $status[2] = '是';

        $black_status[1] = '否';
        $black_status[2] = '是';

        // $repay_way[1] = '现金展期兑付';
        // $repay_way[2] = '实物抵债兑付';

        $debt_status[1] = '转让中';
        $debt_status[5] = '待付款';
        $debt_status[6] = '待收款';

        foreach ($res as $key => $value) {
            if (!empty($ubc_data[$value['loan_user_id']])) {
                $res[$key]['bankcard']  = GibberishAESUtil::dec($ubc_data[$value['loan_user_id']]['bankcard'] , Yii::app()->c->idno_key);
                $res[$key]['card_name'] = $ubc_data[$value['loan_user_id']]['card_name'];
                $res[$key]['bankzone']  = $ubc_data[$value['loan_user_id']]['bankzone'];

                if (!empty($b_data[$ubc_data[$value['loan_user_id']]['bank_id']])) {
                    $res[$key]['branch']    = $b_data[$ubc_data[$value['loan_user_id']]['bank_id']]['name'];
                } else {
                    $res[$key]['branch']    = '';
                }

                if (!empty($bl_data[$res[$key]['bankzone']])) {
                    $res[$key]['province']  = $bl_data[$res[$key]['bankzone']]['province'];
                    $res[$key]['city']      = $bl_data[$res[$key]['bankzone']]['city'];
                    $res[$key]['bank_id']   = $bl_data[$res[$key]['bankzone']]['bank_id'];
                } else {
                    
                    $res[$key]['province']  = '';
                    $res[$key]['city']      = '';
                    $res[$key]['bank_id']   = '';
                }
            } else {
                $res[$key]['bankcard']  = '';
                $res[$key]['card_name'] = '';
                $res[$key]['bankzone']  = '';
                $res[$key]['province']  = '';
                $res[$key]['city']      = '';
                $res[$key]['bank_id']   = '';
            }

            $res[$key]['black_status'] = $black_status[$dl_data[$value['deal_loan_id']]['black_status']];
            $res[$key]['join_reason']  = $dl_data[$value['deal_loan_id']]['join_reason'];
            $res[$key]['status']       = $status[$dl_data[$value['deal_loan_id']]['debt_type']];
            $res[$key]['name']         = $d_data[$value['deal_id']]['name'];
            if ($u_data[$value['loan_user_id']]['idno']) {
                $res[$key]['idno']     = GibberishAESUtil::dec($u_data[$value['loan_user_id']]['idno'] , Yii::app()->c->idno_key);
            } else {
                $res[$key]['idno']     = '';
            }
            $res[$key]['repay_way']    = $repay_way[$dl_data[$value['deal_loan_id']]['repay_way']];

            if (!empty($debt_data[$value['deal_loan_id']])) {
                $res[$key]['debt_status'] = $debt_status[$debt_data[$value['deal_loan_id']]['status']];
            } else {
                $res[$key]['debt_status'] = '';
            }
        }

        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
        $objPHPExcel = new PHPExcel();
        // 设置当前的sheet
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('第一页');
        // 保护
        $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        // $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);

        $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
        $objPHPExcel->getActiveSheet()->setCellValue('B1' , '投资记录ID');
        $objPHPExcel->getActiveSheet()->setCellValue('C1' , '用户ID');
        $objPHPExcel->getActiveSheet()->setCellValue('D1' , '身份证号');
        $objPHPExcel->getActiveSheet()->setCellValue('E1' , '收款方账号');
        $objPHPExcel->getActiveSheet()->setCellValue('F1' , '收款方户名');
        $objPHPExcel->getActiveSheet()->setCellValue('G1' , '收款方银行');
        $objPHPExcel->getActiveSheet()->setCellValue('H1' , '收款方开户行所在省');
        $objPHPExcel->getActiveSheet()->setCellValue('I1' , '收款方开户行所在市');
        $objPHPExcel->getActiveSheet()->setCellValue('J1' , '银行联行号');
        $objPHPExcel->getActiveSheet()->setCellValue('K1' , '收款方开户行名称');
        $objPHPExcel->getActiveSheet()->setCellValue('L1' , '金额');
        $objPHPExcel->getActiveSheet()->setCellValue('M1' , '是否是转让');
        $objPHPExcel->getActiveSheet()->setCellValue('N1' , '是否加入黑名单');
        $objPHPExcel->getActiveSheet()->setCellValue('O1' , '备注');
        // $objPHPExcel->getActiveSheet()->setCellValue('P1' , '还款兑付方式');
        $objPHPExcel->getActiveSheet()->setCellValue('P1' , '债转状态');

        foreach ($res as $key => $value) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['deal_loan_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['loan_user_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , substr($value['idno'] , 0 , 6).'********'.substr($value['idno'] , -4 , 4));
            $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , ' '.$value['bankcard']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['card_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['bankzone']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['province']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['city']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , ' '.$value['bank_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2) , $value['branch']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2) , $value['money']);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2) , $value['status']);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2) , $value['black_status']);
            $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2) , $value['join_reason']);
            // $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2) , $value['repay_way']);
            $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2) , $value['debt_status']);
        }

        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        $name = "出借人信息 还款统计ID：{$id} ".date("Y年m月d日 H时i分s秒" , time());

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

    /**
     * 普惠 待还款列表 添加线下还款 张健
     * @param id                        主键ID
     * @param deal_id                   项目id
     * @param type                      必须为1  1尊享 2普惠
     * @param deal_name                 借款标题
     * @param jys_record_number         交易所备案编号
     * @param deal_advisory_id          融资经办机构ID
     * @param deal_advisory_name        融资经办机构名称 
     * @param deal_user_id              借款人ID
     * @param deal_user_real_name       借款人姓名
     * @param repayment_form            还款形式：0线下，1线上 目前必须为0
     * @param repay_type                还款类型 1常规还款2特殊还款'
     * @param loan_repay_type           资金类型 1-本金 2-利息 3本息全回(特殊还款可传3) 
     * @param evidence_pic              还款或收款凭证图
     * @param plan_time                 计划还款时间 可不填，填必须大于等于今日凌晨
     * @param normal_time               正常还款时间
     * @param repayment_total           还款金额
     * @param loan_user_id              投资人ID（特殊还款二选一必填）
     * @param deal_loan_id              投资记录ID（特殊还款二选一必填）
     * @param project_name              项目名称
     * @param project_product_class     产品大类
     * @param project_id                父项目id
     * @param file                      还款凭证
     */
    public function actionStartLoanRepay()
    {
        if (!empty($_POST)) {
            // $file = $this->upload_rar('file');
            // if ($file['code'] !== 0) {
            //     return $this->actionError($file['info'] , 5);
            // }
            // $data['evidence_pic']          = $file['data'];                                 // 还款凭证
            $data['repay_id']              = $_POST['id'];                                  // 主键ID
            $data['deal_id']               = $_POST['deal_id'];                             // 项目id
            $data['type']                  = 2;                                             // 普惠
            $data['deal_name']             = $_POST['deal_name'];                           // 借款标题
            $data['jys_record_number']     = $_POST['jys_record_number'];                   // 交易所备案编号
            $data['deal_advisory_id']      = $_POST['deal_advisory_id'];                    // 融资经办机构ID
            $data['deal_advisory_name']    = $_POST['deal_advisory_name'];                  // 融资经办机构名称
            $data['deal_user_id']          = $_POST['deal_user_id'];                        // 借款人ID
            $data['deal_user_real_name']   = $_POST['deal_user_real_name'];                 // 借款人姓名
            $data['repayment_form']        = 0;                                             // 线下
            $data['repay_type']            = 1;                                             // 常规还款
            $data['loan_repay_type']       = $_POST['repay_type'];                          // 还款资金类型 1-本金 2-利息
            $data['plan_time']             = strtotime($_POST['plan_time']);                // 计划还款时间
            $data['normal_time']           = strtotime($_POST['loan_repay_time']) - 28800;  // 正常还款时间
            $data['repayment_total']       = $_POST['real_amount'];                         // 还款金额
            $data['loan_user_id']          = '';                                            // 投资人ID（特殊还款二选一必填）
            $data['deal_loan_id']          = '';                                            // 投资记录ID（特殊还款二选一必填）
            $data['project_name']          = $_POST['project_name'];                        // 项目名称
            $data['project_product_class'] = $_POST['project_product_class'];               // 产品大类
            $data['project_id']            = $_POST['project_id'];                          // 父项目id
            // 调用services
            $result = AddpaymentService::getInstance()->addRepaymentPlan($data);
            if ($result['code'] != 0) {
                // unlink('./'.$data['evidence_pic']);
                return $this->actionError($result['info'] , 5);
            }
            return $this->actionSuccess('添加线下还款成功' , 3);
        }

        if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM ag_wx_stat_repay WHERE id = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误' , 5);
            }
            $res['loan_repay_time'] = date('Y-m-d' , $res['loan_repay_time'] + 28800);
            $res['real_amount']     = bcsub($res['repay_amount'] , $res['repaid_amount'] , 2);
        } else {
            return $this->actionError('请输入ID' , 5);
        }

        return $this->renderPartial('StartLoanRepay', array('info' => $res));
    }

    /**
     * 普惠 待审核列表 列表 张健
     * @param product_class     string      产品大类
     * @param deal_name         string      借款标题
     * @param approve_number    string      交易所备案号
     * @param project_name      string      项目名称
     * @param advisory_name     string      融资经办机构
     * @param real_name         string      借款人姓名
     * @param repay_type        int         还款资金类型
     * @param type              int         还款类型
     * @param start_a           string      正常还款日期
     * @param start             string      计划还款开始日期
     * @param end               string      计划还款截止日期
     * @param status            string      状态
     * @param limit             int         每页数据显示量 默认10
     * @param page              int         当前页数 默认1
     */
    public function actionExamineLoanRepayList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            // 校验还款形式
            if (!empty($_POST['repayment'])) {
                $repayment = intval($_POST['repayment']) - 1;
                $where    .= " AND p.repayment_form = {$repayment} ";
            }
            // 校验产品大类
            if (!empty($_POST['product_class'])) {
                $class  = trim($_POST['product_class']);
                $where .= " AND p.project_product_class = '{$class}' ";
            }
            // 校验借款ID
            if (!empty($_POST['deal_id'])) {
                $deal_id = intval($_POST['deal_id']);
                $where  .= " AND p.deal_id = '{$deal_id}' ";
            }
            // 校验借款标题
            if (!empty($_POST['deal_name'])) {
                $deal_name = trim($_POST['deal_name']);
                $where    .= " AND p.deal_name = '{$deal_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_POST['approve_number'])) {
                $approve_number = trim($_POST['approve_number']);
                $where         .= " AND p.jys_record_number = '{$approve_number}' ";
            }
            // 校验项目名称
            if (!empty($_POST['project_name'])) {
                $project_name = trim($_POST['project_name']);
                $where       .= " AND p.project_name = '{$project_name}' ";
            }
            // 校验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $where        .= " AND p.deal_advisory_name = '{$advisory_name}' ";
            }
            // 校验借款人姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND p.deal_user_real_name = '{$real_name}' ";
            }
            // 校验还款资金类型
            if (!empty($_POST['repay_type'])) {
                $repay_type = intval($_POST['repay_type']);
                $where     .= " AND p.loan_repay_type = {$repay_type} ";
            }
            // 校验还款类型
            if (!empty($_POST['type'])) {
                $t      = intval($_POST['type']);
                $where .= " AND p.repay_type = {$t} ";
            }
            // 校验正常还款日期
            if (!empty($_POST['start_a'])) {
                $start_a = strtotime($_POST['start_a']) - 28800;
                $where  .= " AND p.normal_time = '{$start_a}' ";
            }
            // 校验计划还款开始日期
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND p.plan_time >= {$start} ";
            }
            // 校验计划还款截止日期
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND p.plan_time <= {$end} ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status']) - 1;
                $where .= " AND p.status = {$sta} ";
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
            $whereAdd = '';
            if(!empty($adminUserInfo['username'])){
                if($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin){
                    if($adminUserInfo['user_type'] == 2){
                        $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                        if(!empty($deallist)){
                            $dealIds = implode(",",ItzUtil::array_column($deallist,"deal_id"));
                            $whereAdd = " AND d.id IN({$dealIds})";
                        }else{
                            $whereAdd = " AND d.id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            $sql   = "SELECT count(p.id) AS count FROM ag_wx_repayment_plan AS p INNER JOIN firstp2p_deal AS d ON p.deal_id = d.id {$where} where p.status != 5 $whereAdd";
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
            $sql   = "SELECT p.* , d.id AS deal_id , d.borrow_amount , d.repay_time AS deal_repay_time , d.loantype AS deal_loantype , d.rate AS deal_rate , d.success_time AS deal_repay_start_time FROM ag_wx_repayment_plan AS p INNER JOIN firstp2p_deal AS d ON p.deal_id = d.id {$where} where p.status != 5 $whereAdd GROUP BY id DESC";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();

            $loantype = Yii::app()->c->xf_config['loantype'];

            $type[1] = '本金';
            $type[2] = '利息';
            $type[3] = '本息全回';

            $ty[1] = '常规还款';
            $ty[2] = '特殊还款';

            $status[0] = '待审核';
            $status[1] = '审核通过';
            $status[2] = '审核未通过';
            $status[3] = '还款成功';
            $status[4] = '还款失败';

            $repay[0] = '线下';
            $repay[1] = '线上';

            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');

            foreach ($list as $key => $value){
                if ($value['deal_loantype'] == 5) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }
                $value['deal_repay_start_time'] = date('Y-m-d' , $value['deal_repay_start_time'] + 28800);
                $value['plan_time']             = date('Y-m-d' , $value['plan_time']);
                if ($value['task_success_time']) {
                    $value['task_success_time'] = date('Y-m-d H:i:s' , $value['task_success_time']);
                } else {
                    $value['task_success_time'] = '——';
                }
                $value['repayment_total']       = number_format($value['repayment_total'] , 2 , '.' , ',');
                $value['borrow_amount']         = number_format($value['borrow_amount'] , 2 , '.' , ',');
                $value['repayment_form']        = $repay[$value['repayment_form']];
                $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                $value['loan_repay_type']       = $type[$value['loan_repay_type']];
                $value['repay_type_name']       = $ty[$value['repay_type']];
                $value['status_name']           = $status[$value['status']];
                if ($value['evidence_pic']) {
                    $value['evidence_pic'] = "<a class='layui-btn layui-btn-xs layui-btn-normal' href='/{$value['evidence_pic']}' download title='下载还款凭证'><i class='layui-icon'>&#xe601;</i>下载</a>";
                }
                if ($value['attachments_url']) {
                    $value['attachments_url'] = "<a class='layui-btn layui-btn-xs layui-btn-normal' href='/{$value['attachments_url']}' download title='下载附件'><i class='layui-icon'>&#xe601;</i>下载</a>";
                }
                $value['edit_status']   = 0;
                $value['pass_status']   = 0;
                $value['reject_status'] = 0;
                if (!empty($authList) && strstr($authList,'/user/PartialRepay/EditExamineLoanRepay') || empty($authList)) {
                    $value['edit_status'] = 1;
                }
                if (!empty($authList) && strstr($authList,'/user/PartialRepay/PassExamineLoanRepay') || empty($authList)) {
                    $value['pass_status'] = 1;
                }
                if (!empty($authList) && strstr($authList,'/user/PartialRepay/RejectExamineLoanRepay') || empty($authList)) {
                    $value['reject_status'] = 1;
                }
                if (in_array($value['status'] , array(0 , 2))) {
                    $value['edit_status'] = 2;
                }
                if ($value['status'] == 0) {
                    $value['pass_status']   = 2;
                    $value['reject_status'] = 2;
                }

                $normal_time     = explode(',' , $value['normal_time']);
                $normal_time_arr = array();
                foreach ($normal_time as $k => $v) {
                    $normal_time_arr[] = date('Y-m-d' , ($v + 28800));
                }
                $value['normal_time_str'] = implode(',' , $normal_time_arr);

                $listInfo[] = $value;
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }
        
        return $this->renderPartial('ExamineLoanRepayList', array());
    }

    /**
     * 普惠 待审核列表 通过 张健
     * @param id    int     ID
     */
    public function actionPassExamineLoanRepay()
    {
        // 校验ID
        if (!empty($_POST['id'])) {
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_repayment_plan WHERE id = {$id} ";
            $old = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array() , 1000 , 'ID输入错误');
            }
            // 校验状态
            if ($old['status'] != 0) {
                $this->echoJson(array() , 1001 , '此记录不能重复审核');
            }
            // 校验计划还款日期
            if ($old['plan_time'] < strtotime(date('Y-m-d' , time()))) {
                $this->echoJson(array() , 1002 , '此记录的计划还款时间已经过期，请先编辑后再审核');
            }
            // 校验凭证
            if ($old['evidence_pic'] == '') {
                $this->echoJson(array() , 1003 , '此记录未上传还款凭证');
            }
            $start_admin_id = Yii::app()->user->id;
            $start_admin_id = $start_admin_id ? $start_admin_id : 0 ;
            $starttime      = time();
            $startip        = Yii::app()->request->userHostAddress;
            $sql            = "UPDATE ag_wx_repayment_plan SET status = 1 , start_admin_id = {$start_admin_id} , starttime = {$starttime} , startip = '{$startip}' WHERE id = {$old['id']} ";
            $result         = Yii::app()->phdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array() , 1004 , '操作失败');
            }
            $this->echoJson(array() , 0 , '操作成功');
        }
    }

    /**
     * 普惠 待审核列表 拒绝 张健
     * @param id            int     ID
     * @param task_remark   string  拒绝原因
     */
    public function actionRejectExamineLoanRepay()
    {
        // 校验ID
        if (!empty($_POST['id']) && !empty($_POST['task_remark'])) {
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_repayment_plan WHERE id = {$id} ";
            $old = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array() , 1000 , 'ID输入错误');
            }
            // 校验状态
            if ($old['status'] != 0) {
                $this->echoJson(array() , 1001 , '此记录不能重复审核');
            }
            $start_admin_id = Yii::app()->user->id;
            $start_admin_id = $start_admin_id ? $start_admin_id : 0 ;
            $starttime      = time();
            $startip        = Yii::app()->request->userHostAddress;
            $sql            = "UPDATE ag_wx_repayment_plan SET status = 2 , start_admin_id = {$start_admin_id} , starttime = {$starttime} , startip = '{$startip}' , task_remark = '{$_POST['task_remark']}' WHERE id = {$old['id']} ";
            $result         = Yii::app()->phdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array() , 1002 , '操作失败');
            }
            $this->echoJson(array() , 0 , '操作成功');
        }
    }

    /**
     * 普惠 待审核列表 撤销
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
        $model = Yii::app()->phdb;
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
     * 普惠 待审核列表 线下 常规 编辑 张健
     * @param id                        主键ID
     * @param deal_id                   项目id
     * @param type                      必须为1  1尊享 2普惠
     * @param deal_name                 借款标题
     * @param jys_record_number         交易所备案编号
     * @param deal_advisory_id          融资经办机构ID
     * @param deal_advisory_name        融资经办机构名称 
     * @param deal_user_id              借款人ID
     * @param deal_user_real_name       借款人姓名
     * @param repayment_form            还款形式：0线下，1线上 目前必须为0
     * @param repay_type                还款类型 1常规还款2特殊还款'
     * @param loan_repay_type           资金类型 1-本金 2-利息 3本息全回(特殊还款可传3) 
     * @param evidence_pic              还款或收款凭证图
     * @param plan_time                 计划还款时间 可不填，填必须大于等于今日凌晨
     * @param normal_time               正常还款时间
     * @param repayment_total           还款金额
     * @param loan_user_id              投资人ID（特殊还款二选一必填）
     * @param deal_loan_id              投资记录ID（特殊还款二选一必填）
     * @param project_name              项目名称
     * @param project_product_class     产品大类
     * @param file                      还款凭证
     */
    public function actionEditExamineLoanRepay()
    {
        if (!empty($_POST)) {
            $file = $this->upload_rar('file');
            if ($file['code'] === 0) {
                $data['evidence_pic'] = $file['data'];
            } else {
                $data['evidence_pic'] = $_POST['old_evidence_pic'];
            }
            $data['id']                    = $_POST['id'];                              // 主键ID
            $data['repay_id']              = $_POST['repay_id'];                        // stat_repay主键
            $data['deal_id']               = $_POST['deal_id'];                         // 项目id
            $data['type']                  = 2;                                         // 普惠
            $data['deal_name']             = $_POST['deal_name'];                       // 借款标题
            $data['jys_record_number']     = $_POST['jys_record_number'];               // 交易所备案编号
            $data['deal_advisory_id']      = $_POST['deal_advisory_id'];                // 融资经办机构ID
            $data['deal_advisory_name']    = $_POST['deal_advisory_name'];              // 融资经办机构名称
            $data['deal_user_id']          = $_POST['deal_user_id'];                    // 借款人ID
            $data['deal_user_real_name']   = $_POST['deal_user_real_name'];             // 借款人姓名
            $data['repayment_form']        = 0;                                         // 线下
            $data['repay_type']            = 1;                                         // 常规还款
            $data['loan_repay_type']       = $_POST['loan_repay_type'];                 // 还款资金类型 1-本金 2-利息
            $data['plan_time']             = strtotime($_POST['plan_time']);            // 计划还款时间
            $data['normal_time']           = strtotime($_POST['normal_time']) - 28800;  // 正常还款时间
            $data['repayment_total']       = $_POST['repayment_total'];                 // 还款金额
            $data['loan_user_id']          = '';                                        // 投资人ID（特殊还款二选一必填）
            $data['deal_loan_id']          = '';                                        // 投资记录ID（特殊还款二选一必填）
            $data['project_name']          = $_POST['project_name'];                    // 项目名称
            $data['project_product_class'] = $_POST['project_product_class'];           // 产品大类

            // 调用services
            $result = AddpaymentService::getInstance()->addRepaymentPlan($data);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'] , 5);
            }
            return $this->actionSuccess('保存成功' , 3);
        }

        if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM ag_wx_repayment_plan WHERE id = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误' , 5);
            }
            $res['normal_time']     = date('Y-m-d' , $res['normal_time'] + 28800);
            $res['plan_time']       = date('Y-m-d' , $res['plan_time']);
        } else {
            return $this->actionError('请输入ID' , 5);
        }

        return $this->renderPartial('EditExamineLoanRepay', array('info' => $res));
    }

    /**
     * ajax校验借款标题 张健
     * @param project_product_class     string   产品大类
     * @param deal_name                 string   借款标题
     */
    public function actionCheckDealName()
    {
        if (empty($_POST['project_product_class'])) {
            $this->echoJson(array() , 1000 , '请选择产品大类');
        }
        if (empty($_POST['deal_name'])) {
            $this->echoJson(array() , 1001 , '请输入借款标题');
        }
        $project_product_class = trim($_POST['project_product_class']);
        $deal_name             = trim($_POST['deal_name']);
        $sql  = "SELECT * FROM firstp2p_deal WHERE name = '{$deal_name}' ";
        $deal = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$deal) {
            $this->echoJson(array() , 1002 , '借款标题输入错误');
        }
        $sql          = "SELECT * FROM firstp2p_deal_project WHERE id = {$deal['project_id']} ";
        $deal_project = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$deal_project) {
            $this->echoJson(array() , 1003 , '所属项目不存在');
        }
        if ($deal_project['product_class'] != $project_product_class) {
            $this->echoJson(array() , 1004 , '此借款标题不属于所选产品大类');
        }
        $sql             = "SELECT DISTINCT(time) FROM firstp2p_deal_loan_repay WHERE deal_id = {$deal['id']} and status = 0";
        $deal_loan_repay = Yii::app()->phdb->createCommand($sql)->queryAll();
        if ($deal_loan_repay) {
            foreach ($deal_loan_repay as $key => $value) {
                $deal_loan_repay[$key]['time_name'] = date('Y-m-d' , ($value['time'] + 28800));
            }
        } else {
            $deal_loan_repay = array();
        }
        $this->echoJson(array('project_name' => $deal_project['name'] , 'deal_loan_repay' => $deal_loan_repay , 'deal' => $deal , 'deal_project' => $deal_project) , 0 , '成功');
    }

    /**
     * 上传CSV文件 张健 
     * @param name  string  文件名称
     * @return array
     */
    private function upload_csv($name)
    {
        $file  = $_FILES[$name];
        $types = array('csv');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的CSV文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的CSV文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => 'CSV文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有CSV文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => 'CSV文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => 'CSV文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if(!in_array($file_type, $types)){
            return array('code' => 2007 , 'info' => 'CSV文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000,99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir , 0777 , true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建CSV文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"] , './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存CSV文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存CSV文件失败' , 'data' => '');
        }
    }

    /**
     * 普惠 线下 特殊还款申请 张健
     * @param deal_id                   项目id
     * @param type                      必须为1  1尊享 2普惠
     * @param deal_name                 借款标题
     * @param jys_record_number         交易所备案编号
     * @param deal_advisory_id          融资经办机构ID
     * @param deal_advisory_name        融资经办机构名称 
     * @param deal_user_id              借款人ID
     * @param deal_user_real_name       借款人姓名
     * @param repayment_form            还款形式：0线下，1线上 目前必须为0
     * @param repay_type                还款类型 1常规还款2特殊还款'
     * @param loan_repay_type           资金类型 1-本金 2-利息 3本息全回(特殊还款可传3) 
     * @param plan_time                 计划还款时间 可不填，填必须大于等于今日凌晨
     * @param normal_time               正常还款时间
     * @param repayment_total           还款金额
     * @param loan_user_id              投资人ID（特殊还款二选一必填）
     * @param deal_loan_id              投资记录ID（特殊还款二选一必填）
     * @param project_name              项目名称
     * @param project_product_class     产品大类
     * @param file                      还款凭证
     * @param file_csv                  附件
     */
    public function actionAddSpecialLoanRepay()
    {
        if (!empty($_POST)) {
            // $file = $this->upload_rar('file');
            // if ($file['code'] !== 0) {
            //     return $this->actionError($file['info'] , 5);
            // }
            $file_csv = $this->upload_csv('file_csv');
            if ($file_csv['code'] === 0) {
                $data['attachments_url']   = $file_csv['data'];
            } else {
                $data['attachments_url']   = '';
            }
            // $data['evidence_pic']          = $file['data'];                             // 还款凭证
            $data['deal_id']               = $_POST['deal_id'];                         // 项目id
            $data['type']                  = 2;                                         // 普惠
            $data['deal_name']             = $_POST['deal_name'];                       // 借款标题
            $data['jys_record_number']     = $_POST['jys_record_number'];               // 交易所备案编号
            $data['deal_advisory_id']      = $_POST['deal_advisory_id'];                // 融资经办机构ID
            $data['deal_advisory_name']    = $_POST['deal_advisory_name'];              // 融资经办机构名称
            $data['deal_user_id']          = $_POST['deal_user_id'];                    // 借款人ID
            $data['deal_user_real_name']   = $_POST['deal_user_real_name'];             // 借款人姓名
            $data['repayment_form']        = 0;                                         // 线下
            $data['repay_type']            = 2;                                         // 特殊还款
            $data['loan_repay_type']       = $_POST['loan_repay_type'];                 // 还款资金类型 1-本金 2-利息 3-本息全回
            $data['plan_time']             = strtotime($_POST['plan_time']);            // 计划还款时间
            $data['normal_time']           = $_POST['normal_time'];                     // 正常还款时间
            $data['repayment_total']       = $_POST['repayment_total'];                 // 还款金额
            if ($_POST['type'] == 1) {
                $data['loan_user_id']      = $_POST['loan_user_id'];                    // 投资人ID（特殊还款二选一必填）
            } else if ($_POST['type'] == 2) {
                $data['deal_loan_id']      = $_POST['deal_loan_id'];                    // 投资记录ID（特殊还款二选一必填）
            }
            $data['project_name']          = $_POST['project_name'];                    // 项目名称
            $data['project_product_class'] = $_POST['project_product_class'];           // 产品大类
            // 调用services
            $result = AddpaymentService::getInstance()->addRepaymentPlan($data);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'] , 5);
            }
            return $this->actionSuccess('添加特殊还款申请成功' , 3);
        }

        return $this->renderPartial('AddSpecialLoanRepay', array());
    }

    /**
     * 普惠 待审核列表 线下 特殊 编辑 张健
     * @param id                        主键ID
     * @param deal_id                   项目id
     * @param type                      必须为1  1尊享 2普惠
     * @param deal_name                 借款标题
     * @param jys_record_number         交易所备案编号
     * @param deal_advisory_id          融资经办机构ID
     * @param deal_advisory_name        融资经办机构名称 
     * @param deal_user_id              借款人ID
     * @param deal_user_real_name       借款人姓名
     * @param repayment_form            还款形式：0线下，1线上 目前必须为0
     * @param repay_type                还款类型 1常规还款2特殊还款'
     * @param loan_repay_type           资金类型 1-本金 2-利息 3本息全回(特殊还款可传3) 
     * @param plan_time                 计划还款时间 可不填，填必须大于等于今日凌晨
     * @param normal_time               正常还款时间
     * @param repayment_total           还款金额
     * @param loan_user_id              投资人ID（特殊还款二选一必填）
     * @param deal_loan_id              投资记录ID（特殊还款二选一必填）
     * @param project_name              项目名称
     * @param project_product_class     产品大类
     * @param file                      还款凭证
     * @param file_csv                  附件
     */
    public function actionEditSpecialLoanRepay()
    {
        if (!empty($_POST)) {
            $file = $this->upload_rar('file');
            if ($file['code'] === 0) {
                $data['evidence_pic']      = $file['data'];
            } else {
                $data['evidence_pic']      = $_POST['old_evidence_pic'];
            }
            $file_csv = $this->upload_csv('file_csv');
            if ($file_csv['code'] === 0) {
                $data['attachments_url']   = $file_csv['data'];
            } else {
                $data['attachments_url']   = $_POST['old_attachments_url'];
            }
            $data['id']                    = $_POST['id'];                              // ID
            $data['deal_id']               = $_POST['deal_id'];                         // 项目id
            $data['type']                  = 2;                                         // 普惠
            $data['deal_name']             = $_POST['deal_name'];                       // 借款标题
            $data['jys_record_number']     = $_POST['jys_record_number'];               // 交易所备案编号
            $data['deal_advisory_id']      = $_POST['deal_advisory_id'];                // 融资经办机构ID
            $data['deal_advisory_name']    = $_POST['deal_advisory_name'];              // 融资经办机构名称
            $data['deal_user_id']          = $_POST['deal_user_id'];                    // 借款人ID
            $data['deal_user_real_name']   = $_POST['deal_user_real_name'];             // 借款人姓名
            $data['repayment_form']        = 0;                                         // 线下
            $data['repay_type']            = 2;                                         // 特殊还款
            $data['loan_repay_type']       = $_POST['loan_repay_type'];                 // 还款资金类型 1-本金 2-利息 3-本息全回
            $data['plan_time']             = strtotime($_POST['plan_time']);            // 计划还款时间
            $data['normal_time']           = $_POST['normal_time'];                     // 正常还款时间
            $data['repayment_total']       = $_POST['repayment_total'];                 // 还款金额
            if ($_POST['type'] == 1) {
                $data['loan_user_id']      = $_POST['loan_user_id'];                    // 投资人ID（特殊还款二选一必填）
            } else if ($_POST['type'] == 2) {
                $data['deal_loan_id']      = $_POST['deal_loan_id'];                    // 投资记录ID（特殊还款二选一必填）
            }
            $data['project_name']          = $_POST['project_name'];                    // 项目名称
            $data['project_product_class'] = $_POST['project_product_class'];           // 产品大类
            // 调用services
            $result = AddpaymentService::getInstance()->addRepaymentPlan($data);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'] , 5);
            }
            return $this->actionSuccess('保存成功' , 3);
        }

        if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM ag_wx_repayment_plan WHERE id = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误' , 5);
            }
            $res['plan_time']         = date('Y-m-d' , $res['plan_time']);
            $evidence_pic             = explode('/', $res['evidence_pic']);
            $res['evidence_pic_a']    = $evidence_pic[2];
            $attachments_url          = explode('/', $res['attachments_url']);
            $res['attachments_url_a'] = $attachments_url[2];

        } else {
            return $this->actionError('请输入ID' , 5);
        }

        return $this->renderPartial('EditSpecialLoanRepay', array('info' => $res));
    }

    /**
     * 先锋部分还款列表
     */
    public function actionXFPartialList()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->offlinedb;
            // 条件筛选
            $sql = "SELECT id FROM offline_platform ";
            $pla = $model->createCommand($sql)->queryColumn();
            if (!in_array($_POST['platform_id'] , $pla)) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入平台ID';
                echo exit(json_encode($result_data));
            }
            $where = " WHERE platform_id = '{$_POST['platform_id']}' AND type = 1 AND status != 6 ";
            // 序号
            if (!empty($_POST['id'])) {
                $id     = trim($_POST['id']);
                $where .= " AND id = '{$id}' ";
            }
            // 状态
            if (!empty($_POST['status'])) {
                $where .= " AND status = '{$_POST['status']}' ";
            }
            // 时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND pay_plan_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND pay_plan_time <= {$end} ";
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
            $sql   = "SELECT count(id) AS count FROM offline_partial_repay {$where} ";
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
            $sql = "SELECT * FROM offline_partial_repay {$where} ORDER BY id DESC ";
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
            $authList      = \Yii::app()->user->getState('_auth');
            $edit_status   = 0;
            $pass_status   = 0;
            $refuse_status = 0;
            $delete_status = 0;
            if (!empty($authList) && strstr($authList,'/user/PartialRepay/XFEditPartial') || empty($authList)) {
                $edit_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/PartialRepay/XFPassPartial') || empty($authList)) {
                $pass_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/PartialRepay/XFRefusePartial') || empty($authList)) {
                $refuse_status = 1;
            }
            if (!empty($authList) && strstr($authList,'/user/PartialRepay/XFDeletePartial') || empty($authList)) {
                $delete_status = 1;
            }
            $status = array(1 => '待审核' , 2 => '审核已通过' , 3 => '审核未通过' , 4 => '还款成功' , 5 => '还款失败');
            $user_id_arr = array();
            foreach ($list as $key => $value) {
                $value['total_repayment']         = number_format($value['total_repayment'], 2, '.', ',');
                $value['total_successful_amount'] = number_format($value['total_successful_amount'], 2, '.', ',');
                $value['total_fail_amount']       = number_format($value['total_fail_amount'], 2, '.', ',');
                $value['addtime']                 = date('Y-m-d H:i:s' , $value['addtime']);
                $value['pay_plan_time']           = date('Y-m-d' , $value['pay_plan_time']);
                $value['status_name']             = $status[$value['status']];
                if ($value['task_success_time'] > 0) {
                    $value['task_success_time']   = date('Y-m-d H:i:s' , $value['task_success_time']);
                } else {
                    $value['task_success_time']   = '——';
                }
                if ($value['proof_url']) {
                    $value['proof_url'] = "<a href='/{$value['proof_url']}' download><button class='layui-btn layui-btn-primary'>下载</button></a>";
                } else {
                    $value['proof_url'] = '——';
                }
                if ($value['status'] == 1) {
                    $value['edit_status']   = $edit_status;
                    $value['pass_status']   = $pass_status;
                    $value['refuse_status'] = $refuse_status;
                    $value['delete_status'] = $delete_status;
                } else if (in_array($value['status'] , array(2 , 4 , 5))) {
                    $value['edit_status']   = 0;
                    $value['pass_status']   = 0;
                    $value['refuse_status'] = 0;
                    $value['delete_status'] = 0;
                } else if ($value['status'] == 3) {
                    $value['edit_status']   = 0;
                    $value['pass_status']   = 0;
                    $value['refuse_status'] = 0;
                    $value['delete_status'] = $delete_status;
                }
                $user_id_arr[] = $value['admin_user_id'];
                
                $listInfo[] = $value;
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT id , realname FROM itz_user WHERE id IN ({$user_id_str}) ";
                $user_infos_res = Yii::app()->db->createCommand($sql)->queryAll();
                foreach ($user_infos_res as $key => $value) {
                    $user_infos[$value['id']] = $value['realname'];
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['admin_user'] = $user_infos[$value['admin_user_id']];
                }
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/PartialRepay/AddXFPartial') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('XFPartialList', array('add_status' => $add_status));
    }

    // 先锋部分还款录入
    public function actionAddXFPartial()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '投资记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '还款金额（单位元，保留两位小数）');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '工场微金部分还款信息模板 '.date("Y年m月d日 H时i分s秒" , time());

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
            // 校验平台
            if (empty($_POST['platform_id']) || !is_numeric($_POST['platform_id'])) {
                return $this->actionError('请正确输入平台ID' , 5);
            }
            $platform_id = intval($_POST['platform_id']);
            // 校验付款方
            if (empty($_POST['pay_user'])) {
                return $this->actionError('请输入付款方' , 5);
            }
            $pay_user = trim($_POST['pay_user']);
            // 校验还款信息
            if (empty($_FILES['template'])) {
                return $this->actionError('请上传还款信息' , 5);
            }
            // 是否出清
            if (empty($_POST['is_clear']) || !in_array($_POST['is_clear'], [1,2])) {
                return $this->actionError('请选择是否出清' , 5);
            }
            $is_clear = $_POST['is_clear'];
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            $template_url = $upload_xls['data'];
            // 校验计划还款日期
            if (!empty($_POST['pay_plan_time'])) {
                $pay_plan_time = strtotime($_POST['pay_plan_time']);
            } else {
                $pay_plan_time = strtotime(date('Y-m-d' , time()));
            }
            // 校验还款凭证
            // if (empty($_FILES['proof'])) {
            //     return $this->actionError('请上传还款凭证' , 5);
            // }
            // $upload_rar = $this->upload_rar('proof');
            // if ($upload_rar['code'] != 0) {
            //     return $this->actionError($upload_rar['info'] , 5);
            // }
            // $proof_url = $upload_rar['data'];
            $proof_url = '';

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$template_url;
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
            $result = PartialService::getInstance()->add_XF_partial_repayment($pay_user , $pay_plan_time , $template_url , $proof_url , $data, $is_clear , $platform_id);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'] , 5);
                unlink('./'.$template_url);
            }
            return $this->actionSuccess($result['info'] , 3);
        }

        return $this->renderPartial('AddXFPartial', array());
    }

    /**
     * 先锋部分还款详情
     */
    public function actionXFPartialInfo()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->offlinedb;
            // 条件筛选
            if (empty($_POST['id']) || !is_numeric($_POST['id']) || empty($_POST['platform_id']) || !is_numeric($_POST['platform_id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入信息';
                echo exit(json_encode($result_data));
            }
            $where = " WHERE prd.partial_repay_id = '{$_POST['id']}' AND pr.platform_id = '{$_POST['platform_id']}' ";
            // 借款标题
            if (!empty($_POST['name'])) {
                $name   = trim($_POST['name']);
                $where .= " AND prd.name = '{$name}' ";
            }
            // 投资记录ID
            if (!empty($_POST['deal_loan_id'])) {
                $deal_loan_id = trim($_POST['deal_loan_id']);
                $where       .= " AND prd.deal_loan_id = '{$deal_loan_id}' ";
            }
            // 用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
                $where  .= " AND prd.user_id = '{$user_id}' ";
            }
            // 导入状态
            if (!empty($_POST['status'])) {
                $status = intval($_POST['status']);
                $where .= " AND prd.status = '{$status}' ";
            }
            // 还款状态
            if (!empty($_POST['repay_status'])) {
                $repay_status = intval($_POST['repay_status']) - 1;
                $where       .= " AND prd.repay_status = '{$repay_status}' ";
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
            $sql   = "SELECT count(prd.id) AS count FROM offline_partial_repay_detail AS prd INNER JOIN offline_partial_repay AS pr ON pr.id = prd.partial_repay_id {$where} ";
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
            $sql = "SELECT prd.* FROM offline_partial_repay_detail AS prd INNER JOIN offline_partial_repay AS pr ON pr.id = prd.partial_repay_id {$where} ";
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

            $status_name       = array(1 => '成功' , 2 => '失败');
            $repay_status_name = array(0 => '待还' , 1 => '已还');
            foreach ($list as $key => $value) {
                $value['repay_money']  = number_format($value['repay_money'], 2, '.', ',');
                $value['end_time']     = date('Y-m-d H:i:s' , $value['end_time']);
                $value['status']       = $status_name[$value['status']];
                $value['repay_status'] = $repay_status_name[$value['repay_status']];
                if ($value['repay_yestime'] > 0) {
                    $value['repay_yestime'] = date('Y-m-d H:i:s' , $value['repay_yestime']);
                } else {
                    $value['repay_yestime'] = '——';
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

        // 获取当前账号所有子权限
        $authList      = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList,'/user/PartialRepay/XFPartial2Excel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('XFPartialInfo', array('daochu_status' => $daochu_status));
    }

    /**
     * 先锋部分还款导出
     */
    public function actionXFPartial2Excel()
    {
        if (!empty($_GET)) {
            $model = Yii::app()->offlinedb;
            // 条件筛选
            if (empty($_GET['id']) || !is_numeric($_GET['id']) || empty($_GET['platform_id']) || !is_numeric($_GET['platform_id'])) {
                echo '<h1>请正确输入信息</h1>';exit;
            }
            $sql = "SELECT * FROM offline_partial_repay WHERE id = '{$_GET['id']}' AND platform_id = '{$_GET['platform_id']}' ";
            $partial = $model->createCommand($sql)->queryRow();
            if (!$partial) {
                echo '<h1>信息输入错误</h1>';exit;
            }
            $sql = "SELECT * FROM offline_platform WHERE id = '{$_GET['platform_id']}' ";
            $platform = $model->createCommand($sql)->queryRow();
            $where = " WHERE partial_repay_id = '{$_GET['id']}' ";
            // 借款标题
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND name = '{$name}' ";
            }
            // 投资记录ID
            if (!empty($_GET['deal_loan_id'])) {
                $deal_loan_id = trim($_GET['deal_loan_id']);
                $where       .= " AND deal_loan_id = '{$deal_loan_id}' ";
            }
            // 用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND user_id = '{$user_id}' ";
            }
            // 导入状态
            if (!empty($_GET['status'])) {
                $status = intval($_GET['status']);
                $where .= " AND status = '{$status}' ";
            }
            // 还款状态
            if (!empty($_GET['repay_status'])) {
                $repay_status = intval($_GET['repay_status']) - 1;
                $where       .= " AND repay_status = '{$repay_status}' ";
            }
            // 查询数据
            $sql = "SELECT * FROM offline_partial_repay_detail {$where} ";
            $list = $model->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>未查询到数据</h1>';exit;
            }
            $status_name       = array(1 => '成功' , 2 => '失败');
            $repay_status_name = array(0 => '待还' , 1 => '已还');
            foreach ($list as $key => $value) {
                $value['repay_money']  = number_format($value['repay_money'], 2, '.', ',');
                $value['end_time']     = date('Y-m-d H:i:s' , $value['end_time']);
                $value['status']       = $status_name[$value['status']];
                $value['repay_status'] = $repay_status_name[$value['repay_status']];
                if ($value['repay_yestime'] > 0) {
                    $value['repay_yestime'] = date('Y-m-d H:i:s' , $value['repay_yestime']);
                } else {
                    $value['repay_yestime'] = '——';
                }
                
                $listInfo[] = $value;
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

            foreach ($listInfo as $key => $value) {
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
            $name = "{$platform['name']}部分还款详情导出 ".date("Y年m月d日 H时i分s秒" , time());
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
     * 先锋部分还款编辑
     */
    public function actionXFEditPartial()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])){
                return $this->actionError('请输入ID' , 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM offline_partial_repay WHERE id = {$id} AND platform_id = {$_POST['platform_id']}";
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
                $proof_url = $upload_rar['data'];
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
            $sql  = "UPDATE offline_partial_repay SET proof_url = '{$proof_url}' , pay_plan_time = {$pay_plan_time} , updatetime = {$time} WHERE id = {$res['id']}";
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
        $sql = "SELECT * FROM offline_partial_repay WHERE id = {$id} AND platform_id = {$_GET['platform_id']}";
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

        return $this->renderPartial('XFEditPartial', array('res' => $res));
    }

    /**
     * 先锋部分还款通过
     */
    public function actionXFPassPartial()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $admin_user_id = Yii::app()->user->id;
        $status = $_REQUEST['status'];
        $data = array(
            "status" => $status,
            "partial_repayment_id" => $partial_repayment_id,
            "admin_user_id" => $admin_user_id,
        );

        $result = PartialRepaymentService::getInstance()->updateXFPartialRepayment($data);
        if ($result['code'] != 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "审核成功");
    }

    /**
     * 先锋部分还款拒绝
     */
    public function actionXFRefusePartial()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $status = $_REQUEST['status'];
        if (!empty($status) && !empty($partial_repayment_id)) {
            if ($status != 3) {
                $this->echoJson([], 1, "审核状态错误");
            }
            $admin_user_id = Yii::app()->user->id;
            $remark = $_REQUEST['remark'];
            $data = array(
                "status" => $status,
                "partial_repayment_id" => $partial_repayment_id,
                "admin_user_id" => $admin_user_id,
                "remark" => $remark,
            );
            $result = PartialRepaymentService::getInstance()->updateXFPartialRepayment($data);
            if ($result['code'] != 0) {
                $this->echoJson([], $result['code'], $result['info']);
            }
            $this->echoJson([], 0, "拒绝成功");
        }
        return $this->renderPartial('RefuseEdit');
    }

    /**
     * 先锋部分还款移除
     */
    public function actionXFDeletePartial()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $admin_user_id = Yii::app()->user->id;
        $status = $_REQUEST['status'];
        if ($status != 6) {
            $this->echoJson([], 1, "审核状态错误");
        }
        $data = array(
            "status" => $status,
            "partial_repayment_id" => $partial_repayment_id,
            "admin_user_id" => $admin_user_id,
        );
        $result = PartialRepaymentService::getInstance()->updateXFPartialRepayment($data);
        if ($result['code'] != 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "移除成功");
    }

    /**
     * 智多新部分还款列表
     */
    public function actionZDXPartialList()
    {
        // 获取当前账号所有子权限
        $authList    = \Yii::app()->user->getState('_auth');
        $add_status  = 0;
        if (!empty($authList) && strstr($authList,'/user/PartialRepay/AddZDXPartial') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('ZDXPartialList' , array('add_status' => $add_status));
    }

    /**
     * 智多新部分还款录入
     */
    public function actionAddZDXPartial()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '投资记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '还款金额（单位元，保留两位小数）');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '智多新部分还款信息模板 '.date("Y年m月d日 H时i分s秒" , time());

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

        return $this->renderPartial('AddZDXPartial');
    }

    /**
     * 智多新部分还款详情
     */
    public function actionZDXPartialInfo()
    {
        // 获取当前账号所有子权限
        $authList      = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList,'/user/PartialRepay/XFPartial2Excel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('ZDXPartialInfo', array('daochu_status' => $daochu_status));
    }

    /**
     * 智多新部分还款导出
     */
    public function actionZDXPartial2Excel()
    {
        if (!empty($_GET)) {
            $model = Yii::app()->offlinedb;
            // 条件筛选
            if (empty($_GET['id']) || !is_numeric($_GET['id']) || empty($_GET['platform_id']) || !is_numeric($_GET['platform_id'])) {
                echo '<h1>请正确输入信息</h1>';exit;
            }
            $sql = "SELECT * FROM offline_partial_repay WHERE id = '{$_GET['id']}' AND platform_id = '{$_GET['platform_id']}' ";
            $partial = $model->createCommand($sql)->queryRow();
            if (!$partial) {
                echo '<h1>信息输入错误</h1>';exit;
            }
            $sql = "SELECT * FROM offline_platform WHERE id = '{$_GET['platform_id']}' ";
            $platform = $model->createCommand($sql)->queryRow();
            $where = " WHERE partial_repay_id = '{$_GET['id']}' ";
            // 借款标题
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND name = '{$name}' ";
            }
            // 投资记录ID
            if (!empty($_GET['deal_loan_id'])) {
                $deal_loan_id = trim($_GET['deal_loan_id']);
                $where       .= " AND deal_loan_id = '{$deal_loan_id}' ";
            }
            // 用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND user_id = '{$user_id}' ";
            }
            // 导入状态
            if (!empty($_GET['status'])) {
                $status = intval($_GET['status']);
                $where .= " AND status = '{$status}' ";
            }
            // 还款状态
            if (!empty($_GET['repay_status'])) {
                $repay_status = intval($_GET['repay_status']) - 1;
                $where       .= " AND repay_status = '{$repay_status}' ";
            }
            // 查询数据
            $sql = "SELECT * FROM offline_partial_repay_detail {$where} ";
            $list = $model->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>未查询到数据</h1>';exit;
            }
            $status_name       = array(1 => '成功' , 2 => '失败');
            $repay_status_name = array(0 => '待还' , 1 => '已还');
            foreach ($list as $key => $value) {
                $value['repay_money']  = number_format($value['repay_money'], 2, '.', ',');
                $value['end_time']     = date('Y-m-d H:i:s' , $value['end_time']);
                $value['status']       = $status_name[$value['status']];
                $value['repay_status'] = $repay_status_name[$value['repay_status']];
                if ($value['repay_yestime'] > 0) {
                    $value['repay_yestime'] = date('Y-m-d H:i:s' , $value['repay_yestime']);
                } else {
                    $value['repay_yestime'] = '——';
                }
                
                $listInfo[] = $value;
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

            foreach ($listInfo as $key => $value) {
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
            $name = "{$platform['name']}部分还款详情导出 ".date("Y年m月d日 H时i分s秒" , time());
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
     * 智多新部分还款编辑
     */
    public function actionZDXEditPartial()
    {
        if (empty($_GET['id'])) {
            return $this->actionError('请输入ID' , 5);
        }
        $id  = intval($_GET['id']);
        $sql = "SELECT * FROM offline_partial_repay WHERE id = {$id} AND platform_id = {$_GET['platform_id']}";
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

        return $this->renderPartial('ZDXEditPartial', array('res' => $res));
    }

    /**
     * 智多新部分还款通过
     */
    public function actionZDXPassPartial()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $admin_user_id = Yii::app()->user->id;
        $status = $_REQUEST['status'];
        $data = array(
            "status" => $status,
            "partial_repayment_id" => $partial_repayment_id,
            "admin_user_id" => $admin_user_id,
        );

        $result = PartialRepaymentService::getInstance()->updateXFPartialRepayment($data);
        if ($result['code'] != 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "审核成功");
    }

    /**
     * 智多新部分还款拒绝
     */
    public function actionZDXRefusePartial()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $status = $_REQUEST['status'];
        if (!empty($status) && !empty($partial_repayment_id)) {
            if ($status != 3) {
                $this->echoJson([], 1, "审核状态错误");
            }
            $admin_user_id = Yii::app()->user->id;
            $remark = $_REQUEST['remark'];
            $data = array(
                "status" => $status,
                "partial_repayment_id" => $partial_repayment_id,
                "admin_user_id" => $admin_user_id,
                "remark" => $remark,
            );
            $result = PartialRepaymentService::getInstance()->updateXFPartialRepayment($data);
            if ($result['code'] != 0) {
                $this->echoJson([], $result['code'], $result['info']);
            }
            $this->echoJson([], 0, "拒绝成功");
        }
        return $this->renderPartial('RefuseEdit');
    }

    /**
     * 智多新部分还款移除
     */
    public function actionZDXDeletePartial()
    {
        $partial_repayment_id = $_REQUEST['partial_repayment_id'];
        $admin_user_id = Yii::app()->user->id;
        $status = $_REQUEST['status'];
        if ($status != 6) {
            $this->echoJson([], 1, "审核状态错误");
        }
        $data = array(
            "status" => $status,
            "partial_repayment_id" => $partial_repayment_id,
            "admin_user_id" => $admin_user_id,
        );
        $result = PartialRepaymentService::getInstance()->updateXFPartialRepayment($data);
        if ($result['code'] != 0) {
            $this->echoJson([], $result['code'], $result['info']);
        }
        $this->echoJson([], 0, "移除成功");
    }
}
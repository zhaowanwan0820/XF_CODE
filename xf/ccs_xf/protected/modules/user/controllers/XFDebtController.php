<?php
use iauth\models\AuthAssignment;
class XFDebtController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success' , 'Error' , 'P2PStatisticsCompany' , 'P2PStatisticsGuaranteeCompany' , 'P2PStatisticsCooperationCompany' , 'addDebtListCondition' , 'addLoanListCondition' , 'addDealLoadBYDealCondition' , 'addDealLoadBYUserCondition' , 'AddUserConditionUpload'
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
     * 网信在途数据统计表 列表
     */
    public function actionP2PStatistics()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " WHERE platform_id IN (0 , 1 , 2 , 4) ";
            // 校验查询时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start'].' 00:00:00');
                $where .= " AND add_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end'].' 23:59:59');
                $where .= " AND add_time <= {$end} ";
            }
            // 所属平台
            if (!empty($_POST['platform'])) {
                $platform = intval($_POST['platform']);
                $where   .= " AND platform_id = {$platform} ";
            } else {
                $where   .= " AND platform_id = 0 ";
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
            $sql   = "SELECT count(*) AS count FROM xf_data_statistics {$where} ";
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
            $sql  = "SELECT * FROM xf_data_statistics {$where} ORDER BY add_time DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            $pla = array(0 => '全平台' , 1 => '尊享' , 2 => '普惠(不含智多新)' , 4 => '智多新');
            foreach ($list as $key => $value){
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['zx_capital_total']         = number_format($value['zx_capital_total'] , 2 , '.' , ',');
                $value['zx_interest_total']        = number_format($value['zx_interest_total'] , 2 , '.' , ',');
                $value['ph_capital_total']         = number_format($value['ph_capital_total'] , 2 , '.' , ',');
                $value['ph_interest_total']        = number_format($value['ph_interest_total'] , 2 , '.' , ',');
                $value['shop_debt_money_total']    = number_format($value['shop_debt_money_total'] , 2 , '.' , ',');
                $value['zx_debt_money']            = number_format($value['zx_debt_money'] , 2 , '.' , ',');
                $value['ph_debt_money']            = number_format($value['ph_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment_total']     = number_format($value['cash_repayment_total'] , 2 , '.' , ',');
                $value['zx_cash_repayment']        = number_format($value['zx_cash_repayment'] , 2 , '.' , ',');
                $value['ph_cash_repayment']        = number_format($value['ph_cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money_total'] = number_format($value['offline_debt_money_total'] , 2 , '.' , ',');
                $value['zx_offline_debt_money']    = number_format($value['zx_offline_debt_money'] , 2 , '.' , ',');
                $value['ph_offline_debt_money']    = number_format($value['ph_offline_debt_money'] , 2 , '.' , ',');
                $value['shop_debt_money']          = number_format($value['shop_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment']           = number_format($value['cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money']       = number_format($value['offline_debt_money'] , 2 , '.' , ',');
                $value['repayment_capital_total']  = number_format($value['repayment_capital_total'] , 2 , '.' , ',');
                $value['zx_repayment_capital']     = number_format($value['zx_repayment_capital'] , 2 , '.' , ',');
                $value['ph_repayment_capital']     = number_format($value['ph_repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest_total'] = number_format($value['repayment_interest_total'] , 2 , '.' , ',');
                $value['zx_repayment_interest']    = number_format($value['zx_repayment_interest'] , 2 , '.' , ',');
                $value['ph_repayment_interest']    = number_format($value['ph_repayment_interest'] , 2 , '.' , ',');
                $value['repayment_capital']        = number_format($value['repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest']       = number_format($value['repayment_interest'] , 2 , '.' , ',');
                $frozen_capital_total = bcsub($value['capital_total'] ,$value['frozen_capital_total'], 2);
                $frozen_interest_total = bcsub($value['interest_total'] ,$value['frozen_interest_total'], 2);
                $value['capital_total']            = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['interest_total']           = number_format($value['interest_total'] , 2 , '.' , ',');
                $value['frozen_capital_total_tips']            = number_format( $frozen_capital_total, 2 , '.' , ',');
                $value['frozen_interest_total_tips']           = number_format( $frozen_interest_total, 2 , '.' , ',');
                $value['platform']                 = $pla[$value['platform_id']];
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
        $P2PStatistics2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/XFDebt/P2PStatistics2Excel') || empty($authList)) {
            $P2PStatistics2Excel = 1;
        }
        return $this->renderPartial('P2PStatistics' , array('P2PStatistics2Excel' => $P2PStatistics2Excel));
    }

    /**
     *专属收购统计 列表
     */
    public function actionPurchaseStatistics()
    {
        $sql = "SELECT assi.user_id AS id , user.real_name AS name FROM xf_purchase_assignee AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status=2";
        $user_arr = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$user_arr) {
            $user_arr = array(0 => array('id' => -1 , 'name' => '暂无受让方'));
        }
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
            // 所属平台
            if (isset($_POST['purchase_user_id']) && $_POST['purchase_user_id'] != 'ALL') {
                $platform = intval($_POST['purchase_user_id']);
                $where   .= " AND purchase_user_id = {$platform} ";
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
            $sql   = "SELECT count(*) AS count FROM xf_new_purchase_statistics {$where} ";
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
            $sql  = "SELECT * FROM xf_new_purchase_statistics {$where} ORDER BY add_time DESC,purchase_user_id asc ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value){
                $value['add_time'] = date('Y-m-d', $value['add_time']);
                $value['day_capital_total'] = number_format($value['day_capital_total'] , 2 , '.' , ',');
                $value['day_rw_total'] = number_format($value['day_rw_total'] , 2 , '.' , ',');
                //$value['day_user_number'] = number_format($value['day_user_number'] , 2 , '.' , ',');
                $value['day_money_total'] = number_format($value['day_money_total'] , 2 , '.' , ',');
                $value['day_debt_money_ratio'] = $value['day_debt_money_ratio'].'%';
                $value['capital_total'] = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['rw_total'] = number_format($value['rw_total'] , 2 , '.' , ',');
                //$value['user_number'] = number_format($value['user_number'] , 2 , '.' , ',');
                $value['money_total'] = number_format($value['money_total'] , 2 , '.' , ',');
                $value['debt_money_ratio'] = $value['debt_money_ratio'].'%';

                //用户姓名
                $value['purchase_name'] = "合计" ;
                if($value['purchase_user_id'] >0 ){
                    $sql   = "SELECT real_name   FROM firstp2p_user where id={$value['purchase_user_id']} ";
                    $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
                    $value['purchase_name'] = $user_info['real_name'];
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
        $P2PStatistics2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/XFDebt/PurchaseStatistics2Excel') || empty($authList)) {
            $P2PStatistics2Excel = 1;
        }
        return $this->renderPartial('PurchaseStatistics' , array('P2PStatistics2Excel' => $P2PStatistics2Excel , 'user_arr' => $user_arr));
    }

    /**
     * 专属收购 导出
     */
    public function actionPurchaseStatistics2Excel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " WHERE  1=1 ";
            if (empty($_GET['start']) && empty($_GET['end']) && !isset($_GET['purchase_user_id'])) {
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
            // 所属平台
            if (isset($_GET['purchase_user_id']) && $_GET['purchase_user_id'] != 'ALL') {
                $platform = intval($_GET['purchase_user_id']);
                $where   .= " AND purchase_user_id = {$platform} ";
            }
            // 查询数据
            $sql  = "SELECT * FROM xf_new_purchase_statistics {$where} ORDER BY id DESC ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            foreach ($list as $key => $value){
                $value['add_time'] = date('Y-m-d', $value['add_time']);
                $value['day_capital_total'] = number_format($value['day_capital_total'] , 2 , '.' , ',');
                $value['day_rw_total'] = number_format($value['day_rw_total'] , 2 , '.' , ',');
                //$value['day_user_number'] = number_format($value['day_user_number'] , 2 , '.' , ',');
                $value['day_money_total'] = number_format($value['day_money_total'] , 2 , '.' , ',');
                $value['day_debt_money_ratio'] = $value['day_debt_money_ratio'].'%';
                $value['capital_total'] = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['rw_total'] = number_format($value['rw_total'] , 2 , '.' , ',');
                //$value['user_number'] = number_format($value['user_number'] , 2 , '.' , ',');
                $value['money_total'] = number_format($value['money_total'] , 2 , '.' , ',');
                $value['debt_money_ratio'] = $value['debt_money_ratio'].'%';

                //用户姓名
                $value['purchase_name'] = "合计" ;
                if($value['purchase_user_id'] >0 ){
                    $sql   = "SELECT real_name   FROM firstp2p_user where id={$value['purchase_user_id']} ";
                    $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
                    $value['purchase_name'] = $user_info['real_name'];
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);


            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '日期');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '受让方');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '当日收购债权(在途)');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '当日收购债权(充提差)');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '当日收购人数');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '当日收购支付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '当日现金/债权(在途)兑付比例');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '截止当日收购总债权(在途)');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '截止当日收购总债权(充提差)');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '截止当日收购总人数');
            $objPHPExcel->getActiveSheet()->setCellValue('K1' , '截止当日总收购支付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('L1' , '截至当日总现金/债权(在途)兑付比例');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['purchase_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['day_capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['day_rw_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['day_user_number']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['day_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['day_debt_money_ratio']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['rw_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['user_number']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2) , $value['money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2) , $value['debt_money_ratio']);
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '定向收购数据统计表 '.date("Y年m月d日 H时i分s秒" , time());

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
     *交易所在途数据统计表 列表
     */
    public function actionJysStatistics()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " WHERE platform_id=5";
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
            $sql   = "SELECT count(*) AS count FROM xf_data_statistics {$where} ";
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
            $sql  = "SELECT * FROM xf_data_statistics {$where} ORDER BY add_time DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();


            foreach ($list as $key => $value){
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['zx_capital_total']         = number_format($value['zx_capital_total'] , 2 , '.' , ',');
                $value['zx_interest_total']        = number_format($value['zx_interest_total'] , 2 , '.' , ',');
                $value['ph_capital_total']         = number_format($value['ph_capital_total'] , 2 , '.' , ',');
                $value['ph_interest_total']        = number_format($value['ph_interest_total'] , 2 , '.' , ',');
                $value['shop_debt_money_total']    = number_format($value['shop_debt_money_total'] , 2 , '.' , ',');
                $value['zx_debt_money']            = number_format($value['zx_debt_money'] , 2 , '.' , ',');
                $value['ph_debt_money']            = number_format($value['ph_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment_total']     = number_format($value['cash_repayment_total'] , 2 , '.' , ',');
                $value['zx_cash_repayment']        = number_format($value['zx_cash_repayment'] , 2 , '.' , ',');
                $value['ph_cash_repayment']        = number_format($value['ph_cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money_total'] = number_format($value['offline_debt_money_total'] , 2 , '.' , ',');
                $value['zx_offline_debt_money']    = number_format($value['zx_offline_debt_money'] , 2 , '.' , ',');
                $value['ph_offline_debt_money']    = number_format($value['ph_offline_debt_money'] , 2 , '.' , ',');
                $value['shop_debt_money']          = number_format($value['shop_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment']           = number_format($value['cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money']       = number_format($value['offline_debt_money'] , 2 , '.' , ',');
                $value['repayment_capital_total']  = number_format($value['repayment_capital_total'] , 2 , '.' , ',');
                $value['zx_repayment_capital']     = number_format($value['zx_repayment_capital'] , 2 , '.' , ',');
                $value['ph_repayment_capital']     = number_format($value['ph_repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest_total'] = number_format($value['repayment_interest_total'] , 2 , '.' , ',');
                $value['zx_repayment_interest']    = number_format($value['zx_repayment_interest'] , 2 , '.' , ',');
                $value['ph_repayment_interest']    = number_format($value['ph_repayment_interest'] , 2 , '.' , ',');
                $value['repayment_capital']        = number_format($value['repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest']       = number_format($value['repayment_interest'] , 2 , '.' , ',');
                $value['capital_total']            = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['interest_total']           = number_format($value['interest_total'] , 2 , '.' , ',');


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
        $P2PStatistics2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/XFDebt/jysStatistics2Excel') || empty($authList)) {
            $P2PStatistics2Excel = 1;
        }
        return $this->renderPartial('jysStatistics' , array('P2PStatistics2Excel' => $P2PStatistics2Excel));
    }


    /**
     * 查看当日兑付借款企业信息详情
     */
    public function actionP2PStatisticsCompany()
    {
        if (!empty($_POST)) {
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
            if (empty($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请输入ID';
                echo exit(json_encode($result_data));
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_data_statistics WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 2;
                $result_data['info']  = 'ID输入错误';
                echo exit(json_encode($result_data));
            }
            $data = json_decode($res['company'] , true);
            if (!$data) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            $count = count($data);
            $start = ($page - 1) * $limit;
            $end   = ($page * $limit) - 1;
            $i     = 0;
            foreach ($data as $key => $value) {
                if ($i >= $start && $i <= $end) {
                    $temp = array();
                    $temp['name']  = $value['name'];
                    $temp['money'] = number_format($value['repay_money_t'] , 2 , '.' , ',');

                    $listInfo[] = $temp;
                }
                $i++;
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('P2PStatisticsCompany' , array());
    }

    /**
     * 查看当日兑付担保企业信息详情
     */
    public function actionP2PStatisticsGuaranteeCompany()
    {
        if (!empty($_POST)) {
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
            if (empty($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请输入ID';
                echo exit(json_encode($result_data));
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_data_statistics WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 2;
                $result_data['info']  = 'ID输入错误';
                echo exit(json_encode($result_data));
            }
            $data = json_decode($res['guarantee_company'] , true);
            if (!$data) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            $count = count($data);
            $start = ($page - 1) * $limit;
            $end   = ($page * $limit) - 1;
            $i     = 0;
            foreach ($data as $key => $value) {
                if ($i >= $start && $i <= $end) {
                    $temp = array();
                    $temp['name']  = $value['name'];
                    $temp['money'] = number_format($value['repay_money_t'] , 2 , '.' , ',');

                    $listInfo[] = $temp;
                }
                $i++;
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('P2PStatisticsGuaranteeCompany' , array());
    }

    /**
     * 查看当日兑付资产合作机构信息详情
     */
    public function actionP2PStatisticsCooperationCompany()
    {
        if (!empty($_POST)) {
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
            if (empty($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请输入ID';
                echo exit(json_encode($result_data));
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM xf_data_statistics WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 2;
                $result_data['info']  = 'ID输入错误';
                echo exit(json_encode($result_data));
            }
            $data = json_decode($res['cooperation_company'] , true);
            if (!$data) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            $count = count($data);
            $start = ($page - 1) * $limit;
            $end   = ($page * $limit) - 1;
            $i     = 0;
            foreach ($data as $key => $value) {
                if ($i >= $start && $i <= $end) {
                    $temp = array();
                    $temp['name']  = $value['name'];
                    $temp['money'] = number_format($value['repay_money_t'] , 2 , '.' , ',');

                    $listInfo[] = $temp;
                }
                $i++;
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('P2PStatisticsCooperationCompany' , array());
    }

    /**
     * 网信在途数据统计表 导出
     */
    public function actionP2PStatistics2Excel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " WHERE platform_id IN (0 , 1 , 2 , 4) ";
            if (empty($_GET['start']) && empty($_GET['end']) && empty($_GET['platform'])) {
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
            // 所属平台
            if (!empty($_GET['platform'])) {
                $platform = intval($_GET['platform']);
                $where   .= " AND platform_id = {$platform} ";
            } else {
                $where   .= " AND platform_id = 0 ";
            }
            // 查询数据
            $sql  = "SELECT * FROM xf_data_statistics {$where} ORDER BY id DESC ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            foreach ($list as $key => $value){
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['zx_capital_total']         = number_format($value['zx_capital_total'] , 2 , '.' , ',');
                $value['zx_interest_total']        = number_format($value['zx_interest_total'] , 2 , '.' , ',');
                $value['ph_capital_total']         = number_format($value['ph_capital_total'] , 2 , '.' , ',');
                $value['ph_interest_total']        = number_format($value['ph_interest_total'] , 2 , '.' , ',');
                $value['shop_debt_money_total']    = number_format($value['shop_debt_money_total'] , 2 , '.' , ',');
                $value['cash_repayment_total']     = number_format($value['cash_repayment_total'] , 2 , '.' , ',');
                $value['offline_debt_money_total'] = number_format($value['offline_debt_money_total'] , 2 , '.' , ',');
                $value['shop_debt_money']          = number_format($value['shop_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment']           = number_format($value['cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money']       = number_format($value['offline_debt_money'] , 2 , '.' , ',');
                $value['repayment_capital_total']  = number_format($value['repayment_capital_total'] , 2 , '.' , ',');
                $value['repayment_interest_total'] = number_format($value['repayment_interest_total'] , 2 , '.' , ',');
                $value['repayment_capital']        = number_format($value['repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest']       = number_format($value['repayment_interest'] , 2 , '.' , ',');
                $frozen_capital_total = bcsub($value['capital_total'] ,$value['frozen_capital_total'], 2);
                $frozen_interest_total = bcsub($value['interest_total'] ,$value['frozen_interest_total'], 2);
                $value['capital_total']            = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['interest_total']           = number_format($value['interest_total'] , 2 , '.' , ',');
                $value['frozen_capital_total_tips']            = number_format( $frozen_capital_total, 2 , '.' , ',');
                $value['frozen_interest_total_tips']           = number_format( $frozen_interest_total, 2 , '.' , ',');
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('X')->setWidth(20);


            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '统计时间');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '去重后总人数(含冻结)');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '去重后总人数(排除冻结)');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '在途本金总金额(含冻结)');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '在途本金总金额(排除冻结)');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '在途利息总金额(含冻结)');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '在途利息总金额(排除冻结)');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '商城累计化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '商城累计化债人数');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '现金累计兑付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('K1' , '线下咨询权益化债总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('L1' , '当日商城化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('M1' , '当日现金兑付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('N1' , '当日线下咨询权益化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('O1' , '当日商城兑付人数');
            $objPHPExcel->getActiveSheet()->setCellValue('P1' , '当日现金兑付人数');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1' , '当日线下咨询权益化债人数');
            $objPHPExcel->getActiveSheet()->setCellValue('R1' , '累计兑付本金');
            $objPHPExcel->getActiveSheet()->setCellValue('S1' , '累计兑付利息');
            $objPHPExcel->getActiveSheet()->setCellValue('T1' , '当日兑付本金');
            $objPHPExcel->getActiveSheet()->setCellValue('U1' , '当日兑付利息');
            $objPHPExcel->getActiveSheet()->setCellValue('V1' , '当日兑付出借人数');
            $objPHPExcel->getActiveSheet()->setCellValue('W1' , '当日出清出借人数');
            $objPHPExcel->getActiveSheet()->setCellValue('X1' , '累计出清出借人数');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['distinct_user_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['frozen_distinct_user_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['frozen_capital_total_tips']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['interest_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['frozen_interest_total_tips']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['shop_debt_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['shop_debt_user_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['cash_repayment_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2) , $value['offline_debt_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2) , $value['shop_debt_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2) , $value['cash_repayment']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2) , $value['offline_debt_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2) , $value['shop_debt_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2) , $value['cash_repayment_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2) , $value['offline_debt_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('R' . ($key + 2) , $value['repayment_capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('S' . ($key + 2) , $value['repayment_interest_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('T' . ($key + 2) , $value['repayment_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('U' . ($key + 2) , $value['repayment_interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('V' . ($key + 2) , $value['repayment_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('W' . ($key + 2) , $value['repayment_clear_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('X' . ($key + 2) , $value['repayment_clear_user_total']);
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '网信在途数据统计表 '.date("Y年m月d日 H时i分s秒" , time());

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
     * 交易所在途数据统计表 导出
     */
    public function actionJysStatistics2Excel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " WHERE platform_id=5 ";
            if (empty($_GET['start']) && empty($_GET['end']) && empty($_GET['platform'])) {
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
            $sql  = "SELECT * FROM xf_data_statistics {$where} ORDER BY id DESC ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            foreach ($list as $key => $value){
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['zx_capital_total']         = number_format($value['zx_capital_total'] , 2 , '.' , ',');
                $value['zx_interest_total']        = number_format($value['zx_interest_total'] , 2 , '.' , ',');
                $value['ph_capital_total']         = number_format($value['ph_capital_total'] , 2 , '.' , ',');
                $value['ph_interest_total']        = number_format($value['ph_interest_total'] , 2 , '.' , ',');
                $value['shop_debt_money_total']    = number_format($value['shop_debt_money_total'] , 2 , '.' , ',');
                $value['cash_repayment_total']     = number_format($value['cash_repayment_total'] , 2 , '.' , ',');
                $value['offline_debt_money_total'] = number_format($value['offline_debt_money_total'] , 2 , '.' , ',');
                $value['shop_debt_money']          = number_format($value['shop_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment']           = number_format($value['cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money']       = number_format($value['offline_debt_money'] , 2 , '.' , ',');
                $value['repayment_capital_total']  = number_format($value['repayment_capital_total'] , 2 , '.' , ',');
                $value['repayment_interest_total'] = number_format($value['repayment_interest_total'] , 2 , '.' , ',');
                $value['repayment_capital']        = number_format($value['repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest']       = number_format($value['repayment_interest'] , 2 , '.' , ',');
                $value['capital_total']            = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['interest_total']           = number_format($value['interest_total'] , 2 , '.' , ',');

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
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '统计时间');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '去重后总人数');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '在途本金总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '在途利息总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '商城累计化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '商城累计化债人数');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '现金累计兑付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '线下咨询权益化债总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '当日商城化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '当日现金兑付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('K1' , '当日线下咨询权益化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('L1' , '当日商城兑付人数');
            $objPHPExcel->getActiveSheet()->setCellValue('M1' , '当日现金兑付人数');
            $objPHPExcel->getActiveSheet()->setCellValue('N1' , '当日线下咨询权益化债人数');
            $objPHPExcel->getActiveSheet()->setCellValue('O1' , '累计兑付本金');
            $objPHPExcel->getActiveSheet()->setCellValue('P1' , '累计兑付利息');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1' , '当日兑付本金');
            $objPHPExcel->getActiveSheet()->setCellValue('R1' , '当日兑付利息');
            $objPHPExcel->getActiveSheet()->setCellValue('S1' , '当日兑付出借人数');
            $objPHPExcel->getActiveSheet()->setCellValue('T1' , '当日出清出借人数');
            $objPHPExcel->getActiveSheet()->setCellValue('U1' , '累计出清出借人数');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['distinct_user_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['interest_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['shop_debt_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['shop_debt_user_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['cash_repayment_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['offline_debt_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['shop_debt_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['cash_repayment']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2) , $value['offline_debt_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2) , $value['shop_debt_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2) , $value['cash_repayment_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2) , $value['offline_debt_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2) , $value['repayment_capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2) , $value['repayment_interest_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2) , $value['repayment_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('R' . ($key + 2) , $value['repayment_interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('S' . ($key + 2) , $value['repayment_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('T' . ($key + 2) , $value['repayment_clear_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('U' . ($key + 2) , $value['repayment_clear_user_total']);
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '交易所在途数据统计表 '.date("Y年m月d日 H时i分s秒" , time());

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
     * 工场微金有解化债数据查询
     */
    public function actionDebtList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 3) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入所属平台';
                echo exit(json_encode($result_data));
            }
            $platform_id = intval($_POST['deal_type']);
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
                    $platform_id = $condition['platform'];
                }
            }
            $where = " WHERE debt.platform_id = {$platform_id} AND deal.is_effect = 1 AND deal.is_delete = 0 ";
            // 校验转让人ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_POST['serial_number'])) {
                $serial_number = trim($_POST['serial_number']);
                $where        .= " AND debt.serial_number = '{$serial_number}'";
            }
            // 校验借款编号
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
                $sta = intval($_POST['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验借款标题
            if (!empty($_POST['name'])) {
                $name   = trim($_POST['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验转让人手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.phone = '{$mobile}' ";
            }
            // 校验债转类型
            if (!empty($_POST['debt_src'])) {
                $d_src  = intval($_POST['debt_src']);
                $where .= " AND debt.debt_src = {$d_src} ";
            }
            // 校验融资方名称
            if (!empty($_POST['company'])) {
                $company = trim($_POST['company']);
                $sql     = "SELECT user_id FROM firstp2p_enterprise WHERE company_name = '{$company}' ";
                $com_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where  .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $sql         = "SELECT user_id FROM offline_user_platform WHERE real_name = '{$company}' ";
                    $user_id_arr =  Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if ($user_id_arr) {
                        $user_id_str = implode(',' , $user_id_arr);
                        $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                    } else {
                        $where      .= " AND deal.user_id = -1 ";
                    }
                }
            }
            // 校验融资经办机构
            if (!empty($_POST['advisory'])) {
                $advisory = trim($_POST['advisory']);
                $sql      = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory}' AND is_effect = 1 ";
                $adv_arr  = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if (!empty($adv_arr)) {
                    $adv_str = implode(',', $adv_arr);
                    $where  .= " AND deal.advisory_id IN ({$adv_str}) ";
                } else {
                    $where  .= " AND deal.advisory_id = -1 ";
                }
            }
            // 校验受让人ID
            if (!empty($_POST['t_user_id'])) {
                $t_user_id = intval($_POST['t_user_id']);
                $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if ($debt_id_res) {
                    $debt_id_res_str = implode(',' , $debt_id_res);
                    $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_POST['t_mobile'])) {
                $t_mobile = trim($_POST['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT user_id FROM offline_user_platform WHERE phone = '{$t_mobile}'";
                $t_user_id = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
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
            // 校验后台用户是否是融资经办机构
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if(!empty($adminUserInfo['username'])){
                if($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin){
                    if($adminUserInfo['user_type'] == 2){
                        $deallist = Yii::app()->offlinedb->createCommand("SELECT DISTINCT offline_deal.id AS deal_id FROM offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' AND offline_deal_agency.is_effect = 1")->queryColumn();
                        if(!empty($deallist)){
                            $dealIds = implode(',' , $deallist);
                            $where  .= " AND deal.id IN ({$dealIds}) ";
                        }else{
                            $where  .= " AND deal.id = -1 ";
                        }
                    }
                }
            }
            // 查询数据总量
            if ($condition) {
                $redis_time = 86400;
                $redis_key  = 'Offline_Debt_List_Count_'.$platform_id.'_Condition_'.$condition['id'];
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
                                    FROM offline_debt AS debt 
                                    LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                                    LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where_con} ";
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
                        $where .= " AND debt.id = -1 ";
                    }
                }
            } else {
                $sql = "SELECT count(debt.id) AS count 
                        FROM offline_debt AS debt 
                        LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                        LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where} ";
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
            $sql = "SELECT debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name, debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , user.real_name, user.phone AS mobile
                    FROM offline_debt AS debt 
                    LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                    LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where} ORDER BY debt.id DESC ";
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

            $debt_id_arr = array();
            foreach ($list as $key => $value){
                $value['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                $value['mobile']  = $this->strEncrypt($value['mobile'] , 3 , 4);
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
                $value['debt_src']        = $debt_src[$value['debt_src']];
                $value['deal_type']       = $platform_id;
                
                $listInfo[] = $value;

                $debt_id_arr[] = $value['id'];
            }
            $debt_tender = array();
            if ($debt_id_arr) {
                $debt_id_str = implode(',' , $debt_id_arr);
                $sql = "SELECT dt.debt_id , u.id , u.real_name , u.phone AS mobile , dt.new_tender_id , dt.status FROM offline_debt_tender AS dt INNER JOIN offline_user_platform AS u ON dt.user_id = u.id AND dt.debt_id IN ({$debt_id_str}) ";
                $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                if ($tender_res) {
                    foreach ($tender_res as $key => $value) {
                        $debt_tender[$value['debt_id']] = $value;
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

                foreach ($listInfo as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['id'];
                        $listInfo[$key]['t_real_name']  = $debt_tender[$value['id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'] , Yii::app()->c->idno_key); // 手机号解密
                        $listInfo[$key]['t_mobile']     = $this->strEncrypt($listInfo[$key]['t_mobile'] , 3 , 4);
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
        if (!empty($authList) && strstr($authList,'/user/XFDebt/DebtListExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('DebtList', array('daochu_status' => $daochu_status));
    }

    /**
     * 工场微金有解化债数据批量条件上传
     */
    public function actionaddDebtListCondition()
    {
        set_time_limit(0);
        if (in_array($_GET['download'] , array(1 , 2 , 3))) {
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
                $name = '有解化债数据查询 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资经办机构名称');
                $name = '有解化债数据查询 通过上传融资经办机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
                $name = '有解化债数据查询 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
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
                $user_id_data = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
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
                    $sql = "SELECT id FROM offline_debt WHERE user_id IN ({$user_id_str})";
                    $debt = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if (!$debt) {
                        $debt = array();
                    }
                    $data_json = json_encode($debt);
                } else {
                    $data_json = json_encode(array());
                }

            } else if ($type == 2) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行' , 5);
                }
                $name .= ' 通过上传融资经办机构名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id , name FROM offline_deal_agency WHERE name IN ({$user_id_str}) ";
                $deal_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
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
                $sql = "SELECT id FROM offline_deal WHERE advisory_id IN ({$user_id_str})";
                $deal_id = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if ($deal_id) {

                    $deal_id_str = implode(',' , $deal_id);
                    $sql = "SELECT id FROM offline_debt WHERE borrow_id IN ({$deal_id_str})";
                    $debt = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
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
                $sql = "SELECT id , name FROM offline_deal WHERE name IN ({$user_id_str}) ";
                $deal_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
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
                $sql = "SELECT id FROM offline_debt WHERE borrow_id IN ({$user_id_str})";
                $debt = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if (!$debt) {
                    $debt = array();
                }
                $data_json = json_encode($debt);

            }
            
            $sql = "INSERT INTO xf_debt_list_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform['id']} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
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
     * 工场微金有解化债数据导出
     */
    public function actionDebtListExcel()
    {
        set_time_limit(0);
        // 条件筛选
        if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 3) {
            echo '<h1>请正确输入所属平台</h1>';exit;
        }
        $sql = "SELECT * FROM offline_platform WHERE id = '{$_GET['deal_type']}' ";
        $platform = Yii::app()->offlinedb->createCommand($sql)->queryRow();
        if (!$platform) {
            echo '<h1>所属平台输入错误</h1>';exit;
        }
        $platform_id = $platform['id'];
        if (!empty($_GET['condition_id'])) {
            $sql = "SELECT * FROM xf_debt_list_condition WHERE id = '{$_GET['condition_id']}' ";
            $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($condition) {
                $con_data = json_decode($condition['data_json'] , true);
                $platform_id = $condition['platform'];
                $redis_key   = 'Offline_Debt_List_Download_'.$platform_id.'_Condition_'.$condition['id'];
                $check = Yii::app()->rcache->get($redis_key);
                if($check){
                    echo '<h1>此下载地址已失效</h1>';
                    exit;
                }
            }
        }
        $where = " WHERE debt.platform_id = {$platform_id} AND deal.is_effect = 1 AND deal.is_delete = 0 ";
        if ($_GET['user_id']=='' && $_GET['serial_number']=='' && $_GET['borrow_id']=='' && $_GET['tender_id']=='' && $_GET['status']=='' && $_GET['name']=='' && $_GET['mobile']=='' && $_GET['debt_src']=='' && $_GET['company']=='' && $_GET['advisory']=='' && $_GET['t_user_id']=='' && $_GET['t_mobile'] && $_GET['start']=='' && $_GET['end']=='' && $_GET['condition_id']=='') {
            echo '<h1>请输入至少一个查询条件</h1>';exit;
        }
        // 校验转让人ID
        if (!empty($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            $where  .= " AND debt.user_id = {$user_id} ";
        }
        // 校验债转编号
        if (!empty($_GET['serial_number'])) {
            $serial_number = trim($_GET['serial_number']);
            $where        .= " AND debt.serial_number = '{$serial_number}'";
        }
        // 校验借款编号
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
            $sta = intval($_GET['status']);
            $where .= " AND debt.status = {$sta} ";
        }
        // 校验借款标题
        if (!empty($_GET['name'])) {
            $name   = trim($_GET['name']);
            $where .= " AND deal.name = '{$name}' ";
        }
        // 校验转让人手机号
        if (!empty($_GET['mobile'])) {
            $mobile = trim($_GET['mobile']);
            $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
            $where .= " AND user.phone = '{$mobile}' ";
        }
        // 校验债转类型
        if (!empty($_GET['debt_src'])) {
            $d_src  = intval($_GET['debt_src']);
            $where .= " AND debt.debt_src = {$d_src} ";
        }
        // 校验融资方名称
        if (!empty($_GET['company'])) {
            $company = trim($_GET['company']);
            $sql     = "SELECT user_id FROM firstp2p_enterprise WHERE company_name = '{$company}' ";
            $com_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
            if (!empty($com_arr)) {
                $com_str = implode(',', $com_arr);
                $where  .= " AND deal.user_id IN ({$com_str}) ";
            } else {
                $sql         = "SELECT user_id FROM offline_user_platform WHERE real_name = '{$company}' ";
                $user_id_arr =  Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if ($user_id_arr) {
                    $user_id_str = implode(',' , $user_id_arr);
                    $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                } else {
                    $where      .= " AND deal.user_id = -1 ";
                }
            }
        }
        // 校验融资经办机构
        if (!empty($_GET['advisory'])) {
            $advisory = trim($_GET['advisory']);
            $sql      = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory}' AND is_effect = 1 ";
            $adv_arr  = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
            if (!empty($adv_arr)) {
                $adv_str = implode(',', $adv_arr);
                $where  .= " AND deal.advisory_id IN ({$adv_str}) ";
            } else {
                $where  .= " AND deal.advisory_id = -1 ";
            }
        }
        // 校验受让人ID
        if (!empty($_GET['t_user_id'])) {
            $t_user_id = intval($_GET['t_user_id']);
            $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
            $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
            if ($debt_id_res) {
                $debt_id_res_str = implode(',' , $debt_id_res);
                $where .= " AND debt.id IN ({$debt_id_res_str}) ";
            } else {
                $where .= " AND debt.id = -1 ";
            }
        }
        // 校验受让人手机号
        if (!empty($_GET['t_mobile'])) {
            $t_mobile = trim($_GET['t_mobile']);
            $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
            $sql = "SELECT user_id FROM offline_user_platform WHERE phone = '{$t_mobile}'";
            $t_user_id = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
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
        // 校验后台用户是否是融资经办机构
        $adminUserInfo  = \Yii::app()->user->getState('_user');
        if(!empty($adminUserInfo['username'])){
            if($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin){
                if($adminUserInfo['user_type'] == 2){
                    $deallist = Yii::app()->offlinedb->createCommand("SELECT DISTINCT offline_deal.id AS deal_id FROM offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' AND offline_deal_agency.is_effect = 1")->queryColumn();
                    if(!empty($deallist)){
                        $dealIds = implode(',' , $deallist);
                        $where  .= " AND deal.id IN ({$dealIds}) ";
                    }else{
                        $where  .= " AND deal.id = -1 ";
                    }
                }
            }
        }
        // 查询数据总量
        if ($condition) {
            $redis_time = 86400;
            $redis_key  = 'Offline_Debt_List_Count_'.$platform_id.'_Condition_'.$condition['id'];
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
                                FROM offline_debt AS debt 
                                LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                                LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                                LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where_con} ";
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
                    $where .= " AND debt.id = -1 ";
                }
            }
        } else {
            $sql = "SELECT count(debt.id) AS count 
                    FROM offline_debt AS debt 
                    LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                    LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where} ";
            $count = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
        }
        if ($count == 0) {
            echo '<h1>暂无数据</h1>';
            exit;
        }
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
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            // 查询数据
            $sql = "SELECT debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name, debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , user.real_name, user.phone AS mobile
                    FROM offline_debt AS debt 
                    LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                    LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where} ORDER BY debt.user_id ASC , debt.addtime DESC LIMIT {$pass} , 500 ";
            $list = Yii::app()->offlinedb->createCommand($sql)->queryAll();

            $debt_id_arr = array();
            foreach ($list as $key => $value){
                $value['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                if ($value['successtime'] != 0) {
                    $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                } else {
                    $value['successtime'] = '——';
                }
                $value['status']          = $status[$value['status']];
                $value['debt_src']        = $debt_src[$value['debt_src']];
                $value['deal_type']       = $platform_id;
                
                $listInfo[] = $value;

                $debt_id_arr[] = $value['id'];
            }
            $debt_tender = array();
            if ($debt_id_arr) {
                $debt_id_str = implode(',' , $debt_id_arr);
                $sql = "SELECT dt.debt_id , u.id , u.real_name , u.phone AS mobile , dt.new_tender_id , dt.status FROM offline_debt_tender AS dt INNER JOIN offline_user_platform AS u ON dt.user_id = u.id AND dt.debt_id IN ({$debt_id_str}) ";
                $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                if ($tender_res) {
                    foreach ($tender_res as $key => $value) {
                        $debt_tender[$value['debt_id']] = $value;
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

                foreach ($listInfo as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['id'];
                        $listInfo[$key]['t_real_name']  = $debt_tender[$value['id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'] , Yii::app()->c->idno_key); // 手机号解密
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
        }

        $name = $platform['name'].'有解化债数据查询 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "债转ID,债转编号,转让人ID,转让人姓名,转让人手机号,借款编号,借款标题,投资记录ID,投资金额,发起债转金额,已转出金额,折扣,转让状态,债转类型,债转合同编号,受让人ID,受让人姓名,受让人手机号,发起时间,转让完成时间\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['serial_number']},{$value['user_id']},{$value['real_name']},{$value['mobile']},{$value['borrow_id']},{$value['name']},{$value['tender_id']},{$value['deal_load_money']},{$value['money']},{$value['sold_money']},{$value['discount']},{$value['status']},{$value['debt_src']},{$value['contract_number']},{$value['t_user_id']},{$value['t_real_name']},{$value['t_mobile']},{$value['addtime']},{$value['successtime']}\n";
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
            $redis_key = 'Offline_Debt_List_Download_'.$platform_id.'_Condition_'.$condition['id'];
            $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
            if(!$set){
                Yii::log("{$redis_key} redis download set error","error");
            }
        }
    }

    /**
     * 智多新有解化债数据查询
     */
    public function actionZDXDebtList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 4) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = '请正确输入所属平台';
                echo exit(json_encode($result_data));
            }
            $platform_id = intval($_POST['deal_type']);
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
                    $platform_id = $condition['platform'];
                }
            }
            $where = " WHERE debt.platform_id = {$platform_id} AND deal.is_effect = 1 AND deal.is_delete = 0 ";
            // 校验转让人ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_POST['serial_number'])) {
                $serial_number = trim($_POST['serial_number']);
                $where        .= " AND debt.serial_number = '{$serial_number}'";
            }
            // 校验借款编号
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
                $sta = intval($_POST['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验借款标题
            if (!empty($_POST['name'])) {
                $name   = trim($_POST['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验转让人手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.phone = '{$mobile}' ";
            }
            // 校验债转类型
            if (!empty($_POST['debt_src'])) {
                $d_src  = intval($_POST['debt_src']);
                $where .= " AND debt.debt_src = {$d_src} ";
            }
            // 校验融资方名称
            if (!empty($_POST['company'])) {
                $company = trim($_POST['company']);
                $sql     = "SELECT user_id FROM firstp2p_enterprise WHERE company_name = '{$company}' ";
                $com_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where  .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $sql         = "SELECT user_id FROM offline_user_platform WHERE real_name = '{$company}' ";
                    $user_id_arr =  Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if ($user_id_arr) {
                        $user_id_str = implode(',' , $user_id_arr);
                        $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                    } else {
                        $where      .= " AND deal.user_id = -1 ";
                    }
                }
            }
            // 校验融资经办机构
            if (!empty($_POST['advisory'])) {
                $advisory = trim($_POST['advisory']);
                $sql      = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory}' AND is_effect = 1 ";
                $adv_arr  = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if (!empty($adv_arr)) {
                    $adv_str = implode(',', $adv_arr);
                    $where  .= " AND deal.advisory_id IN ({$adv_str}) ";
                } else {
                    $where  .= " AND deal.advisory_id = -1 ";
                }
            }
            // 校验受让人ID
            if (!empty($_POST['t_user_id'])) {
                $t_user_id = intval($_POST['t_user_id']);
                $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if ($debt_id_res) {
                    $debt_id_res_str = implode(',' , $debt_id_res);
                    $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_POST['t_mobile'])) {
                $t_mobile = trim($_POST['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT user_id FROM offline_user_platform WHERE phone = '{$t_mobile}'";
                $t_user_id = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
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
            // 校验后台用户是否是融资经办机构
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if(!empty($adminUserInfo['username'])){
                if($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin){
                    if($adminUserInfo['user_type'] == 2){
                        $deallist = Yii::app()->offlinedb->createCommand("SELECT DISTINCT offline_deal.id AS deal_id FROM offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' AND offline_deal_agency.is_effect = 1")->queryColumn();
                        if(!empty($deallist)){
                            $dealIds = implode(',' , $deallist);
                            $where  .= " AND deal.id IN ({$dealIds}) ";
                        }else{
                            $where  .= " AND deal.id = -1 ";
                        }
                    }
                }
            }
            // 查询数据总量
            if ($condition) {
                $redis_time = 86400;
                $redis_key  = 'Offline_Debt_List_Count_'.$platform_id.'_Condition_'.$condition['id'];
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
                                    FROM offline_debt AS debt 
                                    LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                                    LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where_con} ";
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
                        $where .= " AND debt.id = -1 ";
                    }
                }
            } else {
                $sql = "SELECT count(debt.id) AS count 
                        FROM offline_debt AS debt 
                        LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                        LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where} ";
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
            $sql = "SELECT debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name, debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , user.real_name, user.phone AS mobile
                    FROM offline_debt AS debt 
                    LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                    LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where} ORDER BY debt.id DESC ";
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

            $debt_id_arr = array();
            foreach ($list as $key => $value){
                $value['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                $value['mobile']  = $this->strEncrypt($value['mobile'] , 3 , 4);
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
                $value['debt_src']        = $debt_src[$value['debt_src']];
                $value['deal_type']       = $platform_id;
                
                $listInfo[] = $value;

                $debt_id_arr[] = $value['id'];
            }
            $debt_tender = array();
            if ($debt_id_arr) {
                $debt_id_str = implode(',' , $debt_id_arr);
                $sql = "SELECT dt.debt_id , u.id , u.real_name , u.phone AS mobile , dt.new_tender_id , dt.status FROM offline_debt_tender AS dt INNER JOIN offline_user_platform AS u ON dt.user_id = u.id AND dt.debt_id IN ({$debt_id_str}) ";
                $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                if ($tender_res) {
                    foreach ($tender_res as $key => $value) {
                        $debt_tender[$value['debt_id']] = $value;
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

                foreach ($listInfo as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['id'];
                        $listInfo[$key]['t_real_name']  = $debt_tender[$value['id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'] , Yii::app()->c->idno_key); // 手机号解密
                        $listInfo[$key]['t_mobile']     = $this->strEncrypt($listInfo[$key]['t_mobile'] , 3 , 4);
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
        if (!empty($authList) && strstr($authList,'/user/XFDebt/ZDXDebtListExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('ZDXDebtList', array('daochu_status' => $daochu_status));
    }

    /**
     * 智多新有解化债数据导出
     */
    public function actionZDXDebtListExcel()
    {
        set_time_limit(0);
        // 条件筛选
        if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 4) {
            echo '<h1>请正确输入所属平台</h1>';exit;
        }
        $sql = "SELECT * FROM offline_platform WHERE id = '{$_GET['deal_type']}' ";
        $platform = Yii::app()->offlinedb->createCommand($sql)->queryRow();
        if (!$platform) {
            echo '<h1>所属平台输入错误</h1>';exit;
        }
        $platform_id = $platform['id'];
        if (!empty($_GET['condition_id'])) {
            $sql = "SELECT * FROM xf_debt_list_condition WHERE id = '{$_GET['condition_id']}' ";
            $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($condition) {
                $con_data = json_decode($condition['data_json'] , true);
                $platform_id = $condition['platform'];
                $redis_key   = 'Offline_Debt_List_Download_'.$platform_id.'_Condition_'.$condition['id'];
                $check = Yii::app()->rcache->get($redis_key);
                if($check){
                    echo '<h1>此下载地址已失效</h1>';
                    exit;
                }
            }
        }
        $where = " WHERE debt.platform_id = {$platform_id} AND deal.is_effect = 1 AND deal.is_delete = 0 ";
        if ($_GET['user_id']=='' && $_GET['serial_number']=='' && $_GET['borrow_id']=='' && $_GET['tender_id']=='' && $_GET['status']=='' && $_GET['name']=='' && $_GET['mobile']=='' && $_GET['debt_src']=='' && $_GET['company']=='' && $_GET['advisory']=='' && $_GET['t_user_id']=='' && $_GET['t_mobile'] && $_GET['start']=='' && $_GET['end']=='' && $_GET['condition_id']=='') {
            echo '<h1>请输入至少一个查询条件</h1>';exit;
        }
        // 校验转让人ID
        if (!empty($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            $where  .= " AND debt.user_id = {$user_id} ";
        }
        // 校验债转编号
        if (!empty($_GET['serial_number'])) {
            $serial_number = trim($_GET['serial_number']);
            $where        .= " AND debt.serial_number = '{$serial_number}'";
        }
        // 校验借款编号
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
            $sta = intval($_GET['status']);
            $where .= " AND debt.status = {$sta} ";
        }
        // 校验借款标题
        if (!empty($_GET['name'])) {
            $name   = trim($_GET['name']);
            $where .= " AND deal.name = '{$name}' ";
        }
        // 校验转让人手机号
        if (!empty($_GET['mobile'])) {
            $mobile = trim($_GET['mobile']);
            $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
            $where .= " AND user.phone = '{$mobile}' ";
        }
        // 校验债转类型
        if (!empty($_GET['debt_src'])) {
            $d_src  = intval($_GET['debt_src']);
            $where .= " AND debt.debt_src = {$d_src} ";
        }
        // 校验融资方名称
        if (!empty($_GET['company'])) {
            $company = trim($_GET['company']);
            $sql     = "SELECT user_id FROM firstp2p_enterprise WHERE company_name = '{$company}' ";
            $com_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
            if (!empty($com_arr)) {
                $com_str = implode(',', $com_arr);
                $where  .= " AND deal.user_id IN ({$com_str}) ";
            } else {
                $sql         = "SELECT user_id FROM offline_user_platform WHERE real_name = '{$company}' ";
                $user_id_arr =  Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if ($user_id_arr) {
                    $user_id_str = implode(',' , $user_id_arr);
                    $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                } else {
                    $where      .= " AND deal.user_id = -1 ";
                }
            }
        }
        // 校验融资经办机构
        if (!empty($_GET['advisory'])) {
            $advisory = trim($_GET['advisory']);
            $sql      = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory}' AND is_effect = 1 ";
            $adv_arr  = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
            if (!empty($adv_arr)) {
                $adv_str = implode(',', $adv_arr);
                $where  .= " AND deal.advisory_id IN ({$adv_str}) ";
            } else {
                $where  .= " AND deal.advisory_id = -1 ";
            }
        }
        // 校验受让人ID
        if (!empty($_GET['t_user_id'])) {
            $t_user_id = intval($_GET['t_user_id']);
            $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
            $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
            if ($debt_id_res) {
                $debt_id_res_str = implode(',' , $debt_id_res);
                $where .= " AND debt.id IN ({$debt_id_res_str}) ";
            } else {
                $where .= " AND debt.id = -1 ";
            }
        }
        // 校验受让人手机号
        if (!empty($_GET['t_mobile'])) {
            $t_mobile = trim($_GET['t_mobile']);
            $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
            $sql = "SELECT user_id FROM offline_user_platform WHERE phone = '{$t_mobile}'";
            $t_user_id = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
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
        // 校验后台用户是否是融资经办机构
        $adminUserInfo  = \Yii::app()->user->getState('_user');
        if(!empty($adminUserInfo['username'])){
            if($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin){
                if($adminUserInfo['user_type'] == 2){
                    $deallist = Yii::app()->offlinedb->createCommand("SELECT DISTINCT offline_deal.id AS deal_id FROM offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' AND offline_deal_agency.is_effect = 1")->queryColumn();
                    if(!empty($deallist)){
                        $dealIds = implode(',' , $deallist);
                        $where  .= " AND deal.id IN ({$dealIds}) ";
                    }else{
                        $where  .= " AND deal.id = -1 ";
                    }
                }
            }
        }
        // 查询数据总量
        if ($condition) {
            $redis_time = 86400;
            $redis_key  = 'Offline_Debt_List_Count_'.$platform_id.'_Condition_'.$condition['id'];
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
                                FROM offline_debt AS debt 
                                LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                                LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                                LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where_con} ";
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
                    $where .= " AND debt.id = -1 ";
                }
            }
        } else {
            $sql = "SELECT count(debt.id) AS count 
                    FROM offline_debt AS debt 
                    LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                    LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where} ";
            $count = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
        }
        if ($count == 0) {
            echo '<h1>暂无数据</h1>';
            exit;
        }
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
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            // 查询数据
            $sql = "SELECT debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name, debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , user.real_name, user.phone AS mobile
                    FROM offline_debt AS debt 
                    LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id 
                    LEFT JOIN offline_user_platform AS user ON debt.user_id = user.user_id {$where} ORDER BY debt.user_id ASC , debt.addtime DESC LIMIT {$pass} , 500 ";
            $list = Yii::app()->offlinedb->createCommand($sql)->queryAll();

            $debt_id_arr = array();
            foreach ($list as $key => $value){
                $value['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                if ($value['successtime'] != 0) {
                    $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                } else {
                    $value['successtime'] = '——';
                }
                $value['status']          = $status[$value['status']];
                $value['debt_src']        = $debt_src[$value['debt_src']];
                $value['deal_type']       = $platform_id;
                
                $listInfo[] = $value;

                $debt_id_arr[] = $value['id'];
            }
            $debt_tender = array();
            if ($debt_id_arr) {
                $debt_id_str = implode(',' , $debt_id_arr);
                $sql = "SELECT dt.debt_id , u.id , u.real_name , u.phone AS mobile , dt.new_tender_id , dt.status FROM offline_debt_tender AS dt INNER JOIN offline_user_platform AS u ON dt.user_id = u.id AND dt.debt_id IN ({$debt_id_str}) ";
                $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                if ($tender_res) {
                    foreach ($tender_res as $key => $value) {
                        $debt_tender[$value['debt_id']] = $value;
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

                foreach ($listInfo as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['id'];
                        $listInfo[$key]['t_real_name']  = $debt_tender[$value['id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'] , Yii::app()->c->idno_key); // 手机号解密
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
        }

        $name = $platform['name'].'有解化债数据查询 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "债转ID,债转编号,转让人ID,转让人姓名,转让人手机号,借款编号,借款标题,投资记录ID,投资金额,发起债转金额,已转出金额,折扣,转让状态,债转类型,债转合同编号,受让人ID,受让人姓名,受让人手机号,发起时间,转让完成时间\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['serial_number']},{$value['user_id']},{$value['real_name']},{$value['mobile']},{$value['borrow_id']},{$value['name']},{$value['tender_id']},{$value['deal_load_money']},{$value['money']},{$value['sold_money']},{$value['discount']},{$value['status']},{$value['debt_src']},{$value['contract_number']},{$value['t_user_id']},{$value['t_real_name']},{$value['t_mobile']},{$value['addtime']},{$value['successtime']}\n";
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
            $redis_key = 'Offline_Debt_List_Download_'.$platform_id.'_Condition_'.$condition['id'];
            $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
            if(!$set){
                Yii::log("{$redis_key} redis download set error","error");
            }
        }
    }

    /**
     * 工场微金 在途投资明细 列表
     */
    public function actionLoanList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 3) {
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
            $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
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
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory_name}' ";
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
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND deal.deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND deal.deal_agency_id = -1 ";
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
                                LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where_con} ";
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
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where} ";
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
            $sql = "SELECT deal_load.id , deal_load.money , deal_load.wait_capital , deal_load.user_id , deal_load.deal_id , deal_load.create_time , deal_load.wait_interest , deal_load.yes_interest , deal.deal_name , deal.jys_record_number , deal.deal_agency_id , deal.deal_user_id , deal.deal_repay_start_time , deal.deal_advisory_name , deal.deal_agency_name , deal.project_product_class , deal.project_name , deal.deal_loantype , deal.deal_repay_time , deal.deal_rate , deal.deal_user_real_name , MAX(deal.loan_repay_time) AS max_repay_time , deal_load.black_status 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where} GROUP BY deal_load.id ORDER BY deal_load.id DESC ";
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
            if (!empty($authList) && strstr($authList,'/user/XFDebt/GCBlackEditAdd') || empty($authList)) {
                $edit_status = 1;
            }
            $loantype = Yii::app()->c->xf_config['loantype'];
            foreach ($list as $key => $value) {
                $value['deal_type']      = $platform_id;
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
        if (!empty($authList) && strstr($authList,'/user/XFDebt/LoanList2Excel') || empty($authList)) {
            $LoanList2Excel = 1;
        }
        return $this->renderPartial('LoanList', array('LoanList2Excel' => $LoanList2Excel));
    }

    /**
     * 工场微金 在途投资明细 列表 批量条件上传
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
                $name = '在途投资明细 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款编号');
                $name = '在途投资明细 通过上传借款编号查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '借款标题');
                $name = '在途投资明细 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 4) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '项目名称');
                $name = '在途投资明细 通过上传项目名称查询 '.date("Y年m月d日 H时i分s秒" , time());
            } else if ($_GET['download'] == 5) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '交易所备案编号');
                $name = '在途投资明细 通过上传交易所备案编号查询 '.date("Y年m月d日 H时i分s秒" , time());
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

            } else if ($type == 4) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行' , 5);
                }
                $name .= ' 通过上传项目名称查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT id , name FROM offline_deal_project WHERE name IN ({$user_id_str}) ";
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
                        FROM offline_deal_load AS deal_load 
                        INNER JOIN offline_deal AS deal ON deal_load.deal_id = deal.id 
                        INNER JOIN offline_deal_project AS project ON project.id = deal.project_id 
                        WHERE project.id IN ({$user_id_str}) AND deal.deal_status = 4 AND (deal_load.wait_capital > 0 OR deal_load.wait_interest > 0) ";
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
                $sql = "SELECT id , jys_record_number FROM offline_deal WHERE jys_record_number IN ({$user_id_str}) AND deal_status = 4";
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
     * 工场微金 在途投资明细 列表 导出
     */
    public function actionLoanList2Excel()
    {
        if (!empty($_GET)) {
            set_time_limit(0);
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 3) {
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
            $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
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
            // 校验项目ID
            if (!empty($_GET['project_id'])) {
                $project_id = trim($_GET['project_id']);
                $where     .= " AND deal.project_id = '{$project_id}' ";
            }
            // 校验项目名称
            if (!empty($_GET['project_name'])) {
                $project_name = trim($_GET['project_name']);
                $where       .= " AND deal.project_name= '{$project_name}' ";
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
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory_name}' ";
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
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND deal.deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND deal.deal_agency_id = -1 ";
                }
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
                                LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where_con} ";
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
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where} ";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $loantype = Yii::app()->c->xf_config['loantype'];
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT deal_load.id , deal_load.money , deal_load.wait_capital , deal_load.user_id , deal_load.deal_id , deal_load.create_time , deal_load.wait_interest , deal_load.yes_interest , deal.deal_name , deal.jys_record_number , deal.deal_agency_id , deal.deal_user_id , deal.deal_repay_start_time , deal.deal_advisory_name , deal.deal_agency_name , deal.project_product_class , deal.project_name , deal.deal_loantype , deal.deal_repay_time , deal.deal_rate , deal.deal_user_real_name , MAX(deal.loan_repay_time) AS max_repay_time 
                        FROM offline_deal_load AS deal_load 
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                        {$where} GROUP BY deal_load.id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();

                foreach ($list as $key => $value) {
                    $value['create_time']    = date('Y-m-d H:i:s', $value['create_time']);
                    $value['max_repay_time'] = date('Y-m-d', ($value['max_repay_time'] + 28800));
                    if ($value['deal_loantype'] == 5) {
                        $value['deal_repay_time'] .= '天';
                    } else {
                        $value['deal_repay_time'] .= '个月';
                    }
                    $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                    $value['deal_repay_start_time'] = date('Y-m-d' , ($value['deal_repay_start_time'] + 28800));
                    $value['real_name']             = '';

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
            $data  = "投资记录ID,借款编号,借款标题,项目名称,交易所备案编号,用户ID,用户姓名,投资时间,投资金额,产品大类,借款期限,还款方式,计划最大回款时间,年化收益率,计息时间,剩余待还本金,剩余待还利息,融资方ID,融资方名称,融资经办机构,融资担保机构\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['deal_id']},{$value['deal_name']},{$value['project_name']},{$value['jys_record_number']},{$value['user_id']},{$value['real_name']},{$value['create_time']},{$value['money']},{$value['project_product_class']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['max_repay_time']},{$value['deal_rate']},{$value['deal_repay_start_time']},{$value['wait_capital']},{$value['wait_interest']},{$value['deal_user_id']},{$value['deal_user_real_name']},{$value['deal_advisory_name']},{$value['deal_agency_name']}\n";
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
     * 智多新 在途投资明细 列表
     */
    public function actionZDXLoanList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 4) {
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
            $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
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
            // 搜索冻结状态
            if (!empty($_POST['xf_status']) && in_array($_POST['xf_status'], [2,1])) {
                $xf_status = trim($_POST['xf_status']);
                $xf_status = ($xf_status == 2) ? 0 : $xf_status;
                $where  .= " AND deal_load.xf_status = $xf_status ";
            }
            // 校验融资方名称
            if (!empty($_POST['company'])) {
                $company = trim($_POST['company']);
                $where  .= " AND deal.deal_user_real_name = '{$company}' ";
            }
            // 检验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory_name}' ";
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
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND deal.deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND deal.deal_agency_id = -1 ";
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
                                LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where_con} ";
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
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where} ";
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
            $sql = "SELECT deal_load.xf_status ,deal_load.id , deal_load.money , deal_load.wait_capital , deal_load.user_id , deal_load.deal_id , deal_load.create_time , deal_load.wait_interest , deal_load.yes_interest , deal.deal_name , deal.jys_record_number , deal.deal_agency_id , deal.deal_user_id , deal.deal_repay_start_time , deal.deal_advisory_name , deal.deal_agency_name , deal.project_product_class , deal.project_name , deal.deal_loantype , deal.deal_repay_time , deal.deal_rate , deal.deal_user_real_name , MAX(deal.loan_repay_time) AS max_repay_time , deal_load.black_status 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where} GROUP BY deal_load.id ORDER BY deal_load.id DESC ";
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
            if (!empty($authList) && strstr($authList,'/user/XFDebt/ZDXBlackEditAdd') || empty($authList)) {
                $edit_status = 1;
            }
            $loantype = Yii::app()->c->xf_config['loantype'];
            $xf_status   = array(0 => '未冻结' , 1 => '冻结中');
            foreach ($list as $key => $value) {
                $value['deal_type']      = $platform_id;
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
                $value['xf_status']             = $xf_status[$value['xf_status']];


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
        if (!empty($authList) && strstr($authList,'/user/XFDebt/ZDXLoanList2Excel') || empty($authList)) {
            $LoanList2Excel = 1;
        }
        return $this->renderPartial('ZDXLoanList', array('LoanList2Excel' => $LoanList2Excel));
    }

    /**
     * 智多新 在途投资明细 列表 导出
     */
    public function actionZDXLoanList2Excel()
    {
        if (!empty($_GET)) {
            set_time_limit(0);
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 4) {
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
            $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
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
            // 搜索冻结状态
            if (!empty($_GET['xf_status']) && in_array($_GET['xf_status'], [2,1])) {
                $xf_status = trim($_GET['xf_status']);
                $xf_status = ($xf_status == 2) ? 0 : $xf_status;
                $where  .= " AND deal_load.xf_status = $xf_status ";
            }
            // 校验项目ID
            if (!empty($_GET['project_id'])) {
                $project_id = trim($_GET['project_id']);
                $where     .= " AND deal.project_id = '{$project_id}' ";
            }
            // 校验项目名称
            if (!empty($_GET['project_name'])) {
                $project_name = trim($_GET['project_name']);
                $where       .= " AND deal.project_name= '{$project_name}' ";
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
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$advisory_name}' ";
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
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND deal.deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND deal.deal_agency_id = -1 ";
                }
            }
            if ($_GET['deal_load_id']      == '' &&
                $_GET['user_id']           == '' &&
                $_GET['xf_status']           == '' &&
                $_GET['deal_id']           == '' &&
                $_GET['name']              == '' &&
                $_GET['project_id']        == '' &&
                $_GET['project_name']      == '' &&
                $_GET['jys_record_number'] == '' &&
                $_GET['company']           == '' &&
                $_GET['advisory_name']     == '' &&
                $_GET['agency_name']       == '' &&
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
                                LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where_con} ";
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
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id {$where} ";
                $count = $model->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $loantype = Yii::app()->c->xf_config['loantype'];
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT deal_load.xf_status ,deal_load.id , deal_load.money , deal_load.wait_capital , deal_load.user_id , deal_load.deal_id , deal_load.create_time , deal_load.wait_interest , deal_load.yes_interest , deal.deal_name , deal.jys_record_number , deal.deal_agency_id , deal.deal_user_id , deal.deal_repay_start_time , deal.deal_advisory_name , deal.deal_agency_name , deal.project_product_class , deal.project_name , deal.deal_loantype , deal.deal_repay_time , deal.deal_rate , deal.deal_user_real_name , MAX(deal.loan_repay_time) AS max_repay_time 
                        FROM offline_deal_load AS deal_load 
                        LEFT JOIN offline_stat_repay AS deal ON deal_load.deal_id = deal.deal_id 
                        {$where} GROUP BY deal_load.id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();
                $xf_status   = array(0 => '未冻结' , 1 => '冻结中');
                foreach ($list as $key => $value) {
                    $value['create_time']    = date('Y-m-d H:i:s', $value['create_time']);
                    $value['max_repay_time'] = date('Y-m-d', ($value['max_repay_time'] + 28800));
                    if ($value['deal_loantype'] == 5) {
                        $value['deal_repay_time'] .= '天';
                    } else {
                        $value['deal_repay_time'] .= '个月';
                    }
                    $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                    $value['deal_repay_start_time'] = date('Y-m-d' , ($value['deal_repay_start_time'] + 28800));
                    $value['real_name']             = '';
                    $value['xf_status']             = $xf_status[$value['xf_status']];

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
            $data  = "投资记录ID,借款编号,借款标题,项目名称,交易所备案编号,用户ID,用户姓名,投资时间,投资金额,产品大类,借款期限,还款方式,计划最大回款时间,年化收益率,计息时间,剩余待还本金,剩余待还利息,融资方ID,融资方名称,融资经办机构,融资担保机构,冻结状态\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['deal_id']},{$value['deal_name']},{$value['project_name']},{$value['jys_record_number']},{$value['user_id']},{$value['real_name']},{$value['create_time']},{$value['money']},{$value['project_product_class']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['max_repay_time']},{$value['deal_rate']},{$value['deal_repay_start_time']},{$value['wait_capital']},{$value['wait_interest']},{$value['deal_user_id']},{$value['deal_user_real_name']},{$value['deal_advisory_name']},{$value['deal_agency_name']},{$value['xf_status']}\n";
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
     * 工场微金 在途项目明细 列表
     */
    public function actionDealLoadBYDeal()
    {
        if (!empty($_POST)) {
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 3) {
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
            // 校验融资方名称
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
            $sql = "SELECT deal_id , deal_name , project_id , project_name , jys_record_number , project_product_class , deal_loantype , deal_repay_time , deal_user_id , deal_user_real_name , deal_advisory_id , deal_advisory_name , deal_agency_id , deal_agency_name , jys_name , MAX(loan_repay_time) AS max_repay_time , MIN(loan_repay_time) AS min_repay_time , SUM(CASE WHEN repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_capital , SUM(CASE WHEN repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_interest , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_capital , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_interest FROM offline_stat_repay {$where} GROUP BY deal_id ORDER BY deal_id DESC ";
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
        if (!empty($authList) && strstr($authList,'/user/XFDebt/DealLoadBYDeal2Excel') || empty($authList)) {
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
        if (in_array($_GET['download'] , array(1 , 2 , 3))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1' , '融资方名称');
                $name = '在途项目明细 通过上传融资方名称查询 '.date("Y年m月d日 H时i分s秒" , time());
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

                $name .= ' 通过上传融资方名称查询 '.date("Y年m月d日 H时i分s秒" , time());
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
     * 工场微金 在途项目明细 列表 导出
     */
    public function actionDealLoadBYDeal2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 3) {
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
            // 校验项目ID
            if (!empty($_GET['project_id'])) {
                $project_id = trim($_GET['project_id']);
                $where     .= " AND project_id = '{$project_id}' ";
            }
            // 校验项目名称
            if (!empty($_GET['project_name'])) {
                $project_name = trim($_GET['project_name']);
                $where       .= " AND project_name = '{$project_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_GET['jys_record_number'])) {
                $jys_record_number = trim($_GET['jys_record_number']);
                $where            .= " AND jys_record_number = '{$jys_record_number}' ";
            }
            // 校验借款人名称
            if (!empty($_GET['user_name'])) {
                $user_name = trim($_GET['user_name']);
                $where    .= " AND deal_user_real_name = '{$user_name}' ";
            }
            //检验融资经办机构
            if (!empty($_GET['advisory_name'])) {
                $advisory_name = trim($_GET['advisory_name']);
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
            if (!empty($_GET['agency_name'])) {
                $agency_name = trim($_GET['agency_name']);
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND deal_agency_id = -1 ";
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
                $sql = "SELECT deal_id , deal_name , project_id , project_name , jys_record_number , project_product_class , deal_loantype , deal_repay_time , deal_user_id , deal_user_real_name , deal_advisory_id , deal_advisory_name , deal_agency_id , deal_agency_name , jys_name , MAX(loan_repay_time) AS max_repay_time , MIN(loan_repay_time) AS min_repay_time , SUM(CASE WHEN repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_capital , SUM(CASE WHEN repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_interest , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_capital , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_interest FROM offline_stat_repay {$where} GROUP BY deal_id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();
                foreach ($list as $key => $value) {
                    if ($value['deal_loantype'] == 5) {
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
            $data  = "借款编号,借款标题,项目ID,项目名称,交易所备案号,交易所名称,产品大类,借款期限,还款方式,在途本金,在途利息,计划最大还款时间,逾期天数,逾期本金,逾期利息,借款人ID,借款人名称,融资经办机构名称,融资担保机构名称\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['deal_id']},{$value['deal_name']},{$value['project_id']},{$value['project_name']},{$value['jys_record_number']},{$value['jys_name']},{$value['project_product_class']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['wait_capital']},{$value['wait_interest']},{$value['max_repay_time']},{$value['overdue_day']},{$value['overdue_capital']},{$value['overdue_interest']},{$value['deal_user_id']},{$value['deal_user_real_name']},{$value['deal_advisory_name']},{$value['deal_agency_name']}\n";
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
     * 智多新 在途项目明细 列表
     */
    public function actionZDXDealLoadBYDeal()
    {
        if (!empty($_POST)) {
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 4) {
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
            // 校验融资方名称
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
            $sql = "SELECT deal_id , deal_name , project_id , project_name , jys_record_number , project_product_class , deal_loantype , deal_repay_time , deal_user_id , deal_user_real_name , deal_advisory_id , deal_advisory_name , deal_agency_id , deal_agency_name , jys_name , MAX(loan_repay_time) AS max_repay_time , MIN(loan_repay_time) AS min_repay_time , SUM(CASE WHEN repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_capital , SUM(CASE WHEN repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_interest , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_capital , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_interest FROM offline_stat_repay {$where} GROUP BY deal_id ORDER BY deal_id DESC ";
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

            //冻结金额
            $deal_info = OfflineDeal::model()->find('id=1 and platform_id=4');
            $frozen_capital = $deal_info->frozen_wait_capital ?: 0;
            $loantype = Yii::app()->c->xf_config['loantype'];
            foreach ($list as $key => $value) {
                if ($value['deal_loantype'] == 5) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }
                $value['no_frozen_wait_capital'] = bcsub($value['wait_capital'], $frozen_capital, 2);
                $value['deal_loantype']    = $loantype[$value['deal_loantype']];
                $value['max_repay_time']   = date("Y-m-d" , ($value['max_repay_time'] + 28800));
                $value['wait_capital']     = number_format($value['wait_capital'], 2, '.', ',');
                $value['no_frozen_wait_capital']     = number_format($value['no_frozen_wait_capital'], 2, '.', ',');
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
        if (!empty($authList) && strstr($authList,'/user/XFDebt/ZDXDealLoadBYDeal2Excel') || empty($authList)) {
            $DealLoadBYDeal2Excel = 1;
        }
        return $this->renderPartial('ZDXDealLoadBYDeal' , array('DealLoadBYDeal2Excel' => $DealLoadBYDeal2Excel));
    }

    /**
     * 智多新 在途项目明细 列表 导出
     */
    public function actionZDXDealLoadBYDeal2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 4) {
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
            // 校验项目ID
            if (!empty($_GET['project_id'])) {
                $project_id = trim($_GET['project_id']);
                $where     .= " AND project_id = '{$project_id}' ";
            }
            // 校验项目名称
            if (!empty($_GET['project_name'])) {
                $project_name = trim($_GET['project_name']);
                $where       .= " AND project_name = '{$project_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_GET['jys_record_number'])) {
                $jys_record_number = trim($_GET['jys_record_number']);
                $where            .= " AND jys_record_number = '{$jys_record_number}' ";
            }
            // 校验借款人名称
            if (!empty($_GET['user_name'])) {
                $user_name = trim($_GET['user_name']);
                $where    .= " AND deal_user_real_name = '{$user_name}' ";
            }
            //检验融资经办机构
            if (!empty($_GET['advisory_name'])) {
                $advisory_name = trim($_GET['advisory_name']);
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
            if (!empty($_GET['agency_name'])) {
                $agency_name = trim($_GET['agency_name']);
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$agency_name}' ";
                $agency_id = $model->createCommand($sql)->queryColumn();
                if ($agency_id) {
                    $agency_id_str = implode(',' , $agency_id);
                    $where .= " AND deal_agency_id IN ({$agency_id_str}) ";
                } else {
                    $where .= " AND deal_agency_id = -1 ";
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
                $sql = "SELECT deal_id , deal_name , project_id , project_name , jys_record_number , project_product_class , deal_loantype , deal_repay_time , deal_user_id , deal_user_real_name , deal_advisory_id , deal_advisory_name , deal_agency_id , deal_agency_name , jys_name , MAX(loan_repay_time) AS max_repay_time , MIN(loan_repay_time) AS min_repay_time , SUM(CASE WHEN repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_capital , SUM(CASE WHEN repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS wait_interest , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 1 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_capital , SUM(CASE WHEN loan_repay_time < {$time} AND repay_type = 2 THEN repay_amount - repaid_amount ELSE 0 END) AS overdue_interest FROM offline_stat_repay {$where} GROUP BY deal_id LIMIT {$pass} , 500 ";
                $list = $model->createCommand($sql)->queryAll();
                $deal_info = OfflineDeal::model()->find('id=1 and platform_id=4');
                $frozen_capital = $deal_info->frozen_wait_capital ?: 0;
                foreach ($list as $key => $value) {
                    if ($value['deal_loantype'] == 5) {
                        $value['deal_repay_time'] .= '天';
                    } else {
                        $value['deal_repay_time'] .= '个月';
                    }
                    $value['no_frozen_wait_capital'] = bcsub($value['wait_capital'], $frozen_capital, 2);
                    $value['deal_loantype']    = $loantype[$value['deal_loantype']];
                    $value['max_repay_time']   = date("Y-m-d" , ($value['max_repay_time'] + 28800));
                    $value['overdue_day']      = round(($time - $value['min_repay_time']) / 86400);

                    $listInfo[] = $value;
                }
            }
            $name  = $platform['name'].' 在途项目明细 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
            $name  = iconv('utf-8', 'GBK', $name);
            $data  = "借款编号,借款标题,项目ID,项目名称,交易所备案号,交易所名称,产品大类,借款期限,还款方式,在途本金(含冻结),在途本金(排除冻结),在途利息,计划最大还款时间,逾期天数,逾期本金,逾期利息,借款人ID,借款人名称,融资经办机构名称,融资担保机构名称\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['deal_id']},{$value['deal_name']},{$value['project_id']},{$value['project_name']},{$value['jys_record_number']},{$value['jys_name']},{$value['project_product_class']},{$value['deal_repay_time']},{$value['deal_loantype']},{$value['wait_capital']},{$value['no_frozen_wait_capital']},{$value['wait_interest']},{$value['max_repay_time']},{$value['overdue_day']},{$value['overdue_capital']},{$value['overdue_interest']},{$value['deal_user_id']},{$value['deal_user_real_name']},{$value['deal_advisory_name']},{$value['deal_agency_name']}\n";
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
     * 工场微金 在途出借人明细 列表
     */
    public function actionDealLoadBYUser()
    {
        if (!empty($_POST)) {
            set_time_limit(0);
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 3) {
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
            $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
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
        if (!empty($authList) && strstr($authList,'/user/XFDebt/DealLoadBYUser2Excel') || empty($authList)) {
            $DealLoadBYUser2Excel = 1;
        }
        return $this->renderPartial('DealLoadBYUser' , array('DealLoadBYUser2Excel' => $DealLoadBYUser2Excel));
    }

    /**
     * 工场微金 在途出借人明细 列表 批量条件上传
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
     * 工场微金 在途出借人明细 导出
     */
    public function actionDealLoadBYUser2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 3) {
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
            $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
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
            $data  = "用户ID,用户姓名,性别,年龄,手机号所在地,账户余额,账户冻结金额,在途本金,在途利息,历史累计收益额\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['real_name']},{$value['sex']},{$value['byear']},{$value['mobile_area']},{$value['money']},{$value['lock_money']},{$value['wait_capital']},{$value['wait_interest']},{$value['yes_interest']}\n";
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
     * 智多新 在途出借人明细 列表
     */
    public function actionZDXDealLoadBYUser()
    {
        if (!empty($_POST)) {
            set_time_limit(0);
            // 条件筛选
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || $_POST['deal_type'] != 4) {
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
            $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
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
                $user_id = $model->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND deal_load.user_id = '{$user_id}' ";
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
            $sql = "SELECT deal_load.user_id AS id , SUM(deal_load.wait_capital) AS wait_capital , SUM(deal_load.wait_interest) AS wait_interest , user.money, user.frozen_wait_capital , user.lock_money 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_user_platform AS user ON deal_load.user_id = user.user_id and user.platform_id=4
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
                $value['mobile']   = GibberishAESUtil::dec($user_info[$value['id']]['mobile'], Yii::app()->c->idno_key);
                $value['mobile_a'] = substr($value['mobile'] , 0 , 7);
                // $value['mobile_b'] = $this->strEncrypt($value['mobile'] , 3 , 4);
                $value['idno']     = GibberishAESUtil::dec($user_info[$value['id']]['idno'], Yii::app()->c->idno_key);
                // $value['idno']     = $this->strEncrypt($value['idno'] , 6 , 8);
                $value['sex']      = $sex[$user_info[$value['id']]['sex']];
                $value['real_name']      =$user_info[$value['id']]['real_name'];
                $value['byear']    = date('Y' , time()) - $user_info[$value['id']]['byear'];
                if ($value['byear'] > 150) {
                    $value['byear'] = '——';
                }
                $value['no_frozen_wait_capital'] = bcsub($value['wait_capital'], $value['frozen_wait_capital'], 2);
                $value['yes_interest']  = $interest_data[$value['id']] ? $interest_data[$value['id']] : '0.00';
                $value['money']         = number_format($value['money'], 2, '.', ',');
                $value['lock_money']    = number_format($value['lock_money'], 2, '.', ',');
                $value['wait_capital']  = number_format($value['wait_capital'], 2, '.', ',');
                $value['no_frozen_wait_capital']  = number_format($value['no_frozen_wait_capital'], 2, '.', ',');
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
        if (!empty($authList) && strstr($authList,'/user/XFDebt/ZDXDealLoadBYUser2Excel') || empty($authList)) {
            $DealLoadBYUser2Excel = 1;
        }
        return $this->renderPartial('ZDXDealLoadBYUser' , array('DealLoadBYUser2Excel' => $DealLoadBYUser2Excel));
    }

    /**
     * 智多新 在途出借人明细 导出
     */
    public function actionZDXDealLoadBYUser2Excel()
    {
        set_time_limit(0);
        if (!empty($_GET)) {
            // 条件筛选
            if (empty($_GET['deal_type']) || !is_numeric($_GET['deal_type']) || $_GET['deal_type'] != 4) {
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
            $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
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
                $sql = "SELECT deal_load.user_id AS id , SUM(deal_load.wait_capital) AS wait_capital , SUM(deal_load.wait_interest) AS wait_interest , user.money , user.frozen_wait_capital , user.lock_money 
                        FROM offline_deal_load AS deal_load 
                        LEFT JOIN offline_user_platform AS user ON deal_load.user_id = user.user_id and user.platform_id=4
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
                foreach ($list as $key => $value) {
                    $value['no_frozen_wait_capital'] = bcsub($value['wait_capital'], $value['frozen_wait_capital'], 2);
                    $value['real_name'] = $user_info[$value['id']]['real_name'];
                    $value['mobile']   = GibberishAESUtil::dec($user_info[$value['id']]['mobile'], Yii::app()->c->idno_key);
                    $value['mobile_a'] = substr($value['mobile'] , 0 , 7);
                    // $value['mobile_b'] = $this->strEncrypt($value['mobile'] , 3 , 4);
                    $value['idno']     = GibberishAESUtil::dec($user_info[$value['id']]['idno'], Yii::app()->c->idno_key);
                    // $value['idno']     = $this->strEncrypt($value['idno'] , 6 , 8);
                    $value['sex']      = $sex[$user_info[$value['id']]['sex']];
                    $value['byear']    = date('Y' , time()) - $user_info[$value['id']]['byear'];
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
            $data  = "用户ID,用户姓名,性别,年龄,手机号所在地,账户余额,账户冻结金额,在途本金(含冻结),在途本金(排除冻结),在途利息,历史累计收益额\n";
            $data  = iconv('utf-8', 'GBK', $data);
            foreach ($listInfo as $key => $value) {
                $temp  = "{$value['id']},{$value['real_name']},{$value['sex']},{$value['byear']},{$value['mobile_area']},{$value['money']},{$value['lock_money']},{$value['wait_capital']},{$value['no_frozen_wait_capital']},{$value['wait_interest']},{$value['yes_interest']}\n";
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
     * 工场微金 在途数据统计表 列表
     */
    public function actionJRGCStatistics()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " WHERE platform_id = 3 ";
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
            $sql   = "SELECT count(*) AS count FROM xf_data_statistics {$where} ";
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
            $sql  = "SELECT * FROM xf_data_statistics {$where} ORDER BY add_time DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            foreach ($list as $key => $value){
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['zx_capital_total']         = number_format($value['zx_capital_total'] , 2 , '.' , ',');
                $value['zx_interest_total']        = number_format($value['zx_interest_total'] , 2 , '.' , ',');
                $value['ph_capital_total']         = number_format($value['ph_capital_total'] , 2 , '.' , ',');
                $value['ph_interest_total']        = number_format($value['ph_interest_total'] , 2 , '.' , ',');
                $value['shop_debt_money_total']    = number_format($value['shop_debt_money_total'] , 2 , '.' , ',');
                $value['zx_debt_money']            = number_format($value['zx_debt_money'] , 2 , '.' , ',');
                $value['ph_debt_money']            = number_format($value['ph_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment_total']     = number_format($value['cash_repayment_total'] , 2 , '.' , ',');
                $value['zx_cash_repayment']        = number_format($value['zx_cash_repayment'] , 2 , '.' , ',');
                $value['ph_cash_repayment']        = number_format($value['ph_cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money_total'] = number_format($value['offline_debt_money_total'] , 2 , '.' , ',');
                $value['zx_offline_debt_money']    = number_format($value['zx_offline_debt_money'] , 2 , '.' , ',');
                $value['ph_offline_debt_money']    = number_format($value['ph_offline_debt_money'] , 2 , '.' , ',');
                $value['shop_debt_money']          = number_format($value['shop_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment']           = number_format($value['cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money']       = number_format($value['offline_debt_money'] , 2 , '.' , ',');
                $value['repayment_capital_total']  = number_format($value['repayment_capital_total'] , 2 , '.' , ',');
                $value['zx_repayment_capital']     = number_format($value['zx_repayment_capital'] , 2 , '.' , ',');
                $value['ph_repayment_capital']     = number_format($value['ph_repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest_total'] = number_format($value['repayment_interest_total'] , 2 , '.' , ',');
                $value['zx_repayment_interest']    = number_format($value['zx_repayment_interest'] , 2 , '.' , ',');
                $value['ph_repayment_interest']    = number_format($value['ph_repayment_interest'] , 2 , '.' , ',');
                $value['repayment_capital']        = number_format($value['repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest']       = number_format($value['repayment_interest'] , 2 , '.' , ',');
                $value['capital_total']            = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['interest_total']           = number_format($value['interest_total'] , 2 , '.' , ',');
                
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
        $P2PStatistics2Excel = 0;
        if (!empty($authList) && strstr($authList,'/user/XFDebt/JRGCStatistics2Excel') || empty($authList)) {
            $P2PStatistics2Excel = 1;
        }
        return $this->renderPartial('JRGCStatistics' , array('P2PStatistics2Excel' => $P2PStatistics2Excel));
    }

    /**
     * 工场微金 在途数据统计表 导出
     */
    public function actionJRGCStatistics2Excel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " WHERE platform_id = 3 ";
            if (empty($_GET['start']) && empty($_GET['end'])) {
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
            $sql  = "SELECT * FROM xf_data_statistics {$where} ORDER BY id DESC ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            foreach ($list as $key => $value){
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['zx_capital_total']         = number_format($value['zx_capital_total'] , 2 , '.' , ',');
                $value['zx_interest_total']        = number_format($value['zx_interest_total'] , 2 , '.' , ',');
                $value['ph_capital_total']         = number_format($value['ph_capital_total'] , 2 , '.' , ',');
                $value['ph_interest_total']        = number_format($value['ph_interest_total'] , 2 , '.' , ',');
                $value['shop_debt_money_total']    = number_format($value['shop_debt_money_total'] , 2 , '.' , ',');
                $value['cash_repayment_total']     = number_format($value['cash_repayment_total'] , 2 , '.' , ',');
                $value['offline_debt_money_total'] = number_format($value['offline_debt_money_total'] , 2 , '.' , ',');
                $value['shop_debt_money']          = number_format($value['shop_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment']           = number_format($value['cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money']       = number_format($value['offline_debt_money'] , 2 , '.' , ',');
                $value['repayment_capital_total']  = number_format($value['repayment_capital_total'] , 2 , '.' , ',');
                $value['repayment_interest_total'] = number_format($value['repayment_interest_total'] , 2 , '.' , ',');
                $value['repayment_capital']        = number_format($value['repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest']       = number_format($value['repayment_interest'] , 2 , '.' , ',');
                $value['capital_total']            = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['interest_total']           = number_format($value['interest_total'] , 2 , '.' , ',');
                
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
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '统计时间');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '去重后总人数');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '在途本金总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '在途利息总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '有解商城累计化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '现金累计兑付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '线下咨询权益化债总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '当日有解商城化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '当日现金兑付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '当日线下咨询权益化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('K1' , '当日有解商城兑付人数');
            $objPHPExcel->getActiveSheet()->setCellValue('L1' , '当日现金兑付人数');
            $objPHPExcel->getActiveSheet()->setCellValue('M1' , '当日线下咨询权益化债人数');
            $objPHPExcel->getActiveSheet()->setCellValue('N1' , '累计兑付本金');
            $objPHPExcel->getActiveSheet()->setCellValue('O1' , '累计兑付利息');
            $objPHPExcel->getActiveSheet()->setCellValue('P1' , '当日兑付本金');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1' , '当日兑付利息');
            $objPHPExcel->getActiveSheet()->setCellValue('R1' , '当日兑付出借人数');
            $objPHPExcel->getActiveSheet()->setCellValue('S1' , '当日出清出借人数');
            $objPHPExcel->getActiveSheet()->setCellValue('T1' , '累计出清出借人数');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['distinct_user_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['interest_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['shop_debt_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['cash_repayment_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['offline_debt_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['shop_debt_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['cash_repayment']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['offline_debt_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2) , $value['shop_debt_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2) , $value['cash_repayment_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2) , $value['offline_debt_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2) , $value['repayment_capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2) , $value['repayment_interest_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2) , $value['repayment_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2) , $value['repayment_interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('R' . ($key + 2) , $value['repayment_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('S' . ($key + 2) , $value['repayment_clear_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('T' . ($key + 2) , $value['repayment_clear_user_total']);
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '工场微金在途数据统计表 '.date("Y年m月d日 H时i分s秒" , time());

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
    * 会员编号转为用户ID
    */
    private function de32Tonum($no_32) {
        $char_array=array("2", "3", "4", "5", "6", "7", "8", "9", 
            "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M", 
            "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $num = substr($no_32, 2);//2wwch
        $no = 0; 
        for ($i = 0;$i <= strlen($num)-1;$i++) {
            $no = $no * 32 + array_search($num[$i],$char_array);
        }    
        $no = $no - 34000000;
        return $no; 
    }

    /**
    * 根据用户id和用户类型获取会员编号 
    * no no为用户ID,即firstp2p_user.id
    * type type为用户类型，即firstp2p_user.user_type
    */
    private function numTo32($no, $type=0){
       $no+=34000000;
       $char_array=array("2", "3", "4", "5", "6", "7", "8", "9", 
           "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M", 
           "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
       $rtn = "";
       while($no >= 32) {
           $rtn = $char_array[fmod($no, 32)].$rtn;
           $no = floor($no/32);
       }    

       $prefix = '00';
       if($type == 1){
           $prefix = '66';
       }    
       return $prefix.$char_array[$no].$rtn;
    }

    private function userIdToHex($user_id, $start_id = 1000000, $cardinal_number = 16777216) {
        if (empty($user_id)) {
            return false;
        }
        if ($user_id>=$start_id){
            // 转32进制
           // 从g开始以此类推h、i、j、.....
            $convert_value = base_convert($user_id+$cardinal_number,10,32);
            $str_hex = strtoupper($convert_value);
            $search = array('I','O');
            $replace = array('Y','Z');
            // 替换掉模糊的i和o
            $result = str_replace($search, $replace, $str_hex);
        }else{
            $str_hex = strtoupper(dechex($user_id));
            $result = str_pad($str_hex, 5, '0', STR_PAD_LEFT);
        }

        return $result;
    }

    /**
     * 工场微金 全量用户信息查询
     */
    public function actionUserList()
    {
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE platform_id = 3 ";
            // 校验用户ID
            if (!empty($_POST['id'])) {
                $id     = intval(trim($_POST['id']));
                $where .= " AND user_id = {$id} ";
            }
            // 校验会员编号
            if (!empty($_POST['member_id'])) {
                // 会员编号转为用户id
                $member_user_id = intval($this->de32Tonum(trim($_POST['member_id'])));
                $where .= " AND user_id = {$member_user_id} ";
            }
            // 校验用户名
            if (!empty($_POST['user_name'])) {
                $user_name = trim($_POST['user_name']);
                $sql       = "SELECT id FROM firstp2p_user WHERE user_name = '{$user_name}' ";
                $user_id   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                $where    .= " AND user_id = {$user_id} ";
            }
            // 校验真实姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND real_name = '{$real_name}' ";
            }
            // 校验证件号码
            if (!empty($_POST['idno'])) {
                $idno   = trim($_POST['idno']);
                $idno   = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key); // 证件号码加密
                $where .= " AND idno = '{$idno}' ";
            }
            // 校验手机号码
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND phone = '{$mobile}' ";
            }
            // 校验银行卡号
            if (!empty($_POST['bankcard'])) {
                $bankcard    = trim($_POST['bankcard']);
                $bankcard    = GibberishAESUtil::enc($bankcard, Yii::app()->c->idno_key); // 银行卡号加密
                $sql         = "SELECT user_id FROM firstp2p_user_bankcard WHERE bankcard = '{$bankcard}' AND verify_status = 1";
                $user_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($user_id_arr) {
                    $user_id_str = implode(',' , $user_id_arr);
                    $where .= " AND user_id IN ({$user_id_str}) ";
                } else {
                    $where .= " AND user_id = -1 ";
                }
            }
            // 校验账户类型
            $user_purpose_type = $_POST['user_purpose'];
            if (!empty($user_purpose_type) || strcmp($user_purpose_type, 0) == 0) {
                $user_purpose = trim($_POST['user_purpose']);
                $where .= " AND user_purpose = '{$user_purpose}' ";
            }
            if (!empty($_POST['condition_id'])) {
                // 拼指定的user_id
                $user_condition_sql = "SELECT data_json FROM xf_upload_user_condition WHERE id = '{$_POST['condition_id']}' ";
                $user_condition = Yii::app()->fdb->createCommand($user_condition_sql)->queryRow();
                if ($user_condition) {
                    $con_data = json_decode($user_condition['data_json'] , true);
                    if ($con_data) {
                        $con_data_str = implode(',' , $con_data);
                        $where .= " AND user_id IN ({$con_data_str}) ";
                    }
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
            // 查询数据总量
            $sql   = "SELECT count(user_id) AS count FROM offline_user_platform {$where} ";
            $count = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT user_id , real_name, phone , user_purpose 
                    FROM offline_user_platform {$where} ORDER BY user_id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->offlinedb->createCommand($sql)->queryAll();

            $user_purpose = array("0" => "借贷混合用户", "1" => "投资户", "2" => "融资户", "3" => "咨询户", "4" => "担保/代偿I户", "5" => "渠道户", "6" => "渠道虚拟户", "7" => "资产收购户", "8" => "担保/代偿II-b户", "9" => "受托资产管理户", "10" => "交易中心（所）", "11" => "平台户", "12" => "保证金户", "13" => "支付户", "14" => "投资券户", "15" => "红包户", "16" => "担保/代偿II-a户", "17" => "放贷户", "18" => "垫资户", "19" => "管理户", "20" => "商户账户", "21" => "营销补贴户");
            $info_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Debt/UserInfo') || empty($authList)) {
                $info_status = 1;
            }
            $user_id_arr = array();
            foreach ($list as $key => $value){
                if ($value['phone']) {
                    $value['mobile']   = GibberishAESUtil::dec($value['phone'], Yii::app()->c->idno_key); // 手机号解密
                }
                $value['user_purpose'] = $user_purpose[$value['user_purpose']];
                $value['info_status']  = $info_status;
                $value['user_name']    = '';
                $value['member_id']    = '';
                $value['create_time']  = '';
                
                $listInfo[] = $value;

                $user_id_arr[] = $value['user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql         = "SELECT id , user_name , user_type , create_time FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_res    = Yii::app()->fdb->createCommand($sql)->queryAll();
                $user_info   = array();
                foreach ($user_res as $key => $value) {
                    $user_info[$value['id']] = $value;
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['user_name']   = $user_info[$value['user_id']]['user_name'];
                    $listInfo[$key]['member_id']   = $this->numTo32($value['user_id'], $user_info[$value['user_id']]['user_type']);
                    $listInfo[$key]['create_time'] = date('Y-m-d H:i:s', $user_info[$value['user_id']]['create_time']);
                }
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        $user_export_auth = 0;
        if (!empty($authList) && strstr($authList,'/user/Debt/UserListExcel') || empty($authList)) {
            $user_export_auth = 1;
        }
        return $this->renderPartial('UserList', array('user_export_auth' => $user_export_auth));
    }

    /**
    * 获取普通用户证件类型
    * @param id_type    int     证件类型
    * @param idno       string     证件号码
    */
    private function getIdType($id_type, $id_no) {
        // 证件类型做key, value转换
        $id_type_arr = array("1" => "身份证", "2" => "护照", "3" => "军官", 
                    "4" => "港澳", "6" => "台湾", "99" => "其他");
        if (in_array($id_type, array_keys($id_type_arr))) {
            // id_type 是1,2,3,4,6,99
            return $id_type_arr[$id_type];
        } else {
            // 长度44的当身份证号，24就是港澳
            if(strlen($id_no) == 44) {
                return "身份证";
            } elseif (strlen($id_no) == 22) {
                return "港澳";
            }
        }
        
        return "";
    }

    /**
     * 工场微金 全量用户信息查询 详情
     */
    public function actionUserInfo()
    {
        // 校验用户ID
        if (!empty($_GET['id'])) {
            $id   = intval($_GET['id']);
            $sql  = "SELECT user_id , real_name, phone , user_purpose , idno , id_type FROM offline_user_platform WHERE user_id = {$id} ";
            $info = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            $sql  = "SELECT id , user_name , user_type , create_time FROM firstp2p_user WHERE id = {$id} ";
            $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();
            $sql  = "SELECT * FROM firstp2p_user_bankcard WHERE user_id = {$id} AND verify_status = 1 ";
            $card = Yii::app()->fdb->createCommand($sql)->queryRow();
            $info['user_name']   = $user_info['user_name'];
            $info['member_id']   = $this->numTo32($info['user_id'], $user_info['user_type']);
            $info['create_time'] = date('Y-m-d H:i:s', $user_info['create_time']);
            $info['idno']        = GibberishAESUtil::dec($info['idno'], Yii::app()->c->idno_key); // 证件号码解密
            $info['mobile']      = GibberishAESUtil::dec($info['phone'], Yii::app()->c->idno_key); // 手机号解密
            $info['id_type']     = $this->getIdType($info['id_type'], $info['idno']);
            $info['member_id']   = $this->numTo32($info['user_id'], $user_info['user_type']);
            $user_purpose = array("0" => "借贷混合用户", "1" => "投资户", "2" => "融资户", "3" => "咨询户", "4" => "担保/代偿I户", "5" => "渠道户", "6" => "渠道虚拟户", "7" => "资产收购户", "8" => "担保/代偿II-b户", "9" => "受托资产管理户", "10" => "交易中心（所）", "11" => "平台户", "12" => "保证金户", "13" => "支付户", "14" => "投资券户", "15" => "红包户", "16" => "担保/代偿II-a户", "17" => "放贷户", "18" => "垫资户", "19" => "管理户", "20" => "商户账户", "21" => "营销补贴户");
            $info['user_purpose'] = $user_purpose[$info['user_purpose']];
            if ($card) {
                $info['bankcard'] = GibberishAESUtil::dec($card['bankcard'], Yii::app()->c->idno_key); // 银行卡号解密
                $info['bankzone'] = $card['bankzone'];
            } else {
                $info['bankcard'] = '';
                $info['bankzone'] = '';
            }
            
            
        }        

        return $this->renderPartial('UserInfo', array('info' => $info));
    }

    /**
    * 工场微金 全量用户信息查询 批量条件上传
    */
    public function actionAddUserConditionUpload()
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
            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
            $name = '工场微金 全量用户信息查询 通过上传用户ID查询';
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
            $type         = intval($_POST['type']);
            if ($type != 1) {
                return $this->actionError('请正确输入查询类型' , 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'] , 5);
            }
            $file_address = $upload_xls['data'];

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$file_address;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();      // 获取用户编号列表
            if ($Rows < 2) {
                return $this->actionError('上传的文件中无数据' , 5);
            }
            if ($Rows > 100001) {
                return $this->actionError('上传的文件中数据超过一万行' , 5);
            }
            unset($data[0]);
            $name = '全量用户信息查询';
            if ($type == 1) {
                $name .= ' 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒" , time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = intval($value[0]);
                }
                $user_id_str = "'".implode("','" , $user_id_arr)."'";
                $sql = "SELECT user_id FROM offline_user_platform WHERE user_id IN ({$user_id_str}) ";
                // 根据excel中的用户编号查询数据库用户表
                $user_id_data = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                $count = count($data);
                $false_id_arr = array();    // 不在DB中的用户编号
                if (!$user_id_data) {
                    return $this->renderPartial('AddUserConditionUpload', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                foreach ($data as $key => $value) {
                    if (!in_array($value[0] , $user_id_data)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ' , $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                $data_json = json_encode($user_id_data);
            }
            // 查询条件保存到数据库
            $sql = "INSERT INTO xf_upload_user_condition (type, name , file_address , data_json) 
                    VALUES ({$type} , '{$name}' , '{$file_address}' , '{$data_json}')";
            $add = Yii::app()->fdb->createCommand($sql)->execute();
            $add_id = Yii::app()->fdb->getLastInsertID();
            if ($add) {
                return $this->renderPartial('AddUserConditionUpload', 
                        array('end' => 2 , 'count' => $count , 'true_count' => $true_count , 'false_count' => $false_count , 'false_id_str' => $false_id_str , 'add_id' => $add_id , 'add_name' => $name));
            } else {
                return $this->actionError('保存查询条件失败' , 5);
            }
        }
        return $this->renderPartial('AddUserConditionUpload', array('end' => 0));
    }

    /**
     * 工场微金 全量用户信息查询 导出
     */
    public function actionUserListExcel()
    {
        if (!empty($_GET)) {
            set_time_limit(0);
            // 条件筛选
            $where = " WHERE platform_id = 3 ";
            // 校验用户ID
            if (!empty($_GET['id'])) {
                $id     = intval(trim($_GET['id']));
                $where .= " AND user_id = {$id} ";
            }
            // 校验会员编号
            if (!empty($_GET['member_id'])) {
                // 会员编号转为用户id
                $member_user_id = intval($this->de32Tonum(trim($_GET['member_id'])));
                $where .= " AND user_id = {$member_user_id} ";
            }
            // 校验用户名
            if (!empty($_GET['user_name'])) {
                $user_name = trim($_GET['user_name']);
                $sql       = "SELECT id FROM firstp2p_user WHERE user_name = '{$user_name}' ";
                $user_id   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                $where    .= " AND user_id = {$user_id} ";
            }
            // 校验真实姓名
            if (!empty($_GET['real_name'])) {
                $real_name = trim($_GET['real_name']);
                $where    .= " AND real_name = '{$real_name}' ";
            }
            // 校验证件号码
            if (!empty($_GET['idno'])) {
                $idno   = trim($_GET['idno']);
                $idno   = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key); // 证件号码加密
                $where .= " AND idno = '{$idno}' ";
            }
            // 校验手机号码
            if (!empty($_GET['mobile'])) {
                $mobile = trim($_GET['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND phone = '{$mobile}' ";
            }
            // 校验银行卡号
            if (!empty($_GET['bankcard'])) {
                $bankcard    = trim($_GET['bankcard']);
                $bankcard    = GibberishAESUtil::enc($bankcard, Yii::app()->c->idno_key); // 银行卡号加密
                $sql         = "SELECT user_id FROM firstp2p_user_bankcard WHERE bankcard = '{$bankcard}' AND verify_status = 1";
                $user_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($user_id_arr) {
                    $user_id_str = implode(',' , $user_id_arr);
                    $where .= " AND user_id IN ({$user_id_str}) ";
                } else {
                    $where .= " AND user_id = -1 ";
                }
            }
            // 校验账户类型
            $user_purpose_type = $_GET['user_purpose'];
            if (!empty($user_purpose_type) || strcmp($user_purpose_type, 0) == 0) {
                $user_purpose = trim($_GET['user_purpose']);
                $where .= " AND user_purpose = '{$user_purpose}' ";
            }
            if (!empty($_GET['condition_id'])) {
                // 拼指定的user_id
                $user_condition_sql = "SELECT data_json FROM xf_upload_user_condition WHERE id = '{$_GET['condition_id']}' ";
                $user_condition = Yii::app()->fdb->createCommand($user_condition_sql)->queryRow();
                if ($user_condition) {
                    $con_data = json_decode($user_condition['data_json'] , true);
                    if ($con_data) {
                        $con_data_str = implode(',' , $con_data);
                        $where .= " AND user_id IN ({$con_data_str}) ";
                    }
                }
            }
            // 第一个搜索块搜索
            if ($_GET['search_type'] == 1 && empty($id) && empty($member_user_id) && empty($user_name) && empty($real_name) && empty($idno) && empty($mobile) && empty($bankcard) && empty($short_alias)) {
                echo '<h1>除账户类型外, 请至少选择一个查询条件</h1>';
                exit;
            }
            // 第二个搜索块搜索
            if ($_GET['search_type'] == 2 && empty($_GET['condition_id'])) {
                echo '<h1>缺少查询条件，请先上传文件</h1>';
                exit;
            }
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_upload_user_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    $con_data = json_decode($condition['data_json'] , true);
                    if ($con_data) {
                        $con_data_str = implode(',' , $con_data);
                        $where .= " AND user_id IN ({$con_data_str}) ";
                    }
                    $redis_key = 'Offline_User_List_Download_Condition_'.$condition['id'];
                    $check = Yii::app()->rcache->get($redis_key);
                    if($check){
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                }
            }        
            // 查询数据
            $sql = "SELECT user_id , real_name, phone , user_purpose , id_type , idno 
                    FROM offline_user_platform {$where} ORDER BY user_id DESC ";
            $list = Yii::app()->offlinedb->createCommand($sql)->queryAll();

            $user_purpose = array("0" => "借贷混合用户", "1" => "投资户", "2" => "融资户", "3" => "咨询户", "4" => "担保/代偿I户", "5" => "渠道户", "6" => "渠道虚拟户", "7" => "资产收购户", "8" => "担保/代偿II-b户", "9" => "受托资产管理户", "10" => "交易中心（所）", "11" => "平台户", "12" => "保证金户", "13" => "支付户", "14" => "投资券户", "15" => "红包户", "16" => "担保/代偿II-a户", "17" => "放贷户", "18" => "垫资户", "19" => "管理户", "20" => "商户账户", "21" => "营销补贴户");
            $info_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Debt/UserInfo') || empty($authList)) {
                $info_status = 1;
            }
            $user_id_arr = array();
            foreach ($list as $key => $value){
                if ($value['phone']) {
                    $value['mobile']   = GibberishAESUtil::dec($value['phone'], Yii::app()->c->idno_key); // 手机号解密
                }
                $value['user_purpose'] = $user_purpose[$value['user_purpose']];
                $value['info_status']  = $info_status;
                $value['idno']         = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                $value['id_type']      = $this->getIdType($value['id_type'], $value['idno']);
                $value['user_name']    = '';
                $value['member_id']    = '';
                $value['create_time']  = '';
                $value['bankcard']     = '';
                $value['bankzone']     = '';
                
                $listInfo[] = $value;

                $user_id_arr[] = $value['user_id'];
            }
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql         = "SELECT id , user_name , user_type , create_time FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_res    = Yii::app()->fdb->createCommand($sql)->queryAll();
                $sql         = "SELECT user_id , bankcard , bankzone FROM firstp2p_user_bankcard WHERE user_id IN ({$user_id_str}) ";
                $card_res    = Yii::app()->fdb->createCommand($sql)->queryAll();
                $user_info   = array();
                $card_info   = array();
                foreach ($user_res as $key => $value) {
                    $user_info[$value['id']] = $value;
                }
                foreach ($card_res as $key => $value) {
                    $card_info[$value['user_id']] = $value;
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['user_name']   = $user_info[$value['user_id']]['user_name'];
                    $listInfo[$key]['member_id']   = $this->numTo32($value['user_id'], $user_info[$value['user_id']]['user_type']);
                    $listInfo[$key]['create_time'] = date('Y-m-d H:i:s', $user_info[$value['user_id']]['create_time']);
                    $listInfo[$key]['bankcard']    = $card_info[$value['user_id']]['bankcard'];
                    if ($card_info[$value['user_id']]) {
                        $listInfo[$key]['bankcard'] = GibberishAESUtil::dec($card_info[$value['user_id']]['bankcard'] , Yii::app()->c->idno_key);
                        $listInfo[$key]['bankzone'] = $card_info[$value['user_id']]['bankzone'];
                    } 
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

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '会员编号');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '会员名称');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '用户姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '手机号');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '证件类型');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '证件号码');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '银行卡号');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '开户行信息');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '账户类型');

            foreach ($listInfo as $key => $value) {

                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['member_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['user_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['real_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['mobile']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['id_type']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['idno'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['bankcard'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['bankzone']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['user_purpose']);
            }
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '工场微金 全量用户信息查询 '.date("Y年m月d日 H时i分s秒" , time());
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");
            if ($condition) {
                $redis_time = 3600;
                $redis_key = 'Offline_User_List_Download_Condition_'.$condition['id'];
                $set = Yii::app()->rcache->set($redis_key , date('Y-m-d H:i:s' , time()) , $redis_time);
                if(!$set){
                    Yii::log("{$redis_key} redis download set error","error");
                }
            }
            $objWriter->save('php://output');
        }
    }

    /**
     * 智多新在途投资明细黑名单
     */
    public function actionZDXBlackEditAdd()
    {

        return $this->renderPartial('ZDXBlackEditAdd');
    }

    /**
     * 工场微金在途投资明细黑名单
     */
    public function actionGCBlackEditAdd()
    {

        return $this->renderPartial('GCBlackEditAdd');
    }


    /**
     * 排除冻结网信在途数据统计表 列表
     */
    public function actionP2PStatisticFrozen()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " WHERE platform_id IN (0 , 1 , 2 , 4) ";
            // 校验查询时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start'].' 00:00:00');
                $where .= " AND add_time >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end'].' 23:59:59');
                $where .= " AND add_time <= {$end} ";
            }
            // 所属平台
            if (!empty($_POST['platform'])) {
                $platform = intval($_POST['platform']);
                $where   .= " AND platform_id = {$platform} ";
            } else {
                $where   .= " AND platform_id = 0 ";
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
            $sql   = "SELECT count(*) AS count FROM xf_data_statistics_frozen {$where} ";
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
            $sql  = "SELECT * FROM xf_data_statistics_frozen {$where} ORDER BY add_time DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            $pla = array(0 => '全平台' , 1 => '尊享' , 2 => '普惠(不含智多新)' , 4 => '智多新');
            foreach ($list as $key => $value){
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['zx_capital_total']         = number_format($value['zx_capital_total'] , 2 , '.' , ',');
                $value['zx_interest_total']        = number_format($value['zx_interest_total'] , 2 , '.' , ',');
                $value['ph_capital_total']         = number_format($value['ph_capital_total'] , 2 , '.' , ',');
                $value['ph_interest_total']        = number_format($value['ph_interest_total'] , 2 , '.' , ',');
                $value['shop_debt_money_total']    = number_format($value['shop_debt_money_total'] , 2 , '.' , ',');
                $value['zx_debt_money']            = number_format($value['zx_debt_money'] , 2 , '.' , ',');
                $value['ph_debt_money']            = number_format($value['ph_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment_total']     = number_format($value['cash_repayment_total'] , 2 , '.' , ',');
                $value['zx_cash_repayment']        = number_format($value['zx_cash_repayment'] , 2 , '.' , ',');
                $value['ph_cash_repayment']        = number_format($value['ph_cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money_total'] = number_format($value['offline_debt_money_total'] , 2 , '.' , ',');
                $value['zx_offline_debt_money']    = number_format($value['zx_offline_debt_money'] , 2 , '.' , ',');
                $value['ph_offline_debt_money']    = number_format($value['ph_offline_debt_money'] , 2 , '.' , ',');
                $value['shop_debt_money']          = number_format($value['shop_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment']           = number_format($value['cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money']       = number_format($value['offline_debt_money'] , 2 , '.' , ',');
                $value['repayment_capital_total']  = number_format($value['repayment_capital_total'] , 2 , '.' , ',');
                $value['zx_repayment_capital']     = number_format($value['zx_repayment_capital'] , 2 , '.' , ',');
                $value['ph_repayment_capital']     = number_format($value['ph_repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest_total'] = number_format($value['repayment_interest_total'] , 2 , '.' , ',');
                $value['zx_repayment_interest']    = number_format($value['zx_repayment_interest'] , 2 , '.' , ',');
                $value['ph_repayment_interest']    = number_format($value['ph_repayment_interest'] , 2 , '.' , ',');
                $value['repayment_capital']        = number_format($value['repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest']       = number_format($value['repayment_interest'] , 2 , '.' , ',');
                $value['capital_total']            = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['interest_total']           = number_format($value['interest_total'] , 2 , '.' , ',');
                $value['platform']                 = $pla[$value['platform_id']];

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
        $P2PStatistics2FrozenExcel = 0;
        if (!empty($authList) && strstr($authList,'/user/XFDebt/P2PStatistics2FrozenExcel') || empty($authList)) {
            $P2PStatistics2FrozenExcel = 1;
        }
        return $this->renderPartial('P2PStatisticsFrozen' , array('P2PStatistics2FrozenExcel' => $P2PStatistics2FrozenExcel));
    }


    /**
     * 网信在途数据统计表 导出
     */
    public function actionP2PStatistics2FrozenExcel()
    {
        if (!empty($_GET)) {

            // 条件筛选
            $where = " WHERE platform_id IN (0 , 1 , 2 , 4) ";
            if (empty($_GET['start']) && empty($_GET['end']) && empty($_GET['platform'])) {
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
            // 所属平台
            if (!empty($_GET['platform'])) {
                $platform = intval($_GET['platform']);
                $where   .= " AND platform_id = {$platform} ";
            } else {
                $where   .= " AND platform_id = 0 ";
            }
            // 查询数据
            $sql  = "SELECT * FROM xf_data_statistics_frozen {$where} ORDER BY id DESC ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            foreach ($list as $key => $value){
                $value['add_time']                 = date('Y-m-d', $value['add_time']);
                $value['zx_capital_total']         = number_format($value['zx_capital_total'] , 2 , '.' , ',');
                $value['zx_interest_total']        = number_format($value['zx_interest_total'] , 2 , '.' , ',');
                $value['ph_capital_total']         = number_format($value['ph_capital_total'] , 2 , '.' , ',');
                $value['ph_interest_total']        = number_format($value['ph_interest_total'] , 2 , '.' , ',');
                $value['shop_debt_money_total']    = number_format($value['shop_debt_money_total'] , 2 , '.' , ',');
                $value['cash_repayment_total']     = number_format($value['cash_repayment_total'] , 2 , '.' , ',');
                $value['offline_debt_money_total'] = number_format($value['offline_debt_money_total'] , 2 , '.' , ',');
                $value['shop_debt_money']          = number_format($value['shop_debt_money'] , 2 , '.' , ',');
                $value['cash_repayment']           = number_format($value['cash_repayment'] , 2 , '.' , ',');
                $value['offline_debt_money']       = number_format($value['offline_debt_money'] , 2 , '.' , ',');
                $value['repayment_capital_total']  = number_format($value['repayment_capital_total'] , 2 , '.' , ',');
                $value['repayment_interest_total'] = number_format($value['repayment_interest_total'] , 2 , '.' , ',');
                $value['repayment_capital']        = number_format($value['repayment_capital'] , 2 , '.' , ',');
                $value['repayment_interest']       = number_format($value['repayment_interest'] , 2 , '.' , ',');
                $value['capital_total']            = number_format($value['capital_total'] , 2 , '.' , ',');
                $value['interest_total']           = number_format($value['interest_total'] , 2 , '.' , ',');

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
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '统计时间');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '去重后总人数');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '在途本金总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '在途利息总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '商城累计化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '商城累计化债人数');
            $objPHPExcel->getActiveSheet()->setCellValue('G1' , '现金累计兑付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('H1' , '线下咨询权益化债总金额');
            $objPHPExcel->getActiveSheet()->setCellValue('I1' , '当日商城化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('J1' , '当日现金兑付金额');
            $objPHPExcel->getActiveSheet()->setCellValue('K1' , '当日线下咨询权益化债金额');
            $objPHPExcel->getActiveSheet()->setCellValue('L1' , '当日商城兑付人数');
            $objPHPExcel->getActiveSheet()->setCellValue('M1' , '当日现金兑付人数');
            $objPHPExcel->getActiveSheet()->setCellValue('N1' , '当日线下咨询权益化债人数');
            $objPHPExcel->getActiveSheet()->setCellValue('O1' , '累计兑付本金');
            $objPHPExcel->getActiveSheet()->setCellValue('P1' , '累计兑付利息');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1' , '当日兑付本金');
            $objPHPExcel->getActiveSheet()->setCellValue('R1' , '当日兑付利息');
            $objPHPExcel->getActiveSheet()->setCellValue('S1' , '当日兑付出借人数');
            $objPHPExcel->getActiveSheet()->setCellValue('T1' , '当日出清出借人数');
            $objPHPExcel->getActiveSheet()->setCellValue('U1' , '累计出清出借人数');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['distinct_user_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['interest_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['shop_debt_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['shop_debt_user_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2) , $value['cash_repayment_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2) , $value['offline_debt_money_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2) , $value['shop_debt_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2) , $value['cash_repayment']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2) , $value['offline_debt_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2) , $value['shop_debt_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2) , $value['cash_repayment_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2) , $value['offline_debt_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2) , $value['repayment_capital_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2) , $value['repayment_interest_total']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2) , $value['repayment_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('R' . ($key + 2) , $value['repayment_interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('S' . ($key + 2) , $value['repayment_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('T' . ($key + 2) , $value['repayment_clear_user']);
                $objPHPExcel->getActiveSheet()->setCellValue('U' . ($key + 2) , $value['repayment_clear_user_total']);
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '网信在途数据统计表 '.date("Y年m月d日 H时i分s秒" , time());

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


    public function actionFundInquiry()
    {

        $sql = "SELECT assi.user_id AS id , user.real_name AS name FROM xf_purchase_assignee AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status=2 order by assi.user_id in (12143680) desc";
        $user_arr = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$user_arr) {
            $user_arr = array(0 => array('id' => -1 , 'name' => '暂无受让方'));
        }
        if($_POST){
            $returnResult['can_pay'] = 0;
            $returnResult['use_money'] = 0;
            $purchase_user_id = Yii::app()->request->getParam( 'purchase_user_id' );
            if (!isset($purchase_user_id) || $purchase_user_id == '') {
                $this->echoJsonAuditLog($returnResult, 4000, '请选择要查询的受让方！');
            }
            //代发代付查询
            $customer_amount = $this->query_customer_amount($purchase_user_id);
            //var_dump($customer_amount);
            if(!$customer_amount){
                $this->echoJsonAuditLog($returnResult, 4000, '此受让方未在易宝开户');
            }
            if($customer_amount && $customer_amount['state'] == 'SUCCESS' && $customer_amount['result']['errorCode'] == 'BAC001' ){
                $returnResult['can_pay'] = $customer_amount['result']['wtjsValidAmount'] ;
                $returnResult['use_money'] = $customer_amount['result']['accountAmount'] ;

            }

            //可提现查询
            /*
            $account_amount = $this->account_query($purchase_user_id);
            var_dump($account_amount);
            if($account_amount && $account_amount['state'] == 'SUCCESS' && $account_amount['result']['errorCode'] == 'BAC001' ){
                $returnResult['use_money'] = $account_amount['result']['d0ValidAmount'] ;
            }*/

            $this->echoJsonAuditLog($returnResult, 0, '查询成功！');
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $is_auth = 0;
        if (!empty($authList) && strstr($authList,'/user/XFDebt/FundInquiry') || empty($authList)) {
            $is_auth = 1;
        }

        return $this->renderPartial('FundInquiry', array('is_auth' => $is_auth , 'user_arr' => $user_arr));
    }

    public function account_query($purchase_user_id)
    {

        $config = Yii::app()->c->payment_account_config[$purchase_user_id];

        $request = new YopRequest($config['APP_KEY'], $config['CFCA_PRIVATE_KEY']);
        $request->addParam("merchantNo", $config['MERCHANT_NO']);//商户编号
        //提交Post请求
        $response = YopClient3::get("/rest/v1.0/account/accountinfos/query", $request);
        $re = $this->object_array($response);
        Yii::log(__CLASS__." $purchase_user_id account-query:  yibao return ".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');

        if ($re['validSign'] == 1) {
            return $re;
        }
        return  false ;
    }

    public function query_customer_amount($purchase_user_id)
    {

        $config = Yii::app()->c->payment_account_config[$purchase_user_id];
        if(!$config){
            return false;
        }

        $request = new YopRequest($config['APP_KEY'], $config['CFCA_PRIVATE_KEY']);
        /*
        $request->addParam("merchantno", $config['MERCHANT_NO']);

        //加入请求参数
        $request->addParam("batchNo", $data['batch_no']);//商户生成的唯一请求号
        $request->addParam("orderId", $data['order_no']);//商户生成的唯一订单号
        $request->addParam("amount", $data['purchase_amount']);//金额
        $request->addParam("accountName", $data['real_name']);//收款帐户的开户姓名
        $request->addParam("accountNumber", $data['bank_card']);//收款帐户的卡号
        $request->addParam("bankCode", $data['bankcode']);//银行编码
*/

        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/balance/query_customer_amount", $request);
        $re = $this->object_array($response);
        Yii::log(__CLASS__." $purchase_user_id query_customer_amount:  yibao return ".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');

        if ($re['validSign'] == 1) {
            return $re;
        }
        return  false ;
    }

    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key=>$value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }
}
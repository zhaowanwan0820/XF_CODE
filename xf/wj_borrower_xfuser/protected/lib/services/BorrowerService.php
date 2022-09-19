<?php
/**
 */
use iauth\models\User;

class BorrowerService extends ItzInstanceService
{
    public static $deal_src_cn = [
        0=>"L库原始数据",1=>"L库掌众",
    ];
    public static $data_src_cn = [
        0=>'未知',1=>"L库",2=>"C库",
    ];

    public static $repay_status_cn = [
        0=>'待还款',1=>'还款中',2=>'还款成功',3=>'还款失败',
    ];
    public static $auth_status_cn = [
        0=>'待确认',1=>'待审核',2=>'审核通过',3=>'已拒绝',
    ];

    public static $repay_auth_status_cn = [
       0=>'待审核',1=>'审核通过',2=>'已拒绝',
    ];

  
    public static $organization_type = [
      
        1=>'北京掌众金融信息服务有限公司',
        2=>'悠融资产管理（上海）有限公司',
        3=>'杭州大树网络技术有限公司（功夫贷）',
      
    ];
    public function __construct()
    {
        parent::__construct();
    }


    public function exportBorrowerDetail($params)
    {
        $result= $this->getBorrowerList($params)['list'];

        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
        $objPHPExcel = new PHPExcel();
        // 设置当前的sheet
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('第一页');
        $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
        if($params['id_type'] == 1){
            $name = "个人借款人信息".date("Y年m月d日 H时i分s秒", time());
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(40);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(40);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '借款人姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '借款人手机号码');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '借款人证件号');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '借款人银行卡号');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '分案状态');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '接案公司');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '催收状态');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '联系状态');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '还款意愿');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '催收建议');
            foreach ($result as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['real_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['mobile']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['idno'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['bankcard'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['fenan_status_cn'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['company_name'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['cuishou_status_cn'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['contact_status_cn']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $value['question_3_cn']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['cs_suggest_cn']);
            }
        }else{
            $name = "企业借款人信息".date("Y年m月d日 H时i分s秒", time());
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(40);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(40);
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

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '企业名称');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '联系电话');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '统一识别码');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '银行卡号');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '借款标的数量');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '原始借款本金');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '原始借款利息');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '逾期借款本金');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '逾期借款利息');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '已还借款本金');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '已还借款利息');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', '剩余借款本金');
            $objPHPExcel->getActiveSheet()->setCellValue('N1', '剩余借款利息');
            $objPHPExcel->getActiveSheet()->setCellValue('O1', '企业状态');
            $objPHPExcel->getActiveSheet()->setCellValue('P1', '接案公司');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1', '催收状态');

            foreach ($result as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['real_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['mobile']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['idno'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['bankcard'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['count_deal'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['borrow_amount'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['borrow_interest'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['wait_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $value['wait_interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['paid_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $value['paid_interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2), $value['wait_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2), $value['wait_interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2), $value['company_user_status']);
                $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2), $value['company_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2), $value['cuishou_status_cn']);

            }
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
    
        # code...
    }

    public function exportBeforeBorrowDetail($params)
    {
        $result= $this->getDealOrderList($params)['list'];
        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
        $objPHPExcel = new PHPExcel();
        // 设置当前的sheet
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('第一页');
        // 保护
        $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

        if($params['product_class'] == 1){
            $name = "消费信贷标的列表 ".date("Y年m月d日 H时i分s秒", time());
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(40);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '产品名称');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '订单编号');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '借款编号');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '借款金额');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '借款期限');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '原先锋待还期数');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '原先锋待还本金和');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '原先锋待还利息和');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '借款人ID');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '借款人姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', '借款人证件号');
            $objPHPExcel->getActiveSheet()->setCellValue('N1', '借款人手机号');
            $objPHPExcel->getActiveSheet()->setCellValue('O1', '放款凭证');


            foreach ($result as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['product_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['number']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['deal_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['deal_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['loan_amount']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['repay_type'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['un_puy_num']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['principal']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $value['interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $value['customer_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2), $value['id_number'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2), $value['phone'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2), $value['voucher_url'].' ');
            }

        }elseif($params['product_class'] == 2){
            $name = "供应链标的列表 ".date("Y年m月d日 H时i分s秒", time());
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

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '借款编号');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '借款金额');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '待还本金和');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '待还利息和');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '核心担保企业名称');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '借款方名称');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '统一识别码');
            $objPHPExcel->getActiveSheet()->setCellValue('j1', '联系电话');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '放款凭证');


            foreach ($result as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['deal_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['deal_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['loan_amount']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['principal']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['interest']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['agency_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['customer_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['id_number'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('j' . ($key + 2), $value['phone'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['voucher_url'].' ');
            }

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
    
    /**
    * 获取原始借款明细
    * borrower/DealOrder/index
    * @param [type] $params
    * @return void
    */
    public function getDealOrderList($params)
    {
        $where = '';
        $total_loan_amount = 0;
        $condition = [];
        if (false && $params['type'] != 'all') {
            if ($params['type'] == 1) {
                $condition = ['d.repay_auth_flag in (0,1,3)'];
            } else {
                $condition = ['d.repay_auth_flag  = 2 '];
            }
        }
    
        //审核状态
        if (!empty($params['auth_status'])) {
            if ($params['auth_status'] == 1) {
                $condition[] = " d.repay_auth_flag = 0";
            } elseif ($params['auth_status'] == 2) {
                $condition[] = " d.repay_auth_flag  = 1";
            } elseif ($params['auth_status'] == 3) {
                $condition[] = " d.repay_auth_flag  = 3";
            }
        }
        //产品名称
        if (!empty($params['number'])) {
            $condition[] = " o.number = '".trim($params['number'])."'";
        }

        if (!empty($params['user_id'])) {
            $condition[] = " d.user_id = ".trim($params['user_id']);
        }
       
        if (!empty($params['deal_status'])) {
            $condition[] = " d.deal_status = ".$params['deal_status'];
        }

        if (!empty($params['deal_id'])) {
            $condition[] = " d.id = ".trim($params['deal_id']);
        }

        if (!empty($params['data_src'])) {
            $condition[] = " d.data_src = ".trim($params['data_src']);
        }

        if (!empty($params['deal_name'])) {
            $condition[] = " d.name = '".trim($params['deal_name'])."'";
        }

        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }
        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(' and ', $user_query);
            $user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
            if($user_info){
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }
        if (!empty($params['user_ids'])) {
            $condition[] = " d.user_id  in ( ".implode(',', $params['user_ids'])." )";
        }


        //借款时间
        // if (!empty($params['loan_start'])) {
        //     $condition[] = " o.create_time >= '".$params['loan_start']."'";
        // }
        // if (!empty($params['loan_end'])) {
        //     $condition[] = " o.create_time <= '".date('Y-m-d', (strtotime($params['loan_end'])+86400))."'";
        // }

       // if (!empty($params['organization_name'])) {
       //     $condition[] = " a.name  = '".$params['organization_name']."'";
       // }
        if (!empty($params['product_name'])) {
            $condition[] = " o.product_name  = '".$params['product_name']."'";
        }
        //if (!empty($params['organization_type'])) {
         //   $condition[] = " a.name  = '".self::$organization_type[$params['organization_type']]."'";
       // }



        //末次还款时间
        if (!empty($params['last_repay_start'])) {
            $condition[] = " d.xf_last_repay_time >= '".strtotime($params['last_repay_start'])."'";
        }
        if (!empty($params['last_repay_end'])) {
            $condition[] = " d.xf_last_repay_time < '".(strtotime($params['last_repay_end'])+86400)."'";
        }

        //借款金额borrow_amount
        if (!empty($params['loan_amount_min'])) {
            $condition[] = " d.borrow_amount >= '".$params['loan_amount_min']."'";
        }
        if (!empty($params['loan_amount_max'])) {
            $condition[] = " d.borrow_amount <= '".$params['loan_amount_max']."'";
        }

        //催收公司展示分配的借款人
        $current_admin_id = \Yii::app()->user->id;
        $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
        if ($adminInfo['company_id'] > 0) {
            $now = time();
            /* 2022-05-15打开
            $sql = "select detail.user_id from firstp2p_borrower_distribution  as distribution left join firstp2p_borrower_distribution_detail as detail  on distribution.id = detail.distribution_id where distribution.company_id = {$adminInfo['company_id']} and  distribution.status = 1 and detail.status = 1 and distribution.end_time >=  {$now} and distribution.start_time <={$now}";
            $user_ids  = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if ($user_ids) {
                $user_ids = ArrayUntil::array_column($user_ids, 'user_id');
                $condition[]= "  d.user_id in (".implode(',', $user_ids).")";
            } else {
                return ['countNum' => 0, 'list' => []];
            }*/

            //必须有搜索条件才展示数据
            /*
            if(empty($params['user_id']) && empty($params['product_name']) && empty($params['number']) && empty($params['deal_name']) && empty($params['deal_id']) && empty($params['customer_name']) && empty($params['phone']) && empty($params['id_number'])){
                return ['countNum' => 0, 'list' => []];
            }*/

        }

        if(!empty($params['is_voucher_audit'])){
             $condition[] = " EXISTS (select 1 from firstp2p_deal_reply_slip as s where s.deal_id = d.id ) ";//and status = 0 
        }
        //var_dump(isset($params['is_kf']), $params['is_kf'] == 1, $condition);die;
        if(isset($params['is_kf']) && $params['is_kf'] == 1 && count($condition) == 0){
            return ['countNum' => 0, 'list' => []];
        }

        if ($params['product_class'] == 1) {
            $condition[] = " d.product_class  in ('消费贷','个体经营贷') ";
        }
        if ($params['product_class'] == 2) {
            $condition[] = " d.product_class  in ('供应链','企业经营贷') ";
        }

        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
        
        $result = [];
        $count_sql = "select count(1) from firstp2p_deal as d left join order_info as o on d.approve_number = o.number left join firstp2p_deal_agency  as a on d.agency_id = a.id   {$where} ";
        
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select d.voucher_url,d.reply_prove,d.deal_status, d.borrow_amount as loan_amount ,d.data_src, d.user_id,d.xf_last_repay_time, d.repay_auth_flag, d.id ,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.loantype, d.rate, d.start_time, d.create_time as d_create_time, a.name as agency_name, o.number, o.product_name, o.transaction_number, o.create_time as o_create_time from firstp2p_deal as d left join order_info as o on d.approve_number = o.number left join firstp2p_deal_agency  as a on d.agency_id = a.id   {$where} order by FIELD(d.repay_auth_flag,1,3,0),d.id desc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            
            $deal_id_array  = ArrayUntil::array_column($result, 'id');
            if ($deal_id_array) {
                $deal_ids = implode(',', $deal_id_array);
                //原还款计划 待还
                $_repay_info = Yii::app()->cmsdb->createCommand("select count(1) as un_puy_num, deal_id , sum(if(last_yop_repay_status=2,0,principal) )as principal ,sum(interest) as interest  from firstp2p_deal_repay where deal_id in ($deal_ids) and status = 0 and type = 0 group by deal_id")->queryAll();
                if ($_repay_info) {
                    foreach ($_repay_info as  $item) {
                        $repay_info[$item['deal_id']] = $item;
                    }
                }

                //$_repay_slip = Yii::app()->cmsdb->createCommand("select  firstp2p_deal_reply_slip")->queryAll();

            }

            //c.name as customer_name, c.phone,c.id_number
            $user_ids =   ArrayUntil::array_column($result, 'user_id');
            if ($user_ids) {
                $user_ids = implode(',', $user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key)?:'';
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }

            $deal_ids_array = ArrayUntil::array_column($result, 'id');
            $deal_ids =   implode(',', $deal_ids_array);
          

            $sql = "select deal_id from firstp2p_create_new_repay_log where deal_id in ({$deal_ids})";
            $_new_repay = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            $has_new_repay_log_deal =  ArrayUntil::array_column($_new_repay, 'deal_id');

            //是否有待还的还款计划，排除待审核待凭证录入与线下还款录入
            $deal_repay_status = $this->getRepayStatus($deal_ids, $deal_ids_array);
            $deal_pz_repay_status = $this->getPzRepayStatus($deal_ids, $deal_ids_array);
            $deal_gyl_repay_status = $this->getGylRepayStatus($deal_ids);
            foreach ($result as &$value) {
                $value+= $user_info[$value['user_id']]?:['customer_name'=>'','id_number'=>'','phone'=>''];
                $value+=$repay_info[$value['id']]?:[];
                $value['has_new_repay'] = in_array($value['id'], $has_new_repay_log_deal)?1:0;
                $value['un_puy_num'] = $value['un_puy_num'].'期';
                $value['deal_loantype'] = Yii::app()->c->xf_config['loantype'][$value['loantype']];//还款方式
                //$value['deal_src_cn'] = self::$deal_src_cn[$value['deal_src']];
                $value['data_src_cn'] = self::$data_src_cn[$value['data_src']];
                $value['repay_type'] = $value['repay_type'].'个月';
                $value['rate'] = floatval($value['rate']);
                $total_loan_amount = bcadd($total_loan_amount, $value['loan_amount'], 2);
                $value['loan_amount'] = number_format($value['loan_amount'], 2);
                $value['xf_last_repay_time'] = $value['xf_last_repay_time']>0? date('Y-m-d H:i:s', $value['xf_last_repay_time']):'--';
                $value['auth_status_cn'] = self::$auth_status_cn[$value['repay_auth_flag']];
                $value['deal_status_cn'] = self::$deal_status[$value['deal_status']];
                if(empty($value['voucher_url'])){
                    $value['voucher_url_html'] = '';
                }else{
                    $value['voucher_url'] = "https://admin-upload-file.oss-cn-beijing.aliyuncs.com/{$value['voucher_url']}";
                    $value['voucher_url_html'] = "<a href='{$value['voucher_url']}' target='_blank' download><button class='layui-btn layui-btn-primary'>查看</button></a>";
                }
                $value['repay_status'] = in_array($value['id'], $deal_repay_status) ? 0 : 1;
                $value['voucher_repay_status'] = in_array($value['id'], $deal_pz_repay_status) ? 0 : 1;
                $value['gyl_repay_status'] = in_array($value['id'], $deal_gyl_repay_status) ? 1 : 0;
                $value['principal'] = $value['principal'] ?: 0;
                $value['interest'] = $value['interest'] ?: 0;
            }
        }
        return ['countNum' => $total_num, 'list' => $result,'total_loan_amount' =>$total_loan_amount];
    }

    private function getPzRepayStatus($deal_ids, $deal_ids_array){
        $repay_ids = [];
        $sql_01 = "SELECT DISTINCT b.repay_id from firstp2p_offline_repay a 
                LEFT JOIN firstp2p_offline_repay_detail b on a.id=b.offline_repay_id 
                WHERE a.status in (0,1) and a.deal_id in ({$deal_ids})";
        $repay_ids_01 = Yii::app()->cmsdb->createCommand($sql_01)->queryColumn();
        if($repay_ids_01){
            $repay_ids = array_merge($repay_ids, $repay_ids_01);
        }

        $sql_02 = "SELECT DISTINCT  deal_repay_id from firstp2p_deal_reply_slip  
                WHERE  status in (0,1) and deal_id in ({$deal_ids})";
        $repay_ids_02 = Yii::app()->cmsdb->createCommand($sql_02)->queryColumn();
        if($repay_ids_02){
            $repay_ids = array_merge($repay_ids, $repay_ids_02);
        }
        if(empty($repay_ids)){
            return $deal_ids_array;
        }

        $sql_03 = "SELECT distinct deal_id from firstp2p_deal_repay WHERE deal_id in ({$deal_ids}) and status=0 and part_repay_money=0 and paid_principal=0 and paid_interest=0 and id not in (".implode(',', $repay_ids).")";
        $deal_id_ret = Yii::app()->cmsdb->createCommand($sql_03)->queryColumn();
        if($deal_id_ret){
            return $deal_id_ret;
        }
        return $deal_ids_array;
    }
    private function getRepayStatus($deal_ids, $deal_ids_array){
        $repay_ids = [];
        $sql_01 = "SELECT DISTINCT b.repay_id from firstp2p_offline_repay a 
                LEFT JOIN firstp2p_offline_repay_detail b on a.id=b.offline_repay_id 
                WHERE a.status in (0,1) and a.deal_id in ({$deal_ids})";
        $repay_ids_01 = Yii::app()->cmsdb->createCommand($sql_01)->queryColumn();
        if($repay_ids_01){
            $repay_ids = array_merge($repay_ids, $repay_ids_01);
        }

        $sql_02 = "SELECT DISTINCT  deal_repay_id from firstp2p_deal_reply_slip  
                WHERE  status in (0,1) and deal_id in ({$deal_ids})";
        $repay_ids_02 = Yii::app()->cmsdb->createCommand($sql_02)->queryColumn();
        if($repay_ids_02){
            $repay_ids = array_merge($repay_ids, $repay_ids_02);
        }
        if(empty($repay_ids)){
            return $deal_ids_array;
        }

        $sql_03 = "SELECT distinct deal_id from firstp2p_deal_repay WHERE deal_id in ({$deal_ids}) and status=0 and id not in (".implode(',', $repay_ids).")";
        $deal_id_ret = Yii::app()->cmsdb->createCommand($sql_03)->queryColumn();
        if($deal_id_ret){
            return $deal_id_ret;
        }
        return $deal_ids_array;
    }

    private function getGylRepayStatus($deal_ids){

        $sql_02 = "SELECT DISTINCT  deal_id from firstp2p_gyl_offline_repay  
                WHERE  status in (0,1) and deal_id in ({$deal_ids})";
        $repay_ids_02 = Yii::app()->cmsdb->createCommand($sql_02)->queryColumn();
        if($repay_ids_02){
            return $repay_ids_02;
        }
        return [];
    }
    
    public static $deal_status=[
        '4'=>'还款中','5'=>'已出清'
    ];

    /**
     *
     * 原还款计划相关信息 只取原还款计划
     * borrower/DealOrder/repayPlan
     * @param [type] $deal_id
     * @return void
     */
    public function getAboutDealRepayPlanInfo($deal_id)
    {
        if (empty($deal_id)) {
            return false;
        }
       
        $sql = "select  d.repay_auth_flag, d.id ,d.user_id,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.start_time, d.create_time as d_create_time, d.borrow_amount as loan_amount, a.name as organization_name, o.product_name, o.transaction_number, o.create_time as o_create_time,o.customer_number,o.number from firstp2p_deal as d left join order_info as o on d.approve_number = o.number left join firstp2p_deal_agency  as a on d.advisory_id = a.id   where d.id = {$deal_id}";
        $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
       
        $dealInfo['loan_amount'] = floatval($dealInfo['loan_amount']);
        $dealInfo['d_create_time'] = date('Y-m-d',$dealInfo['d_create_time']);
        $dealInfo['o_create_time'] = date('Y-m-d',$dealInfo['o_create_time']);
      
        $sql = "select b.card_number,b.bank_name, c.name as customer_name, c.phone,c.id_number  from customer_bank_info as b left  join  customer_info as c  on b.customer_number = c.customer_number where c.order_number = '{$dealInfo['number']}'";
      
        $userInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();

        if($dealInfo['user_id']){
            $sql = "select idno,real_name,mobile from firstp2p_user where id = {$dealInfo['user_id']}";
            $firstp2pUserInfo = Yii::app()->phdb->createCommand($sql)->queryRow();
        
            if ($firstp2pUserInfo) {
                $firstp2pUserInfo['idno'] = GibberishAESUtil::dec($firstp2pUserInfo['idno'], Yii::app()->c->idno_key);
                $firstp2pUserInfo['mobile'] = GibberishAESUtil::dec($firstp2pUserInfo['mobile'], Yii::app()->c->idno_key);
            }

            $sql = "SELECT ub.user_id , ub.bankcard as card_number  ,b.name as bank_name FROM firstp2p_user_bankcard as ub left join firstp2p_bank as b on ub.bank_id = b.id where ub.user_id = {$dealInfo['user_id']} and ub.verify_status = 1";
            $firstp2pUserBankInfo= Yii::app()->fdb->createCommand($sql)->queryRow();

            if ($firstp2pUserBankInfo) {
                $firstp2pUserInfo['card_number'] = GibberishAESUtil::dec($firstp2pUserBankInfo['card_number'], Yii::app()->c->idno_key);
                $firstp2pUserInfo['bank_name'] = $firstp2pUserBankInfo['bank_name'];
            }
        }
      
        //原还款计划
        $sql = "SELECT s.reply_slip as reply_slip_url , r.*,s.id as reply_slip_id ,s.status as reply_slip_status from firstp2p_deal_repay as r LEFT JOIN firstp2p_deal_reply_slip as s on r.id = s.deal_repay_id where r.deal_id = {$deal_id} and type = 0  GROUP BY r.id order by r.repay_time desc";
        $repayPlan = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($repayPlan) {
            $total = count($repayPlan);
            $dealInfo['principal'] = 0;
            $dealInfo['interest'] = 0;
            $dealInfo['consult_fee'] = 0;
            $can_slip_ids = $this->getCanSlipIds($deal_id);
            foreach ($repayPlan as $key=>  &$value) {
                if ($value['status'] == 0) {
                    $dealInfo['principal'] += ($value['last_yop_repay_status'] == 2 ? 0 : $value['new_principal']);
                    $dealInfo['interest'] += $value['new_interest'];
                    $dealInfo['consult_fee'] += $value['consult_fee'];
                }
                $value['principal_refund_status_cn'] = self::$principal_refund_status[$value['principal_refund_status']];
                $value['interest_refund_status_cn'] = self::$principal_refund_status[$value['interest_refund_status']];

                $value['principal_repay_type_cn'] = self::$principal_repay_type[$value['principal_repay_type']];
                $value['reply_slip_status_cn'] = $value['reply_slip_id']? self::$reply_slip_status_cn[$value['reply_slip_status']]:'--';
                $value['repay_num'] = ($total-$key).'/'.$total;
                $value['is_repay'] = $value['status']>0?'已还':'未还';
                $value['is_repay_principal'] = ($value['status']==1 || $value['last_yop_repay_status'] == 2)? '已还' : ($value['principal'] > 0 && $value['paid_principal'] > 0 ?'部分还款':'未还');
                $value['is_repay_interest'] =  ($value['status']==1 )? '已还':($value['paid_interest']>0 &&  $value['interest'] > 0 ?'部分还款':'未还');
               
                $value['interest_repay_type_cn'] =  $value['is_repay_interest'] != '未还'? self::$principal_repay_type[$value['interest_repay_type']]:'';

                $value['true_repay_time'] =$value['paid_principal_time']>0 ? date('Y-m-d H:i:s', $value['paid_principal_time']):($value['true_repay_time']>0 ? date('Y-m-d H:i:s', $value['true_repay_time']):'--');
                $value['repay_time'] = date('Y-m-d H:i:s', $value['repay_time']);
                if($value['reply_slip_id']>3907 ){
                    $value['reply_slip'] =  self:: $OSS_URL.$value['reply_slip_url'];
                }else{
                    $value['reply_slip'] =  self:: $XF_OSS_URL.$value['reply_slip_url'];
                }

                $value['principal_refund_time'] =  $value['principal_refund_time'] >0 ? date('Y-m-d H:i:s',$value['principal_refund_time']):'--';
                $value['interest_refund_time'] =  $value['interest_refund_time'] >0 ? date('Y-m-d H:i:s',$value['interest_refund_time']):'--';
                $value['offline_repay_amount'] = $value['real_paid_principal']+$value['real_paid_interest'];

                $value['repay_slip_status'] = !in_array($value['id'], $can_slip_ids) ? 1 : 0;

                $value['principal'] = ($value['is_repay_principal'] == '已还') ? "0" : $value['principal'];

            }
        }
       
       
        return ['firstp2pUserInfo'=>$firstp2pUserInfo,'userInfo'=>$userInfo,'dealInfo'=> $dealInfo,'repayPlan'=>$repayPlan,'modifyLog'=> []];
    }
    public static $principal_repay_type=[
        '0'=>'原库导入', 1=>'自动划扣', 2=>'线下还款',3=>'凭证录入'
    ];
    public static $reply_slip_status_cn=[
        '0'=>'待审核', 1=>'已审核',
    ];
    /**
     * 新还款计划相关信息
     *
     * @param [type] $deal_id
     * @return void
     */
    public function getNewAboutDealRepayPlanInfo($deal_id)
    {
        if (empty($deal_id)) {
            return false;
        }
        $sql = "select d.repay_auth_flag, d.id ,d.user_id,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.start_time, d.create_time as d_create_time, d.borrow_amount as loan_amount, a.name as organization_name, o.product_name, o.transaction_number, o.create_time as o_create_time,o.customer_number,o.number from firstp2p_deal as d  left join order_info as o on d.approve_number = o.number  left join firstp2p_deal_agency  as a on d.advisory_id = a.id where d.id = {$deal_id}";
        $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $dealInfo['loan_amount'] = floatval($dealInfo['loan_amount']);
        $dealInfo['d_create_time'] = date('Y-m-d',$dealInfo['d_create_time']);
        $dealInfo['o_create_time'] = date('Y-m-d',$dealInfo['o_create_time']);

        $sql = "select b.card_number,b.bank_name, c.name as customer_name, c.phone,c.id_number  from customer_bank_info as b left  join  customer_info as c  on b.customer_number = c.customer_number where c.order_number = '{$dealInfo['number']}'";
      
        $userInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();


        $sql = "select idno,real_name,mobile from firstp2p_user where id = {$dealInfo['user_id']}";
        $firstp2pUserInfo = Yii::app()->fdb->createCommand($sql)->queryRow();
        if ($firstp2pUserInfo) {
            $firstp2pUserInfo['idno'] = GibberishAESUtil::dec($firstp2pUserInfo['idno'], Yii::app()->c->idno_key);
            $firstp2pUserInfo['mobile'] = GibberishAESUtil::dec($firstp2pUserInfo['mobile'], Yii::app()->c->idno_key);
        }

        $sql = "SELECT ub.user_id , ub.bankcard as card_number  ,b.name as bank_name FROM firstp2p_user_bankcard as ub left join firstp2p_bank as b on ub.bank_id = b.id where ub.user_id = {$dealInfo['user_id']} and ub.verify_status = 1";
        $firstp2pUserBankInfo= Yii::app()->fdb->createCommand($sql)->queryRow();

        if ($firstp2pUserBankInfo) {
            $firstp2pUserInfo['card_number'] = GibberishAESUtil::dec($firstp2pUserBankInfo['card_number'], Yii::app()->c->idno_key);
            $firstp2pUserInfo['bank_name'] = $firstp2pUserBankInfo['bank_name'];
        }

    
        $tmp_sql = '';
        //催收公司展示分配的借款人
        // $current_admin_id = \Yii::app()->user->id;
        // $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
        // if ($adminInfo['company_id'] > 0) {
        //     $now = time();
        //     $sql = "select * from firstp2p_create_new_repay_log where deal_id={$deal_id} and company_id={$adminInfo['company_id']} and status = 1";
        //     $createNewRepay = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        //     if ($createNewRepay) {
        //         $tmp_sql = "and add_company_id = {$adminInfo['company_id']}";
        //     } else {
        //         return [];
        //     }
        // } else {
           
        //     //先锋登录看到是借款人当前所属公司
        //     $now = time();
        //     $sql = "select distribution.company_id from firstp2p_borrower_distribution  as distribution left join firstp2p_borrower_distribution_detail as detail  on distribution.id = detail.distribution_id where detail.user_id = {$dealInfo['user_id']} and  distribution.status = 1 and detail.status = 1 and distribution.end_time >= {$now} and distribution.start_time <= {$now} order by detail.id desc";
           
        //     $company_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        //     if ($company_info) {
        //         $tmp_sql = "and  company_id = {$company_info['company_id']}";
        //     } else {
        //         return [];
        //     }
        // }
        //原还款计划
        $sql = "select id,principal_refund_status,principal_repay_type, true_repay_time, last_yop_repay_status, paid_principal,paid_interest,id,principal,interest,new_principal,new_interest,status,repay_time,consult_fee from firstp2p_deal_repay where deal_id = {$deal_id}  and type = 0  {$tmp_sql} order by repay_time desc";
        $repayPlan = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($repayPlan) {
            $total = count($repayPlan);
            $dealInfo['principal'] = 0;
            $dealInfo['interest'] = 0;
          
            $dealInfo['consult_fee'] = 0;
            foreach ($repayPlan as $key=>  &$value) {
                if ($value['status'] == 0) {
                    $dealInfo['principal'] +=  ($value['last_yop_repay_status'] == 2 ? 0: $value['principal']);
                    $dealInfo['interest'] += $value['interest'];
                    $dealInfo['consult_fee'] += $value['consult_fee'];
                }
                $value['principal_refund_status_cn'] = self::$principal_refund_status[$value['principal_refund_status']];
                $value['interest_refund_status_cn'] = self::$principal_refund_status[$value['interest_refund_status']];

                $value['principal_repay_type_cn'] = self::$principal_repay_type[$value['principal_repay_type']];
                $value['reply_slip_status_cn'] = $value['reply_slip_id']? self::$reply_slip_status_cn[$value['reply_slip_status']]:'--';
                $value['repay_num'] = ($total-$key).'/'.$total;
                $value['is_repay'] = $value['status']>0?'已还':'未还';
                $value['is_repay_principal'] = ($value['status']==1 || $value['last_yop_repay_status'] == 2)? '已还' : ($value['principal'] > 0 && $value['paid_principal'] > 0 ?'部分还款':'未还');
                $value['is_repay_interest'] =  ($value['status']==1 )? '已还':($value['paid_interest']>0 &&  $value['interest'] > 0 ?'部分还款':'未还');
               
                $value['interest_repay_type_cn'] =  $value['is_repay_interest'] != '未还'? self::$principal_repay_type[$value['interest_repay_type']]:'';

                $value['true_repay_time'] =$value['paid_principal_time']>0 ? date('Y-m-d H:i:s', $value['paid_principal_time']):($value['true_repay_time']>0 ? date('Y-m-d H:i:s', $value['true_repay_time']):'--');
                $value['repay_time'] = date('Y-m-d H:i:s', $value['repay_time']);
                $value['reply_slip'] =  self:: $OSS_URL.$value['reply_slip_url'];
                $value['principal_refund_time'] =  $value['principal_refund_time'] >0 ? date('Y-m-d H:i:s',$value['principal_refund_time']):'--';
                $value['interest_refund_time'] =  $value['interest_refund_time'] >0 ? date('Y-m-d H:i:s',$value['interest_refund_time']):'--';
                $value['offline_repay_amount'] = $value['real_paid_principal']+$value['real_paid_interest'];

                $value['principal'] = ($value['is_repay_principal'] == '已还') ? "0" : $value['principal'];
            }
        }

        $is_has_new_plan = false;
        //新还款计划
        $sql = "select true_repay_time, last_yop_repay_status,paid_principal,paid_interest,id,principal,interest,new_principal,new_interest,status,repay_time,consult_fee,paid_principal_time from firstp2p_deal_repay where deal_id = {$deal_id}  and type = 1  {$tmp_sql} order by repay_time desc";
        $newRepayPlan = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        $newRepayInfo = [];
        $newRepayInfo['need_repay_capital'] = 0;
        $newRepayInfo['need_repay_interest'] = 0;

        if ($newRepayPlan) {
            $is_has_new_plan = true;
            $total = count($newRepayPlan);
           
            $sql = "select * from firstp2p_create_new_repay_log where deal_id = {$deal_id} order by id desc ";
            $_newRepayInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();

            $newRepayInfo['new_wait_capital'] = $_newRepayInfo['after_wait_capital'];
            $newRepayInfo['new_wait_interest'] = $_newRepayInfo['after_wait_interest'];
            $newRepayInfo['new_wait_all'] = $_newRepayInfo['after_wait_capital']+$_newRepayInfo['after_wait_interest'];
            $newRepayInfo['new_youhui'] =  bcsub(($_newRepayInfo['before_wait_capital']+$_newRepayInfo['before_wait_interest']), $newRepayInfo['new_wait_all'], 2);

           
            foreach ($newRepayPlan as $key=>  &$value) {
                //新的还款计划不支持部分还款，所以可以这样用
                $newRepayInfo['need_repay_capital'] += bcsub($value['new_principal'], $value['paid_principal'], 2);
                $newRepayInfo['need_repay_interest'] += bcsub($value['new_interest'], $value['paid_interest'], 2);

                $value['repay_num'] = ($total-$key).'/'.$total;
                $_repayPlanNum[$value['id']] = '第'.($total-$key).'期';
                $value['is_repay'] = $value['status']>0 ||$value['last_yop_repay_status']==2?'已还':'未还';
                $value['true_repay_time'] = $value['true_repay_time']>0 ? date('Y-m-d H:i:s', $value['true_repay_time']):'--';
                $value['repay_time'] = date('Y-m-d H:i:s', $value['repay_time']);
            }
        }

        $sql = "select * from xf_borrower_bank_info_modify_log where user_id = {$dealInfo['user_id']} and status = 1 order by id desc  ";
        $bankcardModifyLog = Yii::app()->phdb->createCommand($sql)->queryRow();
        $newBankInfo = [];
        $is_has_bankcard_modify = false;
        if ($bankcardModifyLog) {
            $is_has_bankcard_modify = true;
            $newBankInfo['bank_mobile'] = $bankcardModifyLog['after_bank_mobile'];
            $newBankInfo['bankcard'] = $bankcardModifyLog['after_bankcard'];
            $newBankInfo['bank_name'] = $bankcardModifyLog['after_bank_name'];
        }

        return ['firstp2pUserInfo'=>$firstp2pUserInfo,'is_has_bankcard_modify'=>$is_has_bankcard_modify,'newBankInfo'=>$newBankInfo,'userInfo'=>$userInfo,'dealInfo'=> $dealInfo,'repayPlan'=>$repayPlan,'newRepayPlan'=> $newRepayPlan,'newRepayInfo'=>$newRepayInfo,'is_has_new_plan'=>$is_has_new_plan];
    }

    public static $principal_refund_status=[
        0=>'未退款',1=>'已退款'
    ];
    /**
     * 获取单条还款计划信息
     *
     * @param [type] $id
     * @return void
     */
    public function getRepayPlanInfo($id)
    {
        $sql = "select id,principal,interest,new_principal,new_interest,true_repay_time,repay_time from firstp2p_deal_repay where id = {$id}";
        $repayPlan = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        if (!$repayPlan) {
            return false;
        }
        $repayPlan['repay_time'] = date('Y-m-d H:i:s', $repayPlan['repay_time']);
        return $repayPlan;
    }

    /**
     * 更新还款计划
     * 废弃
     * @param [type] $params
     * @return void
     */
    public function updateRepayPlan($params)
    {
        try {
            Yii::app()->cmsdb->beginTransaction();
            if (empty($params['id'])) {
                throw new Exception('还款计划id不能为空');
            }
            if ($params['new_principal'] < 0) {
                throw new Exception('修改后待还本金不得小于0');
            }
            if ($params['new_interest'] < 0) {
                throw new Exception('修改后待还利息不得小于0');
            }
           
            $sql = "select * from firstp2p_deal_repay where id = {$params['id']} for update";
            $repayInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (empty($repayInfo)) {
                throw new Exception('还款计划不存在');
            }

            $sql = "select * from firstp2p_deal where id = {$repayInfo['deal_id']}";
            $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$dealInfo) {
                throw new Exception('借款记录不存在');
            }

            if ($dealInfo['repay_auth_flag']==1) {
                throw new Exception('借款记录已经审核，不能继续修改还款计划');
            }

            if ($params['new_principal'] > $repayInfo['principal']) {
                throw new Exception('修改后待还本机不得大于原待还本金');
            }
            if ($params['new_interest'] > $repayInfo['interest']) {
                throw new Exception('修改后待还利息不得大于原待还利息');
            }
            $now = time();
            $repay_time = $params['repay_flag'] == 1?$now:0;
            $sql = "update firstp2p_deal_repay set true_repay_time = {$repay_time}, new_principal = {$params['new_principal']} ,new_interest = {$params['new_interest']} where id = {$params['id']}";
            $updateRes = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($updateRes===false) {
                throw new Exception('数据更新失败，请重试');
            }
            $add_id = Yii::app()->request->userHostAddress;
            $add_user_id = \Yii::app()->user->id;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $remark = $params['repay_flag'] ==1? '修改状态为【已还】;' :'';
            if ($params['new_principal'] != $repayInfo['new_principal']) {
                $remark .="【原应还本金】由".$repayInfo['new_principal']."修改为".$params['new_principal'].";";
            }
            if ($params['new_interest'] != $repayInfo['new_interest']) {
                $remark .="【原应还利息】由".$repayInfo['new_interest']."修改为".$params['new_interest'].";";
            }
            $sql = "insert into firstp2p_deal_repay_modify_log (`deal_id`,`deal_repay_id`,`new_principal`,`new_interest`,`add_user_id`,`add_user_name`,`add_ip`,`add_time`,`remark`) value ({$repayInfo['deal_id']},{$repayInfo['id']},{$params['new_principal']},{$params['new_interest']},$add_user_id,'$username','$add_id',$now,'$remark')";
            $insertRes = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($insertRes === false) {
                throw new Exception('写入更新日志失败，请重试');
            }
            Yii::app()->cmsdb->commit();
            return true;
        } catch (Exception  $e) {
            Yii::app()->cmsdb->rollback();
            throw $e;
        }
    }


    public function getCsCompany()
    {
        $tmp_sql = '';
        //催收公司展示分配的借款人
        $current_admin_id = \Yii::app()->user->id;
        $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
        if ($adminInfo['company_id'] > 0) {
            $tmp_sql = 'where id = '.$adminInfo['company_id'] ;
        }

        $sql ="select * from firstp2p_cs_company ".$tmp_sql;
        $data = Yii::app()->cmsdb->createCommand($sql)->queryAll();
        return $data;
    }

    /**
      * 获取还款成功记录
      *
      * @param [type] $params
      * @return void
      */
    public function getRepaySuccessList($params)
    {
        $where = '';
        
        $condition = ['r.last_yop_repay_status = 2 '];//'d.xf_last_repay_time > 0','d.repay_auth_flag  = 2 ',

        //订单编号
        if (!empty($params['number'])) {
            $condition[] = " d.approve_number = '".trim($params['number'])."'";
        }
    
        if (!empty($params['deal_id'])) {
            $condition[] = " d.id = ".trim($params['deal_id']);
        }
        if (!empty($params['deal_name'])) {
            $condition[] = " d.name = '".trim($params['deal_name'])."'";
        }

        if (!empty($params['data_src'])) {
            $condition[] = " d.data_src = '".trim($params['data_src'])."'";
        }

        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }
        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(',', $user_query);
            $user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
            if ($user_info) {
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }
     
        //还款时间
        if (!empty($params['repay_start'])) {
            $condition[] = " ( r.paid_principal_time >= '".strtotime($params['repay_start'])."' or r.paid_interest_time >= '".strtotime($params['repay_start'])."' )";
        }
        if (!empty($params['repay_end'])) {
            $condition[] = " ( r.paid_principal_time < '".(strtotime($params['repay_end'])+86400)."' or r.paid_interest_time < '".(strtotime($params['repay_end'])+86400)."' )";
        }

        if (!empty($params['cs_company'])) {
            $condition[] = " r.company_id = ".intval($params['cs_company']);
        }
    
        //催收公司展示分配的借款人
        $current_admin_id = \Yii::app()->user->id;
        $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
        if ($adminInfo['company_id'] > 0) {
            $condition[]= " r.type = 1 and r.company_id = {$adminInfo['company_id']}";
        }
         
        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
            
        $result = [];
        $count_sql = "select count(1) from firstp2p_deal_repay as r left join  firstp2p_deal as d on d.id = r.deal_id {$where} ";
        //echo $count_sql;
        //die;
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select d.data_src, r.paid_type,r.company_id, r.paid_type ,d.xf_last_repay_time,d.repay_status, d.approve_number as number, d.id as deal_id ,d.deal_src, d.name as deal_name, r.paid_principal,r.paid_interest,r.paid_principal_time,r.paid_interest_time,r.true_repay_time,r.id ,r.user_id,r.principal,r.interest from firstp2p_deal_repay as r left join firstp2p_deal as d on r.deal_id = d.id {$where} order by r.true_repay_time desc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
                
            $approve_number_array  = ArrayUntil::array_column($result, 'number');
            if ($approve_number_array) {
                $approve_numbers = "'".implode("','", $approve_number_array)."'";
                
                $_organization_name = Yii::app()->cmsdb->createCommand("select organization_name,product_name,number from  order_info  where  number in ({$approve_numbers}) ")->queryAll();
                if ($_organization_name) {
                    foreach ($_organization_name as  $item) {
                        $organization_name[$item['number']] = $item;
                    }
                }
            }
            $user_ids =   ArrayUntil::array_column($result, 'user_id');
            if ($user_ids) {
                $user_ids = implode(',', $user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key);
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }
            $company_box = [];
            $company_ids =   implode(',', ArrayUntil::array_column($result, 'company_id'));
            $sql ="select * from firstp2p_cs_company where id in ($company_ids) ";
            $company = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if ($company) {
                $company_box = ArrayUntil::array_column($company, 'name', 'id');
            }

            foreach ($result as &$value) {
                $value+= $user_info[$value['user_id']]?:['customer_name'=>'','id_number'=>'','phone'=>''];
                $value+=$organization_name[$value['number']];
                $value['cs_company'] = isset($company_box[$value['company_id']]) && $value['paid_type']==1?$company_box[$value['company_id']] :'';
                $value['paid_interest_time'] = $value['paid_interest_time']>0? date('Y-m-d H:i:s', $value['paid_interest_time']):'--';
                $value['paid_principal_time'] = $value['paid_principal_time']>0? date('Y-m-d H:i:s', $value['paid_principal_time']):'--';
                $value['true_repay_time'] = $value['true_repay_time']>0? date('Y-m-d H:i:s', $value['true_repay_time']):'--';
                $value['repay_status_cn'] = self::$repay_status_cn[$value['repay_status']];
                $value['paid_type'] = $value['paid_type']>0? '三方催收还款':'系统还款';
                $value['data_src_cn'] = self::$data_src_cn[$value['data_src']];

            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }

    public function getBorrowerList($params)
    {
        $where = '';
        
        if (!empty($params['phone'])) {
            $phone = GibberishAESUtil::enc(trim($params['phone']), Yii::app()->c->idno_key);
            $condition[] = " a.mobile = '".$phone."'";
        }
        if (!empty($params['user_name'])) {
            $condition[] = " a.real_name = '".trim($params['user_name'])."'";
        }
        if (!empty($params['bankcard'])) {
            $bankcard = GibberishAESUtil::enc(trim($params['bankcard']), Yii::app()->c->idno_key);
            $condition[] = " a.bankcard = '".$bankcard."'";
        }
        if (!empty($params['id_number'])) {
            $id_number = GibberishAESUtil::enc(trim($params['id_number']), Yii::app()->c->idno_key);
            $condition[] = " a.idno = '".$id_number."'";
        }
        if (!empty($params['user_id'])) {
            $condition[] = " a.user_id = '".trim($params['user_id'])."'";
        }
        if (!empty($params['id_type'])) {
            $condition[] = " a.id_type = '".trim($params['id_type'])."'";
        }

        if (!empty($params['user_ids'])) {
            $condition[] = " a.user_id  in (".implode(',', $params['user_ids']).")";
        }

        //分案状态
        if (isset($params['fa_status']) ) {
            if($params['fa_status'] == 1){
                $condition[] = " d.type =0 ";
            }
            if($params['fa_status'] == 2){
                $condition[] = " d.type =1 ";
            }
            if($params['fa_status'] == 3){
                $condition[] = " d.id is null  ";
            }
        }
        //分案公司
        if (!empty($params['fa_company_name'])) {
            $condition[] = " d.id = '".trim($params['fa_company_name'])."'";
        }
        // 企业状态
        if (!empty($params['company_user_status'])) {
            $t = trim($params['company_user_status']);
            if($t == '存续'){
                $condition[] = "  a.company_user_status in ('存续','存续（在营、开业、在册）','在业','迁出') ";
            }elseif($t == '注销'){
                $condition[] = "  a.company_user_status in ('注销','吊销','撤销') ";
            }
        }
        //担保公司ID
        if (!empty($params['agency_id'])) {
            $sql = "SELECT distinct user_id  FROM firstp2p_deal WHERE agency_id='{$params['agency_id']}' and deal_status=4 ";
            $deal_user_ids = Yii::app()->cmsdb->createCommand($sql)->queryColumn();
            if (empty($deal_user_ids)) {
                $deal_user_ids[] = -1;
            }
            $condition[] = " a.user_id in (".implode(',', $deal_user_ids).")";
        }

        //联系状态
        if (!empty($params['contact_status'])) {
            $condition[] = " e.contact_status={$params['contact_status']}";
        }
        //催收状态
        if (!empty($params['legal_status'])) {
            $condition[] = " e.legal_status={$params['legal_status']}";
        }
        if ($params['borrower_src'] == 1) {
            $condition[] = " a.src_zz = 1";
        }
        if ($params['borrower_src'] == 2) {
            $condition[] = " a.src_ds = 1";
        }
        if ($params['borrower_src'] == 3) {
            $condition[] = " a.src_other = 1";
        }
        if (!empty($params['bind_type'])) {
            $condition[] = " a.bind_type = '".trim($params['bind_type'])."'";
        }
        if (!empty($params['status'])) {
            $condition[] =  $params['status'] == 1 ?"a.status = 1":"a.status in (6,9)";
        }
        
        //催收公司展示分配的借款人
        $current_admin_id = \Yii::app()->user->id;
        $now = time();
        $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
        if ($adminInfo['company_id'] > 0) {
            $sql = "select detail.user_id from firstp2p_borrower_distribution  as distribution left join firstp2p_borrower_distribution_detail as detail  on distribution.id = detail.distribution_id where distribution.company_id = {$adminInfo['company_id']} and  distribution.status = 1 and detail.status = 1 and distribution.end_time >= {$now} and distribution.start_time <={$now}";
            $user_ids  = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if ($user_ids) {
                $user_ids = ArrayUntil::array_column($user_ids, 'user_id');
                $condition[]= " a.user_id in (".implode(',', $user_ids).")";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
            //必须有搜索条件才展示数据
            /*
            if(empty($params['user_id']) && empty($params['user_name']) && empty($params['phone']) && empty($params['id_number']) && empty($params['bankcard']) ){
                return ['countNum' => 0, 'list' => []];
            }*/
        }
         

        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
        
        $result = [];
        $count_sql = "select  count(distinct a.user_id)  
                        from xf_borrower_bind_card_info_online a 
                        left join firstp2p_borrower_distribution_detail b on a.user_id=b.user_id and b.status=1 and b.f_status=1
                        LEFT JOIN firstp2p_borrower_distribution c on b.distribution_id=c.id and c.status=1 and c.start_time<{$now} and c.end_time>{$now} 
                        LEFT JOIN  firstp2p_cs_company d on d.id=c.company_id and d.status=0 
                        LEFT JOIN xf_borrower_call_back_log e on e.user_id=a.user_id 
                          {$where}   ";
        //echo $count_sql;die;
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select  a.*,d.name as company_name,d.type as company_type,e.legal_status,e.contact_status,e.question_3  
                    from xf_borrower_bind_card_info_online a 
                    left join firstp2p_borrower_distribution_detail b on a.user_id=b.user_id and b.status=1 and b.f_status=1
                    LEFT JOIN firstp2p_borrower_distribution c on b.distribution_id=c.id and c.status=1 and c.start_time<{$now} and c.end_time>{$now} 
                    LEFT JOIN  firstp2p_cs_company d on d.id=c.company_id and d.status=0 
                    LEFT JOIN xf_borrower_call_back_log e on e.user_id=a.user_id 
                      {$where}
                    GROUP BY a.user_id 
                    order by a.user_id  desc
 LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            
            
            foreach ($result as &$value) {
                $value['idno'] =  GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key) ?: '-';
                $value['mobile'] =  GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key) ?: '-';
                $value['bankcard'] =  GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key) ?: '-';
                //$value['id_type'] = $value['id_type'] == 1?'身份证':'企业三证合一';//还款方式
                $value['status'] = self::$bind_status[$value['status']];
                $value['is_set_retail'] = $value['is_set_retail'] == 1?'是':'否';
                $value['src_other'] = $value['src_other'] == 1?'是':'否';
                $value['src_zz'] = $value['src_zz'] == 1?'是':'否';
                $value['src_ds'] = $value['src_ds'] == 1?'是':'否';
                $value['bind_type'] = self::$bind_type[$value['bind_type']];
                if(!empty($value['company_name'])){
                    if($value['company_type'] == 1){
                        $value['fenan_status_cn'] =  "已分-法催" ;
                        $value['cuishou_status_cn'] =  Yii::app()->c->xf_config['legal_status'][$value['legal_status']] ?: '-' ;
                    }
                    if($value['company_type'] == 0){
                        $value['fenan_status_cn'] =  "已分-电催" ;
                        $value['cuishou_status_cn'] =  "催收中" ;
                    }
                }else{
                    $value['fenan_status_cn'] = '未分案';
                    $value['cuishou_status_cn'] =  "-" ;
                }

                $value['cs_suggest_cn'] =  Yii::app()->c->xf_config['cs_suggest'][$value['cs_suggest']] ?: '-' ;
                $value['question_3_cn'] =  Yii::app()->c->xf_config['question_3'][$value['question_3']] ?: '-' ;
                $value['contact_status_cn'] =  Yii::app()->c->xf_config['contact_status'][$value['contact_status']] ?: '-' ;

                $value['count_deal'] = $value['borrow_amount'] = $value['borrow_interest'] = $value['wait_capital'] = $value['wait_interest'] = $value['paid_capital'] = $value['paid_interest'] = 0;
                if($value['id_type'] == 2){
                    $repay_sql = "SELECT  SUM(CASE WHEN a.status = 0 THEN a.new_principal ELSE 0 END) AS wait_capital , 
                        SUM(CASE WHEN a.status = 0 THEN a.new_interest ELSE 0 END) AS wait_interest ,
                         SUM(a.paid_principal) AS paid_capital ,  SUM(a.paid_interest) AS paid_interest, b.borrow_amount, b.user_id,
                        count(DISTINCT a.deal_id) as count_deal
                        FROM firstp2p_deal_repay a
                        LEFT JOIN firstp2p_deal b on b.id=a.deal_id WHERE a.user_id={$value['user_id']} ";
                    $repay_info = Yii::app()->cmsdb->createCommand($repay_sql)->queryRow();
                    if($repay_info){
                        $value['count_deal'] = $repay_info['count_deal'];
                        $value['borrow_amount'] = $repay_info['borrow_amount'];
                        $value['borrow_interest'] = bcadd($repay_info['wait_interest'], $repay_info['paid_interest'],2);
                        $value['wait_capital'] = $repay_info['wait_capital'];
                        $value['wait_interest'] = $repay_info['wait_interest'];
                        $value['paid_capital'] = $repay_info['paid_capital'];
                        $value['paid_interest'] = $repay_info['paid_interest'];
                    }
                }

            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }
    

    public static $bind_status=[
        '1'=>'成功',
        '0'=>'未处理',
        '9'=>'失败',
        '6'=>'失败',
    ];
    public static $bind_type=[
        '1'=>'协议扣款',
        '2'=>'代扣款',
        '0'=>'',
    ];


    public function getFavouredPolicy()
    {
        $result['capital_policy'] = 0;
        $result['interest_policy'] = 0;
        $result['late_fee_policy'] = 0;
        $result['penalty_interest_policy'] = 0;
     
        $sql = "select * from firstp2p_favoured_policy order by id ";
        $favouredPolicy= Yii::app()->cmsdb->createCommand($sql)->queryRow();
        if (!$favouredPolicy) {
            return $result;
        }
        return $favouredPolicy;
    }

    /**
     * 更新还款计划
     *
     * @param [type] $params
     * @return void
     */
    public function createNewRepayPlan($params)
    {
        try {
            if (!in_array($params['repay_type'], [1,2])) {
                throw new Exception('请选择新还款计划类型');
            }
            $params['deal_id'] = intval($params['deal_id']);

            Yii::app()->cmsdb->beginTransaction();
            
            $sql = "select * from firstp2p_create_new_repay_log where deal_id = ".$params['deal_id'];
            $new_repay_log = Yii::app()->cmsdb->createCommand($sql)->queryRow();

            if ($new_repay_log) {
                throw new Exception('新还款计划已经创建-company_id:'. $new_repay_log['company_id']);
            }


            $sql = "select d.id ,d.user_id from firstp2p_deal as d   where d.id = {$params['deal_id']} for update ";
            $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$dealInfo) {
                throw new Exception('借款记录不存在');
            }

            //催收公司展示分配的借款人
            $current_admin_id = \Yii::app()->user->id;
            $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
            if ($adminInfo['company_id'] == 0) {
                throw new Exception('非三方公司，暂时不能创建');
            }
 
           
            $now = time();
            $sql = "select detail.user_id from firstp2p_borrower_distribution  as distribution left join firstp2p_borrower_distribution_detail as detail  on distribution.id = detail.distribution_id where distribution.company_id = {$adminInfo['company_id']} and  distribution.status = 1 and detail.status = 1 and distribution.end_time >= {$now} and distribution.start_time <= {$now} and detail.user_id = {$dealInfo['user_id']}";
            
            $user_check = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$user_check) {
                throw new Exception('该借款人分配状态不可用');
            }

            //查询剩余待还数据 todo 仅支持一次
            $_repay_info = Yii::app()->cmsdb->createCommand("select  deal_id , sum(new_principal-paid_principal) as new_principal ,sum(new_interest-paid_interest) as new_interest from firstp2p_deal_repay where deal_id  = {$params['deal_id']} and status = 0 and type = 0  ")->queryRow();
            if (!$_repay_info) {
                throw new Exception('该笔借款没有待还数据');
            }

            if (empty($params['repay_plan_num'])) {
                throw new Exception('请录入新还款计划');
            }
            $add_ip      = Yii::app()->request->userHostAddress;

            $repay_plan  = [];
            for ($i=1; $i <=$params['repay_num'] ; $i++) {
                $_plan['money'] = $params['repay_plan_num'][$i];
                $_plan['time']  = $params['repay_plan_time'][$i];
                $repay_plan[] = $_plan;
                # code...
            }
            $repay_plan_money = array_sum($params['repay_plan_num']);
               
            $all_input_money = $params['capital'] + $params['interest'] + $params['zhinajin'] + $params['faxi'];
            if ($repay_plan_money != $all_input_money) {
                throw new Exception('新还款计划录入总额不等于本金、利息、罚息、滞纳金总和');
            }


            $type = 2;
            $status = 0;
            if ($params['repay_type'] == 1) {
                $type = 1;
                $status = 1;
                $all_need_repay = bcadd($_repay_info['new_principal'], $_repay_info['new_interest'], 2);
                $all_input_repay = bcadd($params['capital'], $params['interest'], 2)+bcadd($params['zhinajin'], $params['faxi'], 2);
                if ($all_need_repay < $all_input_repay) {
                    throw new Exception('本息和大于剩余待还本息');
                }
                
                $favouredPolicy = $this->getFavouredPolicy();
                $capital_check = bcdiv(bcsub($_repay_info['new_principal'], $params['capital'], 2), $_repay_info['new_principal'], 2)*100;
                if ($capital_check > $favouredPolicy['capital_policy']) {
                    throw new Exception('本金优惠额度超过'.$favouredPolicy['capital_policy'].'%');
                }

                $interest_check = bcdiv(bcsub($_repay_info['new_interest'], $params['interest'], 2), $_repay_info['new_interest'], 2)*100;
                if ($interest_check > $favouredPolicy['interest_policy']) {
                    throw new Exception('利息优惠额度超过'.$favouredPolicy['interest_policy'].'%');
                }

                $old_sql = "update firstp2p_deal_repay set status = 5 where deal_id = {$params['deal_id']} and user_id = {$dealInfo['user_id']} and  status = 0 and type = 0";
                $res = Yii::app()->cmsdb->createCommand($old_sql)->execute();
                if ($res === false) {
                    throw new Exception('作废旧还款计划表失败');
                }
               
                $sql = 'INSERT INTO firstp2p_deal_repay (deal_id,user_id,repay_money,repay_time,principal,new_principal,type,create_time,add_user_id,add_company_id,add_ip,add_time) VALUES ';

                foreach ($repay_plan as $value) {
                    $repay_time = strtotime($value['time']);
                    $sql .= "({$params['deal_id']},{$dealInfo['user_id']},{$value['money']},{$repay_time},{$value['money']},{$value['money']},1,{$now},{$current_admin_id},{$adminInfo['company_id']},'{$add_ip}',{$now}),";
                }
                $sql = rtrim($sql, ',');
                
                $res = Yii::app()->cmsdb->createCommand($sql)->execute();
                if ($res === false) {
                    throw new Exception('写入还款计划表失败');
                }
            }

            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];

            $add_ip      = Yii::app()->request->userHostAddress;

            $repay_plan = json_encode($repay_plan);
            $sql = "INSERT INTO firstp2p_create_new_repay_log (company_id,deal_id,user_id,before_wait_capital,before_wait_interest,after_wait_capital,after_wait_interest,after_zhinajin,after_faxi,input_repay_plan,status,type,addtime,add_admin_id,add_user_name,add_ip) VALUES ";
            $sql .= "({$adminInfo['company_id']},{$params['deal_id']},{$dealInfo['user_id']},{$_repay_info['new_principal']},{$_repay_info['new_interest']},{$params['capital']},{$params['interest']},{$params['zhinajin']},{$params['faxi']},'{$repay_plan}',{$status},{$type},{$now},{$current_admin_id},'{$username}','{$add_ip}')";
            $sql = rtrim($sql, ',');
            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($res === false) {
                throw new Exception('写入创建新还款计划表记录表失败');
            }

            Yii::app()->cmsdb->commit();
        } catch (Exception  $e) {
            Yii::app()->cmsdb->rollback();
            throw $e;
        }
    }

      
    /**
    * 获取创建了新还款计划的借款标的
    * borrower/DealOrder/index
    * @param [type] $params
    * @return void
    */
    public function getNewRepayDealOrderList($params)
    {
        $where = '';

        //新还款计划标的列表
        if ($params['from']== 1) {
            $condition = [
                'l.status = 1'
            ];
        }
        //特殊协议还款列表
        if ($params['from']==2) {
            $condition = [
                'l.type = 2'
            ];
        }

        if (!empty($params['user_id'])) {
            $condition[] = " d.user_id = ".trim($params['user_id']);
        }
       
    
        if (!empty($params['deal_id'])) {
            $condition[] = " d.id = ".trim($params['deal_id']);
        }
        
        //借款来源
        if (!empty($params['data_src'])) {
            $condition[] = " d.data_src = '".trim($params['data_src'])."'";
        }

        if (!empty($params['add_user_name'])) {
            $condition[] = " l.add_user_name = '".trim($params['add_user_name'])."'";
        }

        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }
        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(',', $user_query);
            $user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
            if ($user_info) {
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }

        //末次还款时间
        if (!empty($params['last_repay_start'])) {
            $condition[] = " d.lately_repay_success_time >= '".strtotime($params['last_repay_start'])."'";
        }
        if (!empty($params['last_repay_end'])) {
            $condition[] = " d.lately_repay_success_time < '".(strtotime($params['last_repay_end'])+86400)."'";
        }

    
        //催收公司展示分配的借款人
        $current_admin_id = \Yii::app()->user->id;
        $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
        if ($adminInfo['company_id'] > 0) {
            $now = time();
            $sql = "select detail.user_id from firstp2p_borrower_distribution  as distribution left join firstp2p_borrower_distribution_detail as detail  on distribution.id = detail.distribution_id where distribution.company_id = {$adminInfo['company_id']} and  distribution.status = 1 and detail.status = 1 and distribution.end_time >=  {$now} and distribution.start_time <={$now}";
            $user_ids  = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if ($user_ids) {
                $user_ids = ArrayUntil::array_column($user_ids, 'user_id');
                $condition[]= "  d.user_id in (".implode(',', $user_ids).")";
            }
        }

        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
        
        $result = [];
        $count_sql = "select count(1) from firstp2p_deal as d left join firstp2p_create_new_repay_log as l on d.id = l.deal_id {$where} ";
        
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            //这里有点恶心，新待还本息和用到还【1】
            $sql = "select d.data_src, l.company_name, l.id as log_id, l.input_repay_plan,l.auth_user_name,l.auth_time, l.status, l.before_wait_capital,l.before_wait_interest,l.after_wait_capital,after_wait_interest,l.after_zhinajin,l.after_faxi,l.add_user_name,l.addtime,l.company_id, d.user_id,d.xf_last_repay_time, d.repay_auth_flag, d.id ,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.loantype, d.rate, d.start_time, d.create_time as d_create_time,d.borrow_amount,d.approve_number from firstp2p_deal as d left  join firstp2p_create_new_repay_log as l on d.id = l.deal_id   {$where} order by d.xf_last_repay_time desc ,l.id desc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
        
            $deal_id_array  = ArrayUntil::array_column($result, 'id');
            if ($deal_id_array) {
                $deal_ids = implode(',', $deal_id_array);
                $_before_repay_info = Yii::app()->cmsdb->createCommand("select count(1) as un_puy_num, deal_id , sum(if(last_yop_repay_status=2,0,principal) ) as principal ,sum(interest) as interest  from firstp2p_deal_repay where deal_id in ($deal_ids) and status = 0 and type = 0 group by deal_id")->queryAll();
                if ($_before_repay_info) {
                    foreach ($_before_repay_info as  $item) {
                        $before_repay_info[$item['deal_id']] = $item;
                    }
                }
                //这里有点恶心，新待还本息和用到
                $_current_repay_info = Yii::app()->cmsdb->createCommand("select count(1) as new_un_puy_num, deal_id , sum(new_principal) as new_principal ,sum(new_interest) as new_interest  from firstp2p_deal_repay where deal_id in ($deal_ids) and status = 1 and type = 1 group by deal_id")->queryAll();
                if ($_current_repay_info) {
                    foreach ($_current_repay_info as  $item) {
                        $current_repay_info[$item['deal_id']] = $item;
                    }
                }
            }

            //c.name as customer_name, c.phone,c.id_number
            $user_ids =   ArrayUntil::array_column($result, 'user_id');
            if ($user_ids) {
                $user_ids = implode(',', $user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key);
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }

            foreach ($result as &$value) {
                $value+= $user_info[$value['user_id']]?:['customer_name'=>'','id_number'=>'','phone'=>''];
                $value+=$before_repay_info[$value['id']]?:[];
                $value+=$current_repay_info[$value['id']]?:[];
                
              
                $before = $value['before_wait_capital']+$value['before_wait_interest'];
                $after = $value['after_wait_capital']+$value['after_wait_interest']+$value['after_zhinajin']+$value['after_faxi'];
                $value['jianmian'] = bcsub($before, $after, 2);
            
                $value['un_puy_num'] = $value['un_puy_num'].'期';
                $value['new_un_puy_num'] = ($value['new_un_puy_num']?:0).'期';
                $value['deal_loantype'] = Yii::app()->c->xf_config['loantype'][$value['loantype']];//还款方式
                $value['deal_src_cn'] = self::$deal_src_cn[$value['deal_src']];
                $value['status_cn'] = self::$repay_auth_status_cn [$value['status']];
                $value['repay_type'] = $value['repay_type'].'个月';
                $value['rate'] = floatval($value['rate']);
                $value['xf_last_repay_time'] = $value['xf_last_repay_time']>0? date('Y-m-d H:i:s', $value['xf_last_repay_time']):'--';
                $value['auth_time'] = $value['auth_time']>0? date('Y-m-d H:i:s', $value['auth_time']):'--';
                $value['auth_user_name'] = !empty($value['auth_user_name'])?$value['auth_user_name']:'--';
                $value['new_plan_num'] = count(json_decode($value['input_repay_plan']), true).'期';
                $value['data_src_cn'] = self::$data_src_cn[$value['data_src']];

            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }

    /**
    * 待审核 新还款计划相关信息
    *
    * @param [type] $log_id
    * @return void
    */
    public function getWaitAuditRepayPlanInfo($log_id)
    {
        if (empty($log_id)) {
            return false;
        }
        //新还款计划
        $sql = "select  *  from firstp2p_create_new_repay_log where id = {$log_id}  ";
        $logInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();

        if (!$logInfo) {
            return false;
        }
        $deal_id = $logInfo['deal_id'];


        $sql = "select d.repay_auth_flag, d.id ,d.user_id,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.start_time, d.create_time as d_create_time, d.borrow_amount as loan_amount, a.name as organization_name, o.product_name, o.transaction_number, o.create_time as o_create_time,o.customer_number,o.number from firstp2p_deal as d left join order_info as o on d.approve_number = o.number left join firstp2p_deal_agency  as a on d.advisory_id = a.id  where d.id = {$deal_id}";
        $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $dealInfo['loan_amount'] = floatval($dealInfo['loan_amount']);
        $dealInfo['d_create_time'] = date('Y-m-d',$dealInfo['d_create_time']);
        $dealInfo['o_create_time'] = date('Y-m-d',$dealInfo['o_create_time']);

        $sql = "select b.card_number,b.bank_name, c.name as customer_name, c.phone,c.id_number  from customer_bank_info as b left  join  customer_info as c  on b.customer_number = c.customer_number where c.order_number = '{$dealInfo['number']}'";
      
        $userInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();



        $sql = "select idno,real_name,mobile from firstp2p_user where id = {$dealInfo['user_id']}";
        $firstp2pUserInfo = Yii::app()->fdb->createCommand($sql)->queryRow();
        if ($firstp2pUserInfo) {
            $firstp2pUserInfo['idno'] = GibberishAESUtil::dec($firstp2pUserInfo['idno'], Yii::app()->c->idno_key);
            $firstp2pUserInfo['mobile'] = GibberishAESUtil::dec($firstp2pUserInfo['mobile'], Yii::app()->c->idno_key);
        }

        $sql = "SELECT ub.user_id , ub.bankcard as card_number  ,b.name as bank_name FROM firstp2p_user_bankcard as ub left join firstp2p_bank as b on ub.bank_id = b.id where ub.user_id = {$dealInfo['user_id']} and ub.verify_status = 1";
        $firstp2pUserBankInfo= Yii::app()->fdb->createCommand($sql)->queryRow();

        if ($firstp2pUserBankInfo) {
            $firstp2pUserInfo['card_number'] = GibberishAESUtil::dec($firstp2pUserBankInfo['card_number'], Yii::app()->c->idno_key);
            $firstp2pUserInfo['bank_name'] = $firstp2pUserBankInfo['bank_name'];
        }



        $tmp_sql = '';
        
        //原还款计划
        $sql = "select true_repay_time,last_yop_repay_status, paid_principal,paid_interest,id,principal,interest,new_principal,new_interest,status,repay_time,consult_fee from firstp2p_deal_repay where deal_id = {$deal_id}  and type = 0  {$tmp_sql} order by repay_time desc";
        $repayPlan = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($repayPlan) {
            $total = count($repayPlan);
            $dealInfo['principal'] = 0;
            $dealInfo['interest'] = 0;
          
            $dealInfo['consult_fee'] = 0;
            foreach ($repayPlan as $key=>  &$value) {
                if ($value['status'] == 0) {
                    $dealInfo['principal'] += ($value['last_yop_repay_status'] == 2 ? 0 :  $value['principal']);
                    $dealInfo['interest'] += $value['interest'];
                    $dealInfo['consult_fee'] += $value['consult_fee'];
                }
               
                $value['repay_num'] = ($total-$key).'/'.$total;
                $_repayPlanNum[$value['id']] = '第'.($total-$key).'期';
                $value['is_repay'] = $value['status']>0?'已还':'未还';
               
                $value['is_repay_principal'] = ($value['status']==1 || $value['last_yop_repay_status'] == 2 )? '已还' : ($value['principal'] > 0 && $value['paid_principal'] > 0 ?'部分还款':'未还');
                $value['is_repay_interest'] = ($value['status']==1 )? '已还' : ($value['interest'] > 0  && $value['paid_interest']  > 0 ?'部分还款':'未还');
                $value['true_repay_time'] = $value['true_repay_time']>0 ? date('Y-m-d H:i:s', $value['true_repay_time']):'--';
                $value['repay_time'] = date('Y-m-d H:i:s', $value['repay_time']);
                $value['principal'] = ($value['is_repay_principal'] == '已还') ? "0" : $value['principal'];
            }
        }

        $is_has_new_plan = true;
        
        $newRepayInfo = [];

        $newRepayInfo['new_wait_capital'] = $logInfo['after_wait_capital'];
        $newRepayInfo['new_wait_interest'] = $logInfo['after_wait_interest'];
        $newRepayInfo['new_wait_all'] = $logInfo['after_wait_capital']+$logInfo['after_wait_interest'];
        $newRepayInfo['new_youhui'] =  bcsub(($logInfo['before_wait_capital']+$logInfo['before_wait_interest']), $newRepayInfo['new_wait_all'], 2);

        $newRepayInfo['status'] = $logInfo['status'];
        $newRepayInfo['log_id'] = $logInfo['id'];
        

        $newRepayPlan = json_decode($logInfo['input_repay_plan'], true);

        $total = count($newRepayPlan);
        foreach ($newRepayPlan  as $key => &$value) {
            $value['repay_num'] = ($key+1).'/'.$total;
            $value['true_repay_time'] = '--';
            $value['is_repay'] = '未还';
        }
       

        $sql = "select * from xf_borrower_bank_info_modify_log where user_id = {$dealInfo['user_id']} and status = 1 order by id desc  ";
        $bankcardModifyLog = Yii::app()->phdb->createCommand($sql)->queryRow();
        $newBankInfo = [];
        $is_has_bankcard_modify = false;
        if ($bankcardModifyLog) {
            $is_has_bankcard_modify = true;
            $newBankInfo['bank_mobile'] = $bankcardModifyLog['after_bank_mobile'];
            $newBankInfo['bankcard'] = $bankcardModifyLog['after_bankcard'];
            $newBankInfo['bank_name'] = $bankcardModifyLog['after_bank_name'];
        }
        
        return ['firstp2pUserInfo'=>$firstp2pUserInfo,'is_has_bankcard_modify'=>$is_has_bankcard_modify ,'newBankInfo'=>$newBankInfo,'userInfo'=>$userInfo,'dealInfo'=> $dealInfo,'repayPlan'=>$repayPlan,'newRepayPlan'=> $newRepayPlan,'newRepayInfo'=>$newRepayInfo,'is_has_new_plan'=>$is_has_new_plan];
    }


    /**
     * 审核新还款计划
     *
     * @param [type] $params
     * @return void
     */
    public function auditRepayPlan($params)
    {
        try {
            Yii::app()->cmsdb->beginTransaction();
            if (!in_array($params['type'], [1,2]) || empty($params['log_id'])) {
                throw new Exception('参数错误');
            }
            $log_id = $params['log_id'];

            //新还款计划
            $sql = "select  *  from firstp2p_create_new_repay_log where id = {$log_id} for update ";
            $logInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();

            if ($logInfo['status']==1) {
                throw new Exception('已审核');
            }
            if ($logInfo['status']==2) {
                throw new Exception('已拒绝');
            }

        
            $current_user_id = \Yii::app()->user->id;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $now = time();

            $sql = "update firstp2p_create_new_repay_log set status = {$params['type']}, auth_admin_id = {$current_user_id},auth_user_name = '{$username}',auth_time = {$now},update_time = {$now} where id = {$log_id}";
            $updateRes = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($updateRes===false) {
                throw new Exception('数据更新失败，请重试');
            }
            if ($params['type'] == 1) {
                $old_sql = "update firstp2p_deal_repay set status = 5 where deal_id = {$logInfo['deal_id']} and user_id = {$logInfo['user_id']} and  status = 0 and type = 0";
                $res = Yii::app()->cmsdb->createCommand($old_sql)->execute();
                if ($res === false) {
                    throw new Exception('作废旧还款计划表失败');
                }

                $sql = 'INSERT INTO firstp2p_deal_repay (deal_id,user_id,repay_money,repay_time,principal,new_principal,type,create_time,add_user_id,add_company_id,add_ip,add_time) VALUES ';

                $repay_plan = json_decode($logInfo['input_repay_plan'], true);
    
                foreach ($repay_plan as $value) {
                    $repay_time = strtotime($value['time']);
                    $sql .= "({$logInfo['deal_id']},{$logInfo['user_id']},{$value['money']},{$repay_time},{$value['money']},{$value['money']},1,{$now},{$logInfo['add_admin_id']},{$logInfo['company_id']},'{$logInfo['add_ip']}',{$logInfo['addtime']}),";
                }
                $sql = rtrim($sql, ',');
                
                $res = Yii::app()->cmsdb->createCommand($sql)->execute();
                if ($res === false) {
                    throw new Exception('写入还款计划表失败');
                }
            }

            Yii::app()->cmsdb->commit();
            return true;
        } catch (Exception  $e) {
            Yii::app()->cmsdb->rollback();
            throw $e;
        }
    }



    /**
     * 修改银行卡号
     *
     * @param [type] $params
     * @return void
     */
    public function editUserBankStep1($params)
    {
        try {
            Yii::app()->phdb->beginTransaction();

            //throw new Exception('测试环境功能暂时不可用');
            //$params['user_id'] = 12130848;
            //throw new Exception('功能暂时不可用');

            if (!FunctionUtil::getLuhn($params['bankcard'])) {
                throw new Exception('银行卡号格式错误');
            }

            if (!FunctionUtil::IsMobile($params['bank_mobile'])) {
                throw new Exception('手机号码格式错误');
            }
            
            
            
            $sql = "select * from xf_borrower_bind_card_info_online where user_id = {$params['user_id']} for update ";
            $userInfo = Yii::app()->phdb->createCommand($sql)->queryRow();

            if (!$userInfo) {
                throw new Exception('借款人不存在');
            }

            //催收公司展示分配的借款人
            $current_admin_id = \Yii::app()->user->id;
            $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
            if ($adminInfo['company_id'] == 0) {
                throw new Exception('非三方公司，暂时不能修改借款人信息');
            }
 
            $now = time();
            $sql = "select detail.user_id from firstp2p_borrower_distribution  as distribution left join firstp2p_borrower_distribution_detail as detail  on distribution.id = detail.distribution_id where distribution.company_id = {$adminInfo['company_id']} and  distribution.status = 1 and detail.status = 1 and distribution.end_time >= {$now} and distribution.start_time <= {$now} and detail.user_id = {$params['user_id']}";
        
            $user_check = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$user_check) {
                throw new Exception('该借款人分配状态不可用');
            }
           

            $current_bankcard_mobile = GibberishAESUtil::dec(empty($userInfo['bank_mobile'])?$userInfo['mobile']:$userInfo['bank_mobile'], Yii::app()->c->idno_key);
            $current_bankcard  = GibberishAESUtil::dec(empty($userInfo['new_bankcard'])?$userInfo['bankcard']:$userInfo['new_bankcard'], Yii::app()->c->idno_key);

            if ($params['bankcard'] ==  $current_bankcard && $params['bank_mobile'] == $current_bankcard_mobile) {
                throw new Exception('银行卡信息相同 无需更换');
            }
            //请求易宝
            $borrower['request_no'] = $request_no = FunctionUtil::getRequestNo("BBBE");
            $borrower['idno']       = GibberishAESUtil::dec($userInfo['idno'], Yii::app()->c->idno_key);
            $borrower['bankcard']   = $params['bankcard'] ;
            $borrower['mobile']     = $params['bank_mobile'] ;
            $borrower['user_id']    = $params['user_id'];
            $borrower['real_name']  = $userInfo['real_name'];
            
            $re = $this->bindBankCardYopStep1($borrower);
            Yii::log("borrowerService  ".__FUNCTION__." Yop return :".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');

            //$re = ['status'=>'BIND_SUCCESS'];
            if (!$re) {
                throw new Exception('请求易宝接口异常');
            }
            $status     = self::$yibao_bind_status[$re['status']]?:9;

           
            $errormsg   = isset($re['errormsg'])?$re['errormsg']:'';
            $cardtop    = isset($re['cardtop'])?$re['cardtop']:'';
            $cardlast   = isset($re['cardlast'])?$re['cardlast']:'';
            $bankcode   = isset($re['bankcode'])?$re['bankcode']:'';
            $verifyStatus = isset($re['verifyStatus'])?$re['verifyStatus']:'';
            $yborderid  = isset($re['yborderid'])?$re['yborderid']:'';
            $remark = json_encode($re);
            $now = time();

            if (!in_array($status, [1,2])) {
                throw new Exception('易宝绑卡返回失败:'.$errormsg);
            }

            $add_ip      = Yii::app()->request->userHostAddress;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $sql = "INSERT INTO xf_borrower_bank_info_modify_log (user_id,current_bank_mobile,current_bankcard,after_bank_mobile,after_bankcard,after_bank_name,request_no,status,errormsg,cardtop,cardlast,bankcode,remark,yborderid,verifyStatus,add_user_id,add_user_name,add_ip,add_time) VALUES ";
            $sql .= "({$params['user_id']},'{$current_bankcard_mobile}','{$current_bankcard}','{$params['bank_mobile']}','{$params['bankcard']}','{$params['bank_name']}','{$request_no}',{$status},'{$errormsg}','{$cardtop}','{$cardlast}','{$bankcode}','{$remark}','{$yborderid}','{$verifyStatus}','{$current_admin_id}','{$username}','{$add_ip}','{$now}')";
            $sql = rtrim($sql, ',');
            // echo $sql;
            // die;
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                throw new Exception('写入换卡记录表失败');
            }

            Yii::app()->phdb->commit();
            return true;
        } catch (Exception  $e) {
            Yii::app()->phdb->rollback();
            throw $e;
        }
    }

    //BIND_SUCCESS ： 绑卡成功 TO_VALIDATE： 待短验 BIND_FAIL： 绑卡失败 BIND_ERROR： 绑卡异常(可重试) TIME_OUT： 超时失败 FAIL： 系统异常
    public static $yibao_bind_status = [
        'BIND_SUCCESS'=>1,
        'TO_VALIDATE'=>2,
        'BIND_FAIL'=>3,
        'BIND_ERROR'=>4,
        'TIME_OUT'=>5,
        'FAIL'=>6,
    ];

    /**
     * 修改银行卡号
     *
     * @param [type] $params
     * @return void
     */
    public function editUserBankStep2($params)
    {
        try {
            //throw new Exception('功能暂时不可用');


            Yii::app()->phdb->beginTransaction();
            
            $sql = "select * from xf_borrower_bank_info_modify_log where user_id = {$params['user_id']} order by id desc limit 1 for update ";
            $bankcardModifyLog = Yii::app()->phdb->createCommand($sql)->queryRow();

            if (!$bankcardModifyLog) {
                Yii::app()->phdb->rollback();
                throw new Exception('换卡申请记录不存在');
            }

            //催收公司展示分配的借款人
            $current_admin_id = \Yii::app()->user->id;
            $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
            if ($adminInfo['company_id'] == 0) {
                Yii::app()->phdb->rollback();
                throw new Exception('非三方公司，暂时不能修改借款人信息');
            }
 
            $now = time();
            $sql = "select detail.user_id from firstp2p_borrower_distribution  as distribution left join firstp2p_borrower_distribution_detail as detail  on distribution.id = detail.distribution_id where distribution.company_id = {$adminInfo['company_id']} and  distribution.status = 1 and detail.status = 1 and distribution.end_time >= {$now} and distribution.start_time <= {$now} and detail.user_id = {$params['user_id']}";
        
            $user_check = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$user_check) {
                Yii::app()->phdb->rollback();
                throw new Exception('该借款人分配状态不可用');
            }

            $wait_modify_bank_mobile = $bankcardModifyLog['after_bank_mobile'];
            $wait_modify_bankcard  = $bankcardModifyLog['after_bankcard'];

            if ($params['bankcard'] !=  $wait_modify_bankcard || $params['bank_mobile'] != $wait_modify_bank_mobile) {
                Yii::app()->phdb->rollback();
                throw new Exception('银行卡信息不一致,请重启发起绑卡申请');
            }
            //请求易宝

            //请求易宝
            $borrower['request_no'] = $bankcardModifyLog['request_no'];
            $borrower['validatecode']   = $params['sms_code'] ;
                 
            $re = $this->bindBankCardYopStep2($borrower);
            //$re = ['status'=>'BIND_SUCCESS'];
            Yii::log("borrowerService  ".__FUNCTION__." Yop return :".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');

            if (!$re) {
                throw new Exception('请求易宝接口异常');
            }
 
            $status     = self::$yibao_bind_status[$re['status']]?:9;
            $errormsg   = isset($re['errormsg'])?$re['errormsg']:'success';
            $cardtop    = isset($re['cardtop'])?$re['cardtop']:'';
            $cardlast   = isset($re['cardlast'])?$re['cardlast']:'';
            $bankcode   = isset($re['bankcode'])?$re['bankcode']:'';
            $verifyStatus = isset($re['verifyStatus'])?$re['verifyStatus']:'';
            $yborderid  = isset($re['yborderid'])?$re['yborderid']:'';
            $remark = json_encode($re);
            $now = time();

            $sql = "UPDATE xf_borrower_bank_info_modify_log SET 
                    status = {$status} ,
                    errormsg = '{$errormsg}',
                    cardtop = '{$cardtop}',
                    cardlast = '{$cardlast}',
                    bankcode = '{$bankcode}',
                    remark = '{$remark}',
                    yborderid = '{$yborderid}',
                    verifyStatus = '{$verifyStatus}' 
                where id = {$bankcardModifyLog['id']}";
              
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                Yii::app()->phdb->rollback();
                throw new Exception('写入换卡记录表失败');
            }

            if ($status == 1) {
                $wait_modify_bank_mobile = GibberishAESUtil::enc($wait_modify_bank_mobile, Yii::app()->c->idno_key);
                $wait_modify_bankcard = GibberishAESUtil::enc($wait_modify_bankcard, Yii::app()->c->idno_key);
                $sql = "UPDATE xf_borrower_bind_card_info_online set bank_mobile = '{$wait_modify_bank_mobile}', new_bankcard = '{$wait_modify_bankcard}', cardtop='{$cardtop}', cardlast='{$cardlast}', bankcode='{$bankcode}' where user_id = {$params['user_id']}";
                $res = Yii::app()->phdb->createCommand($sql)->execute();
                if ($res === false) {
                    throw new Exception('写入创建新还款计划表记录表失败');
                }
                Yii::app()->phdb->commit();
            } else {
                Yii::app()->phdb->commit();
                throw new Exception('易宝绑卡失败:'. $errormsg);
            }
        } catch (Exception  $e) {
            throw $e;
        }
    }


    public function bindBankCardYopStep1($data=[])
    {
        $request = new YopRequest(YopConfig::APP_KEY, YopConfig::PRIVATE_KEY);
        $request->addParam("merchantno", YopConfig::MERCHANT_NO);

        //加入请求参数
        $request->addParam("identityid", $data['user_id']);//商户生成的用户唯一标识
        $request->addParam("cardno", $data['bankcard']);//银行卡号
        $request->addParam("idcardno", $data['idno']);//身份证号
        $request->addParam("username", $data['real_name']);//姓名
        $request->addParam("phone", $data['mobile']);//手机号
        $request->addParam("requestno", $data['request_no']);//商户生成的唯一绑卡请求号


        $request->addParam("requesttime", date('Y-m-d H:i:s'));//请求时间
        $request->addParam("identitytype", "USER_ID");
        $request->addParam("idcardtype", "ID");//身份证号
        $request->addParam("issms", "true");//短信
        $request->addParam("authtype", "COMMON_FOUR");//固定值
       
        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/paperorder/unified/auth/request", $request);
       
        //code...
        if ($response->validSign==1) {
            $re = $this->object_array($response);
            
            if (strtoupper($re['state']) == 'SUCCESS') {
                return $re['result'];
            }
        }
        return false;
    }


    public function bindBankCardYopStep2($data=[])
    {
        $request = new YopRequest(YopConfig::APP_KEY, YopConfig::PRIVATE_KEY);
        $request->addParam("merchantno", YopConfig::MERCHANT_NO);

        //加入请求参数
        $request->addParam("requestno", $data['request_no']);//商户生成的唯一绑卡请求号
        $request->addParam("validatecode", $data['validatecode']);//短信验证码， 6 位数字

        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/paperorder/auth/confirm", $request);

        if ($response->validSign==1) {
            $re = $this->object_array($response);
            
            if (strtoupper($re['state']) == 'SUCCESS') {
                return $re['result'];
            }
        }
        return false;
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

    public function queryBindBankCard($user_id)
    {
        $request = new YopRequest(YopConfig::APP_KEY, YopConfig::PRIVATE_KEY);
        $request->addParam("merchantno", YopConfig::MERCHANT_NO);

        //加入请求参数
        $request->addParam("identityid", $user_id);//商户生成的用户唯一标识
        $request->addParam("identitytype", "USER_ID");
      
       
        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/paperorder/auth/bindcard/list", $request);
       
        //code...
        if ($response->validSign==1) {
            $re = $this->object_array($response);
            
            if (strtoupper($re['state']) == 'SUCCESS') {
                return $re['result'];
            }
        }
        return false;
    }

    public function getUserBindCard($user_id)
    {
        $sql = "select * from xf_borrower_bind_card_info_online where user_id = {$user_id} ";
        $userInfo = Yii::app()->phdb->createCommand($sql)->queryRow();
        $res = $this->queryBindBankCard($user_id);
     
        Yii::log("borrowerService  ".__FUNCTION__." Yop return :".json_encode($res, JSON_UNESCAPED_UNICODE), 'info');

        if (!$res) {
            return false;
        }
        $list = json_decode($res['cardlist'], true);
        foreach ($list as $card) {
            if ($card['verifystatus'] == true && $card['cardlast'] == $userInfo['cardlast'] && $card['cardtop'] == $userInfo['cardtop']) {
                return $card['bindid'];
            }
        }
        return false;
    }




    public function addRepayPlanRefund($params)
    {

        try {

            $file = $this->upload_rar('business_license');
            if($file['code']){
                throw new Exception($file['info']);
                return $file;
            }
            $url = 'replay_plan_refund_info/'.$file['data'];
            $upload_oss = $this->upload_oss('./'.$file['data'], $url);
            if($upload_oss === false){
                throw new Exception('上传OSS失败');
            }
            $sql = "select *  from firstp2p_deal_repay where id = {$params['repay_id']}";
            $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if(empty($repayInfo)){
                throw new Exception('还款计划不存在,请核实');
            }
            //todo
            if($repayInfo['last_yop_repay_status'] != 2 ){
                throw new Exception('该笔还款未成功划扣,请核实');
            }
            $refund_date = strtotime($params['refund_date']);
            $current_admin_id = \Yii::app()->user->id;
            $sql = "update firstp2p_deal_repay set action_refund_admin_id = $current_admin_id, principal_refund_status = 1, principal_refund_time = {$refund_date},reply_slip = '{$url}' where id = {$params['repay_id']}";
            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('更新失败，请稍后重试');
            }
        } catch (\Exception  $e) {
            throw $e;
        }
    }


    public function upload_rar($name)
    {
        $file  = $_FILES[$name];
        $types = array('rar' , 'zip' , '7z', 'pdf', 'jpg', 'png');
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
        if (!in_array($file_type, $types)) {
            return array('code' => 2007 , 'info' => '压缩文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000, 99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir, 0777, true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建压缩文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"], './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存压缩文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存压缩文件失败' , 'data' => '');
        }
    }
    /**
     * $upload_oss = $this->upload_oss('./'.$file['data'], 'assignee_info/'.$file['data']);
     * @return void
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
     * 还款凭证补录
     */
    public function addRepayPlanVoucher($params)
    {

        try {
            if(empty($params['repay_date'])){
                throw new Exception('请输入还款时间');
            }

            $file = $this->upload_rar('business_license');
            if($file['code']){
                throw new Exception($file['info']);
                return $file;
            }
            $url = 'replay_plan_voucher_info/'.$file['data'];
            $upload_oss = $this->upload_oss('./'.$file['data'], $url);
            if($upload_oss === false){
                throw new Exception('上传OSS失败');
            }

            $sql = "select *  from firstp2p_deal_repay where id = {$params['repay_id']} ";
            $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if(empty($repayInfo)){
                throw new Exception('还款计划不存在,请核实');
            }
            if($repayInfo['status'] != 0){
                throw new Exception('还款计划非待还状态');
            }
    
            if($repayInfo['repay_time'] > strtotime($params['repay_date']) ){
                throw new Exception('还款日期不得早于应还日期');
            }

            //获取三方公司ID
            $company_id = $this->getCompanyId(  $repayInfo['deal_id']);
            if(!$company_id){
                throw new Exception('未查询到分配有效的第三方公司');
            }


            $current_admin_id = \Yii::app()->user->id;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];

            $add_ip = Yii::app()->request->userHostAddress;
            $repay_date = strtotime($params['repay_date']);
            $now = time();
            $sql = "INSERT INTO firstp2p_deal_reply_slip (repay_content,deal_id,deal_repay_id,repay_time,reply_slip,addtime,add_admin_id,company_id) VALUES ";
            $sql .= "(2,{$repayInfo['deal_id']},{$repayInfo['id']},$repay_date,'$url', $now,$current_admin_id, $company_id)";

            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('更新失败，请稍后重试');
            }
        } catch (\Exception  $e) {
            throw $e;
        }
    }

    /**
     * 还款凭证补录-xin
     */
    public function addNewRepayPlanVoucher($params)
    {

        try {
            if(empty($params['repay_date'])){
                throw new Exception('请输入还款时间');
            }
            $sql = "select *  from firstp2p_deal_repay where id = {$params['repay_id']} and deal_id={$params['deal_id']} ";
            $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if(empty($repayInfo)){
                throw new Exception('还款计划不存在,请核实');
            }
            if($repayInfo['status'] != 0){
                throw new Exception('还款计划非待还状态');
            }
            if($repayInfo['repay_time'] > strtotime($params['repay_date']) ){
                throw new Exception('还款日期不得早于应还日期');
            }

            $current_admin_id = \Yii::app()->user->id;
            $repay_date = strtotime($params['repay_date']);
            $now = time();
            $sql = "INSERT INTO firstp2p_deal_reply_slip (repay_content,deal_id,deal_repay_id,repay_time,reply_slip,addtime,add_admin_id,company_id) VALUES ";
            $sql .= "(2,{$repayInfo['deal_id']},{$repayInfo['id']},$repay_date,'{$params['file_path_url']}', $now,$current_admin_id, {$params['company_id']})";

            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('更新失败，请稍后重试');
            }
        } catch (\Exception  $e) {
            throw $e;
        }
    }

    public function getCompanyId( $deal_id){
        if(empty($deal_id) || !is_numeric($deal_id) ){
            return false;
        }

        $deal_sql = " SELECT * from firstp2p_deal WHERE id={$deal_id}";
        $deal_info = Yii::app()->cmsdb->createCommand($deal_sql)->queryRow();
        if(!$deal_info){
            return false;
        }

        $n_time = time();
        $sql = " SELECT bd.company_id from firstp2p_borrower_distribution_detail bdd 
LEFT JOIN firstp2p_borrower_distribution bd on bd.id=bdd.distribution_id and  bd.status=1 
 left join firstp2p_deal dl on dl.user_id=bdd.user_id and dl.id={$deal_id}
WHERE   bdd.user_id={$deal_info['user_id']} and bdd.status=1 and bd.start_time<=$n_time and bd.end_time>$n_time";
        $company_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        if(!$company_info){
            return false;
        }
        return  $company_info['company_id'];
    }

      /**
     * 审核凭证补录
     */
    public function auditRepayPlanVoucher($params)
    {

        try {
            
           
            $sql = "select *  from firstp2p_deal_reply_slip where id = {$params['id']} ";
            $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if(empty($repayInfo)){
                throw new Exception('记录不存在,请核实');
            }
            //todo 
            if($repayInfo['status'] == 1 ){
                throw new Exception('已审核');
            }

            $current_admin_id = \Yii::app()->user->id;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];

    
            $now = time();
            $sql ="UPDATE firstp2p_deal_reply_slip set status = 1,auth_user_name='{$username}',auth_admin_id=$current_admin_id,auth_time=$now where id = {$params['id']}";

          
        
            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('更新失败，请稍后重试');
            }
        } catch (\Exception  $e) {
            throw $e;
        }
    }


    /**
    * 退款管理
    * @return void
    */
    public function getRefundDealOrderList($params)
    {
        $where = '';
        $condition = ['r.last_yop_repay_status  = 2 and r.principal_refund_status = 1'];

        //借款标题
        if (!empty($params['deal_name'])) {
            $condition[] = " d.name = '".trim($params['deal_name'])."'";
        }
         //借款标题
         if (!empty($params['user_id'])) {
            $condition[] = " d.user_id = '".trim($params['user_id'])."'";
        }

        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }

        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(' and ', $user_query);
            $user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
            if ($user_info) {
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }


        //退款时间
        if (!empty($params['refund_start'])) {
            $condition[] = " r.principal_refund_time >= ".strtotime($params['refund_start']);
        }
        if (!empty($params['refund_end'])) {
            $condition[] = " r.principal_refund_time <= ".strtotime($params['refund_end'])+86400;
        }

        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
        
        $result = [];
        $count_sql = "select count(1) from firstp2p_deal_repay as r left join firstp2p_deal as d on d.id = r.deal_id   {$where} ";
        
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select r.id, r.deal_id,d.name,d.user_id,r.new_principal,r.principal_refund_time,r.reply_slip from firstp2p_deal_repay as r left join firstp2p_deal as d on d.id = r.deal_id   {$where} order by r.id desc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            
            $deal_id_array  = ArrayUntil::array_column($result, 'deal_id');
            if ($deal_id_array) {
                $deal_ids = implode(',', $deal_id_array);
                //原还款计划 待还
                $_repay_info = Yii::app()->cmsdb->createCommand("select id,deal_id,repay_time from firstp2p_deal_repay where deal_id in ($deal_ids)  and type = 0 order by repay_time asc ")->queryAll();
                if ($_repay_info) {
                    foreach ($_repay_info as $item) {
                        $repay_info[$item['deal_id']][] = $item['id'];
                    }
                }
            }

            //c.name as customer_name, c.phone,c.id_number
            $user_ids =   ArrayUntil::array_column($result, 'user_id');
            if ($user_ids) {
                $user_ids = implode(',', $user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key)?:'';
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }

    
            foreach ($result as &$value) {
                $value+= $user_info[$value['user_id']]?:['customer_name'=>'','id_number'=>'','phone'=>''];
                $value['refund_date'] = date('Y-m-d',$value['principal_refund_time']);
                $value['reply_slip'] = self:: $OSS_URL . $value['reply_slip'];
                $_info = $repay_info[$value['deal_id']];;
                $value['num'] = (array_search($value['id'],$_info)+1) .'/' .count($_info);
                
            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }

    public static $OSS_URL =  'https://admin-upload-file.oss-cn-beijing.aliyuncs.com/';
    public static $XF_OSS_URL =  'https://oss.xfuser.com/';


    public function getDiscountOrRepayAmount($params){

        $repay_ids = implode(',',$params['repay_ids']);
        $sql = "select sum(new_principal ) as new_principal_total , sum(new_interest ) as new_interest_total  from firstp2p_deal_repay where id in ( {$repay_ids})";
        $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        //还本金
        if($params['repay_content'] == 1){
            $deal_money = $repayInfo['new_principal_total'];
        }
        //还利息
        if($params['repay_content'] == 2){
            $deal_money = $repayInfo['new_interest_total'];
        }
         //还本金加利息
        if($params['repay_content'] == 3 || $params['repay_content'] == 4){
            $deal_money = $repayInfo['new_interest_total']+$repayInfo['new_principal_total'];
        }
       
        $data = ['repay_type'=>$params['repay_type']];
        //按折扣  计算金额
        if($params['repay_type'] == 1){
            $data['repay_amount'] = round($deal_money * $params['discount'] * 0.1,2);
        }
        //按金额 计算折扣
        if($params['repay_type'] == 2){
            $data['discount'] = round( (1 - ($deal_money - $params['repay_amount'])/$deal_money )*10   ,1);
            $data['discount'] = $data['discount']<=10 ? $data['discount'] : 10;
        }
        return $data;
    }

    //创建线下还款
    public function createOfflineRepay($params)
    {
        

        if(empty( $params['logo_path'])){
            throw new Exception('请上传打款凭证');
        }
        if(empty($params['repay_type'])){
            throw new Exception('请选择还款类型');
        }

        if(empty( $params['repay_content'])){
            throw new Exception('请选择还款内容');
        }

        if( $params['repay_type'] == 1 && empty($params['discount'])){
            throw new Exception('请输入折扣');
        }

        if( $params['repay_type'] == 2 && empty($params['repay_amount'])){
            throw new Exception('请输入还款金额');
        }

        if(empty($params['repay_ids'])){
            throw new Exception('请勾选还款计划');
        }

        try {

           
            $data = $this->getDiscountOrRepayAmount($params);
            $repay_amount = $params['repay_amount'];
            //按折扣  则获取金额
            if($params['repay_type'] == 1){
                $repay_amount = $data['repay_amount'];
            }
            $discount = $params['discount'];
            //按金额 则获取折扣
            if($params['repay_type'] == 2){
                $discount = $data['discount'];
            }
            

            Yii::app()->cmsdb->beginTransaction();

            $repay_id = implode(',',$params['repay_ids']);
            $sql= "select * from firstp2p_deal_repay where id in ({$repay_id}) for update";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if(empty($result)){
                throw new Exception('还款计划不存在');
            }

            $wait_amount = 0;
            foreach($result as $repay_item){

                /**
                 * 传 本金，不能有 本金 ，本+息的数据 
                    *传 利息，不能有 利息，本➕息的数据 
                    *传当期部分还，审核表加提交的数据不能超过 计划表的本息和
                    *'还款内容：1本金 2利息 3本息 4当期部分还款',
                 */
                if($params['repay_content'] == 1){
                    $sql = "select * from firstp2p_offline_repay as off_re left join firstp2p_offline_repay_detail as rd on rd.offline_repay_id = off_re.id   where rd.repay_id  = {$repay_item['id']} and off_re.repay_content != 2 ";
                    if(Yii::app()->cmsdb->createCommand($sql)->queryRow()){
                        throw new Exception('该笔还款计划仅剩余利息可还:'.$repay_item['id']);
                    }
                }
                if($params['repay_content'] == 2){
                    $sql = "select * from firstp2p_offline_repay as off_re left join firstp2p_offline_repay_detail as rd on rd.offline_repay_id = off_re.id   where rd.repay_id  = {$repay_item['id']} and off_re.repay_content != 1 ";
                    if(Yii::app()->cmsdb->createCommand($sql)->queryRow()){
                        throw new Exception('该笔还款计划仅剩余本金可还:'.$repay_item['id']);
                    }
                }

                if($params['repay_content'] == 3){
                    $sql = "select * from firstp2p_offline_repay as off_re left join firstp2p_offline_repay_detail as rd on rd.offline_repay_id = off_re.id   where rd.repay_id  = {$repay_item['id']} ";
                    if(Yii::app()->cmsdb->createCommand($sql)->queryRow()){
                        throw new Exception('该笔还款计划无待还金额:'.$repay_item['id']);
                    }
                }

                if($params['repay_content'] == 4){
                    $sql = "select * from firstp2p_offline_repay as off_re left join firstp2p_offline_repay_detail as rd on rd.offline_repay_id = off_re.id   where rd.repay_id  = {$repay_item['id']} and off_re.repay_content != 4";
                    if(Yii::app()->cmsdb->createCommand($sql)->queryRow()){
                        throw new Exception('该笔还款计划仅可使用当期部分还款:'.$repay_item['id']);
                    }
                }
                $wait_amount = bcadd($wait_amount, $repay_item['new_principal'], 2);
            }


            $deal_id = $result[0]['deal_id'];


            //获取三方公司ID
            $company_id = $this->getCompanyId($deal_id);
            if(!$company_id){
                throw new Exception('未查询到分配有效的第三方公司');
            }

            if(FunctionUtil::float_bigger($repay_amount, $wait_amount, 2)){
                throw new Exception('还款金额大于待还金额');
            }


            $current_admin_id = \Yii::app()->user->id;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];

            $repay_type = $params['repay_type'];
            $model = new Firstp2pOfflineRepay();
            $model->deal_id = $deal_id ;
            $model->repay_number = count($result) ;
            $model->repay_amount = $repay_amount ;
            $model->repay_type = $repay_type  ;
            $model->repay_discount = $discount ;
            $model->repay_content = $params['repay_content'] ;
            $model->repay_time = strtotime($params['repay_date']) ;
            $model->reply_slip = $params['logo_path'] ;
            $model->status = 0 ;
            $model->addtime = time() ;
            $model->company_id = $company_id ;
            $model->add_admin_id = $current_admin_id  ;
            if($model->save()===false){
                throw new Exception('网络错误 -1');
            }

            $sql = "INSERT into firstp2p_offline_repay_detail (deal_id,repay_id,offline_repay_id,addtime,current_principal,current_interest) values ";
            $now = time();
            foreach($result  as $value){
                $sql .= "($deal_id,{$value['id']},{$model->id},$now,{$value['new_principal']},{$value['new_interest']}),";
            }
            $sql = rtrim($sql, ',');
            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('写入还款明细表失败');
            }
            
            $sql =" UPDATE firstp2p_deal_repay set offline_repay_id= {$model->id} where id in ($repay_id )";

            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('更新失败，请稍后重试');
            }
            
            Yii::app()->cmsdb->commit(); 
            return true;
        } catch (\Exception  $e) {
            Yii::app()->cmsdb->rollback();
            throw $e;
        }
    }

    /**
    * 还款凭证补录审核
    * @return void
    */
    public function auditClearIndex($params)
    {
        $where = '';

        //借款标题
        if (!empty($params['deal_name'])) {
            $condition[] = " d.name = '".trim($params['deal_name'])."'";
        }
        //借款标题
        if (!empty($params['deal_id'])) {
            $condition[] = " d.id = '".trim($params['deal_id'])."'";
        }
         //借款标题
         if (!empty($params['user_id'])) {
            $condition[] = " d.user_id = '".trim($params['user_id'])."'";
        }
        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }

        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(' and ', $user_query);
            $user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
            if ($user_info) {
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }

        $condition[] = " EXISTS (select 1 from firstp2p_deal_reply_slip as s where s.deal_id = d.id ) ";//and status = 0 



        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
        
        $result = [];

        $count_sql = "select count(distinct deal_id) from firstp2p_deal_reply_slip s left join firstp2p_deal as d on s.deal_id = d.id   {$where} ";
        
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select d.data_src,  d.user_id, d.name as deal_name,d.id as deal_id,d.borrow_amount,d.rate,min(s.status) as status   from firstp2p_deal_reply_slip s left join firstp2p_deal as d on s.deal_id = d.id  {$where} group by s.deal_id order by status asc,s.addtime desc  LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            
            foreach($result as $val){
                $user_ids[] = $val['user_id'];
                $deal_ids[] = $val['deal_id'];
            }
            if ($user_ids) {
                $user_ids = implode(',', $user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key)?:'';
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }

            foreach ($result as &$value) {
                $value['rate'] = floatval($value['rate']);
                $value+= $user_info[$value['user_id']]?:['customer_name'=>'','id_number'=>'','phone'=>''];
                $value['data_src_cn'] = self::$data_src_cn[$value['data_src']];
                $value['status_cn'] = self::$offline_repay_status[$value['status']];

            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }



    /**
    * 线下还款审核列表
    * @return void
    */
    public function getOfflineRepayList($params)
    {
        $where = '';
        $condition[] = 'offline.id > 0 ';
        if (!empty($params['organization_type'])) {
            $condition[] = " a.name  = '".self::$organization_type[$params['organization_type']]."'";
        }

        //借款标题
        if (!empty($params['deal_name'])) {
            $condition[] = " d.name = '".trim($params['deal_name'])."'";
        }
        //借款标题
        if (!empty($params['deal_id'])) {
            $condition[] = " d.id = '".trim($params['deal_id'])."'";
        }
         //借款人ID
         if (!empty($params['user_id'])) {
            $condition[] = " d.user_id = '".trim($params['user_id'])."'";
        }

        //借款金额borrow_amount
        if (!empty($params['loan_amount_min'])) {
            $condition[] = " d.borrow_amount >= '".$params['loan_amount_min']."'";
        }
        if (!empty($params['loan_amount_max'])) {
            $condition[] = " d.borrow_amount <= '".$params['loan_amount_max']."'";
        }
        if (!empty($params['status'])) {
            $condition[] = " offline.status = ".$params['status'];
        }

        //还款时间
        if (!empty($params['repay_time_start'])) {
            $condition[] = " offline.repay_time >= '".strtotime($params['repay_time_start'])."'";
        }
        if (!empty($params['repay_time_end'])) {
            $condition[] = " offline.repay_time <= '".strtotime($params['repay_time_end'])."'";
        }

        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }

        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(' and ', $user_query);
            $user_info = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if ($user_info) {
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }



        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
        
        $result = [];

        $count_sql = "select count(1) from firstp2p_deal as d left join firstp2p_offline_repay as offline on d.id = offline.deal_id left join firstp2p_deal_agency  as a on d.advisory_id = a.id   {$where} ";
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select  o.number,o.product_name,offline.id as offline_repay_id, offline.status, d.data_src, a.name as organization_name, d.user_id, d.name as deal_name,d.id as deal_id,d.borrow_amount,d.rate ,offline.repay_number,offline.repay_amount,offline.repay_type,offline.repay_discount,offline.repay_content,offline.total_repay_capital,offline.surplus_repay_capital,offline.repay_time
                    from firstp2p_deal as d 
                        left join order_info as o on d.approve_number = o.number
                    left join firstp2p_offline_repay as offline on d.id = offline.deal_id  
                    left join firstp2p_deal_agency  as a on d.advisory_id = a.id  {$where} 
                    order by offline.status asc,offline.id desc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            $user_ids =   ArrayUntil::array_column($result, 'user_id');
            if ($user_ids) {
                $user_ids = implode(',', $user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key)?:'';
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }

    
            foreach ($result as &$value) {
                $value['rate'] = floatval($value['rate']);
                $value+= $user_info[$value['user_id']]?:['customer_name'=>'','id_number'=>'','phone'=>''];
                $value['refund_date'] = date('Y-m-d',$value['principal_refund_time']);
                $value['repay_time_cn'] = date('Y-m-d',$value['repay_time']);
                $value['reply_slip'] = self:: $OSS_URL . $value['reply_slip'];
                $value['repay_content'] = self::$repay_content[$value['repay_content']];
                $value['data_src_cn'] = self::$data_src_cn[$value['data_src']];
                $value['status_cn'] = self::$offline_repay_status[$value['status']];

            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }

    public static $offline_repay_status=[
        0=>'待审核',
        1=>'审核通过',
        2=>'还款成功',
        3=>'还款失败',
    ];

    /**
     * 审核线下打卡
     *
     * @param [type] $offline_repay_id
     * @return void
     */
    public function doAuditOfflineRepay($offline_repay_id)
    {
        
        if(empty( $offline_repay_id)){
            throw new Exception('参数错误-1');
        }

        try {

        
            Yii::app()->cmsdb->beginTransaction();

       
            $sql= "select * from firstp2p_offline_repay where id  = {$offline_repay_id}  for update";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if(empty($result)){
                throw new Exception('待审核数据不存在');
            }

            if($result['status'] == 1){
                throw new Exception('数据已经审核通过');
            }

        
            $current_admin_id = \Yii::app()->user->id;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $now = time();
            $sql =" UPDATE firstp2p_offline_repay set status = 1, auth_admin_id= {$current_admin_id},auth_user_name='{$username}',auth_time = {$now} where id = {$offline_repay_id}";

            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('更新失败，请稍后重试');
            }
            
            Yii::app()->cmsdb->commit(); 
            return true;
        } catch (\Exception  $e) {
            Yii::app()->cmsdb->rollback();
            throw $e;
        }
    

    }

    public static $repay_content=[
        '1'=>'本金',
        '2'=>'利息',
        '3'=>'本金+利息',
        '4'=>'当期部分还款',
    ];


    public static $offline_repay_type=[
        '1'=>'按折扣还款',
        '2'=>'按金额还款'
    ];

    /**
     * 供应链线下还款审核列表
     * @return void
     */
    public function getCompanyOfflineRepayList($params)
    {
        $where = '';
        $condition[] = 'offline.id > 0 ';


        //借款标题
        if (!empty($params['deal_name'])) {
            $condition[] = " d.name = '".trim($params['deal_name'])."'";
        }
        //借款标题
        if (!empty($params['deal_id'])) {
            $condition[] = " d.id = '".trim($params['deal_id'])."'";
        }
        //借款人ID
        if (!empty($params['user_id'])) {
            $condition[] = " d.user_id = '".trim($params['user_id'])."'";
        }



        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }

        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(' and ', $user_query);
            $user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
            if ($user_info) {
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }



        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }

        $result = [];

        $count_sql = "select count(1) from firstp2p_deal as d left join firstp2p_gyl_offline_repay as offline on d.id = offline.deal_id left join firstp2p_deal_agency  as a on d.agency_id = a.id   {$where} ";

        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select  offline.id as offline_repay_id, offline.status,   a.name as agency_name, d.user_id, d.name as deal_name,d.id as deal_id,d.borrow_amount, offline.repay_interest,offline.repay_capital ,offline.repay_content 
                    from firstp2p_deal as d 
                    left join firstp2p_gyl_offline_repay as offline on d.id = offline.deal_id  
                    left join firstp2p_deal_agency  as a on d.agency_id = a.id  {$where} 
                    order by offline.status asc,offline.id desc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            //c.name as customer_name, c.phone,c.id_number
            $user_ids =   ArrayUntil::array_column($result, 'user_id');
            if ($user_ids) {
                $user_ids = implode(',', $user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key)?:'';
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }


            foreach ($result as &$value) {
                $value['rate'] = floatval($value['rate']);
                $value+= $user_info[$value['user_id']]?:['customer_name'=>'','id_number'=>'','phone'=>'']; 
                $value['repay_content'] = self::$repay_content[$value['repay_content']]; 
                $value['status_cn'] = self::$offline_repay_status[$value['status']];
            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }


    /**
     * 供应链详情
     * @param $deal_id
     * @return array|false
     */
    public function getGylAboutDealRepayPlanInfo($deal_id)
    {
        if (empty($deal_id)) {
            return false;
        }

        $sql = "select  d.repay_auth_flag, d.id ,d.user_id,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.start_time, d.create_time as d_create_time, d.borrow_amount as loan_amount, a.name as organization_name, o.product_name, o.transaction_number, o.create_time as o_create_time,o.customer_number,o.number from firstp2p_deal as d left join order_info as o on d.approve_number = o.number left join firstp2p_deal_agency  as a on d.advisory_id = a.id   where d.id = {$deal_id}";
        $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();

        $dealInfo['loan_amount'] = floatval($dealInfo['loan_amount']);
        $dealInfo['d_create_time'] = date('Y-m-d',$dealInfo['d_create_time']);
        $dealInfo['o_create_time'] = date('Y-m-d',$dealInfo['o_create_time']);

        //$sql = "select b.card_number,b.bank_name, c.name as customer_name, c.phone,c.id_number  from customer_bank_info as b left  join  customer_info as c  on b.customer_number = c.customer_number where c.order_number = '{$dealInfo['number']}'";

        //$userInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();

        if($dealInfo['user_id']){
            $sql = "select idno,real_name,mobile from firstp2p_user where id = {$dealInfo['user_id']}";
            $firstp2pUserInfo = Yii::app()->phdb->createCommand($sql)->queryRow();

            if ($firstp2pUserInfo) {
                $firstp2pUserInfo['idno'] = GibberishAESUtil::dec($firstp2pUserInfo['idno'], Yii::app()->c->idno_key);
                $firstp2pUserInfo['mobile'] = GibberishAESUtil::dec($firstp2pUserInfo['mobile'], Yii::app()->c->idno_key);
            }

            $sql = "SELECT ub.user_id , ub.bankcard as card_number  ,b.name as bank_name FROM firstp2p_user_bankcard as ub left join firstp2p_bank as b on ub.bank_id = b.id where ub.user_id = {$dealInfo['user_id']} and ub.verify_status = 1";
            $firstp2pUserBankInfo= Yii::app()->fdb->createCommand($sql)->queryRow();

            if ($firstp2pUserBankInfo) {
                $firstp2pUserInfo['card_number'] = GibberishAESUtil::dec($firstp2pUserBankInfo['card_number'], Yii::app()->c->idno_key);
                $firstp2pUserInfo['bank_name'] = $firstp2pUserBankInfo['bank_name'];
            }
        }



        //还款记录
        $sql = "SELECT *  from firstp2p_gyl_offline_repay  where status=2 and deal_id = {$deal_id} order by repay_time  desc";
        $repayPlan = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($repayPlan) {
            foreach ($repayPlan as $key=>  &$value) {
                $value['repay_time'] = date('Y-m-d H:i:s', $value['repay_time']);
                $value['repay_content'] = Yii::app()->c->xf_config['gyl_repay_content'][$value['repay_content']];
                if(empty($value['reply_slip'])){
                    $value['reply_slip_html'] = '';
                }else{
                    $value['reply_slip'] = "https://admin-upload-file.oss-cn-beijing.aliyuncs.com/{$value['reply_slip']}";
                    $value['reply_slip_html'] = "<a href='{$value['reply_slip']}' download><button class='layui-btn layui-btn-primary'>下载</button></a>";
                }
            }
        }

        //标的待还本息
        $sql = "SELECT  new_principal,new_interest,last_yop_repay_status  from firstp2p_deal_repay  where deal_id = {$deal_id} and type = 0  and status=0   ";
        $repay_list = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($repay_list) {
            $dealInfo['principal'] = 0;
            $dealInfo['interest'] = 0;
            foreach ($repay_list as $key=>$val) {
                $dealInfo['principal'] += ($val['last_yop_repay_status'] == 2 ? 0 : $val['new_principal']);
                $dealInfo['interest'] += $val['new_interest'];
            }
        }

        //原还款计划
        $sql = "SELECT s.reply_slip as reply_slip_url , r.*,s.id as reply_slip_id ,s.status as reply_slip_status from firstp2p_deal_repay as r LEFT JOIN firstp2p_deal_reply_slip as s on r.id = s.deal_repay_id where r.deal_id = {$deal_id} and type = 0  GROUP BY r.id order by r.repay_time desc";
        $repayList = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($repayList) {
            $total = count($repayList);
            $dealInfo['principal'] = 0;
            $dealInfo['interest'] = 0;
            $dealInfo['consult_fee'] = 0;
            $can_slip_ids = $this->getCanSlipIds($deal_id);
            foreach ($repayList as $key=>  &$value) {
                if ($value['status'] == 0) {
                    $dealInfo['principal'] += ($value['last_yop_repay_status'] == 2 ? 0 : $value['new_principal']);
                    $dealInfo['interest'] += $value['new_interest'];
                    $dealInfo['consult_fee'] += $value['consult_fee'];
                }
                $value['principal_refund_status_cn'] = self::$principal_refund_status[$value['principal_refund_status']];
                $value['interest_refund_status_cn'] = self::$principal_refund_status[$value['interest_refund_status']];

                $value['principal_repay_type_cn'] = self::$principal_repay_type[$value['principal_repay_type']];
                $value['reply_slip_status_cn'] = $value['reply_slip_id']? self::$reply_slip_status_cn[$value['reply_slip_status']]:'--';
                $value['repay_num'] = ($total-$key).'/'.$total;
                $value['is_repay'] = $value['status']>0?'已还':'未还';
                $value['is_repay_principal'] = ($value['status']==1 || $value['last_yop_repay_status'] == 2)? '已还' : ($value['principal'] > 0 && $value['paid_principal'] > 0 ?'部分还款':'未还');
                $value['is_repay_interest'] =  ($value['status']==1 )? '已还':($value['paid_interest']>0 &&  $value['interest'] > 0 ?'部分还款':'未还');

                $value['interest_repay_type_cn'] =  $value['is_repay_interest'] != '未还'? self::$principal_repay_type[$value['interest_repay_type']]:'';

                $value['true_repay_time'] =$value['paid_principal_time']>0 ? date('Y-m-d H:i:s', $value['paid_principal_time']):($value['true_repay_time']>0 ? date('Y-m-d H:i:s', $value['true_repay_time']):'--');
                $value['repay_time'] = date('Y-m-d H:i:s', $value['repay_time']);
                $value['reply_slip'] =  self:: $OSS_URL.$value['reply_slip_url'];
                $value['principal_refund_time'] =  $value['principal_refund_time'] >0 ? date('Y-m-d H:i:s',$value['principal_refund_time']):'--';
                $value['interest_refund_time'] =  $value['interest_refund_time'] >0 ? date('Y-m-d H:i:s',$value['interest_refund_time']):'--';
                $value['offline_repay_amount'] = $value['real_paid_principal']+$value['real_paid_interest'];

                $value['repay_slip_status'] = !in_array($value['id'], $can_slip_ids) ? 1 : 0;
                $value['principal'] = ($value['is_repay_principal'] == '已还') ? "0" : $value['principal'];
            }
        }

        return ['firstp2pUserInfo'=>$firstp2pUserInfo,'dealInfo'=> $dealInfo,'repayList'=>$repayList,'repayPlan'=>$repayPlan,'modifyLog'=> []];
    }


    public function getDistributionDetail($params)
    {
        $where = '';
        $condition[] = " a.status=1 ";
        if (!empty($params['user_id'])) {
            $condition[] = " a.user_id = ".trim($params['user_id']);
        }

        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }

        $result = [];
        $count_sql = "select count(1) from firstp2p_borrower_distribution_detail as a  {$where} ";
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = " select  b.start_time,b.end_time,c.name as company_name,sum(a.repay_capital) as repay_amount_total 
                    from firstp2p_borrower_distribution_detail a 
                    LEFT JOIN firstp2p_borrower_distribution b on a.distribution_id=b.id
                    LEFT JOIN  firstp2p_cs_company c on c.id=b.company_id   
                    {$where} GROUP BY a.id ORDER BY a.id desc  LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            foreach ($result as &$value) {
                $value['start_time'] = $value['start_time']>0? date('Y-m-d', $value['start_time']):'-';
                $value['end_time'] = $value['end_time']>0? date('Y-m-d', $value['end_time']):'-';
                $value['start_end_time'] = $value['start_time'].'~'.$value['end_time'];
                $value['repay_amount_total'] = $value['repay_amount_total'] ?: 0;
            }
        }
        return ['countNum' => $total_num, 'list' => $result ];
    }

    public function getCallLog($params)
    {
        $where = '';
        if (!empty($params['user_id'])) {
            $where = " where user_id={$params['user_id']}";
        }
        $result = [];
        $count_sql = "select count(1) from xf_borrower_call_back_log {$where} ";
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = " select  * from xf_borrower_call_back_log   {$where}  ORDER BY  id desc  LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            foreach ($result as &$value) {
                $value['add_time'] = $value['add_time']>0? date('Y-m-d H:i:s', $value['add_time']):'-';
                $value['question_3_cn'] =  Yii::app()->c->xf_config['question_3'][$value['question_3']] ?: '-' ;
                $value['contact_status_cn'] =  Yii::app()->c->xf_config['contact_status'][$value['contact_status']] ?: '-' ;
                $value['legal_status_cn'] =  Yii::app()->c->xf_config['legal_status'][$value['legal_status']] ?: '-' ;
                $value['legal_files_html'] = "<a href='/{$value['legal_files']}' download><button class='layui-btn layui-btn-primary'>下载</button></a>";

            }
        }
        return ['countNum' => $total_num, 'list' => $result ];
    }

    public function checkCsCompany(){
        $op_user_id = \Yii::app()->user->id;
        $user_info_sql  = "SELECT b.item_id,a.company_id, a.type from itz_user a 
                LEFT JOIN itz_auth_assignment b on b.user_id=a.id 
                WHERE a.id= $op_user_id";
        $user_info = Yii::app()->db->createCommand($user_info_sql)->queryRow();

        if(!$user_info || $user_info['type'] != 1 || empty($user_info['company_id']) || !in_array($user_info['item_id'], \Yii::app()->c->xf_config['borrower_distribution_itemid'])){
            return false;
        }
        //提测时删除
        //$user_info['company_id'] = 11;
        return $user_info;
    }

    public function getCompanyInfo($company_id)
    {
        $company_info = \Yii::app()->cmsdb->createCommand()
            ->select('id,name,tax_number,status')
            ->from('firstp2p_cs_company')
            ->where("id={$company_id}")
            ->queryRow();
        if(!$company_info || $company_info['status'] != 0 ){
            return false;
        }
        return $company_info;
    }


    public function getDistributionUid(){
        $user_info = $this->checkCsCompany();
        if(!$user_info){
            return false;
        }
        $company_id = $user_info['company_id'];
        /*
        $company_info = $this->getCompanyInfo($company_id);
        if(!$company_info){
            return false;
        }*/
        $now = time();
        $sql = "SELECT distinct detail.user_id 
                FROM firstp2p_borrower_distribution AS distribution
                LEFT JOIN firstp2p_borrower_distribution_detail AS detail ON distribution.id = detail.distribution_id
                LEFT JOIN firstp2p_cs_company cs on cs.id=distribution.company_id 
                WHERE cs.status=0 and distribution.company_id ={$company_id}
                AND distribution. STATUS = 1
                AND detail. STATUS = 1
                AND distribution.end_time >= {$now}
                AND distribution.start_time <={$now}";
        $user_ids  = Yii::app()->cmsdb->createCommand($sql)->queryAll();
        if(!$user_ids){
            return false;
        }
        $user_ids = ArrayUntil::array_column($user_ids, 'user_id');
        return $user_ids;
    }


    private function getCanSlipIds($deal_id){
        $repay_ids = [];
        $sql_01 = "SELECT DISTINCT b.repay_id from firstp2p_offline_repay a 
                LEFT JOIN firstp2p_offline_repay_detail b on a.id=b.offline_repay_id 
                WHERE a.status in (0,1) and a.deal_id=$deal_id";
        $repay_ids_01 = Yii::app()->cmsdb->createCommand($sql_01)->queryColumn();
        if($repay_ids_01){
            $repay_ids = array_merge($repay_ids, $repay_ids_01);
        }

        $sql_02 = "SELECT DISTINCT  deal_repay_id from firstp2p_deal_reply_slip  
                WHERE  status in (0,1) and deal_id=$deal_id";
        $repay_ids_02 = Yii::app()->cmsdb->createCommand($sql_02)->queryColumn();
        if($repay_ids_02){
            $repay_ids = array_merge($repay_ids, $repay_ids_02);
        }
        return $repay_ids;
    }


    public function getGylDealRepayPlanInfo($deal_id)
    {
        if (empty($deal_id)) {
            return false;
        }

        $sql = "select  d.repay_auth_flag, d.id ,d.user_id,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.start_time, d.create_time as d_create_time, d.borrow_amount as loan_amount, a.name as agency_name, o.product_name, o.transaction_number, o.create_time as o_create_time,o.customer_number,o.number from firstp2p_deal as d left join order_info as o on d.approve_number = o.number left join firstp2p_deal_agency  as a on d.agency_id = a.id   where d.id = {$deal_id}";
        $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();

        $dealInfo['loan_amount'] = floatval($dealInfo['loan_amount']);
        $dealInfo['d_create_time'] = date('Y-m-d',$dealInfo['d_create_time']);
        $dealInfo['o_create_time'] = date('Y-m-d',$dealInfo['o_create_time']);

        $sql = "select b.card_number,b.bank_name, c.name as customer_name, c.phone,c.id_number  from customer_bank_info as b left  join  customer_info as c  on b.customer_number = c.customer_number where c.order_number = '{$dealInfo['number']}'";

        $userInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();

        if($dealInfo['user_id']){
            $sql = "select idno,real_name,mobile from firstp2p_user where id = {$dealInfo['user_id']}";
            $firstp2pUserInfo = Yii::app()->phdb->createCommand($sql)->queryRow();

            if ($firstp2pUserInfo) {
                $firstp2pUserInfo['idno'] = GibberishAESUtil::dec($firstp2pUserInfo['idno'], Yii::app()->c->idno_key);
                $firstp2pUserInfo['mobile'] = GibberishAESUtil::dec($firstp2pUserInfo['mobile'], Yii::app()->c->idno_key) ?: '-';
            }

            $sql = "SELECT ub.user_id , ub.bankcard as card_number  ,b.name as bank_name FROM firstp2p_user_bankcard as ub left join firstp2p_bank as b on ub.bank_id = b.id where ub.user_id = {$dealInfo['user_id']} and ub.verify_status = 1";
            $firstp2pUserBankInfo= Yii::app()->fdb->createCommand($sql)->queryRow();

            if ($firstp2pUserBankInfo) {
                $firstp2pUserInfo['card_number'] = GibberishAESUtil::dec($firstp2pUserBankInfo['card_number'], Yii::app()->c->idno_key);
                $firstp2pUserInfo['bank_name'] = $firstp2pUserBankInfo['bank_name'];
            }
        }

        //原还款计划
        $sql = "SELECT s.reply_slip as reply_slip_url , r.*,s.id as reply_slip_id ,s.status as reply_slip_status from firstp2p_deal_repay as r LEFT JOIN firstp2p_deal_reply_slip as s on r.id = s.deal_repay_id where r.deal_id = {$deal_id} and type = 0  GROUP BY r.id order by r.repay_time desc";
        $repayPlan = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($repayPlan) {
            $total = count($repayPlan);
            $dealInfo['principal'] = 0;
            $dealInfo['interest'] = 0;
            $dealInfo['consult_fee'] = 0;
            $can_slip_ids = $this->getCanSlipIds($deal_id);
            foreach ($repayPlan as $key=>  &$value) {
                if ($value['status'] == 0) {
                    $dealInfo['principal'] += ($value['last_yop_repay_status'] == 2 ? 0 : $value['new_principal']);
                    $dealInfo['interest'] += $value['new_interest'];
                    $dealInfo['consult_fee'] += $value['consult_fee'];
                }
                $value['principal_refund_status_cn'] = self::$principal_refund_status[$value['principal_refund_status']];
                $value['interest_refund_status_cn'] = self::$principal_refund_status[$value['interest_refund_status']];

                $value['principal_repay_type_cn'] = self::$principal_repay_type[$value['principal_repay_type']];
                $value['reply_slip_status_cn'] = $value['reply_slip_id']? self::$reply_slip_status_cn[$value['reply_slip_status']]:'--';
                $value['repay_num'] = ($total-$key).'/'.$total;
                $value['is_repay'] = $value['status']>0?'已还':'未还';
                $value['is_repay_principal'] = ($value['status']==1 || $value['last_yop_repay_status'] == 2)? '已还' : ($value['principal'] > 0 && $value['paid_principal'] > 0 ?'部分还款':'未还');
                $value['is_repay_interest'] =  ($value['status']==1 )? '已还':($value['paid_interest']>0 &&  $value['interest'] > 0 ?'部分还款':'未还');

                $value['interest_repay_type_cn'] =  $value['is_repay_interest'] != '未还'? self::$principal_repay_type[$value['interest_repay_type']]:'';

                $value['true_repay_time'] =$value['paid_principal_time']>0 ? date('Y-m-d H:i:s', $value['paid_principal_time']):($value['true_repay_time']>0 ? date('Y-m-d H:i:s', $value['true_repay_time']):'--');
                $value['repay_time'] = date('Y-m-d H:i:s', $value['repay_time']);
                $value['reply_slip'] =  self:: $OSS_URL.$value['reply_slip_url'];
                $value['principal_refund_time'] =  $value['principal_refund_time'] >0 ? date('Y-m-d H:i:s',$value['principal_refund_time']):'--';
                $value['interest_refund_time'] =  $value['interest_refund_time'] >0 ? date('Y-m-d H:i:s',$value['interest_refund_time']):'--';
                $value['offline_repay_amount'] = $value['real_paid_principal']+$value['real_paid_interest'];

                $value['repay_slip_status'] = !in_array($value['id'], $can_slip_ids) ? 1 : 0;
                $value['principal'] = ($value['is_repay_principal'] == '已还') ? "0" : $value['principal'];
            }
        }


        return ['firstp2pUserInfo'=>$firstp2pUserInfo,'userInfo'=>$userInfo,'dealInfo'=> $dealInfo,'repayPlan'=>$repayPlan,'modifyLog'=> []];
    }


    /**
     * 供应链还款-xin
     */
    public function addGylRepayPlanVoucher($params)
    {

        try {
            if(empty($params['repay_time'])){
                throw new Exception('请输入还款时间');
            }
            $sql = "select *  from firstp2p_gyl_offline_repay where deal_id = {$params['deal_id']} and status in (0,1) ";
            $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if($repayInfo){
                throw new Exception('有待审核的还款录入，请稍后再添加');
            }

            $sql = "select sum(new_principal) as new_principal,sum(new_interest) as new_interest  from firstp2p_deal_repay where deal_id = {$params['deal_id']} and status=0 and true_repay_time=0 ";
            $repay_data  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if(empty($repay_data)){
                throw new Exception('还款计划异常，请联系客服');
            }
            $wait_repay_amount = bcadd( $repay_data['new_principal'],  $repay_data['new_interest'], 2);
            if(FunctionUtil::float_bigger($params['repay_capital'], $wait_repay_amount, 2)){
                throw new Exception('还款金额错误，不可大于待还金额');
            }
            /*
            if(FunctionUtil::float_bigger($params['repay_interest'], $repay_data['new_interest'], 2)){
                throw new Exception('还款利息错误，不可大于待还利息');
            }
            if(in_array($params['repay_content'], [1,3])  && $params['repay_capital'] <= 0){
                throw new Exception('还款内容选择错误');
            }
            if(in_array($params['repay_content'], [2,3])  && $params['repay_interest'] <= 0){
                throw new Exception('还款内容选择错误');
            }*/

            $current_admin_id = \Yii::app()->user->id;
            $repay_date = strtotime($params['repay_time']);
            $now = time();
            $sql = "INSERT INTO firstp2p_gyl_offline_repay (repay_content,deal_id,repay_capital,repay_interest,repay_time,reply_slip,addtime,add_admin_id,company_id,distribution_id) VALUES ";
            $sql .= "(3,{$params['deal_id']},{$params['repay_capital']},0,$repay_date,'{$params['file_path_url']}', $now,$current_admin_id, {$params['company_id']}, {$params['distribution_id']})";

            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('更新失败，请稍后重试');
            }
        } catch (\Exception  $e) {
            throw $e;
        }
    }

    public function getGylCompanyInfo( $deal_id){
        if(empty($deal_id) || !is_numeric($deal_id) ){
            return false;
        }

        $deal_sql = " SELECT * from firstp2p_deal WHERE id={$deal_id}";
        $deal_info = Yii::app()->cmsdb->createCommand($deal_sql)->queryRow();
        if(!$deal_info){
            return false;
        }

        $n_time = time();
        $sql = " SELECT bd.company_id,bd.id from firstp2p_borrower_distribution_detail bdd 
LEFT JOIN firstp2p_borrower_distribution bd on bd.id=bdd.distribution_id and  bd.status=1 
 left join firstp2p_deal dl on dl.user_id=bdd.user_id and dl.id={$deal_id}
WHERE   bdd.user_id={$deal_info['user_id']} and bdd.status=1 and bd.start_time<=$n_time and bd.end_time>$n_time";
        $company_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        if(!$company_info){
            return false;
        }
        return  $company_info ;
    }

}

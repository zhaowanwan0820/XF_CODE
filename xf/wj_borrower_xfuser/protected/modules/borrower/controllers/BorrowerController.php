<?php

/**
 * 借款记录
 */
class BorrowerController extends \iauth\components\IAuthController
{
    public $pageSize = 10;

    private $company_user_ids = [];

    public function init()
    {
        //催收公司展示分配的借款人
        $current_admin_id = \Yii::app()->user->id;
        $adminInfo = Yii::app()->db->createCommand("select company_id from itz_user where id = {$current_admin_id}")->queryRow();
        if ($adminInfo['company_id'] > 0) {
            $now = time();
            $sql = "select detail.user_id from firstp2p_borrower_distribution  as distribution left join firstp2p_borrower_distribution_detail as detail  on distribution.id = detail.distribution_id where distribution.company_id = {$adminInfo['company_id']} and  distribution.status = 1 and detail.status = 1 and distribution.end_time >=  {$now} and distribution.start_time <={$now}";
            $user_ids  = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if ($user_ids) {
                $this->company_user_ids = ArrayUntil::array_column($user_ids, 'user_id');
            }
        }
        parent::init();
    }

    /**
     * 初始化数据
     * 个人借款列表
     */
    public function actionIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'user_name'=>Yii::app()->request->getParam('real_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'user_id'=>Yii::app()->request->getParam('user_id'),
                'bankcard'=>Yii::app()->request->getParam('bankcard'),
                'fa_status'=>Yii::app()->request->getParam('fa_status'),
                'fa_company_name'=>Yii::app()->request->getParam('fa_company_name'),
                'contact_status'=>Yii::app()->request->getParam('contact_status'),
                'id_type'=> 1,//1个人 2企业借款人
            ];
            
        if (\Yii::app()->request->isPostRequest) {
            $result  = BorrowerService::getInstance()->getBorrowerList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBorrowerDetail($params);
            return;
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $tpl_data['can_export'] = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('borrower/borrower/index_execl')) || empty($authList)) {
            $tpl_data['can_export'] = 1;
        }
        $tpl_data['contact_status'] = Yii::app()->c->xf_config['contact_status'];
        $tpl_data['fa_status'] = Yii::app()->c->xf_config['fa_status'];
        $tpl_data['fa_company_name_list'] = $this->getCsCompany();//接案公司列表
        return $this->renderPartial('index_init', $tpl_data);
    }

    private function getCsCompany(){
        $company_list = \Yii::app()->cmsdb->createCommand()
            ->select('id,name')
            ->from('firstp2p_cs_company')
            ->where('status=0')
            ->queryAll();
        return $company_list ?: [];
    }

    public function actionDetail()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'user_id'=>Yii::app()->request->getParam('user_id'),
                'number'=>Yii::app()->request->getParam('number'),
                'deal_name'=>Yii::app()->request->getParam('deal_name'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'last_repay_end'=>Yii::app()->request->getParam('last_repay_end'),
                'last_repay_start'=>Yii::app()->request->getParam('last_repay_start'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'type'=>'all',
                
               
            ];
            //获取借款列表
            $result         = BorrowerService::getInstance()->getDealOrderList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }
        $user_id = Yii::app()->request->getParam('user_id')?:0;
        $deal_id = Yii::app()->request->getParam('deal_id')?:0;
       
        return $this->renderPartial('borrow_list', ['user_id'=>$user_id,'deal_id'=>$deal_id]);
    }

    /**
     * 原还款相关信息
     *
     * @return void
     */
    public function actionRepayPlan()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $result         = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrower/DealOrder/authDeal')) || empty($authList)) {
            $can_auth = 1;
        }
        $result['auth_type'] =  $can_auth ;
        return $this->renderPartial('detail', $result);
    }

    /**
     * 新还款相关信息
     *
     * @return void
     */
    public function actionNewRepayPlan()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $result         = BorrowerService::getInstance()->getNewAboutDealRepayPlanInfo($deal_id);
        return $this->renderPartial('detail_new', $result);
    }

    /**
     * 编辑还款计划
     *
     * @return void
     */
    public function actionEditRepayPlan()
    {
        $repay_plan_id = Yii::app()->request->getParam('id');
        $result = BorrowerService::getInstance()->getRepayPlanInfo($repay_plan_id);
        
        return $this->renderPartial('editRepayPlan', $result);
    }
    /**
     * 修改还款计划
     *
     * @return void
     */
    public function actionUpdateRepayPlan()
    {
        $params   = [
            'id'=>Yii::app()->request->getParam('id'),
            'new_principal'=>Yii::app()->request->getParam('new_principal'),
            'new_interest'=>Yii::app()->request->getParam('new_interest'),
            'principal'=>Yii::app()->request->getParam('principal'),
            'interest'=>Yii::app()->request->getParam('interest'),
            'repay_flag'=>Yii::app()->request->getParam('repay_flag'),
        ];

        try {
            //
            $res        = BorrowerService::getInstance()->updateRepayPlan($params);
            if ($res) {
                $importFileInfo['code'] = 0;
                $importFileInfo['info'] = '修改成功';
                echo json_encode($importFileInfo);
                die;
            }
        } catch (\Exception $e) {
            $importFileInfo['code'] = 10;
            $importFileInfo['info'] = $e->getMessage();
            echo json_encode($importFileInfo);
            die;
        }
    }

    /**
     * 审核
     */
    public function actionAuthDeal()
    {
        try {
            $params = [
                'user_id' => \Yii::app()->user->id,
                'deal_id' => \Yii::app()->request->getParam('id'),
                'type' => Yii::app()->request->getParam('type'),
              
            ];
            $res        = BorrowerService::getInstance()->authDeal($params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 提交到待审核
     */
    public function actionAddAuth()
    {
        try {
            $params = [
                'user_id' => \Yii::app()->user->id,
                'deal_id' => \Yii::app()->request->getParam('id'),
                'type' => 1,
              
            ];
            $res        = BorrowerService::getInstance()->authDeal($params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    public function actionEditUserBank()
    {
        if (\Yii::app()->request->isPostRequest) {
            $params   = [
                'user_id'=>Yii::app()->request->getParam('user_id'),
                'bankcard'=>Yii::app()->request->getParam('bankcard'),
                'bank_mobile'=>Yii::app()->request->getParam('bank_mobile'),
                'bank_name'=>Yii::app()->request->getParam('bank_name'),
                'sms_code'=>Yii::app()->request->getParam('sms_code'),
                'step'=>Yii::app()->request->getParam('step')
            ];
           
            try {
                if ($params['step']==1) {
                    BorrowerService::getInstance()->editUserBankStep1($params);
                } else {
                    BorrowerService::getInstance()->editUserBankStep2($params);
                }
                $result['code'] = 0;
                $result['info'] = 'success';
            } catch (Exception $e) {
                $result['code'] = 100;
                $result['info'] = $e->getMessage();
            }
            echo json_encode($result);
            die;
        }
        $user_id = Yii::app()->request->getParam('user_id')?:0;
        if ($this->company_user_ids && !in_array($user_id, $this->company_user_ids)) {
            return $this->renderPartial('result', ['type'=>2,'msg'=>'该用户不归属当前公司','time'=>3]);
        }
        //$bindid = BorrowerService::getInstance()->getUserBindCard($user_id);
        //,'bindid'=> $bindid
        return $this->renderPartial('edit_user_bank', ['user_id'=>$user_id ]);
    }

    public function actionUserRepayList()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'customer_name'=>Yii::app()->request->getParam('customer_name'),
            'repay_time_start'=>Yii::app()->request->getParam('repay_time_start'),
            'repay_time_end'=>Yii::app()->request->getParam('repay_time_end'),
            'status'=>2,

        ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result = BorrowerService::getInstance()->getOfflineRepayList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }


        //获取当前账号所有子权限 //导出
        $authList = \Yii::app()->user->getState('_auth');
        $can_export = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrower/borrower/userRepayList2Excel')) || empty($authList)) {
            $can_export = 1;
        }
        return $this->renderPartial('user_repay_list', ['can_export'=>$can_export]);

    }

    /**
     * 和人借款人回款记录
     */
    public function actionUserRepayList2Excel()
    {
        if (!empty($_GET)) {
            // 条件筛选
            if (empty($_GET['user_id']) && empty($_GET['customer_name']) && empty($_GET['repay_time_start']) && empty($_GET['repay_time_end'])) {
                echo '<h1>请输入至少一个查询条件</h1>';
                exit;
            }
            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $condition[] = " d.user_id = '".trim($_GET['user_id'])."'";
            }
            if (!empty($_GET['customer_name'])) {
                $user_query[] = " real_name = '".trim($_GET['customer_name'])."'";
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
            //还款时间
            if (!empty($_GET['repay_time_start'])) {
                $condition[] = " offline.repay_time >= '".strtotime($_GET['repay_time_start'])."'";
            }
            if (!empty($_GET['repay_time_end'])) {
                $condition[] = " offline.repay_time <= '".strtotime($_GET['repay_time_end'])."'";
            }
            if (!empty($condition)) {
                $where = ' where '. implode(' and ', $condition) ;
            }

            // 查询数据
            $sql = "select  o.number,o.product_name,offline.id as offline_repay_id, offline.status, d.data_src, a.name as organization_name, d.user_id, d.name as deal_name,d.id as deal_id,d.borrow_amount,d.rate ,offline.repay_number,offline.repay_amount,offline.repay_type,offline.repay_discount,offline.repay_content,offline.total_repay_capital,offline.surplus_repay_capital,offline.repay_time
                    from firstp2p_deal as d 
                        left join order_info as o on d.approve_number = o.number
                    left join firstp2p_offline_repay as offline on d.id = offline.deal_id  
                    left join firstp2p_deal_agency  as a on d.advisory_id = a.id  {$where} 
                    order by offline.status asc,offline.id desc   ";
            $list = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $user_ids =   ArrayUntil::array_column($list, 'user_id');
            if ($user_ids) {
                $user_ids = implode(',', $user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->cmsdb->createCommand($sql)->queryAll();
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
            foreach ($list as $key => &$value) {
                $value+= $user_info[$value['user_id']]?:['customer_name'=>'','id_number'=>'','phone'=>''];
                $value['repay_content'] = self::$repay_content[$value['repay_content']];
                $value['repay_time_cn'] = date('Y-m-d',$value['repay_time']);
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel2007.php';
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

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '还款记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '借款人ID');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '借款人姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '本次还款金额');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '还款内容');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '累计还款金额');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '剩余还款本金');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '产品名称');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '订单编号');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '借款编号');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '回款时间');
            foreach ($list as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['offline_repay_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['customer_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['repay_amount']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['repay_content']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['total_repay_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['surplus_repay_capital']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['product_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['number']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $value['deal_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['deal_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $value['repay_time_cn']);
            }

            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '个人借款人回款记录 '.date("Y年m月d日 H时i分s秒", time());

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xlsx"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
        }
    }

    public static $repay_content=[
        '1'=>'本金',
        '2'=>'利息',
        '3'=>'本金+利息',
        '4'=>'当期部分还款',
    ];


    /**
     * 初始化数据
     * 企业借款人列表
     */
    public function actionCompanyIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_name'=>Yii::app()->request->getParam('real_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'bankcard'=>Yii::app()->request->getParam('bankcard'),
            'company_user_status'=>Yii::app()->request->getParam('company_user_status'),
            'fa_status'=>Yii::app()->request->getParam('fa_status'),
            'fa_company_name'=>Yii::app()->request->getParam('fa_company_name'),
            'legal_status'=>Yii::app()->request->getParam('legal_status'),
            'id_type'=> 2,//1个人 2企业借款人
        ];

        if (\Yii::app()->request->isPostRequest) {
            $result  = BorrowerService::getInstance()->getBorrowerList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBorrowerDetail($params);
            return;
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $tpl_data['can_export'] = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('borrower/borrower/companyIndex_execl')) || empty($authList)) {
            $tpl_data['can_export'] = 1;
        }
        $tpl_data['legal_status'] = Yii::app()->c->xf_config['legal_status'];
        $tpl_data['company_user_status'] = Yii::app()->c->xf_config['company_user_status'];
        $tpl_data['fa_status'] = Yii::app()->c->xf_config['fa_status'];
        $tpl_data['fa_company_name_list'] = $this->getCsCompany();//接案公司列表
        return $this->renderPartial('company_index_init', $tpl_data);
    }

    /**
     * 核心担保企业列表
     * @return mixed
     */
    public function actionAgencyList()
    {
        if (!empty($_POST)) {
            //数据过滤只展示供应链标的担保方
            $where = " WHERE a.id in (200,581,547,209,582,528,488,198,560,569,534,409,579,576,588,518,190,597,572,593,524,554,633,626,638,631,616,650,599,563,604,549,539,1501,1507,1502,620,141,1510,286,1514,1508,30,546,535,1515,1520,1521,1527,1528,512,1524,495,1531,574,1534,1541,1537,1545,1516,1556) ";
            // 企业名称
            if (!empty($_POST['name'])) {
                $name = trim($_POST['name']);
                $where .= " AND a.name = '{$name}'";
            }
            // 联系电话
            if (!empty($_POST['contract_mobile'])) {
                $phone = GibberishAESUtil::enc(trim($_POST['contract_mobile']), Yii::app()->c->idno_key);
                $where .= " AND a.mobile = '{$phone}' ";
            }

            // 企业状态
            if (!empty($_POST['company_user_status'])) {
                $t = trim($_POST['company_user_status']);
                if($t == '存续'){
                    $where .= " AND a.agency_status in ('存续','存续（在营、开业、在册）','在业','迁出') ";
                }elseif($t == '注销'){
                    $where .= " AND a.agency_status in ('注销','吊销','撤销') ";
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
            $sql   = "SELECT count(distinct a.id) from firstp2p_deal_agency a LEFT JOIN firstp2p_deal b on b.agency_id=a.id and b.deal_status=4 and b.product_class in  ('供应链','企业经营贷') {$where}    ";
            $count = Yii::app()->cmsdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT a.id,a.name,a.agency_status,a.mobile,count(DISTINCT b.id) as deal_count,count(DISTINCT b.user_id) as deal_user_count,sum(b.borrow_amount) as agency_amount 
                    from firstp2p_deal_agency  a
                    LEFT JOIN firstp2p_deal b on b.agency_id=a.id and b.deal_status=4 and b.product_class in  ('供应链','企业经营贷')
                    {$where} GROUP BY a.id  ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            foreach ($list as $key => &$value) {
                $value['mobile'] =  GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key) ?: '-';
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $list;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限 //导出
        $authList = \Yii::app()->user->getState('_auth');
        $tpl_data['can_export'] = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrower/borrower/AgencyList2Excel')) || empty($authList)) {
            $tpl_data['can_export'] = 1;
        }

        $tpl_data['company_user_status'] = Yii::app()->c->xf_config['company_user_status'];
        return $this->renderPartial('agency_list', $tpl_data);
    }

    /**
     * 初始化数据
     * 担保公司下-企业借款人列表
     */
    public function actionAgencyCompanyIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_name'=>Yii::app()->request->getParam('real_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'bankcard'=>Yii::app()->request->getParam('bankcard'),
            'company_user_status'=>Yii::app()->request->getParam('company_user_status'),
            'fa_status'=>Yii::app()->request->getParam('fa_status'),
            'fa_company_name'=>Yii::app()->request->getParam('fa_company_name'),
            'legal_status'=>Yii::app()->request->getParam('legal_status'),
            'id_type'=> 2,//1个人 2企业借款人
            'agency_id'=>Yii::app()->request->getParam('agency_id'),
        ];

        if (\Yii::app()->request->isPostRequest) {
            $result  = BorrowerService::getInstance()->getBorrowerList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBorrowerDetail($params);
            return;
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $tpl_data['can_export'] = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('borrower/borrower/companyIndex_execl')) || empty($authList)) {
            $tpl_data['can_export'] = 1;
        }
        $tpl_data['legal_status'] = Yii::app()->c->xf_config['legal_status'];
        $tpl_data['company_user_status'] = Yii::app()->c->xf_config['company_user_status'];
        $tpl_data['fa_status'] = Yii::app()->c->xf_config['fa_status'];
        $tpl_data['fa_company_name_list'] = $this->getCsCompany();//接案公司列表
        return $this->renderPartial('agency_company_index_init', $tpl_data);
    }


    /**
     * 初始化数据
     * 担保公司下-企业借款人列表
     */
    public function actionCsAgencyCompanyIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_name'=>Yii::app()->request->getParam('real_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'bankcard'=>Yii::app()->request->getParam('bankcard'),
            'company_user_status'=>Yii::app()->request->getParam('company_user_status'),
            'fa_status'=>Yii::app()->request->getParam('fa_status'),
            'fa_company_name'=>Yii::app()->request->getParam('fa_company_name'),
            'legal_status'=>Yii::app()->request->getParam('legal_status'),
            'id_type'=> 2,//1个人 2企业借款人
            'agency_id'=>Yii::app()->request->getParam('agency_id'),
        ];

        if (\Yii::app()->request->isPostRequest) {
            $result  = BorrowerService::getInstance()->getBorrowerList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBorrowerDetail($params);
            return;
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $tpl_data['can_export'] = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('borrower/borrower/companyIndex_execl')) || empty($authList)) {
            $tpl_data['can_export'] = 1;
        }
        $tpl_data['legal_status'] = Yii::app()->c->xf_config['legal_status'];
        $tpl_data['company_user_status'] = Yii::app()->c->xf_config['company_user_status'];
        $tpl_data['fa_status'] = Yii::app()->c->xf_config['fa_status'];
        $tpl_data['fa_company_name_list'] = $this->getCsCompany();//接案公司列表
        return $this->renderPartial('cs_agency_company_index_init', $tpl_data);
    }


    /**
     * 核心担保企业列表导出
     * @return mixed
     */
    public function actionAgencyList2Excel()
    {
        if (!empty($_GET)) {
            //数据过滤只展示供应链标的担保方
            $where = " WHERE a.id in (200,581,547,209,582,528,488,198,560,569,534,409,579,576,588,518,190,597,572,593,524,554,633,626,638,631,616,650,599,563,604,549,539,1501,1507,1502,620,141,1510,286,1514,1508,30,546,535,1515,1520,1521,1527,1528,512,1524,495,1531,574,1534,1541,1537,1545,1516,1556) ";
            // 企业名称
            if (!empty($_GET['name'])) {
                $name = trim($_GET['name']);
                $where .= " AND a.name = '{$name}'";
            }
            // 联系电话
            if (!empty($_GET['contract_mobile'])) {
                $phone = GibberishAESUtil::enc(trim($_GET['contract_mobile']), Yii::app()->c->idno_key);
                $where .= " AND a.mobile = '{$phone}' ";
            }

            // 企业状态
            if (!empty($_GET['company_user_status'])) {
                $t = trim($_GET['company_user_status']);
                if ($t == '存续') {
                    $where .= " AND a.agency_status in ('存续','存续（在营、开业、在册）','在业','迁出') ";
                } elseif ($t == '注销') {
                    $where .= " AND a.agency_status in ('注销','吊销','撤销') ";
                }
            }
            // 查询数据
            $sql = "SELECT a.id,a.name,a.agency_status,a.mobile,count(DISTINCT b.id) as deal_count,count(DISTINCT b.user_id) as deal_user_count,sum(b.borrow_amount) as agency_amount 
                    from firstp2p_deal_agency  a
                    LEFT JOIN firstp2p_deal b on b.agency_id=a.id and b.deal_status=4 and b.product_class in  ('供应链','企业经营贷')
                    {$where} GROUP BY a.id  ";
            $list = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            foreach ($list as $key => &$value) {
                $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key) ?: '-';
            }
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel2007.php';
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

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '核心担保企业名称');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '关联标的数量');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '关联借款方数量');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '总担保金额');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '企业状态');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '联系电话');
            foreach ($list as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['name']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['deal_count']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['deal_user_count']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['agency_amount']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['agency_status']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['mobile']);
            }
            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '核心担保企业 ' . date("Y年m月d日 H时i分s秒", time());

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="' . $name . '.xlsx"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output'); 
        }
    }




    /**
     * 电话录音
     * @return mixed
     */
    public function actionRecording()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE 1 = 1 ";
            // 企业名称
            if (!empty($_POST['company_id'])) {
                $company_id = intval($_POST['company_id']);
                $where .= " AND company_id = {$company_id} ";
            }
            // 统一识别码
            if (!empty($_POST['tax_number'])) {
                $t      = trim($_POST['tax_number']);
                $where .= " AND tax_number = '{$t}' ";
            }

            //还款时间
            if (!empty($_POST['record_time_start'])) {
                $where .= " AND record_time >= '".strtotime($_POST['record_time_start'])."'";
            }
            if (!empty($_POST['record_time_end'])) {
                $where .= " AND record_time <= '".strtotime($_POST['record_time_end'])."'";
            }

            if (!empty($_POST['addtime_start'])) {
                $where .= " AND addtime >= '".strtotime($_POST['addtime_start'])."'";
            }
            if (!empty($_POST['addtime_end'])) {
                $where .= " AND addtime <= '".strtotime($_POST['addtime_end'])."'";
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
            $sql   = "SELECT count(id) AS count FROM firstp2p_phone_records {$where} ";
            $count = Yii::app()->cmsdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT * FROM firstp2p_phone_records {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                $value['record_time'] = $value['record_time'] ? date('Y.m.d' , $value['record_time']) : '-';
                $value['addtime'] = $value['addtime'] ? date('Y.m.d' , $value['addtime']) : '-';
                $value['operate'] = "<a href='/{$value['file_path']}'  ><button class='layui-btn layui-btn-green'>下载</button></a>";
                $listInfo[] = $value;
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        $company_list = \Yii::app()->cmsdb->createCommand()
            ->select('id,name')
            ->from('firstp2p_cs_company')
            ->queryAll();
        return $this->renderPartial('recording',array( 'company_list'=>$company_list));
    }

    /**
     * 录入录音
     */
    public function actionAddRecording()
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

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '电话录音用户名单模板 '.date("Y年m月d日 H时i分s秒", time());

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

            // 录音时间
            if (empty($_POST['record_time'])) {
                return $this->actionError('录音时间不能为空', 5);
            }
            if (empty($_POST['record_num']) || !is_numeric($_POST['record_num'])) {
                return $this->actionError('录音数量不能为空且必须为数字', 5);
            }

            $_POST['record_time'] = $record_time = strtotime($_POST['record_time']);
            $time = time();
            if($record_time > $time){
                return $this->actionError('请填写有效且正确的时间', 5);
            }
            if(empty($_FILES['uid_path']) || empty($_FILES['file_path'])){
                return $this->actionError('请选择要上传的数据文件', 5);
            }
            //角色校验
            $user_info = BorrowerService::getInstance()->checkCsCompany();
            if($user_info == false){
                return $this->actionError('请使用归属第三方公司登陆账号添加录音', 5);
            }
            $company_info = BorrowerService::getInstance()->getCompanyInfo($user_info['company_id']);
            if($company_info == false || $company_info['status'] != 0){
                return $this->actionError('催收公司数据异常', 5);
            }

            $upload_rar = $this->upload_rar('file_path');
            if ($upload_rar['code'] != 0) {
                return $this->actionError($upload_rar['info'] , 5);
            } else {
                $file_path = $upload_rar['data'];
            }
            $upload_xls = $this->upload_xls('uid_path');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'], 5);
            }
            $template_url = $upload_xls['data'];
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
                return $this->actionError('分配信息中无数据', 5);
            }
            if ($Rows > 10001) {
                return $this->actionError('分配信息中的数据超过1万行', 5);
            }
            unset($data[0]);
            //数据入库
            $_POST['company_id'] = $user_info['company_id'];
            $_POST['company_name'] = $company_info['name'];
            $_POST['tax_number'] = $company_info['tax_number'];
            $result = PartialService::getInstance()->add_records_uids($_POST, $file_path, $data);
            if ($result['code'] != 0) {
                unlink('./'.$template_url);
                return $this->actionError($result['info'], 5);
            }
            return $this->actionSuccess($result['info'], 3);
        }
        return $this->renderPartial('AddRecording' );
    }

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

    public function actionError($msg = '失败', $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }

    public function actionSuccess($msg = '成功', $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    /**
     * 初始化数据
     * 个人借款列表-催收公司
     */
    public function actionCsIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_name'=>Yii::app()->request->getParam('real_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'bankcard'=>Yii::app()->request->getParam('bankcard'),
            'fa_status'=>Yii::app()->request->getParam('fa_status'),
            'fa_company_name'=>Yii::app()->request->getParam('fa_company_name'),
            'contact_status'=>Yii::app()->request->getParam('contact_status'),
            'id_type'=> 1,//1个人 2企业借款人
        ];

        if (\Yii::app()->request->isPostRequest) {
            $user_ids = BorrowerService::getInstance()->getDistributionUid();
            if($user_ids == false){
                $result['data'] = [];
                $result['code'] = 0;
                $result['info'] = 'success';
                echo json_encode($result);die;
            }
            $params['user_ids'] = $user_ids;
            $result  = BorrowerService::getInstance()->getBorrowerList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);die;
        }

        $tpl_data['contact_status'] = Yii::app()->c->xf_config['contact_status'];
        $tpl_data['fa_status'] = Yii::app()->c->xf_config['fa_status'];
        $tpl_data['fa_company_name_list'] = $this->getCsCompany();//接案公司列表
        return $this->renderPartial('cs_index_init', $tpl_data);
    }



    /**
     * 初始化数据
     * 企业借款人列表-催收公司
     */
    public function actionCsCompanyIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_name'=>Yii::app()->request->getParam('real_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'bankcard'=>Yii::app()->request->getParam('bankcard'),
            'company_user_status'=>Yii::app()->request->getParam('company_user_status'),
            'fa_status'=>Yii::app()->request->getParam('fa_status'),
            'fa_company_name'=>Yii::app()->request->getParam('fa_company_name'),
            'legal_status'=>Yii::app()->request->getParam('legal_status'),
            'id_type'=> 2,//1个人 2企业借款人
        ];

        if (\Yii::app()->request->isPostRequest) {

            $user_ids = BorrowerService::getInstance()->getDistributionUid();
            if($user_ids == false){
                $result['data'] = [];
                $result['code'] = 0;
                $result['info'] = 'success';
                echo json_encode($result);die;
            }

            $params['user_ids'] = $user_ids;
            $result  = BorrowerService::getInstance()->getBorrowerList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBorrowerDetail($params);
            return;
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $tpl_data['can_export'] = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('borrower/borrower/companyIndex_execl')) || empty($authList)) {
            $tpl_data['can_export'] = 1;
        }
        $tpl_data['legal_status'] = Yii::app()->c->xf_config['legal_status'];
        $tpl_data['company_user_status'] = Yii::app()->c->xf_config['company_user_status'];
        $tpl_data['fa_status'] = Yii::app()->c->xf_config['fa_status'];
        $tpl_data['fa_company_name_list'] = $this->getCsCompany();//接案公司列表
        return $this->renderPartial('cs_company_index_init', $tpl_data);
    }


    /**
     * 核心担保企业列表-催收管理
     * @return mixed
     */
    public function actionCsAgencyList()
    {
        if (!empty($_POST)) {
            //数据过滤只展示供应链标的担保方
            $where = " WHERE a.id in (200,581,547,209,582,528,488,198,560,569,534,409,579,576,588,518,190,597,572,593,524,554,633,626,638,631,616,650,599,563,604,549,539,1501,1507,1502,620,141,1510,286,1514,1508,30,546,535,1515,1520,1521,1527,1528,512,1524,495,1531,574,1534,1541,1537,1545,1516,1556) ";
            // 企业名称
            if (!empty($_POST['name'])) {
                $name = trim($_POST['name']);
                $where .= " AND a.name = '{$name}'";
            }
            // 联系电话
            if (!empty($_POST['contract_mobile'])) {
                $phone = GibberishAESUtil::enc(trim($_POST['contract_mobile']), Yii::app()->c->idno_key);
                $where .= " AND a.mobile = '{$phone}' ";
            }

            // 企业状态
            if (!empty($_POST['company_user_status'])) {
                $t = trim($_POST['company_user_status']);
                if($t == '存续'){
                    $where .= " AND a.agency_status in ('存续','存续（在营、开业、在册）','在业','迁出') ";
                }elseif($t == '注销'){
                    $where .= " AND a.agency_status in ('注销','吊销','撤销') ";
                }
            }

            $user_ids = BorrowerService::getInstance()->getDistributionUid();
            if($user_ids == false){
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }

            $where .= " and b.user_id in (".implode(',', $user_ids).")";
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

            $sql = "SELECT count(distinct a.id) from firstp2p_deal_agency a LEFT JOIN firstp2p_deal b on b.agency_id=a.id  and b.deal_status=4 and b.product_class in  ('供应链','企业经营贷') {$where}    ";
            $count = Yii::app()->cmsdb->createCommand($sql)->queryScalar();
            if($count == 0){
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT a.id,a.name,a.agency_status,a.mobile,count(DISTINCT b.id) as deal_count,count(DISTINCT b.user_id) as deal_user_count,sum(b.borrow_amount) as agency_amount 
                    from firstp2p_deal_agency  a
                    LEFT JOIN firstp2p_deal b on b.agency_id=a.id   and b.deal_status=4 and b.product_class in  ('供应链','企业经营贷')
                    {$where} GROUP BY a.id  ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            foreach ($list as $key => &$value) {
                $value['mobile'] =  GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key) ?: '-';
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $list;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        $tpl_data['company_user_status'] = Yii::app()->c->xf_config['company_user_status'];
        return $this->renderPartial('cs_agency_list', $tpl_data);
    }

}

<?php

/**
 * 借款记录
 */
class DealOrderController extends \iauth\components\IAuthController
{
    public $pageSize = 10;
    public $contract_dir;
    public $handle_deal_loan_id = [];

    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'csCompanyPolicyMaking','index02','Voucher','AddOfflineRepay', 'index01','callLog','GylVoucher','csPolicyMaking','policyMaking','companyAuditOfflineRepay','CreateNewRepayPlan','Upload','getDiscountOrRepayAmount','index','addVoucher','GetClearData' ,
         );
    }

    /**
     * ok
     * 初始化数据
     * 借款列表-消费信贷标的
     */
    public function actionIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'number'=>Yii::app()->request->getParam('number'),
                'deal_name'=>Yii::app()->request->getParam('deal_name'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'user_id'=>Yii::app()->request->getParam('user_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'auth_status'=>Yii::app()->request->getParam('auth_status'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'organization_type'=>Yii::app()->request->getParam('organization_type'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'product_name'=>Yii::app()->request->getParam('product_name'),
                'deal_status'=>Yii::app()->request->getParam('deal_status'),
                'type'=>1,
                'product_class'=>1,//1消费贷，个体经营贷 2供应链企业经营贷
            ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result         = BorrowerService::getInstance()->getDealOrderList($params);
           
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBeforeBorrowDetail($params);
            return;
        }
        
        //获取当前账号所有子权限 //导出
        $authList = \Yii::app()->user->getState('_auth');
        $can_export = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrow/DealOrder/index_execl')) || empty($authList)) {
            $can_export = 1;
        }

       
        return $this->renderPartial('index_init', ['can_export'=>$can_export]);
    }

    /**
     * 借款列表-供应链标的
     */
    public function actionGylIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'number'=>Yii::app()->request->getParam('number'),
            'deal_name'=>Yii::app()->request->getParam('deal_name'),
            'deal_id'=>Yii::app()->request->getParam('deal_id'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'customer_name'=>Yii::app()->request->getParam('customer_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
            'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
            'auth_status'=>Yii::app()->request->getParam('auth_status'),
            'organization_name'=>Yii::app()->request->getParam('organization_name'),
            'organization_type'=>Yii::app()->request->getParam('organization_type'),
            'data_src'=>Yii::app()->request->getParam('data_src'),
            'product_name'=>Yii::app()->request->getParam('product_name'),
            'deal_status'=>Yii::app()->request->getParam('deal_status'),
            'type'=>1,
            'product_class'=>2,//1消费贷，个体经营贷 2供应链企业经营贷
        ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result         = BorrowerService::getInstance()->getDealOrderList($params);

            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBeforeBorrowDetail($params);
            return;
        }

        //获取当前账号所有子权限 //导出
        $authList = \Yii::app()->user->getState('_auth');
        $can_export = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrow/DealOrder/gyl_index_execl')) || empty($authList)) {
            $can_export = 1;
        }


        return $this->renderPartial('gyl_index_init', ['can_export'=>$can_export]);
    }

    public function actionUploadDealVoucher()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                set_time_limit(0); // 设置脚本最大执行时间 为0 永不过期
                if(empty($_POST['id']) || !is_numeric($_POST['id'])){
                    return $this->actionError('标的信息有误，请联系开发人员' , 3);
                }
                $now_time = time();
                if(empty($_POST['make_loan_amount']) || !is_numeric($_POST['make_loan_amount'])){
                    return $this->actionError('放款金额有误，请重新填写' , 3);
                }
                if(empty($_POST['voucher_time']) ){
                    return $this->actionError('请填写放款时间' , 3);
                }
                $voucher_time = strtotime($_POST['voucher_time']);
                if($voucher_time > $now_time ){
                    return $this->actionError('放款时间不能是未来时间' , 3);
                }
                $file = $this->upload_rar('voucher_url');
                if ($file['code'] != 0) {
                    return $this->actionError('放款流水上传失败，请联系开发人员' , 3);
                }
                $voucher_url = 'voucher_data/'.$file['data'];
                $upload_oss = $this->upload_oss('./'.$file['data'], $voucher_url);
                if ($upload_oss === false) {
                    return $this->actionError('放款流水上传至OSS失败，请联系开发人员', 5);
                }
                unlink('./'.$file['data']);
                $edit_data = [
                    'voucher_url' => $voucher_url,
                    'op_voucher_time' =>$now_time,
                    'voucher_time' => $voucher_time,
                    'make_loan_amount' =>$_POST['make_loan_amount'],
                ];
                $edit_ret = Firstp2pDeal::model()->updateByPk($_POST['id'], $edit_data);
                if($edit_ret){
                    return $this->actionSuccess('上传成功' , 3);
                }else{
                    return $this->actionError('上传失败' , 3);
                }
            } catch (Exception $e) {
                return $this->actionError('上传失败' , 3);
            }
        }
        return $this->renderPartial('uploadDealVoucher', ['end' => 0]);
    }

    /**
     * 文件上传OSS
     * @param $filePath
     * @param $ossPath
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
     * 上传压缩文件
     * @param name  string  压缩文件名称
     * @return array
     */
    private function upload_rar($name)
    {
        $file  = $_FILES[$name];
        $types = array('rar' , 'zip' , '7z', 'pdf', 'png', 'jpg', 'jpeg');
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
        $name = $file['name'];
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
     * 消费信贷还款相关信息
     *
     * @return void
     */
    public function actionRepayPlan()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $result = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);
        // //获取当前账号所有子权限
        // $authList = \Yii::app()->user->getState('_auth');
        // $can_auth = 0;
        // if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrower/DealOrder/authDeal')) || empty($authList)) {
        //     $can_auth = 1;
        // }
        $result['auth_type'] =  0 ;
        return $this->renderPartial('detail', $result);
        //return $this->renderPartial('detail_offline_repay', $result);
    }

    /**
     * 新还款计划标的列表
     *
     * @return void
     */
    public function actionNewIndex()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                // 'number'=>Yii::app()->request->getParam('number'),
                // 'deal_name'=>Yii::app()->request->getParam('deal_name'),
                // 'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                // 'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                // 'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'last_repay_end'=>Yii::app()->request->getParam('last_repay_end'),
                'last_repay_start'=>Yii::app()->request->getParam('last_repay_start'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'add_user_name'=>Yii::app()->request->getParam('add_admin_name'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'from'=>1,
                
               
            ];
            //获取借款列表
            $result         = BorrowerService::getInstance()->getNewRepayDealOrderList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/shop/ShopOrder/Auth')) || empty($authList)) {
            $can_auth = 1;
        }
      
        return $this->renderPartial('index_new');
    }


    /**
     * 新还款计划标的特殊协议列表
     *
     * @return void
     */
    public function actionNewRepayPlanAuditList()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                // 'number'=>Yii::app()->request->getParam('number'),
                // 'deal_name'=>Yii::app()->request->getParam('deal_name'),
                // 'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                // 'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                // 'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'last_repay_end'=>Yii::app()->request->getParam('last_repay_end'),
                'last_repay_start'=>Yii::app()->request->getParam('last_repay_start'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'add_user_name'=>Yii::app()->request->getParam('add_admin_name'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'from'=>2,
                
               
            ];
            //获取借款列表
            $result         = BorrowerService::getInstance()->getNewRepayDealOrderList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrower/DealOrder/AuditNewRepayPlan')) || empty($authList)) {
            $can_auth = 1;
        }
      
        return $this->renderPartial('new_repay_plan_audit_list', ['can_auth'=>$can_auth]);
    }



    /**
     * 新还款相关信息
     *
     * @return void
     */
    public function actionNewRepayPlan()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $from_repay_success = Yii::app()->request->getParam('from_repay_success')?1:0;
        $result         = BorrowerService::getInstance()->getNewAboutDealRepayPlanInfo($deal_id);
        $result['from_repay_success'] = $from_repay_success;
        $view = 'detail_new';
        if($from_repay_success){
            $view = 'detail_new_replay_plain';
        }
        return $this->renderPartial($view, $result);
    }

    /**
     * 审核新还款计划
     *
     * @return void
     */
    public function actionAuditNewRepayPlan()
    {
        $log_id = Yii::app()->request->getParam('log_id');
        $result = BorrowerService::getInstance()->getWaitAuditRepayPlanInfo($log_id);
        
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrower/DealOrder/AuditNewRepayPlan')) || empty($authList)) {
            $can_auth = 1;
        }
        $result['can_auth'] = $can_auth;

        return $this->renderPartial('audit_replay_plan', $result);
    }
    
    /**
     * 废弃
     * 审核
     */
    public function actionAuditRepayPlan()
    {
        try {
            $params = [
                'user_id' => \Yii::app()->user->id,
                'log_id' => \Yii::app()->request->getParam('log_id'),
                'type' => Yii::app()->request->getParam('type'),
              
            ];
            $res        = BorrowerService::getInstance()->auditRepayPlan($params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 废弃
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


    /**
     * 新还款成功列表
     * OK
     *
     * @return void
     */
    public function actionRepaySuccess()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'number'=>Yii::app()->request->getParam('number'),
                'deal_name'=>Yii::app()->request->getParam('deal_name'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'repay_end'=>Yii::app()->request->getParam('repay_end'),
                'repay_start'=>Yii::app()->request->getParam('repay_start'),
                'cs_company'=>Yii::app()->request->getParam('cs_company'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'type'=>2,
                
               
            ];
            //获取借款列表
            $result         = BorrowerService::getInstance()->getRepaySuccessList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/shop/ShopOrder/Auth')) || empty($authList)) {
            $can_auth = 1;
        }
        $cs_company = BorrowerService::getInstance()->getCsCompany();
       
        return $this->renderPartial('repay_success', ['can_auth'=>$can_auth,'cs_company'=>$cs_company]);
    }


    /**
    * 添加新还款相关信息
    *
    * @return void
    */
    public function actionAddRepayPlan()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $result         = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);
        //获取当前账号所有子权限
        $result['deal_id'] = $deal_id;
        $authList = \Yii::app()->user->getState('_auth');
       
        $result += BorrowerService::getInstance()->getFavouredPolicy();
    
        $result['can_edit_user_bank'] = false;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrower/borrower/EditUserBank')) || empty($authList)) {
            $result['can_edit_user_bank'] = true;
        }

        return $this->renderPartial('add_repay_plan', $result);
    }


    /**
     * 提交新款还款计划数据
     *
     * @return void
     */
    public function actionCreateNewRepayPlan()
    {
        try {
            $params   = [
                'repay_type'     => \Yii::app()->request->getParam('repay_type'),
                'capital'=>Yii::app()->request->getParam('capital'),
                'interest'=>Yii::app()->request->getParam('interest'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'zhinajin'=>Yii::app()->request->getParam('zhinajin'),
                'faxi'=>Yii::app()->request->getParam('faxi'),
                'repay_plan_num'=>Yii::app()->request->getParam('repay_plan_num'),
                'repay_plan_time'=>Yii::app()->request->getParam('repay_plan_time'),
                'repay_num'=>Yii::app()->request->getParam('repay_num'),
               
            ];
    
            //获取借款列表
            BorrowerService::getInstance()->createNewRepayPlan($params);
            $result['code'] = 0;
            $result['info'] = '创建成功';
            echo json_encode($result);
            die;
        } catch (Exception $e) {
            $result['code'] = 100;
            $result['info'] = $e->getMessage();
            echo json_encode($result);
            die;
        }
    }

    public function actionClearManage()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $result         = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);
        // //获取当前账号所有子权限
        // $authList = \Yii::app()->user->getState('_auth');
        // $can_auth = 0;
        // if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrower/DealOrder/authDeal')) || empty($authList)) {
        //     $can_auth = 1;
        // }
        $result['auth_type'] =  0 ;
        //return $this->renderPartial('detail', $result);
        return $this->renderPartial('detail_offline_repay', $result);
    }


    /**
     * TODO 2021-11-13
     * 退款
     *
     * @return void
     */
    public function actionRefund()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $result         = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);
        $result['auth_type'] =  0 ;
        return $this->renderPartial('refund', $result);
    }


    /**
     * TODO 2021-11-13
     *
     * @return void
     */
    public function actionAddRefund()
    {

        if (\Yii::app()->request->isPostRequest) {
            $params   = [
                'amount'=>Yii::app()->request->getParam('amount'),
                'refund_date'=>Yii::app()->request->getParam('refund_date'),
                'repay_id'=>Yii::app()->request->getParam('repay_id'),
            ];
            $time = 2;
            try {
                BorrowerService::getInstance()->addRepayPlanRefund($params);
                $data = array('type' => 1 , 'msg' => '提交成功' , 'time' => $time);
                $result['code'] = 0;
                $result['info'] = 'success';
            } catch (Exception $e) {
               
                $data = array('type' => 2 , 'msg' => $e->getMessage() , 'time' => $time);

            }
            return $this->renderPartial('render_result', $data);
        }
        $repay_id = Yii::app()->request->getParam('repay_id')?:0;
        $sql = "select last_yop_repay_money  from firstp2p_deal_repay where id = {$repay_id}";
        $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
       
        //$bindid = BorrowerService::getInstance()->getUserBindCard($repay_id);
        //,'bindid'=> $bindid
        return $this->renderPartial('add_refund', ['repay_id'=>$repay_id ,'new_principal'=>$repayInfo['last_yop_repay_money']]);
    }


    /**
     * TODO 2021-11-13
     * 还款凭证补录
     */
    public function actionClearIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'number'=>Yii::app()->request->getParam('number'),
                'deal_name'=>Yii::app()->request->getParam('deal_name'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'user_id'=>Yii::app()->request->getParam('user_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'auth_status'=>Yii::app()->request->getParam('auth_status'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'organization_type'=>Yii::app()->request->getParam('organization_type'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'product_name'=>Yii::app()->request->getParam('product_name'),
                'deal_status'=>Yii::app()->request->getParam('deal_status'),
                'type'=>1,
            ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result         = BorrowerService::getInstance()->getDealOrderList($params);
           
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBeforeBorrowDetail($params);
            return;
        }
        
        //获取当前账号所有子权限 //导出
        $authList = \Yii::app()->user->getState('_auth');
        $can_export = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrow/DealOrder/index_execl')) || empty($authList)) {
            $can_export = 1;
        }

        return $this->renderPartial('clear_index', ['can_export'=>$can_export]);
    }

      /**
     * TODO 2021-11-13
     * 还款凭证补录审核
     */
    public function actionAuditClearIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'number'=>Yii::app()->request->getParam('number'),
                'deal_name'=>Yii::app()->request->getParam('deal_name'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'user_id'=>Yii::app()->request->getParam('user_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'auth_status'=>Yii::app()->request->getParam('auth_status'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'organization_type'=>Yii::app()->request->getParam('organization_type'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'product_name'=>Yii::app()->request->getParam('product_name'),
                'deal_status'=>Yii::app()->request->getParam('deal_status'),
                'type'=>1,
                'is_voucher_audit'=>1,
            ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result         = BorrowerService::getInstance()->auditClearIndex($params);
           
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBeforeBorrowDetail($params);
            return;
        }
        
        //获取当前账号所有子权限 //导出
        $authList = \Yii::app()->user->getState('_auth');
        $can_export = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrow/DealOrder/index_execl')) || empty($authList)) {
            $can_export = 1;
        }

        return $this->renderPartial('audit_clear_index', ['can_export'=>$can_export]);
    }


    /**
     * TODO 2021-11-13
     * 出清列表
     */
    public function actionClearList()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'number'=>Yii::app()->request->getParam('number'),
                'deal_name'=>Yii::app()->request->getParam('deal_name'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'auth_status'=>Yii::app()->request->getParam('auth_status'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'organization_type'=>Yii::app()->request->getParam('organization_type'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'product_name'=>Yii::app()->request->getParam('product_name'),
                'deal_status'=>5,
                'type'=>1,
            ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result         = BorrowerService::getInstance()->getDealOrderList($params);
           
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBeforeBorrowDetail($params);
            return;
        }
        
        //获取当前账号所有子权限 //导出
        $authList = \Yii::app()->user->getState('_auth');
        $can_export = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrow/DealOrder/index_execl')) || empty($authList)) {
            $can_export = 1;
        }

        return $this->renderPartial('clear_list', ['can_export'=>$can_export]);
    }


    /**
     * TODO 2021-11-13
     *  还款凭证补录入口页
     *
     * @return void
     */
    public function actionVoucher()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $is_show_audit = Yii::app()->request->getParam('is_show_audit')?1:0;
        $is_from_clear = Yii::app()->request->getParam('is_from_clear')?1:0;
        $result = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);
        $result['is_show_audit'] =  $is_show_audit ;
        $result['is_from_clear'] =  $is_from_clear ;
        $result['add_voucher'] = Yii::app()->request->getParam('add_voucher')?1:0;
        return $this->renderPartial('voucher', $result);
    }




    /**
     * TODO 2021-11-13
     * 添加凭证
     * @return void
     */
    public function actionAddVoucher()
    {

        if (\Yii::app()->request->isPostRequest) {
            $params   = [
                'amount'=>Yii::app()->request->getParam('amount'),
                'repay_date'=>Yii::app()->request->getParam('repay_date'),
                'repay_id'=>Yii::app()->request->getParam('repay_id'),
            ];
            $time = 2;
            try {
                BorrowerService::getInstance()->addRepayPlanVoucher($params);
                $data = array('type' => 1 , 'msg' => '提交成功' , 'time' => $time);
                $result['code'] = 0;
                $result['info'] = 'success';
            } catch (Exception $e) {
               
                $data = array('type' => 2 , 'msg' => $e->getMessage() , 'time' => $time);

            }
            return $this->renderPartial('render_result', $data);
        }
        $repay_id = Yii::app()->request->getParam('repay_id')?:0;
        $sql = "select new_principal,new_interest  from firstp2p_deal_repay where id = {$repay_id}";
        $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
       
        //$bindid = BorrowerService::getInstance()->getUserBindCard($repay_id);
        //,'bindid'=> $bindid
        return $this->renderPartial('add_voucher', ['repay_id'=>$repay_id ,'new_principal'=>$repayInfo['new_principal'],'new_interest'=>$repayInfo['new_interest']]);
    }
    /**
     * TODO 2021-11-13
     * 审核凭证
     * @return void
     */
    public function actionAuditVoucher()
    {

        if (\Yii::app()->request->isPostRequest) {
            $params   = [
                'id'=>Yii::app()->request->getParam('id'),
            ];
            $time = 2;
            try {
                BorrowerService::getInstance()->auditRepayPlanVoucher($params);
              
                $result['code'] = 0;
                $result['info'] = '审核通过';
                echo json_encode($result);
                die;
            } catch (Exception $e) {
                $result['code'] = 100;
                $result['info'] = $e->getMessage();
                echo json_encode($result);
                die;
            }
        }
        $repay_id = Yii::app()->request->getParam('repay_id')?:0;
        $sql = "select new_principal,new_interest  from firstp2p_deal_repay where id = {$repay_id}";
        $repayInfo  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $sql = "select * from firstp2p_deal_reply_slip where deal_repay_id = {$repay_id} order by id desc";
        $deal_reply_slip  = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $deal_reply_slip['repay_date'] = date('Y-m-d',$deal_reply_slip['repay_time']);
        $deal_reply_slip['reply_slip'] = BorrowerService::$OSS_URL.$deal_reply_slip['reply_slip'];

        $deal_reply_slip['status_cn'] = BorrowerService::$offline_repay_status[ $deal_reply_slip['status']];
    
        return $this->renderPartial('audit_voucher', ['repay_id'=>$repay_id ,'new_principal'=>$repayInfo['new_principal'],'new_interest'=>$repayInfo['new_interest'],'deal_reply_slip'=>$deal_reply_slip]);
    }




    public function actionRefundIndex()
    {

       
        if (\Yii::app()->request->isPostRequest) {

            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                    'page'     => \Yii::app()->request->getParam('page') ?: 1,
                    'pageSize' => $pageSize,
                    'deal_name'=>Yii::app()->request->getParam('deal_name'),
                    'user_id'=>Yii::app()->request->getParam('user_id'),
                    'customer_name'=>Yii::app()->request->getParam('customer_name'),
                    'phone'=>Yii::app()->request->getParam('phone'),
                    'id_number'=>Yii::app()->request->getParam('id_number'),
                    'refund_start'=>Yii::app()->request->getParam('refund_start'),
                    'refund_end'=>Yii::app()->request->getParam('refund_end'),
                    
                ];
            //获取借款列表
            $result = BorrowerService::getInstance()->getRefundDealOrderList($params);
           
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }
       
        return $this->renderPartial('refund_index');

    }

    //2021 11 21  新增线下还款
 
    public function actionOfflineRepay()
    {

        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'number'=>Yii::app()->request->getParam('number'),
                'deal_name'=>Yii::app()->request->getParam('deal_name'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'auth_status'=>Yii::app()->request->getParam('auth_status'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'organization_type'=>Yii::app()->request->getParam('organization_type'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'product_name'=>Yii::app()->request->getParam('product_name'),
                'deal_status'=>Yii::app()->request->getParam('deal_status'),
                'type'=>1,
            ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result         = BorrowerService::getInstance()->getDealOrderList($params);
           
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (Yii::app()->request->getParam('execl') == 1) {
            $params['pageSize'] = 1000000;
            $params['page'] = 1;
            BorrowerService::getInstance()->exportBeforeBorrowDetail($params);
            return;
        }
        
        //获取当前账号所有子权限 //导出
        $authList = \Yii::app()->user->getState('_auth');
        $can_export = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrow/DealOrder/index_execl')) || empty($authList)) {
            $can_export = 1;
        }

        return $this->renderPartial('offline_repay_index', ['can_export'=>$can_export]);
    }

    /**
     * TODO 2021-11-21
     * 添加下线打款
     *
     * @return void
     */
    public function actionAddOfflineRepay()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $result         = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);

        $sql =" select a.*  from firstp2p_offline_repay_detail as a 
LEFT JOIN  firstp2p_offline_repay b on a.offline_repay_id=b.id 
where a.deal_id ={$deal_id} and b.status in (0,1,3) ";
        $firstp2p_offline_repay = Yii::app()->cmsdb->createCommand($sql)->queryAll();

        foreach($firstp2p_offline_repay as $v){
            $repay_ids[] = $v['repay_id'];
        }

        foreach($result['repayPlan'] as &$val ){
            $val['is_add_offline_repay'] = in_array($val['id'],$repay_ids) ? 1: 0;
        }
        $result['auth_type'] =  0 ;
        return $this->renderPartial('add_offline_repay', $result);
    }

       /**
     * 文件上传
     */
    public function actionUpload()
    {
        $input_name = key($_FILES);
        if (!$input_name) {
            return $this->echoJson(array(), 100, "params error : name", true);
        }
        $CUploadedFile = CUploadedFile::getInstanceByName($input_name);
        //上传图片的width*height
        // $sizes = getimagesize($CUploadedFile->tempName);
        // $width = $sizes[0];
        // $height = $sizes[1];
       

        if ($CUploadedFile->hasError) {
            return $this->echoJson(array(), 100, "file upload faild e1", true);
        } else {
            $upresult   = Upload::createFile($CUploadedFile, $_GET['type'], 'create');
            
            if ($upresult != false) {
                Yii::log(basename($upresult).'文件正在上传!', CLogger::LEVEL_INFO);
                try {
                    $img_oss_path = 'purchase'.$upresult;
                    Yii::app()->oss->bigFileUpload("./".$upresult, $img_oss_path);
                    unlink($upresult);
                } catch (Exception $e) {
                    return $this->echoJson(array(), 100, $e->getMessage(), true);
                    // return false;
                }

                $this->echoJson(['file_path'=>$img_oss_path,'file_url'=> Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$img_oss_path], 0);
            } else {
                return $this->echoJson(array(), 100, "file upload faild e2", true);
            }
        }
    }



    public function actionGetDiscountOrRepayAmount()
    {
        $repay_ids = Yii::app()->request->getParam('repay_ids');
        $repay_type = Yii::app()->request->getParam('repay_type');
        $repay_content = Yii::app()->request->getParam('repay_content');
        $repay_amount = Yii::app()->request->getParam('repay_amount');
        $discount = Yii::app()->request->getParam('discount');
        if(empty($repay_ids)){
            return $this->echoJson([],100,'请选择还款计划');
        }
        //$repay_type 1 按折扣 2 按金额
        if($repay_type==1 && empty($discount)){
            return $this->echoJson([],100,'请输入折扣');
        }
        if($repay_type==2 && empty($repay_amount)){
            return $this->echoJson([],100,'请输入还款金额');
        }
        $data = BorrowerService::getInstance()->getDiscountOrRepayAmount($_POST);
        return $this->echoJson($data,0);
    }


    ///创建线下打款
    public function actionCreateOfflineRepay(){

        $params   = [
            'repay_ids' => Yii::app()->request->getParam('repay_ids'),
            'repay_type' => Yii::app()->request->getParam('repay_type'),
            'repay_content' => Yii::app()->request->getParam('repay_content'),
            'repay_amount' => Yii::app()->request->getParam('repay_amount'),
            'discount' => Yii::app()->request->getParam('discount'),
            'logo_path' => Yii::app()->request->getParam('logo_path'),
            'repay_date' => Yii::app()->request->getParam('repay_date'),
        ];
        try{
            BorrowerService::getInstance()->createOfflineRepay( $params );
            return $this->echoJson([],0);
        }catch(Exception $e){
            return $this->echoJson([],100,$e->getMessage());
        }
    }


    //2021 11 21 
    public function actionAuditOfflineRepayList()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'number'=>Yii::app()->request->getParam('number'),
                'deal_name'=>Yii::app()->request->getParam('deal_name'),
                'deal_id'=>Yii::app()->request->getParam('deal_id'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
                'customer_name'=>Yii::app()->request->getParam('customer_name'),
                'phone'=>Yii::app()->request->getParam('phone'),
                'id_number'=>Yii::app()->request->getParam('id_number'),
                'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
                'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
                'auth_status'=>Yii::app()->request->getParam('auth_status'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'organization_type'=>Yii::app()->request->getParam('organization_type'),
                'data_src'=>Yii::app()->request->getParam('data_src'),
                'product_name'=>Yii::app()->request->getParam('product_name'),
              
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
        if (!empty($authList) && strstr(strtolower($authList), strtolower('/borrow/DealOrder/index_execl')) || empty($authList)) {
            $can_export = 1;
        }


        return $this->renderPartial('audit_offline_repay_list', ['can_export'=>0]);

    }


    /**
     * TODO 2021-11-21
     * 下线打款
     *
     * @return void
     */
    public function actionOfflineRepayDetail()
    {

        $offline_repay_id = Yii::app()->request->getParam('offline_repay_id');
        $sql = "select firstp2p_offline_repay.*,firstp2p_offline_repay_detail.repay_id from firstp2p_offline_repay left join firstp2p_offline_repay_detail on firstp2p_offline_repay_detail.offline_repay_id = firstp2p_offline_repay.id  where firstp2p_offline_repay.id = {$offline_repay_id}";
        $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();

        foreach($result as $v){
            $offline_repay_info = $v;
            $repay_ids[] = $v['repay_id'];
            $deal_id = $v['deal_id'];
        }

        

        $offline_repay_info['repay_content_cn'] = BorrowerService::$repay_content[$offline_repay_info['repay_content']];

        $offline_repay_info['repay_type_cn'] = BorrowerService::$offline_repay_type[$offline_repay_info['repay_type']];
        $offline_repay_info['repay_time'] = date('Y-m-d',$offline_repay_info['repay_time']);
        $offline_repay_info['auth_time'] = date('Y-m-d',$offline_repay_info['auth_time']);

     
        $result   = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);

        $result['offline_repay_info'] = $offline_repay_info;
        //var_dump( $result['offline_repay_info'] );die;
        foreach($result['repayPlan'] as &$repay){
            if(in_array($repay['id'],$repay_ids)){
                $repay['is_selected'] = 1;
            }else{
                $repay['is_selected'] = 0;
            }

        }
        //var_dump($result['repayPlan']);die;

        $result['auth_type'] =  0 ;
        return $this->renderPartial('offline_repay_detail', $result);
    }

       /**
     * TODO 2021-11-21
     * 下线打款
     *
     * @return void
     */
    public function actionAuditOfflineRepay()
    {

        $offline_repay_id = Yii::app()->request->getParam('offline_repay_id');
        $sql = "select firstp2p_offline_repay.*,firstp2p_offline_repay_detail.repay_id from firstp2p_offline_repay left join firstp2p_offline_repay_detail on firstp2p_offline_repay_detail.offline_repay_id = firstp2p_offline_repay.id  where firstp2p_offline_repay.id = {$offline_repay_id}";
        $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();

        foreach($result as $v){
            $offline_repay_info = $v;
            $repay_ids[] = $v['repay_id'];
            $deal_id = $v['deal_id'];
        }

        

        $offline_repay_info['repay_content_cn'] = BorrowerService::$repay_content[$offline_repay_info['repay_content']];

        $offline_repay_info['repay_type_cn'] = BorrowerService::$offline_repay_type[$offline_repay_info['repay_type']];
        $offline_repay_info['repay_time'] = date('Y-m-d',$offline_repay_info['repay_time']);

     
        $result   = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);

        $result['offline_repay_info'] = $offline_repay_info;
        //var_dump( $result['offline_repay_info'] );die;
        foreach($result['repayPlan'] as &$repay){
            if(in_array($repay['id'],$repay_ids)){
                $repay['is_selected'] = 1;
            }else{
                $repay['is_selected'] = 0;
            }

        }
        //var_dump($result['repayPlan']);die;

        $result['auth_type'] =  0 ;
        return $this->renderPartial('audit_offline_repay', $result);
    }

    /*
    * TODO 2021-11-21
    * 下线打款
    *
    * @return void
    */
   public function actionDoAuditOfflineRepay()
   {
        try {
            $offline_repay_id = Yii::app()->request->getParam('id');
            //获取借款列表
            $result         = BorrowerService::getInstance()->doAuditOfflineRepay($offline_repay_id);
        
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');  
   }




    public function actionClearStatistics()
    {
        $csCompanyList = RepayService::getInstance()->csCompanyList();

        foreach ($csCompanyList as $key => &$value) {
            $value['user_statistics'] = 'user_statistics_'.$value['company_id'];
            $value['debt_statistics'] = 'debt_statistics'.$value['company_id'];
            # code...
        }

        return $this->renderPartial('clear_statistics',  ['csCompanyList'=>$csCompanyList]);

    }

    public function actionGetClearData()
    {
        
        $borrower = RepayService::getInstance()->borrowerNum();
        $detail = array_values($borrower['detail']);
       
        $data['borrower']['total'] = $borrower['total'] ;
        $data['borrower']['detail'] = $detail;
        $data['borrower']['name_list'] = ArrayUntil::array_column($detail,'name') ;

        $repay = RepayService::getInstance()->repayNum();
     
        $detail = array_values($repay['detail']);
        $data['repay']['total'] = $repay['total'] ;
        $data['repay']['detail'] = $detail;
        $data['repay']['name_list'] = ArrayUntil::array_column($detail,'name') ;

        $debt = RepayService::getInstance()->debtAmount();
     
        $detail = array_values($debt['detail']);
        $data['debt']['total'] = $debt['total'] ;
        $data['debt']['detail'] = $detail;
        $data['debt']['name_list'] = ArrayUntil::array_column($detail,'name') ;

        $repay_amount = RepayService::getInstance()->repayAmount();
     
        $detail = array_values($repay_amount['detail']);
        $data['repay_amount']['total'] = $repay_amount['total'] ;
        $data['repay_amount']['detail'] = $detail;
        $data['repay_amount']['name_list'] = ArrayUntil::array_column($detail,'name') ;

        $csCompanyList = RepayService::getInstance()->csCompanyList();
        foreach ($csCompanyList as $key => &$value) {
            $value['user_statistics'] = 'user_statistics_'.$value['company_id'];
            $value['debt_statistics'] = 'debt_statistics'.$value['company_id'];
            # code...
        }
        $data['csCompanyList'] = $csCompanyList ;
       

        //var_dump($csCompanyList);die;
        return $this->echoJson($data,0);
    }

    /**
     * 出清统计
     */
    public function actionClearStat(){
        $tpl_data = [];
        //借款人分配情况总览
        $fp_num = RepayService::getInstance()->borrowerNum();
        $tpl_data['total'] = $fp_num['total'];
        $tpl_data['no_distribution'] = $fp_num['no_distribution'];
        unset($fp_num['detail'][0]);
        $tpl_data['distribution_detail'] = $fp_num['detail'];
        $tpl_data['no_clear'] = $fp_num['no_clear'];
        $tpl_data['yes_clear'] = $fp_num['yes_clear'];
        //借款人出清情况总览
        //$tpl_data['user_clear'] = RepayService::getInstance()->userClearStat();
        //借款人债权出清情况总览
        $tpl_data['debt_clear'] = RepayService::getInstance()->debtClearStat($fp_num['company_ids']);

        //借款人回款情况总览
        //$tpl_data['repay_info'] = RepayService::getInstance()->repayStat();


        return $this->renderPartial('clearStat', $tpl_data);
    }

    public function actionSuccess($msg = '成功' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    public function actionError($msg = '失败' , $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }


    /**
     * 供应链线下还款审核列表页面
     * @return mixed
     */
    public function actionCompanyAuditOfflineRepayList()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'number'=>Yii::app()->request->getParam('number'),
            'deal_name'=>Yii::app()->request->getParam('deal_name'),
            'deal_id'=>Yii::app()->request->getParam('deal_id'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'customer_name'=>Yii::app()->request->getParam('customer_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
            'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
            'auth_status'=>Yii::app()->request->getParam('auth_status'),
            'organization_name'=>Yii::app()->request->getParam('organization_name'),
            'organization_type'=>Yii::app()->request->getParam('organization_type'),
            'data_src'=>Yii::app()->request->getParam('data_src'),
            'product_name'=>Yii::app()->request->getParam('product_name'),
        ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result = BorrowerService::getInstance()->getCompanyOfflineRepayList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        return $this->renderPartial('company_audit_offline_repay_list');

    }

    /**
     * 供应链还款相关信息
     *
     * @return void
     */
    public function actionGylRepayPlan()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $result = BorrowerService::getInstance()->getGylAboutDealRepayPlanInfo($deal_id);

        return $this->renderPartial('gyl_detail', $result);
    }

    /**
     * 线下还款审核
     * @return void
     */
    public function actionCompanyAuditOfflineRepay()
    {

        $offline_repay_id = Yii::app()->request->getParam('offline_repay_id');
        $sql = "select * from firstp2p_gyl_offline_repay   where id = {$offline_repay_id}";
        $gyl_offline_repay = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $gyl_offline_repay['repay_content'] = Yii::app()->c->xf_config['gyl_repay_content'][$gyl_offline_repay['repay_content']];
        $gyl_offline_repay['repay_time'] = date('Y-m-d',$gyl_offline_repay['repay_time']);
        if(!empty($gyl_offline_repay['reply_slip'])){
            $gyl_offline_repay['reply_slip'] = "https://admin-upload-file.oss-cn-beijing.aliyuncs.com/{$gyl_offline_repay['reply_slip']}";
            //$gyl_offline_repay['reply_slip_html'] = "<a href='{$gyl_offline_repay['reply_slip']}' target='_blank' download><button class='layui-btn layui-btn-primary'>{$gyl_offline_repay['reply_slip']}</button></a>";
        }
        $deal_id = $gyl_offline_repay['deal_id'];

        //还款信息
        $sql = "select    d.id ,d.user_id,  d.name as deal_name, d.repay_time as repay_type, d.start_time, d.create_time as d_create_time, d.borrow_amount as loan_amount, a.name as agency_name  
from firstp2p_deal as d  
    left join firstp2p_deal_agency  as a on d.agency_id = a.id   where d.id = {$deal_id}";
        $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $dealInfo['loan_amount'] = floatval($dealInfo['loan_amount']);
        $dealInfo['d_create_time'] = date('Y-m-d',$dealInfo['d_create_time']);
        $dealInfo['o_create_time'] = date('Y-m-d',$dealInfo['o_create_time']);

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

        $result['firstp2pUserInfo'] = $firstp2pUserInfo;
        $result['dealInfo'] = $dealInfo;
        $result['gyl_offline_repay'] = $gyl_offline_repay;
        $result['auth_type'] =  0 ;
        return $this->renderPartial('company_audit_offline_repay', $result);
    }

    /**
     * 线下还款详情
     * @return void
     */
    public function actionCompanyOfflineRepayDetail()
    {

        $offline_repay_id = Yii::app()->request->getParam('offline_repay_id');
        $sql = "select * from firstp2p_gyl_offline_repay   where id = {$offline_repay_id}";
        $gyl_offline_repay = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $gyl_offline_repay['repay_content'] = Yii::app()->c->xf_config['gyl_repay_content'][$gyl_offline_repay['repay_content']];
        $gyl_offline_repay['repay_time'] = date('Y-m-d',$gyl_offline_repay['repay_time']);
        if(!empty($gyl_offline_repay['reply_slip'])){
            $gyl_offline_repay['reply_slip'] = "https://admin-upload-file.oss-cn-beijing.aliyuncs.com/{$gyl_offline_repay['reply_slip']}";
            //$gyl_offline_repay['reply_slip_html'] = "<a href='{$gyl_offline_repay['reply_slip']}' target='_blank' download><button class='layui-btn layui-btn-primary'>{$gyl_offline_repay['reply_slip']}</button></a>";
        }
        $deal_id = $gyl_offline_repay['deal_id'];

        //还款信息
        $sql = "select    d.id ,d.user_id,  d.name as deal_name, d.repay_time as repay_type, d.start_time, d.create_time as d_create_time, d.borrow_amount as loan_amount, a.name as agency_name  
from firstp2p_deal as d  
    left join firstp2p_deal_agency  as a on d.agency_id = a.id   where d.id = {$deal_id}";
        $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $dealInfo['loan_amount'] = floatval($dealInfo['loan_amount']);
        $dealInfo['d_create_time'] = date('Y-m-d',$dealInfo['d_create_time']);
        $dealInfo['o_create_time'] = date('Y-m-d',$dealInfo['o_create_time']);

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

        $result['firstp2pUserInfo'] = $firstp2pUserInfo;
        $result['dealInfo'] = $dealInfo;
        $result['gyl_offline_repay'] = $gyl_offline_repay;
        $result['auth_type'] =  0 ;
        return $this->renderPartial('company_offline_repay_detail', $result);
    }


    public function actionDoCompanyAuditOfflineRepay()
    {
        try {
            $offline_repay_id = Yii::app()->request->getParam('id');
            if(empty( $offline_repay_id)){
                throw new Exception('参数错误-1');
            }
            $sql= "select * from firstp2p_gyl_offline_repay where id  = {$offline_repay_id}  for update";
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
            $sql =" UPDATE firstp2p_gyl_offline_repay set status = 1, auth_admin_id= {$current_admin_id},auth_user_name='{$username}',auth_time = {$now} where id = {$offline_repay_id}";
            $res = Yii::app()->cmsdb->createCommand($sql)->execute();
            if($res === false){
                throw new Exception('更新失败，请稍后重试');
            }
            $this->echoJson([], 0, '操作成功');
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
    }

    /**
     * ok
     * 初始化数据
     * 借款列表-消费信贷标的
     */
    public function actionIndex01()
    {
        $result = [];
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_id'=>\Yii::app()->request->getParam('user_id'),
            'type'=>1,
            'product_class'=>1,//1消费贷，个体经营贷 2供应链企业经营贷
        ];
        if(empty($params['user_id']) || !is_numeric($params['user_id'])){
            $result['data'] = [];
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result = BorrowerService::getInstance()->getDealOrderList($params);

            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }
        $result = BorrowerService::getInstance()->getDealOrderList($params);
        return $this->renderPartial('index_init_01',['countNum'=> $result['countNum'],'total_loan_amount'=> $result['total_loan_amount']]);
    }


    /**
     * ok
     * 初始化数据
     * 借款列表-消费信贷标的
     */
    public function actionDistributionDetail()
    {
        $result = [];
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_id'=>\Yii::app()->request->getParam('user_id'),
        ];
        if(empty($params['user_id']) || !is_numeric($params['user_id'])){
            $result['data'] = [];
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (\Yii::app()->request->isPostRequest) {
            $result = BorrowerService::getInstance()->getDistributionDetail($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        return $this->renderPartial('DistributionDetail' );
    }


    /**
     * 策略制定
     */
    public function actionPolicyMaking()
    {
        $user_id=\Yii::app()->request->getParam('user_id');
        $cs_suggest = '';
        if(!empty($user_id) && is_numeric($user_id)){
            $sql = "select  user_id,cs_suggest  from xf_borrower_bind_card_info_online where user_id={$user_id} ";
            $user_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if ($user_info && in_array($user_info['cs_suggest'], [1,2])) {
                $cs_suggest = $user_info['cs_suggest'];
            }
        }
        return $this->renderPartial('policy_making', ['cs_suggest' => $cs_suggest]);
    }

    public function actionCallLog()
    {
        $result = [];
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_id'=>\Yii::app()->request->getParam('user_id'),
            'type'=>1,
            'product_class'=>1,//1消费贷，个体经营贷 2供应链企业经营贷
        ];
        if(empty($params['user_id']) || !is_numeric($params['user_id']) || !\Yii::app()->request->isPostRequest){
            $result['data'] = [];
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        //维护记录
        $result = BorrowerService::getInstance()->getCallLog($params);
        $result['code'] = 0;
        $result['info'] = 'success';
        echo json_encode($result);
        die;
    }


    public function actionAddCsSuggest()
    {
        if (!empty($_POST)) {
            if (empty($_POST['user_id']) ) {
                $this->echoJson(array(), 1, "请正确输入用户ID");
            }
            $user_id = intval($_POST['user_id']);
            if (empty($_POST['cs_suggest']) ) {
                $this->echoJson(array(), 1, "请正确选择分类");
            }

            $sql = "select  *  from xf_borrower_bind_card_info_online where  user_id={$user_id} ";
            $user_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 1, "用户信息不存在");
            }
            if ($_POST['cs_suggest'] == $user_info['cs_suggest']) {
                $this->echoJson(array(), 1, "催收建议无变更，无需修改");
            }

            $sql = "UPDATE xf_borrower_bind_card_info_online SET cs_suggest ={$_POST['cs_suggest']} WHERE id = {$user_info['id']} ";
            $update = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($update) {
                $this->echoJson(array(), 0, "保存成功");
            } else {
                $this->echoJson(array(), 1, "保存失败");
            }
        }

        return $this->renderPartial('AddCallBackLog', array());
    }


    /**
     * 借款列表-供应链
     */
    public function actionIndex02()
    {
        $result = [];
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'user_id'=>\Yii::app()->request->getParam('user_id'),
            'type'=>1,
            'product_class'=>2,//1消费贷，个体经营贷 2供应链企业经营贷
        ];
        if(empty($params['user_id']) || !is_numeric($params['user_id'])){
            $result['data'] = [];
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result = BorrowerService::getInstance()->getDealOrderList($params);

            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        return $this->renderPartial('index_init_02');
    }


    /**
     * 策略制定
     */
    public function actionCsPolicyMaking()
    {
        $user_id=\Yii::app()->request->getParam('user_id');
        $cs_suggest = '';
        $user_info = [];
        if(!empty($user_id) && is_numeric($user_id)){
            $sql = "select  user_id,real_name,idno,mobile   from xf_borrower_bind_card_info_online where user_id={$user_id} ";
            $user_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if ($user_info ) {
                $user_info['idno'] = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
                $user_info['mobile'] = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key);
            }
        }
        return $this->renderPartial('cs_policy_making', $user_info);
    }

    public function actionAddCallLog()
    {
        if (!empty($_POST)) {
            if (empty($_POST['user_id']) ) {
                $this->echoJson(array(), 1, "请正确输入用户ID");
            }
            $user_id = intval($_POST['user_id']);
            if (empty($_POST['id_type']) || !in_array($_POST['id_type'], [1,2]) ) {
                $this->echoJson(array(), 1, "用户类型异常");
            }

            if($_POST['id_type'] == 1){
                if (empty($_POST['contact_status']) || !in_array($_POST['contact_status'], [1,2,3,4,5,6]) ) {
                    $this->echoJson(array(), 1, "联系状态异常");
                }
                if (empty($_POST['question_2']) || !in_array($_POST['question_2'], [1,2]) ) {
                    $this->echoJson(array(), 1, "是否本人接听异常");
                }

                if (empty($_POST['question_3']) || !in_array($_POST['question_3'], [1,2,3,4,5,6]) ) {
                    $this->echoJson(array(), 1, "客户状态异常");
                }
                if(empty($_POST['question_3']) == 6 && empty($_POST['other'])){
                    $this->echoJson(array(), 1, "请输入用户的其他状态");
                }
            }elseif($_POST['id_type'] == 2){
                if (empty($_POST['legal_status']) || !in_array($_POST['legal_status'], [1,2,3,4,5,6]) ) {
                    $this->echoJson(array(), 1, "法催状态异常");
                }
                if(empty($_POST['file_path'])){
                    $this->echoJson(array(), 1, "请选择要上传的数据文件");
                }
                $_FILES['file_path'] = $_POST['file_path'];
                $upload_rar = $this->upload_rar('file_path');
                if ($upload_rar['code'] != 0) {
                    $this->echoJson(array(), 1, $upload_rar['info']);
                }
                $file_path_url = $upload_rar['data'];
            }


            if (empty($_POST['remark'])   ) {
                $remark_info = $_POST['id_type'] == 1 ? "催收记录异常" : "情况说明异常";
                $this->echoJson(array(), 1, $remark_info);
            }

            $sql = "select  *  from xf_borrower_bind_card_info_online where  user_id={$user_id} ";
            $user_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 1, "用户信息不存在");
            }
            $data = [];
            $time = time();
            $data['user_id'] = $_POST['user_id'];
            $data['user_type'] = $_POST['id_type'];
            $data['mobile'] = GibberishAESUtil::dec($user_info['mobile'] , Yii::app()->c->idno_key);
            $data['add_time'] = $time;
            $data['add_user_name'] = \Yii::app()->user->name;
            $data['add_user_id'] = \Yii::app()->user->id;;
            $data['remark'] = $_POST['remark'];
            if($_POST['id_type'] == 1){
                $data['contact_status'] = $_POST['contact_status'];
                $data['question_2'] = $_POST['question_2'];
                $data['question_3'] = $_POST['question_3'];
                $data['other'] = $_POST['other'] ?: '';
            }elseif($_POST['id_type'] == 2){
                $data['legal_status'] = $_POST['legal_status'];
                $data['file_path'] = $file_path_url ?: '';
            }

            $ret = BaseCrudService::getInstance()->add('BorrowerCallBackLog', $data);
            if(false == $ret){
                $this->echoJson(array(), 1, "维护记录失败");
            }

            if($_POST['id_type'] == 2){
                $sql = "UPDATE xf_borrower_bind_card_info_online SET cs_suggest ={$_POST['legal_status']} WHERE id = {$user_info['id']} and  id_type=2";
                Yii::app()->cmsdb->createCommand($sql)->execute();
            }

            $this->echoJson(array(), 0, "维护记录成功");
        }

        return $this->renderPartial('AddCallBackLog', array());
    }

    public function actionAddCompanyCallLog()
    {
        if (!empty($_POST)) {
            if (empty($_POST['user_id']) ) {
                return $this->actionError('请正确输入用户ID', 5);
            }
            $user_id = intval($_POST['user_id']);
            if (empty($_POST['id_type']) || !in_array($_POST['id_type'], [1,2]) ) {
                return $this->actionError('请正确输入用户类型', 5);
            }

            if (empty($_POST['legal_status']) || !in_array($_POST['legal_status'], [1,2,3,4,5,6]) ) {
                return $this->actionError('法催状态异常', 5);
            }
            if(empty($_FILES['file_path'])){
                return $this->actionError('请选择要上传的数据文件', 5);
            }
            $upload_rar = $this->upload_rar('file_path');
            if ($upload_rar['code'] != 0) {
                return $this->actionError( $upload_rar['info'], 5);
            }
            $file_path_url = $upload_rar['data'];
            if (empty($_POST['remark'])   ) {
                $remark_info = $_POST['id_type'] == 1 ? "催收记录异常" : "情况说明异常";
                return $this->actionError($remark_info, 5);
            }

            $sql = "select  *  from xf_borrower_bind_card_info_online where  user_id={$user_id} ";
            $user_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$user_info) {
                return $this->actionError('用户信息不存在', 5);
            }
            Yii::app()->cmsdb->beginTransaction();
            $data = [];
            $time = time();
            $data['user_id'] = $_POST['user_id'];
            $data['user_type'] = $_POST['id_type'];
            $data['mobile'] = GibberishAESUtil::dec($user_info['mobile'] , Yii::app()->c->idno_key);
            $data['add_time'] = $time;
            $data['add_user_name'] = \Yii::app()->user->name;
            $data['add_user_id'] = \Yii::app()->user->id;;
            $data['remark'] = $_POST['remark'];
            $data['legal_status'] = $_POST['legal_status'];
            $data['legal_files'] = $file_path_url ?: '';
            $ret = BaseCrudService::getInstance()->add('BorrowerCallBackLog', $data);
            if(false == $ret){
                Yii::app()->cmsdb->rollback();
                return $this->actionError('维护记录失败', 5);
            }

            $sql = "UPDATE xf_borrower_bind_card_info_online SET cs_suggest ={$_POST['legal_status']},update_time={$time} WHERE id = {$user_info['id']} and  id_type=2";
            $edit_01 = Yii::app()->cmsdb->createCommand($sql)->execute();
            if(!$edit_01){
                Yii::app()->cmsdb->rollback();
                return $this->actionError('维护记录失败', 5);
            }
            Yii::app()->cmsdb->commit();
            return $this->actionSuccess('维护记录成功', 3);
        }
    }

    /**
     * 企业借款人-法诉状态维护
     */
    public function actionCsCompanyPolicyMaking()
    {
        $user_id=\Yii::app()->request->getParam('user_id');
        $cs_suggest = '';
        $user_info = [];
        if(!empty($user_id) && is_numeric($user_id)){
            $sql = "select  user_id,real_name,idno,mobile,cs_suggest   from xf_borrower_bind_card_info_online where user_id={$user_id} ";
            $user_info = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if ($user_info ) {
                $user_info['idno'] = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
                $user_info['mobile'] = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key) ?: '-';
                $user_info['old_legal_status'] = Yii::app()->c->xf_config['legal_status'][$user_info['cs_suggest']] ?: '-' ;
            }
        }
        $user_info['legal_status'] = Yii::app()->c->xf_config['legal_status'];
        return $this->renderPartial('cs_company_policy_making', $user_info);
    }


    /**
     * ok
     * 初始化数据
     * 借款列表-消费信贷标的-催收公司
     */
    public function actionCsIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'number'=>Yii::app()->request->getParam('number'),
            'deal_name'=>Yii::app()->request->getParam('deal_name'),
            'deal_id'=>Yii::app()->request->getParam('deal_id'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'customer_name'=>Yii::app()->request->getParam('customer_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
            'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
            'auth_status'=>Yii::app()->request->getParam('auth_status'),
            'organization_name'=>Yii::app()->request->getParam('organization_name'),
            'organization_type'=>Yii::app()->request->getParam('organization_type'),
            'data_src'=>Yii::app()->request->getParam('data_src'),
            'product_name'=>Yii::app()->request->getParam('product_name'),
            'deal_status'=>Yii::app()->request->getParam('deal_status'),
            'type'=>1,
            'product_class'=>1,//1消费贷，个体经营贷 2供应链企业经营贷
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
            //获取借款列表
            $result = BorrowerService::getInstance()->getDealOrderList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }
        return $this->renderPartial('cs_index_init' );
    }


    /**
     * TODO 2021-11-13
     * 添加凭证
     * @return void
     */
    public function actionAddNewVoucher()
    {
        if (empty($_POST)) {
            return $this->actionError('请求方式异常', 5);
        }
        if (empty($_POST['deal_id']) ) {
            return $this->actionError('借款编号异常', 5);
        }
        $deal_id = intval($_POST['deal_id']);
        if (empty($_POST['repay_date'])   ) {
            return $this->actionError('凭证中还款日期异常', 5);
        }
        if(empty($_FILES['file_path'])){
            return $this->actionError('请选择要上传的付款凭证', 5);
        }
        //获取三方公司ID
        $company_id = BorrowerService::getInstance()->getCompanyId($deal_id);
        if(!$company_id){
            return $this->actionError('未查询到分配有效的第三方公司', 5);
        }

        $upload_rar = $this->upload_rar('file_path');
        if ($upload_rar['code'] != 0) {
            return $this->actionError( $upload_rar['info'], 5);
        }

        $file_path_url = 'replay_plan_voucher_info/'.$upload_rar['data'];
        $upload_oss = $this->upload_oss('./'.$upload_rar['data'], $file_path_url);
        if($upload_oss === false){
            return $this->actionError( '上传OSS失败', 5);
        }
        if (empty($_POST['repay_ids']) ) {
            return $this->actionError('还款期数异常', 5);
        }
        try {
            foreach ($_POST['repay_ids'] as $value ){
                $params   = [
                    'deal_id' => $deal_id,
                    'repay_date' => $_POST['repay_date'],
                    'repay_id' => $value,
                    'file_path_url'=>$file_path_url,
                    'company_id'=>$company_id
                ];
                BorrowerService::getInstance()->addNewRepayPlanVoucher($params);
            }
            return $this->actionSuccess('提交成功', 5);
        } catch (Exception $e) {
            Yii::log("actionAddNewVoucher  error  {$e->getMessage()}", 'error');
            return $this->actionError($e->getMessage(), 5);
        }
    }

    /**
     * 借款列表-供应链标的
     */
    public function actionCsGylIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'number'=>Yii::app()->request->getParam('number'),
            'deal_name'=>Yii::app()->request->getParam('deal_name'),
            'deal_id'=>Yii::app()->request->getParam('deal_id'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'customer_name'=>Yii::app()->request->getParam('customer_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
            'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
            'auth_status'=>Yii::app()->request->getParam('auth_status'),
            'organization_name'=>Yii::app()->request->getParam('organization_name'),
            'organization_type'=>Yii::app()->request->getParam('organization_type'),
            'data_src'=>Yii::app()->request->getParam('data_src'),
            'product_name'=>Yii::app()->request->getParam('product_name'),
            'deal_status'=>Yii::app()->request->getParam('deal_status'),
            'type'=>1,
            'product_class'=>2,//1消费贷，个体经营贷 2供应链企业经营贷
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
            //获取借款列表
            $result = BorrowerService::getInstance()->getDealOrderList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        return $this->renderPartial('cs_gyl_index_init' );
    }

    /**
     *  工银亮还款录入入口
     * @return void
     */
    public function actionGylVoucher()
    {
        $deal_id = Yii::app()->request->getParam('deal_id');
        $is_show_audit = Yii::app()->request->getParam('is_show_audit')?1:0;
        $is_from_clear = Yii::app()->request->getParam('is_from_clear')?1:0;
        $result = BorrowerService::getInstance()->getGylDealRepayPlanInfo($deal_id);
        $result['is_show_audit'] =  $is_show_audit ;
        $result['is_from_clear'] =  $is_from_clear ;
        $result['add_voucher'] = Yii::app()->request->getParam('add_voucher')?1:0;
        return $this->renderPartial('gyl_voucher', $result);
    }

    /**
     * 供应链增加还款
     * @return void
     */
    public function actionAddGylVoucher()
    {
        if (empty($_POST)) {
            return $this->actionError('请求方式异常', 5);
        }
        if (empty($_POST['deal_id']) ) {
            return $this->actionError('借款编号异常', 5);
        }
        $deal_id = intval($_POST['deal_id']);
        if (empty($_POST['repay_time'])   ) {
            return $this->actionError('凭证中还款日期异常', 5);
        }
        if(empty($_FILES['file_path'])){
            return $this->actionError('请选择要上传的付款凭证', 5);
        }
        /*
        if (empty($_POST['repay_content']) || !in_array($_POST['repay_content'], [1,2,3])) {
            return $this->actionError('还款内容异常', 5);
        }
        if (!empty($_POST['repay_interest']) && !is_numeric($_POST['repay_interest'])) {
            return $this->actionError('还款利息异常', 5);
        }
        if(in_array($_POST['repay_content'], [1,3])  && $_POST['repay_capital'] <= 0){
            return $this->actionError('还款内容选择错误', 5);
        }
        if(in_array($_POST['repay_content'], [2,3])  && $_POST['repay_interest'] <= 0){
            return $this->actionError('还款内容选择错误', 5);
        }
        */

        if (empty($_POST['repay_capital']) || !is_numeric($_POST['repay_capital']) ) {
            return $this->actionError('还款金额异常', 5);
        }
        //获取三方公司ID
        $company_info = BorrowerService::getInstance()->getGylCompanyInfo($deal_id);
        if(!$company_info || empty($company_info['company_id']) || empty($company_info['id'])){
            return $this->actionError('未查询到分配有效的第三方公司', 5);
        }
        $company_id = $company_info['company_id'];
        $distribution_id = $company_info['id'];

        $upload_rar = $this->upload_rar('file_path');
        if ($upload_rar['code'] != 0) {
            return $this->actionError( $upload_rar['info'], 5);
        }

        $file_path_url = 'replay_gyl_voucher_info/'.$upload_rar['data'];
        $upload_oss = $this->upload_oss('./'.$upload_rar['data'], $file_path_url);
        if($upload_oss === false){
            return $this->actionError( '上传OSS失败', 5);
        }

        try {
            $params   = [
                'deal_id' => $deal_id,
                'repay_time' => $_POST['repay_time'],
                //'repay_content' => $_POST['repay_content'],
                'repay_capital' => $_POST['repay_capital'] ?: 0,
               // 'repay_interest' => $_POST['repay_interest'] ?: 0,
                'file_path_url'=>$file_path_url,
                'company_id'=>$company_id,
                'distribution_id'=>$distribution_id
            ];
            BorrowerService::getInstance()->addGylRepayPlanVoucher($params);
            return $this->actionSuccess('提交成功', 5);
        } catch (Exception $e) {
            Yii::log("AddGylVoucher  error  {$e->getMessage()}", 'error');
            return $this->actionError($e->getMessage(), 5);
        }
    }


    public function actionA(){
        $mobile = GibberishAESUtil::enc(6214660012364675, Yii::app()->c->idno_key); // 手机号加密
        var_dump($mobile);die;
    }

    /**
     * 下载相关资料
     */
    public function actionDownloadContract(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $user_id = Yii::app()->request->getParam('user_id');

        try {
            $user_sql = "select id,real_name,idno from xf_borrower_bind_card_info_online where user_id={$user_id}  ";
            $user_info = Yii::app()->cmsdb->createCommand($user_sql)->queryRow();
            if (!$user_info) {
                exit("未查询到此用户信息");
            }
            $user_info['idno'] = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);

            $deal_sql = "select id from firstp2p_deal where user_id={$user_id} and deal_status in (4,5)";
            $deal_ids = Yii::app()->cmsdb->createCommand($deal_sql)->queryColumn();
            if (!$deal_ids) {
                exit("未查询到此用户的借款信息");
            }

            //创建文件夹
            $base_dir = 'upload/contract';
            $firstp2p_dir_name = $user_info['real_name'].'_'.$user_info['idno'];
            $dir = $this->contract_dir =  $base_dir. '/' .$firstp2p_dir_name. '/';

            $ret_04 = $this->getDealUserContract($deal_ids);
            if(!$ret_04){
                exit("合同下载失败");
            }

            $zipName = $base_dir.'/'.$firstp2p_dir_name.'.zip';
            // 如果压缩文件不存在，就创建压缩文件
            if (! is_file($zipName)) {
                $fp = fopen($zipName, 'w');
                fclose($fp);
            }
            $zip = new \ZipArchive();
            if ($zip->open($zipName, \ZipArchive::CREATE) === true) {
                $this->addFileToZip($dir, $zip);
                $zip->close();
            } else {
                exit('下载失败！');
            }
            $file = fopen($zipName, "r");
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length: ".filesize($zipName));
            Header("Content-Disposition: attachment; filename=".$firstp2p_dir_name.".zip");
            $buffer=1024;
            while (!feof($file)) {
                $file_data = fread($file, $buffer);
                echo $file_data;
            }
            return true;
        } catch (Exception $e) {
            Yii::log("DownloadContract Exception error  {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    private function getDealUserContract($deal_ids){
        try {
            Yii::log('getDealUserContract   deals num : '.count($deal_ids), 'info', __CLASS__);
            foreach ($deal_ids as $deal_id) {
                $file_dir = $this->contract_dir.'/'.$deal_id;
                if (!file_exists($file_dir) && !mkdir($file_dir, 0777, true)) {
                    Yii::log('makeDealContract contract deal_id : '.$deal_id. ' 创建项目合同目录失败 ');
                    return false;
                }

                //创建原始借款与出借合同
                $ret_01 = $this->makeDealContract($deal_id, $file_dir);
                if(!$ret_01){
                    Yii::log('getDealUserContract  deal_id: '.$deal_id. ' makeDealContract return false ');
                    return false;
                }

                //已签署授权委托协议
                $ret_02 = $this->getIntensiveContract($deal_id,$file_dir);
                if(!$ret_02){
                    Yii::log('getDealUserContract  deal_id: '.$deal_id. ' getIntensiveContract return false ');
                    return false;
                }

                //化债协议生成
                $ret_03 = $this->getDebtContract($deal_id,$file_dir);
                if(!$ret_03){
                    Yii::log('getDealUserContract  deal_id: '.$deal_id. ' getDebtContract return false ');
                    return false;
                }

                //4-原始放款流水
                $ret_04 = $this->getVoucher($deal_id,$file_dir);
                if(!$ret_04){
                    Yii::log('getDealUserContract  deal_id: '.$deal_id. ' getVoucher return false ');
                    return false;
                }

                return true;
            }
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getVoucher($deal_id, $file_dir){
        //委托协议
        $deal_sql = "select id,voucher_url from firstp2p_deal where id={$deal_id} ";
        $deal_info = Yii::app()->cmsdb->createCommand($deal_sql)->queryRow();
        if (!$deal_info) {
            Yii::log('getVoucher contract deal_id : '.$deal_id. ' 标的数据异常 ');
            return false;
        }

        $user_file_dir = $file_dir.'/4-原始放款流水';
        if (!file_exists($user_file_dir) && !mkdir($user_file_dir, 0777, true)) {
            Yii::log('getVoucher contract deal_id : '.$deal_id. ' 原始放款流水创建失败 ');
            return false;
        }
        if(!empty($deal_info['voucher_url'])) {
            $oss_preview_address = "https://admin-upload-file.oss-cn-beijing.aliyuncs.com/{$deal_info['voucher_url']}";
            $r = file_get_contents($oss_preview_address);
           // $pz_name = str_replace("voucher_data/upload/","",$deal_info['voucher_url']);
            $pz_name = substr($deal_info['voucher_url'], 29);
            $pz_name_array = explode('.', $pz_name);
            file_put_contents($user_file_dir."/放款凭证.".$pz_name_array[1], $r);
        }else{
          //  file_put_contents($user_file_dir."/示例.txt", '');
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
                }
            }
        }
        @closedir($handler);
    }

    public function makeDealContract($deal_id, $file_dir){
        //借款方借款合同
        $deal_info = Yii::app()->phdb->createCommand("select id,contract_path,name from firstp2p_deal where id={$deal_id} and deal_status in (4,5) and contract_path!='' ")->queryRow();
        if(empty($deal_info)){
            Yii::log('makeDealContract contract firstp2p_deal for deal_id: '.$deal_id. ' is not find  contract file ');
            return false;
        }
        //项目合同文件
        if(empty($deal_info['contract_path'])){
            Yii::log('makeDealContract contract deals name : '.$deal_info['name']. ' is not find  contract file ');
            return false;
        }

        //借款合同
        $deal_user_file_dir = $file_dir.'/5-借款相关协议';
        if (!file_exists($deal_user_file_dir) && !mkdir($deal_user_file_dir, 0777, true)) {
            Yii::log('makeDealContract contract deals name : '.$deal_info['name']. ' 借款合同目录创建失败 ');
            return false;
        }
        $deal_contracts = explode(';',$deal_info['contract_path']);
        foreach ($deal_contracts as $d_c){
            $oss_preview_address = 'https://xf-deal-contract.oss-cn-beijing-internal.aliyuncs.com/'.$deal_info['id'].'/'.$d_c;
            $r = file_get_contents($oss_preview_address);
            file_put_contents($deal_user_file_dir."/".$d_c, $r);
        }

        //原始出借合同
        $table_name = $deal_id % 128;
        $contract_sql = "select number,title,user_id,deal_load_id,deal_id from contract_$table_name  
					 where deal_id=$deal_id and status=1 and source_type=0 and deal_load_id>0  ";
        $contract_info = Yii::app()->contractdb->createCommand($contract_sql)->queryAll();
        if (!$contract_info) {
            Yii::log("makeDealContract  deal_id= {$deal_id} contract_info error,sql:$contract_sql");
            return false;
        }
        foreach ($contract_info as $item) {
            $pathInfo = Yii::app()->contractdb->createCommand('select group_id,path from '.$this->getTableName($item['number']).' where contract_number =:num and status = 1 ')->bindValues([':num' => $item['number']])->queryRow();
            if (empty($pathInfo)) {
                Yii::log("makeDealContract  deal_id= {$deal_id} contract_number {$item['number']} is not find  contract file  ");
                continue;
            }
            $oss_preview_address = 'https://xf-data.oss-cn-beijing.aliyuncs.com/'.$pathInfo['group_id'].substr($pathInfo['path'], 3);
            $r = file_get_contents($oss_preview_address);
            $load_user_file_dir = $file_dir.'/1-原始出借合同';
            if (!file_exists($load_user_file_dir) && !mkdir($load_user_file_dir, 0777, true)) {
                Yii::log("makeDealContract  deal_id= {$deal_id} mkdir $load_user_file_dir error   ");
                return false;
            }
           // $user_id = $item['user_id'] ? '-'.$item['user_id'] : '';
            $pdf_name =  $item['user_id'].'-'.$item['deal_load_id'].'-'.$deal_info['id'].'-原始出借合同.pdf';
           // $contract_path = '/'.$deal_info['deal_id'].'/'.$pdf_name;
            file_put_contents($load_user_file_dir."/".$pdf_name, $r);
        }
        return true;
    }

    public function getIntensiveContract($deal_id, $file_dir){
        //委托协议
        $user_load_sql = "select user_id,id from firstp2p_deal_load where deal_id={$deal_id} ";
        $load_info = Yii::app()->phdb->createCommand($user_load_sql)->queryAll();
        if (!$load_info) {
            Yii::log('getIntensiveContract contract deal_id : '.$deal_id. ' 投资记录数据异常 ');
            return false;
        }
        $load_uids = [];
        foreach ($load_info as $value){
            $load_uids[$value['user_id']][] = $value['id'];
        }
        $load_uids_key = array_keys($load_uids);
        $user_load_sql = "select id,intensive_oss_contract_url from firstp2p_user where id in (".implode(',', $load_uids_key).") and intensive_sign_status=1 and intensive_sign_time_yi>0 and intensive_oss_contract_url!=''";
        $load_info = Yii::app()->fdb->createCommand($user_load_sql)->queryAll();
        if ($load_info) {
            $user_file_dir = $file_dir.'/3-已签署授权委托协议';
            if (!file_exists($user_file_dir) && !mkdir($user_file_dir, 0777, true)) {
                Yii::log('getIntensiveContract contract deal_id : '.$deal_id. ' 已签署授权委托协议创建失败 ');
                return false;
            }
            foreach ($load_info as $d_b){
                //委托协议下载
                $oss_preview_address = 'https://oss.xfuser.com/'.$d_b['intensive_oss_contract_url'];
                $r = file_get_contents($oss_preview_address);
                file_put_contents($user_file_dir."/".$d_b['id'].'-授权委托协议.pdf', $r);

                //相关出借合同位置转移
                foreach ($load_uids[$d_b['id']] as $d_id){
                    $old_contract_url = $file_dir.'/1-原始出借合同/'.$d_b['id'].'-'.$d_id.'-'.$deal_id.'-原始出借合同.pdf';
                    $new_contract_url = $user_file_dir.'/'.$d_b['id'].'-'.$d_id.'-'.$deal_id.'-原始出借合同.pdf';
                    $move_status = rename($old_contract_url,$new_contract_url);
                    if($move_status){
                        Yii::log('getIntensiveContract contract deal_load_id : '.$d_id. ' move success ');
                    }else{
                        Yii::log('getIntensiveContract contract deal_load_id : '.$d_id. ' move error ');
                    }
                }
            }
        }
        return true;
    }

    public function getDebtContract($deal_id, $file_dir){
        //化债协议地址
        $debt_file_dir = $file_dir.'/2-债转相关协议';
        if (!file_exists($debt_file_dir) && !mkdir($debt_file_dir, 0777, true)) {
            Yii::log('getDebtContract contract deal_id : '.$deal_id. ' 参与化债创建失败 ');
            return false;
        }

        //债转协议
        $user_load_sql = "select id,debt_type,status from firstp2p_deal_load where deal_id={$deal_id} and debt_type=2 and status=1 ";
        $load_info = Yii::app()->phdb->createCommand($user_load_sql)->queryAll();
        if (!$load_info) {
            Yii::log('getDebtContract contract deal_id : '.$deal_id. ' 无化债数据 ');
            return true;
        }

        //循环逐层上查询
        foreach ($load_info as $value){
            $tender_id = $value['id'];
            while (!empty($tender_id)){
                $ret = $this->getContractUrl($tender_id, $debt_file_dir, $file_dir);
                if(!empty($ret) && is_numeric($ret)){
                    Yii::log('getDebtContract contract tender_id : '.$tender_id. ' continue ');
                    $tender_id = $ret;
                    continue;
                }else{
                    Yii::log('getDebtContract contract tender_id : '.$tender_id. ' break ');
                    break;
                }
            }
        }
        Yii::log('getDebtContract contract deal_id : '.$deal_id. '  end ');
        return true;
    }


    public function getContractUrl($deal_load_id, $debt_file_dir,$file_dir){
        $debt_sql = "SELECT f.oss_contract_url as purchase_contract_url,c.id as old_load_id,c.debt_type,c.status,c.exclusive_purchase_id,c.deal_id,c.user_id,d.status as contract_status,d.oss_download,e.contract_transaction_id  
FROM firstp2p_debt_tender a 
LEFT JOIN firstp2p_debt b on a.debt_id=b.id 
LEFT JOIN firstp2p_deal_load c on c.id=b.tender_id 
LEFT JOIN xf_exclusive_purchase f on f.id=c.exclusive_purchase_id and f.status=4 
LEFT JOIN firstp2p_contract_task d on d.tender_id=a.new_tender_id and d.status=2
LEFT JOIN firstp2p_debt_exchange_log e on e.new_deal_load_id=a.new_tender_id   
where a.new_tender_id={$deal_load_id}";
        $load_info = Yii::app()->phdb->createCommand($debt_sql)->queryRow();
        if (!$load_info) {
            Yii::log('getContractUrl contract deal_load_id : '.$deal_load_id. ' sql: '.$debt_sql);
            return false;
        }


        //专属收购
        if($load_info['exclusive_purchase_id'] > 0 && !empty($load_info['purchase_contract_url'])){
            //委托协议下载
            $oss_preview_address = 'https://oss.xfuser.com/'.$load_info['purchase_contract_url'];
            $r = file_get_contents($oss_preview_address);
            file_put_contents($debt_file_dir."/".$load_info['user_id'].'-专属收购协议.pdf', $r);
        }

        //历史积分兑换
        if(!empty($load_info['oss_download'])){
            //化债协议
            $contract_name = $load_info['user_id'].'-'.$load_info['old_load_id'].'-'.$load_info['deal_id'];
            $oss_preview_address = 'https://oss.xfuser.com/'.$load_info['oss_download'];
            $r = file_get_contents($oss_preview_address);
            file_put_contents($debt_file_dir."/$contract_name-债转协议-单独.pdf", $r);
        }

        //升级后积分兑换
        if(!empty($load_info['contract_transaction_id'])){
            $debt_contract_sql = "SELECT * from  xf_debt_contract where status=2 and contract_transaction_id='{$load_info['contract_transaction_id']}' ";
            $debt_contract = Yii::app()->fdb->createCommand($debt_contract_sql)->queryRow();
            if(!$debt_contract || empty($debt_contract['oss_contract_url'])){
                Yii::log('getContractUrl contract deal_load_id : '.$deal_load_id. " contract_transaction_id[{$load_info['contract_transaction_id']}] error ");
                return  false;
            }

            //升级后化债协议
            $contract_name = $load_info['user_id'].'-'.$load_info['old_load_id'].'-'.$load_info['deal_id'];
            $oss_preview_address = 'https://oss.xfuser.com/'.$debt_contract['oss_contract_url'];
            $r = file_get_contents($oss_preview_address);
            file_put_contents($debt_file_dir."/$contract_name-债转协议-批量.pdf", $r);
        }

        //相关出借合同位置转移
        if($load_info['debt_type'] == 1){
            $contract_name = $load_info['user_id'].'-'.$load_info['old_load_id'].'-'.$load_info['deal_id'].'-原始出借合同.pdf';
            $old_contract_url = $file_dir.'/1-原始出借合同/'.$contract_name;
            $new_contract_url = $debt_file_dir.'/'.$contract_name;
            $move_status = rename($old_contract_url,$new_contract_url);
            if($move_status){
                Yii::log('getContractUrl contract deal_load_id : '.$load_info['old_load_id']. ' move success ');
            }else{
                Yii::log('getContractUrl contract deal_load_id : '.$load_info['old_load_id']. ' move error ');
            }
            return false;
        }

        return $load_info['old_load_id'];
    }

    protected function getTableName($contractNum)
    {
        // 简单hash crc32 后对64取余
        $crc = intval(abs(crc32($contractNum)));
        $tableSurfix = $crc % 64;
        $tableName = sprintf('firstp2p_contract_files_with_num_%s', $tableSurfix);

        return $tableName;
    }

    /**
     * ok
     * 初始化数据
     * 借款列表-消费信贷标的-客服专属
     */
    public function actionKfIndex()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'     => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize' => $pageSize,
            'number'=>Yii::app()->request->getParam('number'),
            'deal_name'=>Yii::app()->request->getParam('deal_name'),
            'deal_id'=>Yii::app()->request->getParam('deal_id'),
            'user_id'=>Yii::app()->request->getParam('user_id'),
            'customer_name'=>Yii::app()->request->getParam('customer_name'),
            'phone'=>Yii::app()->request->getParam('phone'),
            'id_number'=>Yii::app()->request->getParam('id_number'),
            'loan_amount_min'=>Yii::app()->request->getParam('loan_amount_min'),
            'loan_amount_max'=>Yii::app()->request->getParam('loan_amount_max'),
            'auth_status'=>Yii::app()->request->getParam('auth_status'),
            'organization_name'=>Yii::app()->request->getParam('organization_name'),
            'organization_type'=>Yii::app()->request->getParam('organization_type'),
            'data_src'=>Yii::app()->request->getParam('data_src'),
            'product_name'=>Yii::app()->request->getParam('product_name'),
            'deal_status'=>Yii::app()->request->getParam('deal_status'),
            'type'=>1,
            'product_class'=>1,//1消费贷，个体经营贷 2供应链企业经营贷
            'is_kf'=>1,
        ];
        if (\Yii::app()->request->isPostRequest) {
            //获取借款列表
            $result = BorrowerService::getInstance()->getDealOrderList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }

        return $this->renderPartial('kf_index_init' );
    }

}

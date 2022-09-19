<?php

/**
 * 借款记录
 */
class DealOrderController extends \iauth\components\IAuthController
{
    public $pageSize = 10;

    //不加权限限制的接口
    public function allowActions()
    {
        return array(
             'CreateNewRepayPlan','Upload','getDiscountOrRepayAmount','index','addVoucher','GetClearData' ,
         );
    }

    /**
     * ok
     * 初始化数据
     * 借款列表
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
     * 原还款相关信息
     *
     * @return void
     */
    public function actionRepayPlan()
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
        $result         = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);
        $result['is_show_audit'] =  $is_show_audit ;
        $result['is_from_clear'] =  $is_from_clear ;
       // var_dump($result);die;
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

        $sql =" select * from firstp2p_offline_repay_detail where deal_id = {$deal_id}";
        $firstp2p_offline_repay = Yii::app()->cmsdb->createCommand($sql)->queryAll();

        foreach($firstp2p_offline_repay as $v){
            $repay_ids[] = $v['repay_id'];
        }

        foreach($result['repayPlan'] as &$val ){
            $val['is_add_offline_repay'] = in_array($val['id'],$repay_ids) ? 1: 0;
        }
        //var_dump($result);die;
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
            $result         = BorrowerService::getInstance()->getOfflineRepayList($params);
           
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

}

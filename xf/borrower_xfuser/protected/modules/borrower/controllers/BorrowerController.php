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
     * 借款列表
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
                'id_type'=>Yii::app()->request->getParam('id_type'),
                'status'=>Yii::app()->request->getParam('status'),
                'borrower_src'=>Yii::app()->request->getParam('borrower_src'),
                'bind_type'=>Yii::app()->request->getParam('bind_type'),
                
            ];
            
        if (\Yii::app()->request->isPostRequest) {
        
            //获取借款列表
            $result         = BorrowerService::getInstance()->getBorrowerList($params);
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
        $can_export = 0;
        if (!empty($authList) && strstr(strtolower($authList), strtolower('borrower/borrower/index_execl')) || empty($authList)) {
            $can_export = 1;
        }
       
        return $this->renderPartial('index_init', ['can_export'=>$can_export]);
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
}

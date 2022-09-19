<?php

/**
 * 借款记录
 */
class DealOrderController extends \iauth\components\IAuthController
{
    public $pageSize = 10;

    /**
     * 初始化数据
     * 借款列表
     */
    public function actionIndex()
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
                'auth_status'=>Yii::app()->request->getParam('auth_status'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'organization_type'=>Yii::app()->request->getParam('organization_type'),
                'type'=>1,
                
               
            ];
            
            //获取借款列表
            $result         = BorrowerService::getInstance()->getDealOrderList($params);
            $result['code'] = 0;
            $result['info'] = 'success';
            echo json_encode($result);
            die;
        }
        
       
        return $this->renderPartial('index_init');
    }

    public function actionNewIndex()
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
                'last_repay_end'=>Yii::app()->request->getParam('last_repay_end'),
                'last_repay_start'=>Yii::app()->request->getParam('last_repay_start'),
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
                'type'=>2,
                
               
            ];
            //获取借款列表
            $result         = BorrowerService::getInstance()->getDealOrderList($params);
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
        $platform = (new XfDebtExchangePlatform)->getShopList(['page' => 1,'pageSize' => 1000]);
      
        return $this->renderPartial('index_new', ['can_auth'=>$can_auth,'shopList'=>$platform['list']]);
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
        $result         = BorrowerService::getInstance()->getAboutDealRepayPlanInfo($deal_id);
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
                'organization_name'=>Yii::app()->request->getParam('organization_name'),
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
        $platform = (new XfDebtExchangePlatform)->getShopList(['page' => 1,'pageSize' => 1000]);
      
        return $this->renderPartial('repay_success', ['can_auth'=>$can_auth,'shopList'=>$platform['list']]);
    }
}

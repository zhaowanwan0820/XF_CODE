<?php

class UserCenterController extends CommonController
{
    /**
     * 总资产
     */
    public function actionUserTotalAccount(){
        $returnData = [
            'total' => 0,
            'wait_acount' => 0,
        ];
        //$this->user_id = 9999;
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        $bindPlatformIds = Yii::app()->agdb->createCommand("select u.platform_id, u.platform_user_id, p.name 
                                                              from ag_user_platform as u 
                                                              left join ag_platform as p on u.platform_id = p.id  
                                                              where u.user_id=".$this->user_id." and authorization_status=1")->queryAll();
        if ($bindPlatformIds){
            foreach ($bindPlatformIds as $value){
                if ($value['platform_id'] == Yii::app()->c->itouzi['itouzi']['platform_id']){
                    $user_id = $value['platform_user_id'];
                }else{
                    $user_id = $this->user_id;
                }
                $returnData[$value['platform_id']]['name'] = $value['name'];
                $returnData[$value['platform_id']]['platform_id'] = $value['platform_id'];
                $returnData[$value['platform_id']]['platform_user_id'] = $value['platform_user_id'];
                $tenderDebtConfirm = BaseTenderService::run(['platform' => $value['platform_id']])->getConfirmedTenderSum(['user_id' => $user_id, 'platform_id' => $value['platform_id']]);
                $returnData[$value['platform_id']]['confirm'] = $tenderDebtConfirm;
                $returnData['wait_acount'] += $returnData[$value['platform_id']]['confirm'];
            }
            $returnData['total'] = $returnData['wait_acount'];
        }

        $this->echoJson($returnData,0);
    }

    /**
     * 资产确权
     */
    public function actionUserPlatformConfirm(){
        $returnData = [

        ];
        //$this->user_id = 9999;
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        $bindPlatformIds = Yii::app()->agdb->createCommand("select u.platform_id, u.platform_user_id, p.name 
                                                              from ag_user_platform as u 
                                                              left join ag_platform as p on u.platform_id = p.id  
                                                              where u.user_id=".$this->user_id." and authorization_status=1")->queryAll();
        if ($bindPlatformIds){
            foreach ($bindPlatformIds as $value){
                if ($value['platform_id'] == Yii::app()->c->itouzi['itouzi']['platform_id']){
                    $user_id = $value['platform_user_id'];
                }else{
                    $user_id = $this->user_id;
                }
                $returnData[$value['platform_id']]['name'] = $value['name'];
                $returnData[$value['platform_id']]['platform_id'] = $value['platform_id'];
                $returnData[$value['platform_id']]['platform_user_id'] = $value['platform_user_id'];
                $tenderDebtConfirm = BaseTenderService::run(['platform' => $value['platform_id']])->getTenderWaitMoney(['user_id' => $user_id, 'platform_id' => $value['platform_id'],'is_debt_confirm'=>0]);
                $returnData[$value['platform_id']]['wait_money'] = $tenderDebtConfirm;
            }
        }

        $this->echoJson($returnData,0);
    }

    /**
     * 机构资产明细
     */
    public function actionPlatformUserAccount(){
        $returnData = [
            'total' => 0,
            'wait_acount' => 0,
        ];
        //$this->user_id = 9999;
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }

        if (isset($_POST['platform_id']) && !empty($_POST['platform_id'])){
            $platform_id = $_POST['platform_id'];
        }else{
            $this->echoJson($returnData,100,'平台id不能为空');
        }

        if (!isset($_POST['platform_user_id'])){
            $this->echoJson($returnData,100,'平台用户id不能为空');
        }

        if ($platform_id == Yii::app()->c->itouzi['itouzi']['platform_id']){
            $user_id = $_POST['platform_user_id'];
        }else{
            $user_id = $this->user_id;
        }
        $tenderDebtConfirm = BaseTenderService::run(['platform' => $platform_id])->getTenderDebtConfirmCount(['user_id' => $user_id, 'platform' => $platform_id]);
        if ($tenderDebtConfirm){
            foreach ($tenderDebtConfirm['project'] as $v){
                $returnData['wait_acount'] += $v['confirm'];
            }
            $returnData['total'] = $returnData['wait_acount'];
            $returnData['list'] = $tenderDebtConfirm;
        }

        $this->echoJson($returnData,0);

    }

    /**
     * 已确权列表
     */
    public function actionConfirmedList(){
        $returnData = [
            'paying_count' => [
                'paying' => 0,
                'ended' => 0,
            ],
            'list' => [                        // 项目列表

            ],
        ];
//        $this->user_id = 9999;
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }

        if (isset($_POST['platform_id']) && !empty($_POST['platform_id'])){
            $platform_id = $_POST['platform_id'];
        }else{
            $this->echoJson($returnData,100,'平台id不能为空');
        }

        if (!isset($_POST['platform_user_id'])){
            $this->echoJson($returnData,100,'平台用户id不能为空');
        }

        if (!isset($_POST['status'])){
            $this->echoJson($returnData,100,'筛选类型不能为空');
        }

        if ($platform_id == Yii::app()->c->itouzi['itouzi']['platform_id']){
            $user_id = $_POST['platform_user_id'];
        }else{
            $user_id = $this->user_id;
        }
        $_POST['user_id'] = $user_id;
        $_POST['platform_id'] = $platform_id;
        $tenderConfirmed = BaseTenderService::run(['platform' => $platform_id])->getConfirmedList($_POST);
        if ($tenderConfirmed){
            $returnData = $tenderConfirmed;
        }

        $this->echoJson($returnData,0);
    }

    /**
     * 选择机构
     */
    public function actionUserPlatform(){
        $returnData = [];
//        $this->user_id = 9999;
        if(!$this->user_id){
            $this->echoJson($returnData,1001);
        }
        $returnData = AgUserService::getInstance()->getUserBindPlantForm(['user_id' => $this->user_id, 'authorization_status' => 1]);
        $this->echoJson($returnData['data'],0);
    }
}

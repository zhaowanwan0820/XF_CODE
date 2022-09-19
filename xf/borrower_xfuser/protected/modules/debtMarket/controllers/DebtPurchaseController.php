<?php
use iauth\models\AuthAssignment;

class DebtPurchaseController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
              'Upload',
         );
    }
    /**
    * 成功提示页
    * @param msg   string  提示信息
    * @param time  int     显示时间
    */
    public function actionSuccess($msg = '成功', $time = 3)
    {
        return $this->renderPartial('result', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    /**
     * 失败提示页
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionError($msg = '失败', $time = 3)
    {
        return $this->renderPartial('result', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }
    /**
     * 汇源专区 列表
     */
    public function actionHuiyuan()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'              => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize'          => $pageSize,
                'area_id'           =>1,
                'discount'          =>Yii::app()->request->getParam('discount'),
                'to_be_processed'   =>Yii::app()->request->getParam('to_be_processed'),
                'buyer_people'      =>Yii::app()->request->getParam('buyer_people'),
                'purchase_status'   =>Yii::app()->request->getParam('purchase_status'),
                'release_people'    =>Yii::app()->request->getParam('release_people'),
                'action_start'      =>Yii::app()->request->getParam('action_start'),
                'action_end'        =>Yii::app()->request->getParam('action_end'),
            ];
            //获取用户列表
            $importFileInfo         = DebtPurchaseService::getInstance()->getList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
       
        return $this->renderPartial('index');
    }
    /**
     * 创建求购
     *
     * @return void
     */
    public function actionCreate()
    {
        $area_id  = \Yii::app()->request->getParam('area_id')?:1;
        if (\Yii::app()->request->isPostRequest) {
            try {
                $res = DebtPurchaseService::getInstance()->createPurchase($area_id);
                if ($res) {
                    return $this->actionSuccess('添加成功', 3);
                }
            } catch (Exception $e) {
                return $this->actionError($e->getMessage(), 5);
            }
        }
        $buyer_list = [];
        $buyers = DebtPurchaseService::getInstance()->getAssignees(['area_id'=>$area_id]);
        
        if ($buyers) {
            $now = time();
            //进行中的剩余总额
            $db = \Yii::app()->request->getParam('deal_type')==1?"fdb":'phdb';
            $user_surplus     = Yii::app()->$db->createCommand("select sum(total_amount-purchased_amount-trading_amount) as amount ,user_id from xf_plan_purchase where area_id = {$area_id} and status in (0,1) and starttime < {$now} and endtime > {$now} group by user_id ")->queryAll();
            if ($user_surplus) {
                $user_id_surplus = ArrayUntil::array_column($user_surplus, 'amount', 'user_id');
            }
            
            foreach ($buyers as $value) {
                $tmp['id']      = $value['user_id'];
                $m = $value['transferred_amount']  + $value['trading_amount']  + ($user_id_surplus[$value['user_id']]?:0);
                $amount         = number_format(bcsub($value['transferability_limit'], $m, 2), 2) ;
                $tmp['info']    = $value['real_name']."（剩余受让额度：". $amount ."）";
                $buyer_list[]   = $tmp;
            }
        }
        
        return $this->renderPartial('create', ['buyer_list'=>$buyer_list]);
    }

    /**
     * 终止求购
     *
     * @return void
     */
    public function actionStop()
    {
        try {
            $params   = [
                'id'     => \Yii::app()->request->getParam('id'),
            ];
            $res = DebtPurchaseService::getInstance()->stopPurchase($params);
            if ($res) {
                $importFileInfo['code'] = 0;
                $importFileInfo['info'] = 'success';
                echo json_encode($importFileInfo);
                exit;
            }
        } catch (Exception $e) {
            $importFileInfo['code'] = 100;
            $importFileInfo['info'] = $e->getMessage();
            echo json_encode($importFileInfo);
            exit;
        }
    }

    /**
    * 审核
    *
    * @return void
    */
    public function actionAuth()
    {
        try {
            $params   = [
                'id'     => \Yii::app()->request->getParam('id'),
                'type'  => \Yii::app()->request->getParam('type'),
                'area_id'  => \Yii::app()->request->getParam('area_id')?:1,
            ];
            $res = DebtPurchaseService::getInstance()->authPurchase($params);
            if ($res) {
                $importFileInfo['code'] = 0;
                $importFileInfo['info'] = 'success';
                echo json_encode($importFileInfo);
                exit;
            }
        } catch (Exception $e) {
            $importFileInfo['code'] = 100;
            $importFileInfo['info'] = $e->getMessage();
            echo json_encode($importFileInfo);
            exit;
        }
    }

    /**
    * 项目投用与禁用审核
    *
    * @return void
    */
    public function actionPurchaseDealStatus()
    {
        try {
            $params   = [
                'id'     => \Yii::app()->request->getParam('id'),
                'type'  => \Yii::app()->request->getParam('type'),
            ];
            $res = DebtPurchaseService::getInstance()->purchaseDealStatus($params);
            if ($res) {
                $importFileInfo['code'] = 0;
                $importFileInfo['info'] = 'success';
                echo json_encode($importFileInfo);
                exit;
            }
        } catch (Exception $e) {
            $importFileInfo['code'] = 100;
            $importFileInfo['info'] = $e->getMessage();
            echo json_encode($importFileInfo);
            exit;
        }
    }

    /**
    * 项目明细
    * @return false|string|string[]|null
    * @throws CException
    */
    public function actionDealList()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'purchase_id'=>Yii::app()->request->getParam('purchase_id'),
            ];
            if ($deal_id = Yii::app()->request->getParam('deal_id')) {
                $params['deal_id'] = $deal_id;
            }
            if ($name = Yii::app()->request->getParam('name')) {
                $params['name'] = $name;
            }
            //获取用户列表
            $importFileInfo         = DebtPurchaseService::getInstance()->getDealList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }

        return $this->renderPartial('deal_list', ['purchase_id'=>Yii::app()->request->getParam('purchase_id')]);
    }

    /**
     * 求购记录明细
     *
     * @return void
     */
    public function actionDetail()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                $params   = [
                    'deal_type' => \Yii::app()->request->getParam('deal_type'),
                    'page'      => \Yii::app()->request->getParam('page') ?: 1,
                    'pageSize'  => 10,
                    'id'        => \Yii::app()->request->getParam('id'),
                    'status'    => \Yii::app()->request->getParam('status'),
                    'real_name' => \Yii::app()->request->getParam('real_name'),
                    'bank_num'  => \Yii::app()->request->getParam('bank_num'),
                    'user_id'   => \Yii::app()->request->getParam('user_id'),
                   
                ];
                $importFileInfo  = DebtPurchaseService::getInstance()->purchaseDetail($params);
                $importFileInfo['code'] = 0;
                $importFileInfo['info'] = 'success';
                echo json_encode($importFileInfo);
                exit;
            } catch (Exception $e) {
                $importFileInfo['code'] = 100;
                $importFileInfo['info'] = $e->getMessage();
                echo json_encode($importFileInfo);
                exit;
            }
        }
        $buyer_list = [];
        $buyers = DebtPurchaseService::getInstance()->getAssignees(['area_id'=>\Yii::app()->request->getParam('area_id')]);
        if ($buyers) {
            foreach ($buyers as $value) {
                $tmp['id'] = $value['user_id'];
                $amount = number_format(bcsub($value['transferability_limit'], bcadd($value['transferred_amount'], $value['trading_amount'], 2), 2), 2) ;
                $tmp['info'] = $value['real_name']."（剩余受让额度：". $amount ."）";
                $buyer_list[] = $tmp;
            }
        }
        
        return $this->renderPartial('detail', ['buyer_list'=>$buyer_list]);
    }
    



    /**
     * 上传 、保存付款凭证
     */
    public function actionUploadCredentials()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                $params   = [
                    'payment_voucher' => \Yii::app()->request->getParam('logo_path'),
                    'debt_id'        => \Yii::app()->request->getParam('debt_id'),
                ];
                $res = DebtPurchaseService::getInstance()->saveCredentials($params);
                if ($res) {
                    $importFileInfo['code'] = 0;
                    $importFileInfo['info'] = 'success';
                    echo json_encode($importFileInfo);
                    exit;
                }
            } catch (Exception $e) {
                $importFileInfo['code'] = 100;
                $importFileInfo['info'] = $e->getMessage();
                echo json_encode($importFileInfo);
                exit;
            }
        }
        return $this->renderPartial('upload_credentials');
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

    /**
     * 查看凭证
     *
     * @return void
     */
    public function actionViewCredentials()
    {
        $params   = [
            'debt_id'        => \Yii::app()->request->getParam('id'),
        ];
        $res = DebtPurchaseService::getInstance()->viewCredentials($params);
        
        return $this->renderPartial('view_credentials', ['img'=>$res]);
    }
}

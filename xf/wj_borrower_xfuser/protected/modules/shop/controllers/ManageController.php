<?php

/**
 */
class ManageController extends \iauth\components\IAuthController
{
    public $pageSize = 10;

    /**
     *列表
     */
    public function actionIndex(){


        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'name'=>Yii::app()->request->getParam('name'),
            ];
            //获取用户列表
            $importFileInfo         = (new XfDebtExchangePlatform)->getShopList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList),strtolower('/shop/Manage/ShopEdit')) || empty($authList)) {
            $can_auth = 1;
        }
        return $this->renderPartial('index',['can_auth'=>$can_auth]);
    }

    /**
     * 编辑
     * @return false|string|string[]|null
     * @throws CException
     */
    public function actionShopEdit()
    {
        $model = new XfDebtExchangePlatform();

        $id = \Yii::app()->request->getParam('id');
        $shopInfo = $model->findByPk($id)->attributes;
        //编辑更新
        if (\Yii::app()->request->isPostRequest) {
            //接收更改参数
            $name = \Yii::app()->request->getPost('name');
            $status= \Yii::app()->request->getPost('status');
            /*
             $buyer_uid = \Yii::app()->request->getPost('buyer_uid');

            $sql      = "SELECT user_id FROM ag_wx_assignee_info WHERE user_id = '{$buyer_uid}' AND status = 2 AND  `type` = 2 ";
            $assignee = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$assignee) {
                $this->echoJson( [],100,'此受让人不在受让方列表！');
            }*/
            //save模型更新
            $saveModel = XfDebtExchangePlatform::model();
            $saveModel->id = $id;
            $saveModel->name = $name;
            $saveModel->status = $status;
            //$saveModel->buyer_uid = $buyer_uid;

            if ($saveModel->save(false)) {
                $this->echoJson("", 0, "修改成功");
            } else {
                $this->echoJson("", 1, "修改失败");
            }
        }

        return $this->renderPartial("shopEdit", array("shopInfo" => $shopInfo));
    }

    /**
     * 添加
     * @return false|string|string[]|null
     * @throws CException
     */
    public function actionShopAdd()
    {
        $model = new XfDebtExchangePlatform();
        if (\Yii::app()->request->isPostRequest) {
            /*
            $sql      = "SELECT user_id FROM ag_wx_assignee_info WHERE user_id = '{$_POST['buyer_uid']}' AND status = 2 AND  `type` = 2 ";
            $assignee = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$assignee) {
                $this->echoJson( [],100,'此受让人不在受让方列表！');
            }*/
            $model->name = $_POST['name'];
            $model->status = 0;
            $model->secret = strtoupper(md5($_POST['name'].time()));
            $model->created_at = time();
            //$model->buyer_uid = $_POST['buyer_uid'];
            if ($model->save()) {
                $this->echoJson("", 0, "添加商城成功");
            } else {
                $this->echoJson("", 1, current(current($model->getErrors())));
            }
        }

        return $this->renderPartial("shopAdd");
    }

    /**
     * 用户白名单列表
     * @return false|string|string[]|null
     * @throws CException
     */
    public function actionUserAllowDetail(){


        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'appid'=>Yii::app()->request->getParam('appid'),

            ];
            if($user_id = Yii::app()->request->getParam('user_id')){
                $params['user_id'] = $user_id;
            }
            if($mobile = Yii::app()->request->getParam('mobile')){
                $params['mobile'] = $mobile;
            }
            //获取用户列表
            $importFileInfo         = (new XfDebtExchangeUserAllowList())->getUserList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_edit = 0;
        if (!empty($authList) && strstr(strtolower($authList),strtolower('/shop/Manage/EditUserStatus')) || empty($authList)) {
            $can_edit = 1;
        }

        return $this->renderPartial('userAllowDetail',['appid'=>Yii::app()->request->getParam('appid'),'can_edit'=>$can_edit]);
    }

    /**
     * 项目白名单列表
     * @return false|string|string[]|null
     * @throws CException
     */
    public function actionDealAllowDetail(){


        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'appid'=>Yii::app()->request->getParam('appid'),
                'area_id'=>Yii::app()->request->getParam('area_id')?:0,
                'type'=>Yii::app()->request->getParam('deal_type')
            ];
            if($deal_id = Yii::app()->request->getParam('deal_id')){
                $params['deal_id'] = $deal_id;
            }
            if($name = Yii::app()->request->getParam('name')){
                $params['name'] = $name;
            }
            //获取用户列表
            $importFileInfo         = (new XfDebtExchangeDealAllowList())->getList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_edit = 0;
        if (!empty($authList) && strstr(strtolower($authList),strtolower('/shop/Manage/EditDealStatus')) || empty($authList)) {
            $can_edit = 1;
        }

        return $this->renderPartial('dealAllowDetail',['appid'=>Yii::app()->request->getParam('appid')?:0,'area_id'=>Yii::app()->request->getParam('area_id')?:0,'can_edit'=>$can_edit]);
    }

    /**
     * 用户白名单编辑
     */
    public function actionEditUserStatus(){

        $id = Yii::app()->request->getParam('id');
        $status =Yii::app()->request->getParam('status');
        if(empty($id) || !in_array($status,[1,2])){
            $this->echoJson([], 100, '参数错误');
        }
        $model = XfDebtExchangeUserAllowList::model()->findByPk($id);
        if(empty($model)){
            $this->echoJson([], 100, '数据不存在');
        }
        $model->status = $status;
        $model->update_at = time();
        if($model->save()==false){
            $this->echoJson([], 100, '网络错误，请重试');

        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 项目白名单编辑
     */
    public function actionEditDealStatus(){

        $id = Yii::app()->request->getParam('id');
        $status =Yii::app()->request->getParam('status');
        if(empty($id) || !in_array($status,[1,2])){
            $this->echoJson([], 100, '参数错误');
        }
        $model = XfDebtExchangeDealAllowList::model()->findByPk($id);
        if(empty($model)){
            $this->echoJson([], 100, '数据不存在');
        }
        $model->status = $status;
        $model->update_at = time();
        if($model->save()==false){
            $this->echoJson([], 100, '网络错误，请重试');

        }
        $this->echoJson([], 0, '操作成功');
    }

}

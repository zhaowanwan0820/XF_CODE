<?php

/**
 */
class SpecialAreaController extends \iauth\components\IAuthController
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
            $importFileInfo         = (new XfDebtExchangeSpecialArea)->getList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList),strtolower('/shop/SpecialArea/AreaEdit')) || empty($authList)) {
            $can_auth = 1;
        }
        return $this->renderPartial('index',['can_auth'=>$can_auth]);
    }

    /**
     * 编辑
     * @return false|string|string[]|null
     * @throws CException
     */
    public function actionAreaEdit()
    {
        $model = new XfDebtExchangeSpecialArea();

        $id = \Yii::app()->request->getParam('id');
        $areaInfo = $model->findByPk($id)->attributes;

        $model = new XfDebtExchangePlatform();
        $areaInfo['p_name'] = $model->findByPk($areaInfo['appid'])->name;
        //编辑更新
        if (\Yii::app()->request->isPostRequest) {
            //接收更改参数
            $name = \Yii::app()->request->getPost('name');
            $code = \Yii::app()->request->getPost('code');
            $status= \Yii::app()->request->getPost('status');
            $sql      = "SELECT id FROM xf_debt_exchange_special_area WHERE id != {$id} and  appid = {$areaInfo['appid']} and code = '{$code}' ";
            $isSet = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($isSet) {
                $this->echoJson( [],100,'专区代码重复！');
            }
            //save模型更新
            $saveModel = XfDebtExchangeSpecialArea::model();
            $saveModel->id = $id;
            $saveModel->name = $name;
            $saveModel->code = $code;
            $saveModel->status = $status;

            if ($saveModel->save(false)) {
                $this->echoJson("", 0, "修改成功");
            } else {
                $this->echoJson("", 1, "修改失败");
            }
        }

        return $this->renderPartial("areaEdit", array("areaInfo" => $areaInfo));
    }

    /**
     * 添加
     * @return false|string|string[]|null
     * @throws CException
     */
    public function actionAreaAdd()
    {

        $platform = (new XfDebtExchangePlatform)->getShopList(['page' => 1,'pageSize' => 1000]);
        if (\Yii::app()->request->isPostRequest) {

            $sql      = "SELECT id FROM xf_debt_exchange_special_area WHERE  appid = {$_POST['appid']} and code = '{$_POST['code']}' ";
            $isSet = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($isSet) {
                $this->echoJson( [],100,'专区代码重复！');
            }

            $model = new XfDebtExchangeSpecialArea();

            $model->appid = $_POST['appid'];
            $model->name = $_POST['name'];
            $model->code = $_POST['code'];
            $model->status = 0;
            $model->created_at = time();
            //$model->buyer_uid = $_POST['buyer_uid'];
            if ($model->save()) {
                $this->echoJson("", 0, "添加专区成功");
            } else {
                $this->echoJson("", 1, current(current($model->getErrors())));
            }
        }

        return $this->renderPartial("areaAdd",['shopList' => $platform['list']]);
    }


    public function actionGetSpecialAreaByAppId(){
        $appid = Yii::app()->request->getParam('appid')?:0 ;
        $data[] = ['id'=>0,'name'=>'商城范围'];
        if(empty($appid)){
            $area_list['code'] = 0;
            $area_list['list'] = $data;
            $area_list['info'] = 'success';
            echo json_encode($area_list);
            die;
        }
        $area_list_arr = (new XfDebtExchangeSpecialArea())->getList(['page' => 1,'pageSize' => 1000,'appid'=>$appid]);

        if($area_list_arr['list']){
            foreach ($area_list_arr['list'] as $item) {
                $_t['id'] = $item['id'];
                $_t['name'] = $item['name'];
                $data[] = $_t;
            }
        }
        $area_list['list'] = $data;
        $area_list['code'] = 0;
        $area_list['info'] = 'success';
        echo json_encode($area_list);
        die;
    }



}

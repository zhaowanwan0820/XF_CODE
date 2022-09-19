<?php

class RoleController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Index', 'LoginCcs', 'Logout', 'test', 'AdminRole', 'adminadd', 'adminedit'
        );
    }

    /**
     * 后台用户列表
     * @return mixed|string
     * @throws CException
     */
    public function actionIndex()
    {
        $sql = "select id,username,addtime,phone,email,status from itz_user order by id asc";
        $criteria = new CDbCriteria();
        $result = Yii::app()->db->createCommand($sql)->query();
        $pages = new CPagination($result->rowCount);
        $pages->pageSize = 10;
        $pages->applyLimit($criteria);
        $result = Yii::app()->db->createCommand($sql . " LIMIT :offset,:limit");
        $result->bindValue(':offset', $pages->currentPage * $pages->pageSize);
        $result->bindValue(':limit', $pages->pageSize);
        //配置分页样式
        $pages = $this->widget('CLinkPager', array(
            'header' => '',
            'firstPageLabel' => '首页',
            'lastPageLabel' => '末页',
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'pages' => $pages,
            'maxButtonCount' => 8,
            'cssFile' => false,
            'htmlOptions' => array("class" => "pagination"),
            'selectedPageCssClass' => "active"
        ), true);
        $brand = $result->queryAll();
        return $this->renderPartial('adminlist', array('brand' => $brand, 'pages' => $pages));
    }






    /**
     * 权限添加
     * @return string
     * @throws CException
     */
    public function actionAdminRule()
    {

        return $this->renderPartial("adminrule");
    }
    /**
     * 角色权限添加
     * @return string
     * @throws CException
     */
    public function actionAdminRoleAdd()
    {
        $AuthItemModel = new \iauth\models\AuthItem();
        //对角色添加权限
        if (Yii::app()->request->isPostRequest) {
            //接收更改参数
            $name = Yii::app()->request->getPost('name');//角色名
            $desc = Yii::app()->request->getPost('desc');//描述
            $childs = Yii::app()->request->getPost('id');//所有权限id
           //添加权限组记录
            $AuthItemModel->name = $name;
            $AuthItemModel->desc = $desc;
            $AuthItemModel->type = 1;
            $AuthItemModel->system = 1;;
            $AuthItemModel->status = 1;
            $AuthItemModel->created_time = time();
            $AuthItemModel->updated_time = time();
            if($AuthItemModel->save()) {
                $this->echoJson("", 0, "添加用户成功");
            } else {
                $this->echoJson("", 1, current(current($AuthItemModel->getErrors())));
            }

        }
        $ret = array();
        //显示权限管理
        $authitem = Yii::app()->db->createCommand()
        ->select('id,name')
        ->from('itz_auth_item')
        ->where('status = 1 and type = 1')
        ->queryAll();
        if(!empty($authitem)){
            foreach($authitem as $key => $val){
                //获取指定权限组下的子权限列表
                $result = $AuthItemModel->getAuthGroupChildList($val['id']);
                if(!empty($result)){
                    $ret[] = array(
                        "id" => $val['id'],
                        "name" => $val['name'],
                        "listrolename" => $result
                    );
                }
            }
        }
        return $this->renderPartial("adminroleadd",array("ret" => $ret));
    }
    /**
     * 添加权限组
     */
    public function actionAddGroup()
    {
        if ($this->expectJson && isset($_POST['item'])) {
            $this->create(\iauth\models\AuthItem::TYPE_GROUP);
        }
    }
    /**
     * 添加权限表 item
     * @param int $type
     */
    public function create($type)
    {
        /* @var AuthItem $model */
        $model = new \iauth\models\AuthItem();
        $model->attributes = $_POST['item'];
        $model->type = $type;
        if (!$model->validate()) {
            $errCode = $model->getErrCode();
            $this->logReqParamsWith(\iauth\helpers\Meta::getCodeInfo($errCode));
            $this->renderJson($errCode);
        } else {
            if (!$model->save()) {
                $this->logReqParamsWith($model->getErrors());
                $this->renderJson(\iauth\helpers\Meta::C_FAILURE);
            } else {
                if ($type == \iauth\models\AuthItem::TYPE_ACTION) {
                    /* 产品业务需求的是权限一对多，而实际开发为扩展方便使用了多对多。
                        故此处需单独添加一条多对多记录 */
                    if ($this->createRelation($_POST['item']['parent'], $model->id)) {
                        $this->renderJson(\iauth\helpers\Meta::C_SUCCESS, ['item_id' => $model->id]);
                    } else {
                        $this->renderJson(\iauth\helpers\Meta::C_AUTH_ITEM_PARENT_UPDATE_FAILURE);
                    }
                } else {
                    $this->renderJson(\iauth\helpers\Meta::C_SUCCESS, ['item_id' => $model->id]);
                }
            }
        }
    }
    /**
     * 创建权限与权限组关系记录
     * @param $parentId
     * @param $childId
     * @return bool
     */
    public function createRelation($parentId, $childId)
    {
        $attributes = [
            'parent' => $parentId,
            'child' => $childId,
        ];

        $authItem = new \iauth\models\AuthItemChild();
        $authItem->attributes = $attributes;
        return $authItem->save();
    }
    /**
     * 更新权限的状态
     * @param $id
     * @param int $type
     */
    public function updateStatus($id, $type = AuthItem::STATUS_ENABLED)
    {
        if (!\iauth\helpers\Number::isIntPk($id)) {
            $this->renderJson(\iauth\helpers\Meta::C_UNSAFE_ARGUMENT);
        } else {
            $model = \iauth\models\AuthItem::model()->findByPk($id);
            if (!$model) {
                $this->renderJson(\iauth\helpers\Meta::C_AUTH_ITEM_NOT_FOUND);
            } else {
                /* @var $model \iauth\models\AuthItem */
                if ($model->updateStatus($type)) {
                    $this->renderJson(\iauth\helpers\Meta::C_SUCCESS);
                } else {
                    $errCode = $model->getErrCode();
                    $this->logReqParamsWith(Meta::getMeta($errCode));
                    $this->renderJson($errCode);
                }
            }
        }
    }
    /**
     * 权限分类
     * @return string
     * @throws CException
     */
    public function actionAdminCate()
    {
        $AuthItemModel = new \iauth\models\AuthItem();
        //对角色添加权限
        if (Yii::app()->request->isPostRequest) {
            //接收更改参数
            $name = Yii::app()->request->getPost('name');//角色名
            $desc = Yii::app()->request->getPost('desc');//描述
            $childs = Yii::app()->request->getPost('id');//所有权限id
            //添加权限组记录
            $AuthItemModel->name = $name;
            $AuthItemModel->desc = $desc;
            $AuthItemModel->type = 1;
            $AuthItemModel->system = 1;;
            $AuthItemModel->status = 1;
            $AuthItemModel->created_time = time();
            $AuthItemModel->updated_time = time();
            if($AuthItemModel->save()) {
                $this->echoJson("", 0, "添加用户成功");
            } else {
                $this->echoJson("", 1, current(current($AuthItemModel->getErrors())));
            }

        }

        $ret = array();
        //显示权限分类type为1
        $authitem = Yii::app()->db->createCommand()
            ->select('id,name')
            ->from('itz_auth_item')
            ->where('status = 1 and type = 1')
            ->queryAll();
        if(!empty($authitem)){
            foreach($authitem as $key => $val){
                //获取指定权限组下的子权限列表
                $result = $AuthItemModel->getAuthGroupChildList($val['id']);
                if(!empty($result)){
                    $ret[] = array(
                        "id" => $val['id'],
                        "name" => $val['name'],
                        "listrolename" => $result
                    );
                }
            }
        }
        return $this->renderPartial("admincate");
    }
}

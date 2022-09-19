<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/25
 * Time: 12:33
 */

namespace iauth\controllers;

use iauth\components\IAuthController;
use iauth\helpers\Meta;
use iauth\helpers\Number;
use iauth\models\AuthItem;
use iauth\models\AuthItemChild;

class AuthItemController extends IAuthController
{
    const PAGE_SIZE = 10;
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'groupactionlist','userrole','Disable','Enable','userroleadd','userroleedit'
        );
    }
    /**
     * 角色管理列表
     * @return string
     * @throws CException
     */
    public function actionUserRole()
    {
        $where = "";
        if(!empty($_GET['name'])){
            $name = trim($_GET['name']);
            $where .= " and name = '{$name}'";
        }
        $sql = "SELECT `id`,`name`,`status`,`desc` from itz_auth_item WHERE type = 2 $where order by id asc";
        $criteria = new \CDbCriteria();
        $result = \Yii::app()->db->createCommand($sql)->query();
        $pages = new \CPagination($result->rowCount);
        $pages->pageSize = self::PAGE_SIZE;
        $pages->applyLimit($criteria);
        $result = \Yii::app()->db->createCommand($sql . " LIMIT :offset,:limit");
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
        if (!empty($brand)) {
            foreach ($brand as $key => $val) {
                //角色管理分配
                $authitem = \Yii::app()->db->createCommand()
                    ->select('child')
                    ->from('itz_auth_item_child')
                    ->where("parent = {$val["id"]}")
                    ->queryAll();
                $names = '';//拥有权限规则
                if (!empty($authitem)) {
                    $childs = \ArrayUtil::array_column($authitem, "child");
                    //显示子类角色管理名称
                    $authitem = \Yii::app()->db->createCommand()
                        ->select('name')
                        ->from('itz_auth_item')
                        ->where(['in', 'id', $childs])
                        ->andWhere('status = 1')
                        ->queryAll();
                    //取得所有授权项目名称
                    $names = \ArrayUtil::array_column($authitem, "name");
                }
                //状态名称
                $statusName = ($val['status'] == 1) ? "已启用" : "未启用";
                $ret[] = array(
                    "name" => $val['name'],//角色名
                    "rolename" => (!empty($names)) ? implode(",", $names) : "",//拥有权限规则
                    "remark" => $val['desc'],//描述
                    "status" => $statusName,//状态
                    "id" => $val['id'],//角色id
                    "is_edit" => (in_array($val['id'], \Yii::app()->c->xf_config['borrower_distribution_itemid'])) ? 1 : 0,
                );
            }
        }

        return $this->renderPartial("userrole", array('ret' => $ret, 'pages' => $pages));
    }

    /**
     * 角色添加
     * @return string
     * @throws CException
     */
    public function actionUserRoleAdd()
    {
        $AuthItemModel = new AuthItem();
        //对角色添加权限
        if (\Yii::app()->request->isPostRequest) {
            //接收更改参数
            $name = \Yii::app()->request->getPost('name');//角色名
            $desc = \Yii::app()->request->getPost('desc');//描述
            $childs = \Yii::app()->request->getPost('childids');//所有子权限id
            $topItemId = \Yii::app()->request->getPost('topItemId');//权限组id
            if(empty($topItemId)){
                $this->echoJson("", 1, "请选择权限组");
            }
            if(empty($childs)){
                $this->echoJson("", 1, "请选择子权限");
            }

            //添加权限组记录
            try {
                $AuthItemModel->name = $name;
                $AuthItemModel->desc = $desc;
                $AuthItemModel->code = md5(uniqid(md5(microtime(true)), true));
                $AuthItemModel->type = 2;
                $AuthItemModel->system = 1;;
                $AuthItemModel->status = 1;
                $AuthItemModel->created_time = time();
                $AuthItemModel->updated_time = time();
                if ($AuthItemModel->save()) {
                    $itemId = $AuthItemModel->attributes['id'];
                    $AuthItemChild = new AuthItemChild();
                    //拼接数据子权限
                    foreach ($childs as $ley => $val) {
                        $ret[] = array(
                            "parent" => $itemId,
                            "child" => $val,
                        );
                    }
                    //拼接权限组
                    foreach ($topItemId as $ley => $val) {
                        $ret[] = array(
                            "parent" => $itemId,
                            "child" => $val,
                        );
                    }
                    if (isset($ret) && !empty($ret)) {
                        foreach ($ret as $attributes) {
                            $_model = clone $AuthItemChild; //克隆对象
                            $_model->setAttributes($attributes);
                            $_model->save();
                        }
                    }
                    $this->echoJson("", 0, "添加角色成功");
                } else {
                    $this->echoJson("", 1, "添加角色失败");
                }
            } catch (Exception $e) {
                $this->echoJson("", 1, "添加角色失败");
            }
        }
        $ret = array();
        //显示全部权限管理
        $authitem = \Yii::app()->db->createCommand()
            ->select('id,name')
            ->from('itz_auth_item')
            ->where('status = 1 and type = 1')
            ->queryAll();
        if (!empty($authitem)) {
            foreach ($authitem as $key => $val) {
                //获取指定权限组下的子权限列表
                $result = $AuthItemModel->getAuthGroupChildList($val['id']);
                if (!empty($result)) {
                    $ret[] = array(
                        "id" => $val['id'],
                        "name" => $val['name'],
                        "listrolename" => $result
                    );
                }
            }
        }

        return $this->renderPartial("userroleadd", array("ret" => $ret));
    }

    /**
     * 停用权限
     * @param $id
     */
    public function actionDisable($id)
    {
        $this->updateStatus($id, AuthItem::STATUS_DISABLED);
    }

    /**
     * 开启权限
     * @param $id
     */
    public function actionEnable($id)
    {
        $this->updateStatus($id, AuthItem::STATUS_ENABLED);
    }

    /**
     * 编辑角色权限
     */
    public function actionUserRoleEdit()
    {
        $itemId = \Yii::app()->request->getParam('itemId');
        if(in_array($itemId, \Yii::app()->c->xf_config['borrower_distribution_itemid'])){
            $this->echoJson("", 1, "此角色权限不可编辑");
        }
        $AuthItemModel = AuthItem::model();
        //对角色编辑权限
        if (\Yii::app()->request->isPostRequest) {
            //接收更改参数
            $name = \Yii::app()->request->getPost('name');//角色名
            $desc = \Yii::app()->request->getPost('desc');//描述
            $childs = \Yii::app()->request->getPost('childids');//所有子权限id
            $topItemId = \Yii::app()->request->getPost('topItemId');//权限组id
            if(empty($topItemId)) $this->echoJson("", 1, "权限组不能为空");
            //编辑角色记录
            try {
                \Yii::app()->db->beginTransaction();
                $AuthItemModel->id = $itemId;
                $AuthItemModel->name = $name;
                $AuthItemModel->desc = $desc;
                $AuthItemModel->code = md5(uniqid(md5(microtime(true)), true));
                $AuthItemModel->type = 2;
                $AuthItemModel->system = 1;;
                $AuthItemModel->status = 1;
                $AuthItemModel->created_time = time();
                $AuthItemModel->updated_time = time();
                if ($AuthItemModel->save()) {
                    //先将以前的角色权限删除
                    \Yii::app()->db->createCommand("delete from itz_auth_item_child WHERE parent={$itemId}")->execute();
                    //重新添加
                    $AuthItemChild = new AuthItemChild();
                    //拼接子权限数据
                    foreach ($childs as $ley => $val) {
                        $ret[] = array(
                            "parent" => $itemId,
                            "child" => $val,
                        );
                    }
                    //拼接权限组id
                    foreach ($topItemId as $ley => $val) {
                        $ret[] = array(
                            "parent" => $itemId,
                            "child" => $val,
                        );
                    }
                    if (isset($ret) && !empty($ret)) {
                        foreach ($ret as $attributes) {
                            $_model = clone $AuthItemChild; //克隆对象
                            $_model->setAttributes($attributes);
                            $_model->save();
                        }
                     }
                    \Yii::app()->db->commit();
                    $this->echoJson("", 0, "编辑角色成功");
                } else {
                    \Yii::app()->db->rollback();
                    $this->echoJson("", 1, "编辑角色失败");
                }
            } catch (Exception $e) {
                \Yii::app()->db->rollback();
                $this->echoJson("", 1, "编辑角色失败");
            }
        }
        $ret = array();
        //显示单独角色权限
        $alone = \Yii::app()->db->createCommand()
            ->select('id,name,desc')
            ->from('itz_auth_item')
            ->where("status = 1 and type = 2 and id = {$itemId}")
            ->queryRow();
            if(empty($alone)) $this->echoJson("", 1, "没有此角色数据");
            //选中的权限组ids
            $itemIds = $this->getParentItemIds($itemId,1);
//            if(empty($itemIds)) $this->echoJson("", 1, "没有此权限组");
            //显示全部权限管理
            $authitem = \Yii::app()->db->createCommand()
                ->select('id,name')
                ->from('itz_auth_item')
                ->where('status = 1 and type = 1')
                ->queryAll();
            if (empty($authitem)) $this->echoJson("", 1, "没有权限信息");
                $childIds = $this->getParentItemIds($itemId,0);
                foreach ($authitem as $key => $val) {
                    //获取指定权限组下的子权限列表
                    $itemchild = $AuthItemModel->getAuthGroupChildList($val['id']);
                    //判断权限组是否被选中
                    $ret[] = array(
                        "id" => $val['id'],
                        "name" => $val['name'],
                        "status" => in_array($val["id"],$itemIds) ? 1 : 0 ,//判断是否选中
                        "listrolename" => $itemchild,
                    );
                }
        return $this->renderPartial("userroleedit", array("ret" => $ret, "alone" => $alone,"childIds" => $childIds));
    }

    /**
     * 根据角色itemId获取权限ids
     * @param $itemId 权限id
     * @param $type 1：查询选中的权限组 0：查询选中子权限
     */
    public function getParentItemIds($itemId,$type=1){
        //查询选中的权限
        $sql = "SELECT id,name FROM itz_auth_item WHERE id IN(SELECT child from itz_auth_item_child WHERE parent = {$itemId}) AND type = {$type} AND status = 1";
        $itemData = \Yii::app()->db->createCommand($sql)->queryAll();
        if(empty($itemData)) return '';
        return  \ArrayUtil::array_column($itemData,"id");
    }
    /**
     * 更新权限的状态
     * @param $id
     * @param int $type
     */
    public function updateStatus($id, $type = AuthItem::STATUS_ENABLED)
    {
        if (!Number::isIntPk($id)) {
            $this->renderJson(Meta::C_UNSAFE_ARGUMENT);
        } else {
            $model = AuthItem::model()->findByPk($id);
            if (!$model) {
                $this->renderJson(Meta::C_AUTH_ITEM_NOT_FOUND);
            } else {
                /* @var $model \iauth\models\AuthItem */
                if ($model->updateStatus($type)) {
                    $this->renderJson(Meta::C_SUCCESS);
                } else {
                    $errCode = $model->getErrCode();
                    $this->logReqParamsWith(Meta::getMeta($errCode));
                    $this->renderJson($errCode);
                }
            }
        }
    }
}

<?php
/**
 *
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/29
 * Time: 14:52
 */

namespace iauth\controllers;

use iauth\components\IAuthController;
use iauth\models\AuthAssignment;
use iauth\helpers\Meta;
use iauth\models\AuthItem;
use iauth\models\AuthItemChild;

class AuthAssignmentController extends IAuthController
{

    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'UserCate', 'UserRoleDel', 'AuthEdit', 'AuthAdd', 'RoleDel', 'RoleEdit'
        );
    }

    /**
     * 权限分类列表
     */
    public function actionUserCate()
    {
        //添加权限分类
        if (\Yii::app()->request->isPostRequest) {
            $name = \Yii::app()->request->getParam("username");
            if ($name != "") {
                try {
                    $AuthItemModel = new AuthItem();
                    $AuthItemModel->name = $name;
                    $AuthItemModel->desc = "";
                    $AuthItemModel->code = md5(uniqid(md5(microtime(true)), true));
                    $AuthItemModel->type = 1;
                    $AuthItemModel->system = 1;;
                    $AuthItemModel->status = 1;
                    $AuthItemModel->created_time = time();
                    $AuthItemModel->updated_time = time();
                    if ($AuthItemModel->save()) {
                        $this->echoJson("", 0, "添加成功");
                    } else {
                        $this->echoJson("", 0, "添加失败");
                    }
                } catch (Exception $e) {
                    $this->echoJson("", 1, "添加角色失败");
                }
            }
            $this->echoJson("", 1, "权限分类不能为空");
        }
        $sql = "SELECT `id`,`name` from itz_auth_item WHERE type = 1 order by id DESC";
        $criteria = new \CDbCriteria();
        $result = \Yii::app()->db->createCommand($sql)->query();
        $pages = new \CPagination($result->rowCount);
        $pages->pageSize = 10;
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
        $authitem = $result->queryAll();
        return $this->renderPartial("usercate", array("authitem" => $authitem, "pages" => $pages));
    }

    /**
     * 权限分类编辑
     */
    public function actionAuthEdit()
    {
        $itemId = \Yii::app()->request->getParam("itemId");
        $AuthItemModel = AuthItem::model();
        if (\Yii::app()->request->isPostRequest) {
            $name = \Yii::app()->request->getParam("username");
            $desc = \Yii::app()->request->getParam("desc");
            $AuthItemModel->id = $itemId;
            $AuthItemModel->name = $name;
            $AuthItemModel->desc = $desc;
            $AuthItemModel->code = md5(uniqid(md5(microtime(true)), true));
            $AuthItemModel->type = 1;
            $AuthItemModel->system = 1;
            $AuthItemModel->status = 1;
            $AuthItemModel->updated_time = time();
            if ($AuthItemModel->save()) {
                $this->echoJson("", 0, "编辑成功");
            } else {
                $this->echoJson("", 0, "编辑成功");
            }
        }
        $authData = $AuthItemModel->findByPk($itemId);
        return $this->renderPartial("authedit", array("authData" => $authData));
    }

    /**
     * 删除权限组
     */
    public function actionUserRoleDel($itemId)
    {
        $sql = "DELETE FROM itz_auth_item WHERE id = {$itemId} AND type = 1";
        $result = \Yii::app()->db->createCommand($sql)->execute();
        if ($result) {
            $this->echoJson("", 0, "删除成功");
        } else {
            $this->echoJson("", 1, "删除失败");
        }
    }

    /**
     * 删除权限管理
     */
    public function actionRoleDel($itemId)
    {
        $sql = "DELETE FROM itz_auth_item WHERE id = {$itemId} AND type = 0";
        $result = \Yii::app()->db->createCommand($sql)->execute();
        if ($result) {
            $this->echoJson("", 0, "删除成功");
        } else {
            $this->echoJson("", 1, "删除失败");
        }
    }

    /**
     * 添加权限管理
     */
    public function actionRoleAdd()
    {
        $where = "";
        if(!empty($_GET['name'])){
            $where .= " and name = '{$_GET['name']}'";
        }
        if(!empty($_GET['parent'])){
            $groupArr = AuthItem::model()->getAuthGroupChildList($_GET['parent']);
            if(!empty($groupArr)){
                $childIds = \ItzUtil::array_column($groupArr,"id");
                $childIds = implode(",",$childIds);
                $where .= " and id in($childIds)";
            }
        }
        //权限列表展示
        $sql = "SELECT `id`,`name`,`code` from itz_auth_item WHERE type = 0 $where order by id DESC";
        $criteria = new \CDbCriteria();
        $result = \Yii::app()->db->createCommand($sql)->query();
        $pages = new \CPagination($result->rowCount);
        $pages->pageSize = 10;
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
        $authitem = $result->queryAll();
        if (!empty($authitem)) {
            foreach ($authitem as $key => $val) {
                $assignment = \Yii::app()->db->createCommand()
                    ->select('b.name')
                    ->from('itz_auth_item_child a')
                    ->join("itz_auth_item b", "a.parent=b.id")
                    ->where("a.child = {$val['id']} and b.type = 1")
                    ->queryRow();
                $ret[] = array(
                    "id" => $val['id'],
                    "name" => $val['name'],
                    "pname" => $assignment['name'],
                    "code" => $val['code'],
                );
            }
        }
        //显示顶级权限
        $assignTop = \Yii::app()->db->createCommand()
            ->select('id,name')
            ->from('itz_auth_item a')
            ->where("type = 1 and status = 1")
            ->queryAll();
        return $this->renderPartial("roleadd", array("authitem" => $ret, "pages" => $pages, "assignTop" => $assignTop));
    }

    private function addonly($data)
    {
        $return_result = array(
            'code' => '0', 'info' => 'error', 'data' => array()
        );
        $code = $data['code'];
        $parent = $data['parent'];
        $name = $data['name'];
        if (empty($code)) {
            $return_result['info'] = "权限规则不能为空";
            $return_result['code'] = 1;
            return $return_result;
        }
        if (empty($parent)) {
            $return_result['info'] = "权限分类不能为空";
            $return_result['code'] = 1;
            return $return_result;
        }
        if (empty($name)) {
            $return_result['info'] = "权限名称不能为空";
            $return_result['code'] = 1;
            return $return_result;
        }
        try {
            $AuthItemModel = new AuthItem();
            //添加一条权限
            $AuthItemModel->name = $name;
            $AuthItemModel->desc = "";
            $AuthItemModel->code = $code;
            $AuthItemModel->type = 0;
            $AuthItemModel->system = 1;
            $AuthItemModel->status = 1;
            $AuthItemModel->created_time = time();
            $AuthItemModel->updated_time = time();
            if ($AuthItemModel->save()) {
                $child = $AuthItemModel->attributes['id'];
                $sql = "INSERT INTO itz_auth_item_child (parent,child) VALUE ('{$parent}','{$child}')";
                $res = \Yii::app()->db->createCommand($sql)->execute();
                if (!$res) {
                    $return_result['info'] = "添加失败的权限名称" . $name;
                    $return_result['code'] = 1;
                    return $return_result;
                }
                $return_result['info'] = "添加成功";
                $return_result['code'] = 0;
                return $return_result;
            } else {
                $return_result['info'] = "规则名不要重复";
                $return_result['code'] = 1;
                return $return_result;
            }
        } catch (Exception $e) {
            $return_result['info'] = "添加失败";
            $return_result['code'] = 1;
            return $return_result;
        }
    }
    /**
     * 添加子权限新页面
     */
    public function actionAddJurisdiction()
    {
        $model = \Yii::app()->db;
        if (\Yii::app()->request->isPostRequest) {
            //添加子权限
            $parent = \Yii::app()->request->getParam("parent");
            $code_content = trim(\Yii::app()->request->getParam("code_content"));
            if (empty($code_content)) {
                $this->echoJson("", 1, "权限不能为空");
            }
            if(strpos($code_content,"|") !== false){
                $code_content_arr = explode('|', $code_content);
            }else{
                $code_content_arr = [$code_content];
            }
            foreach ($code_content_arr as $key => $val) {
                $data = array(
                    "code" => substr(trim($val), strripos(trim($val), ",") + 1),
                    "name" => substr(trim($val), 0, strrpos(trim($val), ",")),
                    "parent" => $parent,
                );
                $result = $this->addonly($data);
                if ($result['code'] != 0) {
                    $this->echoJson("", 1, $result['info']);
                }
            }
            $this->echoJson("", 0, "添加成功");
        }
        //显示顶级权限
        $assignTop = $model->createCommand()
            ->select('id,name')
            ->from('itz_auth_item a')
            ->where("type = 1 and status = 1")
            ->queryAll();
        return $this->renderPartial("addjurisdiction",['assignTop' => $assignTop]);
    }
    /**
     * 编辑权限管理
     */
    public function actionRoleEdit()
    {
        $itemId = \Yii::app()->request->getParam("itemId");
        $model = \Yii::app()->db;
        if (\Yii::app()->request->isPostRequest) {
            $name = \Yii::app()->request->getParam("username");
            $code = \Yii::app()->request->getParam("code");
            $parent = \Yii::app()->request->getParam("parent");
            if (empty($code)) $this->echoJson("", 1, "权限规则不能为空");
            if (empty($parent)) $this->echoJson("", 1, "权限分类不能为空");
            if (empty($name)) $this->echoJson("", 1, "权限名称不能为空");
            //权限编辑
            try {
                \Yii::app()->db->beginTransaction();
                $sql = "UPDATE  itz_auth_item SET name = '{$name}',code = '{$code}' WHERE id = {$itemId}";
                $model->createCommand($sql)->execute();
                if ($parent) {
                    //查询出子权限对应的权限组和角色
                    $sql = "SELECT iai.parent FROM itz_auth_item_child iai left join itz_auth_item item on iai.parent = item.id WHERE iai.child = $itemId and item.type = 1";
                    $ret = $model->createCommand($sql)->queryAll();
                    if (!empty($ret)) {
                        $parentIds = implode(",", \ArrayUtil::array_column($ret, 'parent'));
                        $delsql = "delete from itz_auth_item_child where parent IN ({$parentIds}) and child = {$itemId}";
                        $model->createCommand($delsql)->execute();
                    }
                    $sqlinsert = "insert into itz_auth_item_child (parent,child) values($parent,$itemId)";
                    $res = $model->createCommand($sqlinsert)->execute();
                    if (!$res) {
                        \Yii::app()->db->rollback();
                        $this->echoJson("", 1, "编辑失败");
                    }
                    \Yii::app()->db->commit();
                    $this->echoJson("", 0, "编辑成功");
                }
            } catch (Exception $e) {
                \Yii::app()->db->rollback();
                $this->echoJson("", 1, "编辑失败");
            }
        }
        $assignment = $model->createCommand()
            ->select('id,name,code')
            ->from('itz_auth_item')
            ->where("id = {$itemId}")
            ->queryRow();
        $assignTop = array();
        if (!empty($assignment)) {
            $data = $model->createCommand()
                ->select('b.id,b.name')
                ->from('itz_auth_item_child a')
                ->join("itz_auth_item b", "a.parent=b.id")
                ->where("a.child = {$assignment['id']} and b.type = 1")
                ->queryRow();
            //显示顶级权限
            $assignTop = $model->createCommand()
                ->select('id,name')
                ->from('itz_auth_item a')
                ->where("type = 1 and status = 1")
                ->queryAll();
        }
        return $this->renderPartial("roleedit", array('assignment' => $assignment, "data" => $data, "assignTop" => $assignTop));
    }
}

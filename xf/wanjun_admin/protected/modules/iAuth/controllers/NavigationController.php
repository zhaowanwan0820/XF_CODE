<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 1/4/2016
 * Time: 20:57
 */

namespace iauth\controllers;

use iauth\components\IAuthController;
use iauth\models\AuthAssignment;

class NavigationController extends IAuthController
{

    /**
     * 栏目列表
     * @return mixed|string
     * @throws \CException
     */
    public function actionNavList()
    {
        $model = \Yii::app()->db;
        $navCount = $model->createCommand("select count(*) from itz_auth_navigation where is_del = 0")->queryScalar();
        if($navCount > 0){
            $navArr = $model->createCommand("select * from itz_auth_navigation where is_del = 0")->queryAll();
            //顶级栏目名称
            foreach($navArr as $key => $val){
                if($val['parent_id'] == 0){
                    $top[] = array(
                        "id" => $val['id'],
                        "n_name" => $val['n_name'],
                    );
                }
            }
            $topid = 0;//顶级
            $ret = $this->getTree($navArr,$topid);
        }
        $icon = '';
        if(!empty($_GET['icon'])){
            $icon = $_GET['icon'];
            $icon = "#".$icon;
        }
        return $this->renderPartial("navList",array('top' => $top,'navArr' => $navArr,'ret' => $ret, 'icon' => $icon));
    }

    /**
     * @param $data
     * @param string $pid
     * @return array
     */
    public function getTree($data,$pid = '1'){
        $tree = array();
        foreach($data as $v){
            if($v['parent_id'] == $pid){//匹配子记录
                $v['children'] = $this->getTree($data,$v['id']); //递归获取子记录
                if($v['children'] == null){
                    unset($v['children']);//如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
                }
                $tree[] = $v;//将记录存入新数组
            }
        }
        return $tree;
    }
    /**
     * 添加栏目
     * @return mixed|string
     * @throws \CException
     */
    public function actionNavAdd()
    {
        $n_name = $_POST['n_name'];//栏目名称
        $parent_id = $_POST['parent_id'];//父类关系0：顶级栏目
        $top_id = $_POST['top_id'];//是否顶级栏目1:是2:否
        $code = $_POST['code'];//导航栏规则
        $icon = $_POST['icon'];//导航栏图标
        if(!is_numeric($parent_id) || !is_numeric($top_id) || !in_array($top_id,[1,2])){
            $this->echoJson("", 1, "参数错误");
        }
        if(empty($n_name)){
            $this->echoJson("", 1, "请填写栏目名称");
        }
        if($top_id == 0){
            $this->echoJson("", 1, "请选择是否为顶级栏目");
        }
        //非顶级栏目验证导航栏规则
        if($top_id == 2){
            if(empty($code)){
                $this->echoJson("", 1, "请填写导航栏规则");
            }
        }
        //顶级栏目添加图标
        if($top_id == 1){
            if(empty($icon)){
                $this->echoJson("", 1, "请选择导航栏图标");
            }
        }
        //是否有顶级栏目
        $count = \Yii::app()->db->createCommand("select count(*) from itz_auth_navigation where parent_id = 0 and is_del = 0")->queryScalar();
        if($count == 0 && $top_id == 2){
            $this->echoJson("", 1, "请先添加顶级栏目");
        }
        //如果顶级栏目生成唯一code
        if($top_id == 1){
            $code = md5(uniqid(md5(microtime(true)), true));
            $parent_id = 0;
        }
        $now = time();
        $navAdd = array(
            "n_name" => $n_name,
            "parent_id" => $parent_id,
            "code" => $code,
            "icon" => $icon,
            "login_id" => \Yii::app()->user->id,
            "ip_address" => $_SERVER['REMOTE_ADDR'],
            "create_time" => $now,
            "update_time" => $now,
        );
        $sql = \ArrayUntil::get_insert_db_sql('itz_auth_navigation',$navAdd);
        $result = \Yii::app()->db->createCommand($sql)->execute();
        if (!$result) {
            $this->echoJson("", 1, "添加栏目失败");
        }
        $this->echoJson("", 0, "添加栏目成功");
    }
    /**
     * 编辑栏目
     * @return mixed|string
     * @throws \CException
     */
    public function actionNavEdit()
    {
        $id = $_REQUEST['id'];
        if(empty($id) || !is_numeric($id)){
            $this->echoJson("", 1, "参数错误");
        }
        $model = \Yii::app()->db;
        $navigationInfo = $model->createCommand("select * from itz_auth_navigation where id = $id and is_del = 0")->queryRow();
        if(empty($navigationInfo)){
            $this->echoJson("", 1, "栏目不存在");
        }

        //编辑
        if (\Yii::app()->request->isPostRequest) {
            $parnetid = $_POST['parent_id'];
            $code    = $_POST['code'];
            $n_name  = $_POST['n_name'];
            $icon    = $_POST['icon'];
            $navEdit = array(
                "parent_id" => $parnetid,
                "n_name"    => $n_name,
                "code"      => $code,
                "icon"      => $icon,
            );
            //顶级栏目下有对应子栏目
            if($parnetid != 0){
                $childCount = $model->createCommand("select count(*) from itz_auth_navigation where parent_id = $id and is_del = 0")->queryScalar();
                if($childCount > 0){
                    $this->echoJson("", 1, "此顶级栏目下有对应子栏目，不能编辑所属栏目");
                }
            }
            $sql = \ArrayUntil::get_update_db_sql('itz_auth_navigation',$navEdit," id = $id");
            $result = $model->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson("", 1, "编辑栏目失败");
            }
            $this->echoJson("", 0, "编辑栏目成功");
        }
        $top = '';
        $navCount = $model->createCommand("select count(*) from itz_auth_navigation where parent_id = 0 and is_del = 0")->queryScalar();
        if($navCount > 0){
            $top = $model->createCommand("select * from itz_auth_navigation where parent_id = 0 and is_del = 0")->queryAll();
        }
        return $this->renderPartial("navEdit",array('navigationInfo' => $navigationInfo,'top' => $top));
    }
    /**
     * 删除栏目（软删除）
     * @return mixed|string
     * @throws \CException
     */
    public function actionNavDel()
    {
        $id = $_REQUEST['id'];
        if(empty($id) || !is_numeric($id)){
            $this->echoJson("", 1, "参数错误");
        }
        $model = \Yii::app()->db;
        $navigationInfo = $model->createCommand("select * from itz_auth_navigation where id = $id and is_del = 0")->queryRow();
        if(empty($navigationInfo)){
            $this->echoJson("", 1, "栏目不存在");
        }
        //顶级栏目下有对应子栏目不能删除
        $childCount = $model->createCommand("select count(*) from itz_auth_navigation where parent_id = $id and is_del = 0")->queryScalar();
        if($childCount > 0){
            $this->echoJson("", 1, "此顶级栏目下有对应子栏目，不能删除");
        }
        $navDel = array('is_del' => 1);
        $sql = \ArrayUntil::get_update_db_sql('itz_auth_navigation',$navDel," id = $id");
        $result = $model->createCommand($sql)->execute();
        if (!$result) {
            $this->echoJson("", 1, "删除栏目失败");
        }
        $this->echoJson("", 1, "删除栏目成功");
        return $this->renderPartial("userrole");
    }

    /**
     *
     */
    public function actionNavIconEdit()
    {
        return $this->renderPartial("navIconEdit");
    }
}

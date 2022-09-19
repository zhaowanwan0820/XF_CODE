<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class DController extends CController {
    public $menu = array();
    public $ajaxTpl = false; //此参数控制ajax翻页时不使用默认的layout模板
    /**
     * @var array the breadcrumbs of the current page. The value of this property will
     * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
     * for more details on how to specify this property.
     */
    public $breadcrumbs = array();

    /**
    *  pageInfo added by dlj
    */
    /**
     * _navNum
     * 下面展示的页码数
     *
     * @var float
     */
    protected $_navNum = 7;

    /**
     * _pn
     * 页码
     *
     * @var float
     */
    protected $_pn = 1;

    /**
     * _rn
     * 每页展示个数
     *
     * @var float
     */
    protected $_rn = 10;

    /**
     * _nn
     * 总页码个数
     *
     * @var float
     */
    protected $_nn = 0;

    /**
     * _tn
     * 总结果个数
     *
     * @var float
     */
    protected $_tn = 0;
    public $AuditLog = array(
        'status'=>false#默认关闭审计日志
    );

    /**
     * setPn
     *
     * @param mixed $pn
     * @access protected
     * @return void
     */
    protected function setPn($pn)
    {
        $pn = intval($pn);
        $pn = $pn>0 ? $pn : 1;
        $this->_pn=$pn;;
    }

    /**
     * getPn
     *
     * @access protected
     * @return void
     */
    protected function getPn()
    {
        return $this->_pn;
    }

    /**
     * setRn
     *
     * @param mixed $rn
     * @access protected
     * @return void
     */
    protected function setRn($rn)
    {
        $rn = intval($rn);
        $this->_rn=$rn;
    }

    /**
     * getRn
     *
     * @return void
     */
    protected function getRn()
    {
        return $this->_rn;
    }

    /**
     * getOffset
     *
     * @return void
     */
    protected function getOffset()
    {
        return ($this->getPn()-1)*$this->getRn();
    }

    /**
     * setTn
     *
     * @param mixed $tn
     * @return void
     */
    protected function setTn($tn)
    {
        $tn = intval($tn);
        $this->_tn=$tn;
    }

	/**
     * echoJson
     * 输出json
     *
     * @param mixed $data
     * @param int $code 0:success
     * @access protected
     * @return void
     */
    protected function echoJson($data=array(),$code=0,$info="",$plain_flag=false){
        if($plain_flag){
            if(strpos(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '', 'application/json') !== false){
                 header('Content-type:text/plain; charset=utf-8');
            }
        }else{
            header ( "Content-type:application/json; charset=utf-8" );
        }
        $data = ArrayUtil::getArray($data);
        $res["data"] = $data;
        $res['code']=intval($code);
        $res['info']=$info;
        echo exit(json_encode ( $res ));

    }
    /**
     * echoJsonAuditLog
     * 输出json并记录审计日志
     *
     * @param mixed $data
     * @param int $code 0:success
     * @access protected
     * @return void
     */
    public function echoJsonAuditLog ($data=array(),$code=0,$info="",$plain_flag=false,$type="json") {
        //处理审计日志
        $this->auditLogAdd($code,$info);

        if(isset($_REQUEST['data_type']) && $_REQUEST['data_type'] =="jsonp" ){
            $this->echoJsonp($data,$code,$info);return;
        }
        if($type=="jsonp"){
            $this->echoJsonp($data,$code,$info);return;
        }
        if($plain_flag){
            if(strpos(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '', 'application/json') !== false){
                header('Content-type:text/plain; charset=utf-8');
            }
        }else{
            header ( "Content-type:application/json; charset=utf-8" );
        }
        $data = ArrayUtil::getArray($data);
        $res["data"] = $data;
        $res['code'] = intval($code);
        $res['info'] = $info;
        echo exit(json_encode ( $res ));

    }
    //记录审计日志
    public function auditLogAdd($code,$info)
    {
        //如果不需要记录
        if($this->AuditLog['status']==false){
            return false;
        }
        //识别用户
        $user_id = Yii::app()->user->id;
        //识别设备
        $system = 'ccs';
        //状态
        $status = ($code==0) ? 'success' : 'fail';
        //收集信息
        $parameters = array();
        if (isset($_SERVER["HTTP_CLIENT_VERSION"])) {
            $parameters['app_version'] = $_SERVER["HTTP_CLIENT_VERSION"];
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $parameters['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        }
        $parameters['info'] = $info;

        //收集 POST
        if ($_POST) {
            if (isset($_POST['check_sign_str'])) {
                unset($_POST['check_sign_str']);
            }
            foreach ($_POST as $key=>$v) {
                if (stripos(','.$key,'password')){
                    continue;
                }
                if (stripos(','.$key,'passwd')) {
                    continue;
                }
                $parameters[$key] = $v;
            }
        }

        //收集 GET
        if ($_GET) {
            foreach ($_GET as $key=>$v) {
                if (stripos(','.$key,'password')) {
                    continue;
                }
                if (stripos(','.$key,'passwd')) {
                    continue;
                }
                $parameters[$key] = $v;
            }
        }
        //自定义
        if(!empty($this->AuditLog['parameters']))
        {
            $parameters = $parameters+ $this->AuditLog['parameters'];
        }
        #审计日志 登录错误
        AuditLog::getInstance()->method('add', array(
            "user_id"   => $user_id,
            "system"    => $system,
            "action"    => $this->AuditLog['action'],
            "resource"  => $this->AuditLog['resource'],
            "status"    => $status,
            "parameters"=> json_encode($parameters)
        ));
    }
    /**
     * render
     *
     * @param mixed $view
     * @param mixed $data
     * @param mixed $return
     * @access public
     * @return void
     */
    public function render($view,$data=null,$return=false)
    {
        if($this->beforeRender($view))
        {
            $data['Itz']=array(
            );

            $data['pageInfo']['pn'] = $this->_pn;
            $data['pageInfo']['rn'] = $this->_rn;

            if(!empty($this->_tn) && !empty($this->_pn) && !empty($this->_rn)){
                $data['pageInfo']['tn'] = $this->_tn;
                $this->_nn = ceil($this->_tn/$this->_rn);
                $data['pageInfo']['nn'] = $this->_nn;
                $data['pageInfo']['arrNavi']=ItzPageUtil::getPageNavigation($this->_navNum,$this->_pn,$this->_nn);
            }

            $output=$this->renderPartial($view,$data,true);
            if(($layoutFile=$this->getLayoutFile($this->layout))!==false && !$this->ajaxTpl){
                 $output=$this->renderFile($layoutFile,array('content'=>$output),true);
            }

            $this->afterRender($view,$output);

            $output=$this->processOutput($output);

            if($return)
                return $output;
            else
                echo $output;
        }
    }

    /**
     * 提示信息
     */
    public function redirect_message($message='成功', $status='info',$time=3, $url='' )
    {
        if($status =='success') {
			$backColorClass = 'alert-success';
        } elseif($status =='warning') {
			$backColorClass = 'alert-warning';
        } elseif($status =='error') {
			$backColorClass = 'alert-danger';
        } elseif($status =='info') {
			$backColorClass = 'alert-info';
        }

		if($time) {
			$timeTips = '页面正在跳转请等待<span id="sec" style="font-weight:bold; font-size:16px;">'.$time.'</span>秒';
		} else {
			$timeTips = '';
		}

        if(is_array($url)) {
            $route=isset($url[0]) ? $url[0] : '';
            $url=$this->createUrl($route,array_splice($url,1));
        }
        if ($url) {
            $url = "window.location.href='{$url}'";
        } else {
            $url = "history.back();";
        }
        header("Content-Type:text/html;charset=utf-8");
        echo <<<HTML
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="/css/bootstrap.css" />
		<title>提示信息 - dashboard</title>
		</head>
		<body>
			<div class="alert {$backColorClass}" style=" margin: 80px auto; padding: 80px; width: 600px;" >
				{$message}
				<p>{$timeTips}</p>
			</div>
		</body>
		</head>
		<script type="text/javascript">
		function run(){
			var s = document.getElementById("sec");
			if(s.innerHTML == 0){
				{$url}
				return false;
			}
			s.innerHTML = s.innerHTML * 1 - 1;
		}
		window.setInterval("run();", 1000);
		</script>
HTML;
    }

    //获取用户信息
    public function getUserInfoByFiled($filed,$val)
    {
       $uids = -2;
       $userInfo = User::model()->findAllByAttributes(array($filed=>$val)); 
       if($userInfo){
           $uids = array();
           foreach ($userInfo as $key => $value) {
               $uids[] = $value->user_id;
           }
       }
       return $uids;
    }

    //用户验证校验
    public function checkUserVcode($code){
        $returnResult = array(
            "code" => 0, "info"=> "" , "data"=> array(),
        );
        
        if(empty($code)){
                $returnResult["code"] = 100;
                $returnResult["info"] = "请重新获取验证码！";
                return $returnResult;
        }
        
        $_s_code    = isset($_SESSION['admin_vcode_'.Yii::app()->user->id])?$_SESSION['admin_vcode_'.Yii::app()->user->id]:'';
        $_code_time = isset($_SESSION['admin_vcode_time_'.Yii::app()->user->id])?$_SESSION['admin_vcode_time_'.Yii::app()->user->id]:0;
        //$_s_code = '111111';
        if($code != $_s_code){
                $returnResult["code"] = 101;
                $returnResult["info"] = "验证码有误！";
                return $returnResult;
        }
        
        if((time()-$_code_time)>600){
                $returnResult["code"] = 102;
                $returnResult["info"] = "验证码已过期！";
                return $returnResult;
        }
        
        $returnResult["code"] = 1;
        $returnResult["info"] = "验证成功！";
        $_SESSION['admin_vcode_'.Yii::app()->user->id]='';
        $_SESSION['admin_vcode_time_'.Yii::app()->user->id]='';
        return $returnResult;
    }

    //获取内部用户的用户名根据
    public function getinnerUsername($user_id){
        $returnResult = '';
        $userInfo = ItzUser::model()->findByPk($user_id);
        if(count($userInfo)>0){
            $returnResult = $userInfo->username;
        }
        return $returnResult;
    }
    //获取用户的用户名根据用户id
    public function getUsername($user_id){
        $returnResult = '';
        $userInfo = User::model()->findByPk($user_id);
        if(count($userInfo)>0){
            $returnResult = $userInfo->username;
        }
        return $returnResult;
    }
    //获取用户id根据用户名
    public function getUserid($username){
        $returnResult = -1;
        $userInfo = User::model()->find('username=:username',array(':username'=>$username));
        if(count($userInfo)>0){
            $returnResult = $userInfo->user_id;
        }
        return $returnResult;
    }            
    //获取用户id根据用户名
    public function getinnderUserid($username){
        $returnResult = -1;
        $userInfo = ItzUser::model()->find('username=:username',array(':username'=>$username));
        if(count($userInfo)>0){
            $returnResult = $userInfo->id;
        }
        return $returnResult;
    }  
}

<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class HcommonController extends CController {





    public function __construct()
    {
        header("Content-type:text/html;charset=utf-8");
    }


    public function filterUserLogin($filterChain)
    {
        //识别用户

        $filterChain->run();
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
     * echoJsonp
     * 输出jsonp
     * fixme 该函数逐渐改为private，请使用echoOut
     *
     * @param mixed $data
     * @access protected
     * @return void
     */
    protected function echoJsonp($data=array(),$code=0,$info=""){
        $func = "jsoncallback";
        if(isset($_REQUEST['jsoncallback'])){
            $func = $_REQUEST['jsoncallback'];
        }
        header ( "Content-type:application/html; charset=utf-8" );
        $res["data"] = $data;
        $res['code']=intval($code);
        $res['info']=$info;
        echo $func."(".json_encode ( $res ).")";
    }
    /**
     * 验证参数
     * @param  $field 参数名称
     * @param  $default 默认值
     * @return void
     */
    protected function paramsvaild($field ,$mustfill = 0 ,$default = ''){
        if (!Yii::app()->request->isPostRequest) {
            $this->echoJson([] , 4006 , $field.Yii::app()->c->data['errorcodeinfo']['4006']);
        }
        $data = Yii::app()->request->getParam($field);
        if($mustfill === true){
            if(empty($data) || $data === 0){
                $this->echoJson([] , 4001 , $field.Yii::app()->c->data['errorcodeinfo']['4001']);
            }
        }
        if(!empty($default)){
            $data = isset($data) ? $data : $default;
        }
        return $data;
    }
    /**
     * 验证json格式
     * @param  $string
     * @return void
     */
   public function is_json($string) {
         json_decode($string);
         return (json_last_error() == JSON_ERROR_NONE);
        }

}

